<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Services;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Exceptions\MeasurementException;
use App\Modules\Measurement\Models\MeasurementAlert;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use Illuminate\Support\Facades\DB;

final class MeasurementService
{
    public function __construct(private readonly SignificantChangeDetector $detector) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProfile(Customer $customer, array $data, User $actor): MeasurementProfile
    {
        return DB::transaction(function () use ($customer, $data, $actor): MeasurementProfile {
            $profile = MeasurementProfile::query()->create([
                'branch_id' => $customer->branch_id,
                'customer_id' => $customer->id,
                'family_member_id' => $data['family_member_id'] ?? null,
                'name' => $data['name'],
                'type' => $data['type'],
                'is_default' => $data['is_default'] ?? false,
            ]);

            $this->createVersion($profile, $data, $actor);

            return $profile;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createVersion(MeasurementProfile $profile, array $payload, User $actor): MeasurementVersion
    {
        return DB::transaction(function () use ($profile, $payload, $actor): MeasurementVersion {
            // Serialize version numbering for this profile.
            MeasurementProfile::query()->whereKey($profile->id)->lockForUpdate()->first();

            /** @var array<string, mixed>|null $shirt */
            $shirt = $payload['shirt_data'] ?? null;
            /** @var array<string, mixed>|null $pant */
            $pant = $payload['pant_data'] ?? null;

            $this->assertHasData($profile, $shirt, $pant);

            $prior = $this->currentEffective($profile);
            $versionNumber = (int) MeasurementVersion::query()
                ->where('profile_id', $profile->id)
                ->max('version_number') + 1;

            $significant = false;
            $diff = null;
            $breached = [];

            if ($prior !== null) {
                $detect = $this->detector->detect(
                    $this->flatten($prior->shirt_data, $prior->pant_data),
                    $this->flatten($shirt, $pant),
                    config('measurements.thresholds', []),
                );
                $diff = $detect['fields_changed'];
                $breached = $detect['threshold_breached'];
                $significant = $detect['is_significant'];
            }

            $status = $significant ? MeasurementVersion::STATUS_PENDING : MeasurementVersion::STATUS_APPROVED;
            $effectiveFrom = $status === MeasurementVersion::STATUS_APPROVED ? now() : null;

            $version = MeasurementVersion::query()->create([
                'branch_id' => $profile->branch_id,
                'profile_id' => $profile->id,
                'version_number' => $versionNumber,
                'status' => $status,
                'shirt_data' => $shirt,
                'pant_data' => $pant,
                'effective_from' => $effectiveFrom,
                'diff_json' => $diff,
                'significant_change' => $significant,
                'created_by' => $actor->id,
                'approved_by' => $status === MeasurementVersion::STATUS_APPROVED ? $actor->id : null,
                'approved_at' => $status === MeasurementVersion::STATUS_APPROVED ? now() : null,
            ]);

            if ($status === MeasurementVersion::STATUS_APPROVED && $prior !== null) {
                $prior->effective_to = $effectiveFrom;
                $prior->save();
            }

            if ($significant) {
                MeasurementAlert::query()->create([
                    'branch_id' => $profile->branch_id,
                    'version_id' => $version->id,
                    'fields_changed' => $diff,
                    'threshold_breached' => $breached,
                    'created_at' => now(),
                ]);
            }

            return $version;
        });
    }

    /**
     * Rename a profile and/or toggle its default flag. When set as default, any
     * other default profile for the same customer is cleared.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(MeasurementProfile $profile, array $data): MeasurementProfile
    {
        return DB::transaction(function () use ($profile, $data): MeasurementProfile {
            if (array_key_exists('name', $data) && $data['name'] !== null) {
                $profile->name = (string) $data['name'];
            }

            if (array_key_exists('is_default', $data)) {
                $makeDefault = (bool) $data['is_default'];
                if ($makeDefault) {
                    MeasurementProfile::query()
                        ->where('customer_id', $profile->customer_id)
                        ->whereKeyNot($profile->id)
                        ->update(['is_default' => false]);
                }
                $profile->is_default = $makeDefault;
            }

            $profile->save();

            return $profile;
        });
    }

    /**
     * Soft-delete a profile. Blocked when any of its versions is already in use
     * on an order item — measurement history backing a real order must survive.
     */
    public function deleteProfile(MeasurementProfile $profile): void
    {
        $inUse = DB::table('order_items')
            ->whereIn(
                'measurement_version_id',
                MeasurementVersion::query()->where('profile_id', $profile->id)->select('id'),
            )
            ->exists();

        if ($inUse) {
            throw MeasurementException::invalidData('This measurement is used on an order and cannot be deleted.');
        }

        $profile->delete();
    }

    public function approve(MeasurementVersion $version, User $actor): MeasurementVersion
    {
        return DB::transaction(function () use ($version, $actor): MeasurementVersion {
            if ($version->status === MeasurementVersion::STATUS_APPROVED) {
                throw MeasurementException::alreadyApproved();
            }

            if ($version->status !== MeasurementVersion::STATUS_PENDING) {
                throw MeasurementException::notPending();
            }

            $effectiveFrom = now();
            $prior = $this->currentEffective($version->profile);

            $version->status = MeasurementVersion::STATUS_APPROVED;
            $version->approved_by = $actor->id;
            $version->approved_at = now();
            $version->effective_from = $effectiveFrom;
            $version->save();

            if ($prior !== null && $prior->id !== $version->id) {
                $prior->effective_to = $effectiveFrom;
                $prior->save();
            }

            return $version;
        });
    }

    public function reject(MeasurementVersion $version, string $reason, User $actor): MeasurementVersion
    {
        if ($version->status === MeasurementVersion::STATUS_APPROVED) {
            throw MeasurementException::cannotRejectApproved();
        }

        if ($version->status !== MeasurementVersion::STATUS_PENDING) {
            throw MeasurementException::notPending();
        }

        $version->status = MeasurementVersion::STATUS_REJECTED;
        $version->rejection_reason = $reason;
        $version->approved_by = $actor->id;
        $version->approved_at = null;
        $version->save();

        return $version;
    }

    private function currentEffective(MeasurementProfile $profile): ?MeasurementVersion
    {
        return MeasurementVersion::query()
            ->where('profile_id', $profile->id)
            ->where('status', MeasurementVersion::STATUS_APPROVED)
            ->whereNull('effective_to')
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $shirt
     * @param  array<string, mixed>|null  $pant
     * @return array<string, mixed>
     */
    private function flatten(?array $shirt, ?array $pant): array
    {
        $out = [];

        foreach ((array) config('measurements.shirt_fields') as $field) {
            if (is_array($shirt) && isset($shirt[$field]) && is_numeric($shirt[$field])) {
                $out['shirt.' . $field] = $shirt[$field];
            }
        }

        foreach ((array) config('measurements.pant_fields') as $field) {
            if (is_array($pant) && isset($pant[$field]) && is_numeric($pant[$field])) {
                $out['pant.' . $field] = $pant[$field];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $shirt
     * @param  array<string, mixed>|null  $pant
     */
    private function assertHasData(MeasurementProfile $profile, ?array $shirt, ?array $pant): void
    {
        $hasShirt = is_array($shirt) && $shirt !== [];
        $hasPant = is_array($pant) && $pant !== [];

        if (!$hasShirt && !$hasPant) {
            throw MeasurementException::invalidData('At least one of shirt_data or pant_data is required.');
        }

        if ($profile->type === 'shirt' && !$hasShirt) {
            throw MeasurementException::invalidData('shirt_data is required for a shirt profile.');
        }

        if ($profile->type === 'pant' && !$hasPant) {
            throw MeasurementException::invalidData('pant_data is required for a pant profile.');
        }
    }
}
