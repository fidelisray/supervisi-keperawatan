<?php

namespace Database\Seeders;

use App\Models\AppRole;
use App\Models\AppRoleAssignment;
use App\Models\RefAppRole;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefAppRoleSeeder extends Seeder
{
    /**
     * Synchronize role definitions and (when in temporary dev mode) user role assignments
     * for app_id = 6 while respecting production tables.
     */
    public function run(): void
    {
        $connection = DB::connection('sqlsrv');
        $schema = Schema::connection('sqlsrv');
        $legacyAppId = $connection->getDriverName() === 'sqlsrv'
            ? (string) AppRole::APP_ID
            : AppRole::APP_ID;

        // 1. Sync role definitions from ref_approles (app_id = 6) + core defaults into the configured RefAppRole table
        $rolesToSeed = RefAppRole::DEFAULT_ROLE_LABELS;

        if ($schema->hasTable('ref_approles')) {
            $sourceRefRoles = $connection->table('ref_approles')
                ->where('app_id', $legacyAppId)
                ->get(['role_id', 'role_name']);

            foreach ($sourceRefRoles as $row) {
                $rawRoleId = trim((string) $row->role_id);
                $trimmedName = trim((string) $row->role_name);

                if ($rawRoleId !== '' && is_numeric($rawRoleId) && $trimmedName !== '') {
                    $rolesToSeed[(int) $rawRoleId] = $trimmedName;
                }
            }
        }

        foreach ($rolesToSeed as $roleId => $roleName) {
            RefAppRole::upsertForSupervisiRole((int) $roleId, $roleName);
        }

        RefAppRole::clearRoleCache();

        // 2. Sync tb_master_nurse_supervision_approles from tb_approles (app_id = 6) ONLY in temporary dev mode
        if (AppRoleAssignment::usesTemporaryWriteTable() && $schema->hasTable('tb_approles')) {
            $sourceAppRoles = $connection->table('tb_approles')
                ->where('app_id', $legacyAppId)
                ->orderBy('id')
                ->get(['nip', 'app_id', 'app_roles', 'push_token']);

            foreach ($sourceAppRoles as $row) {
                $nip = trim((string) $row->nip);
                $rawAppRoles = trim((string) $row->app_roles);

                if ($nip === '' || $rawAppRoles === '' || ! is_numeric($rawAppRoles)) {
                    continue;
                }

                AppRoleAssignment::upsertForSupervisiUser(
                    $nip,
                    (int) $rawAppRoles,
                    ['push_token' => $row->push_token]
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
