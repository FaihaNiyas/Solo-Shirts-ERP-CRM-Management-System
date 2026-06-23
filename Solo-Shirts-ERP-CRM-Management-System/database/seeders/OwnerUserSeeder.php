<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'HQ')->first()
            ?? Branch::query()->firstOrFail();

        $email = (string) env('OWNER_EMAIL', 'owner@soloshirts.test');
        $password = (string) env('OWNER_PASSWORD', 'password');

        $owner = User::query()->withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => $branch->id,
                'name' => 'Solo Shirts Owner',
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
                'deleted_at' => null,
            ],
        );

        // Role assignments are scoped to the user's home branch (Spatie teams).
        app(PermissionRegistrar::class)->setPermissionsTeamId($owner->branch_id);

        if (!$owner->hasRole('Owner')) {
            $owner->assignRole('Owner');
        }
    }
}
