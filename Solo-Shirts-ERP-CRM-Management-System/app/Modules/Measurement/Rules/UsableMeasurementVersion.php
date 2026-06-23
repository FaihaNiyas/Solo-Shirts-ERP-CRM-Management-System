<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Rules;

use App\Modules\Measurement\Models\MeasurementVersion;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a measurement_version_id refers to an existing version in the
 * caller's branch (via the global BranchScope) and, when a customer is given,
 * that it belongs to that customer.
 *
 * Deliberately does NOT require an "approved" status: customer measurements are
 * usable immediately on an order — there is no measurement approval gate.
 */
final class UsableMeasurementVersion implements ValidationRule
{
    public function __construct(private readonly ?int $customerId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $version = MeasurementVersion::query()->find($value); // branch-scoped

        if ($version === null) {
            $fail('The selected measurement version was not found.');

            return;
        }

        if ($this->customerId !== null && (int) $version->profile->customer_id !== $this->customerId) {
            $fail('The measurement version does not belong to this customer.');
        }
    }
}
