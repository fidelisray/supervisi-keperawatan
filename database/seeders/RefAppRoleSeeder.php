<?php

namespace Database\Seeders;

use App\Models\AppRole;
use App\Models\RefAppRole;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefAppRoleSeeder extends Seeder
{
    /**
     * Initialize ref_master_nurse_supervision_approles and tb_master_nurse_supervision_approles
     * from the hospital's ref_approles and tb_approles tables (where app_id = 6).
     */
    public function run(): void
    {
        $connection = DB::connection('sqlsrv');
        $schema = Schema::connection('sqlsrv');

        // 1. Initialize ref_master_nurse_supervision_approles from ref_approles (app_id = 6) + defaults
        $rolesToSeed = RefAppRole::DEFAULT_ROLE_LABELS;

        if ($schema->hasTable('ref_approles')) {
            $sourceRefRoles = $connection->table('ref_approles')
                ->where('app_id', AppRole::APP_ID)
                ->get(['role_id', 'role_name']);

            foreach ($sourceRefRoles as $row) {
                $rolesToSeed[(int) $row->role_id] = trim((string) $row->role_name);
            }
        }

        foreach ($rolesToSeed as $roleId => $roleName) {
            RefAppRole::query()->updateOrCreate(
                [
                    'app_id' => AppRole::APP_ID,
                    'role_id' => (int) $roleId,
                ],
                [
                    'role_name' => $roleName,
                ]
            );
        }

        RefAppRole::clearRoleCache();

        // 2. Initialize tb_master_nurse_supervision_approles from tb_approles (app_id = 6)
        if ($schema->hasTable('tb_approles')) {
            $sourceAppRoles = $connection->table('tb_approles')
                ->where('app_id', AppRole::APP_ID)
                ->orderBy('id')
                ->get(['nip', 'app_id', 'app_roles', 'description', 'push_token']);

            foreach ($sourceAppRoles as $row) {
                $nip = trim((string) $row->nip);
                if ($nip === '') {
                    continue;
                }

                AppRole::query()->updateOrCreate(
                    [
                        'app_id' => AppRole::APP_ID,
                        'nip' => $nip,
                    ],
                    [
                        'app_roles' => (int) $row->app_roles,
                        'description' => $row->description !== null ? trim((string) $row->description) : null,
                        'push_token' => $row->push_token,
                    ]
                );
            }
        }

        // 3. Initialize default permissions for Role 0 and Role 1 in ref_master_nurse_supervision_role_permissions
        if ($schema->hasTable('ref_master_nurse_supervision_role_permissions')) {
            RefAppRole::syncPermissions(AppRole::ROLE_ADMIN, array_keys(RolePermission::AVAILABLE_PERMISSIONS));
            RefAppRole::syncPermissions(AppRole::ROLE_SUPERVISOR, RolePermission::DEFAULT_SUPERVISOR_PERMISSIONS);
        }
    }
}
