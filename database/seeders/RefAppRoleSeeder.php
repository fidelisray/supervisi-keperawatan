<?php

namespace Database\Seeders;

use App\Models\AppRole;
use App\Models\RefAppRole;
use Illuminate\Database\Seeder;

class RefAppRoleSeeder extends Seeder
{
    /**
     * Seed the role reference definitions for the Nursing Supervision application (app_id = 6).
     */
    public function run(): void
    {
        foreach (RefAppRole::DEFAULT_ROLE_LABELS as $roleId => $roleName) {
            RefAppRole::query()->updateOrCreate(
                [
                    'app_id' => AppRole::APP_ID,
                    'role_id' => $roleId,
                ],
                [
                    'role_name' => $roleName,
                ]
            );
        }

        RefAppRole::clearRoleCache();
    }
}
