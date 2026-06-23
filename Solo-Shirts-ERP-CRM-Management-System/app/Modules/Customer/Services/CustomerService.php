<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Models\User;
use App\Modules\Customer\Exceptions\DuplicatePhoneException;
use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\Branch;
use App\Modules\Shared\Exceptions\BranchIsolationException;
use App\Modules\Shared\Services\BranchContext;
use App\Modules\Shared\Services\CodeGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CustomerService
{
    public function __construct(
        private readonly BranchContext $branchContext,
        private readonly CodeGenerator $codes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Customer
    {
        $branchId = isset($data['branch_id'])
            ? (int) $data['branch_id']
            : $this->branchContext->current();

        if ($branchId === null) {
            throw new BranchIsolationException('A branch must be selected to create a customer.');
        }

        $phone = (string) $data['phone'];
        $last4 = $this->last4($phone);

        return DB::transaction(function () use ($data, $actor, $branchId, $phone, $last4): Customer {
            $this->assertPhoneUnique($last4, $phone);

            $branch = Branch::query()->findOrFail($branchId);
            $code = $this->codes->next('customer_sequences', $branchId, 'SSI-' . $branch->code . '-');

            return Customer::query()->create([
                'branch_id' => $branchId,
                'customer_code' => $code,
                'name' => $data['name'],
                'phone' => $phone,
                'phone_last4' => $last4,
                'phone_search' => $this->digits($phone),
                'address' => $data['address'] ?? null,
                'preferred_fabric_id' => $data['preferred_fabric_id'] ?? null,
                'special_notes' => $data['special_notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $data, $actor): Customer {
            if (isset($data['phone'])) {
                $phone = (string) $data['phone'];
                $last4 = $this->last4($phone);

                if ($phone !== $customer->phone) {
                    $this->assertPhoneUnique($last4, $phone, $customer->id);
                }

                $customer->phone = $phone;
                $customer->phone_last4 = $last4;
                $customer->phone_search = $this->digits($phone);
            }

            $customer->fill([
                'name' => $data['name'] ?? $customer->name,
                'address' => $data['address'] ?? $customer->address,
                'preferred_fabric_id' => $data['preferred_fabric_id'] ?? $customer->preferred_fabric_id,
                'special_notes' => $data['special_notes'] ?? $customer->special_notes,
            ]);
            $customer->updated_by = $actor->id;
            $customer->save();

            return $customer;
        });
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function search(?string $term, int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::query();

        if ($term !== null && $term !== '') {
            $digits = preg_replace('/\D/', '', $term) ?? '';

            $query->where(function ($builder) use ($term, $digits): void {
                $builder->where('name', 'like', '%' . $term . '%');

                if ($digits !== '') {
                    // Progressive phone match: any partial run of digits the user
                    // types narrows the list (falls back to last4 for legacy rows
                    // not yet backfilled into phone_search).
                    $builder
                        ->orWhere('phone_search', 'like', '%' . $digits . '%')
                        ->orWhere('phone_last4', 'like', '%' . $digits . '%');
                }
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Phone uniqueness is GLOBAL across all branches (customers are shared), so a
     * phone can exist at most once regardless of which branch registered it.
     */
    private function assertPhoneUnique(string $last4, string $phone, ?int $ignoreId = null): void
    {
        $candidates = Customer::query()
            ->where('phone_last4', $last4)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($candidates as $candidate) {
            if ($candidate->phone === $phone) {
                throw DuplicatePhoneException::forCustomer($candidate->id);
            }
        }
    }

    private function last4(string $phone): string
    {
        return substr($this->digits($phone), -4);
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }
}
