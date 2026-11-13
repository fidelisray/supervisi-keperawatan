<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRole;
use App\Models\AppRoleAssignment;
use App\Models\Employee;
use App\Models\RefAppRole;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $availableRoles = RefAppRole::availableRoles();

        $appRoles = AppRole::query()
            ->forSupervisiApp()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function (Builder $sub) use ($search): void {
                    $sub->where('nip', 'like', '%'.$search.'%')
                        ->orWhere('nama', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('role_filter'), function (Builder $query) use ($request): void {
                $query->where('app_roles', (int) $request->input('role_filter'));
            })
            ->orderBy('app_roles')
            ->orderBy('nama')
            ->get(['nip', 'nama', 'app_id', 'app_roles']);

        $registeredNips = AppRole::query()
            ->forSupervisiApp()
            ->pluck('nip')
            ->map(fn ($nip) => trim((string) $nip))
            ->all();

        $unregisteredEmployees = Employee::query()
            ->nurses()
            ->whereNotIn('nip', $registeredNips)
            ->orderBy('nama')
            ->limit(500)
            ->get(['nip', 'nama']);

        $rolePermissionsMap = [];
        foreach (array_keys($availableRoles) as $rId) {
            $rolePermissionsMap[(int) $rId] = RefAppRole::permissionKeysForRole((int) $rId);
        }

        return view('admin.roles.index', [
            'availableRoles' => $availableRoles,
            'appRoles' => $appRoles,
            'unregisteredEmployees' => $unregisteredEmployees,
            'groupedPermissions' => RolePermission::groupedPermissions(),
            'rolePermissionsMap' => $rolePermissionsMap,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'min:0', 'max:99'],
            'role_name' => ['required', 'string', 'max:24'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', array_keys(RolePermission::AVAILABLE_PERMISSIONS))],
        ]);

        $roleId = (int) $validated['role_id'];
        $roleName = strtoupper(trim((string) $validated['role_name']));

        RefAppRole::query()->updateOrCreate(
            [
                'app_id' => AppRole::APP_ID,
                'role_id' => $roleId,
            ],
            [
                'role_name' => $roleName,
            ]
        );

        if ($roleId > 1 && isset($validated['permissions'])) {
            RefAppRole::syncPermissions($roleId, $validated['permissions']);
        }

        RefAppRole::clearRoleCache($roleId);

        return redirect()->route('admin.roles.index')
            ->with('success', "Definisi role [{$roleId} - {$roleName}] untuk aplikasi Supervisi Keperawatan berhasil disimpan.");
    }

    public function updateRolePermissions(Request $request, int $roleId): RedirectResponse
    {
        if ($roleId <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'Hak akses role bawaan sistem (Administrator & Supervisor Keperawatan) terlindungi dan tidak dapat diubah.',
            ]);
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', array_keys(RolePermission::AVAILABLE_PERMISSIONS))],
        ]);

        RefAppRole::syncPermissions($roleId, $validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Hak akses (permission) untuk role ['.$roleId.' - '.RefAppRole::resolveRoleName($roleId).'] berhasil diperbarui.');
    }

    public function destroyRole(Request $request, int $roleId): RedirectResponse
    {
        if (array_key_exists($roleId, RefAppRole::DEFAULT_ROLE_LABELS)) {
            throw ValidationException::withMessages([
                'role_id' => 'Role dasar sistem (Administrator & Supervisor Keperawatan) tidak dapat dihapus.',
            ]);
        }

        $usersCount = AppRole::query()
            ->forSupervisiApp()
            ->where('app_roles', $roleId)
            ->count();

        if ($usersCount > 0) {
            throw ValidationException::withMessages([
                'role_id' => "Role ini masih digunakan oleh {$usersCount} pegawai. Silakan pindahkan role pegawai terkait terlebih dahulu.",
            ]);
        }

        RolePermission::query()
            ->where('app_id', AppRole::APP_ID)
            ->where('role_id', $roleId)
            ->delete();

        RefAppRole::query()
            ->forSupervisiApp()
            ->where('role_id', $roleId)
            ->delete();

        RefAppRole::clearRoleCache($roleId);

        return redirect()->route('admin.roles.index')
            ->with('success', "Definisi role dengan kode [{$roleId}] berhasil dihapus.");
    }

    public function assignUserRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
            'app_roles' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $nip = trim((string) $validated['nip']);
        $roleId = (int) $validated['app_roles'];

        $employee = Employee::query()
            ->where('nip', $nip)
            ->first(['nip', 'nama']);

        if ($employee === null) {
            throw ValidationException::withMessages([
                'nip' => 'Pegawai dengan NIP tersebut tidak ditemukan di master pegawai rumah sakit.',
            ]);
        }

        AppRoleAssignment::query()->updateOrCreate(
            [
                'app_id' => AppRole::APP_ID,
                'nip' => $employee->nip,
            ],
            [
                'app_roles' => $roleId,
            ]
        );

        User::query()
            ->where('nip', $employee->nip)
            ->update([
                'name' => trim((string) $employee->nama),
                'app_roles' => $roleId,
            ]);

        return redirect()->route('admin.roles.index')
            ->with('success', "Hak akses untuk {$employee->nama} (NIP: {$employee->nip}) berhasil disimpan.");
    }

    public function updateUserRole(Request $request, string $nip): RedirectResponse
    {
        $targetNip = trim($nip);

        $appRole = AppRole::query()
            ->forSupervisiApp()
            ->where('nip', $targetNip)
            ->firstOrFail(['nip', 'nama', 'app_id', 'app_roles']);

        $validated = $request->validate([
            'app_roles' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $roleId = (int) $validated['app_roles'];

        AppRoleAssignment::query()->updateOrCreate(
            [
                'app_id' => AppRole::APP_ID,
                'nip' => $appRole->nip,
            ],
            [
                'app_roles' => $roleId,
            ]
        );

        User::query()
            ->where('nip', $appRole->nip)
            ->update([
                'app_roles' => $roleId,
            ]);

        $displayName = $appRole->displayName();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role untuk {$displayName} (NIP: {$appRole->nip}) berhasil diperbarui.");
    }

    public function destroyUserRole(Request $request, string $nip): RedirectResponse
    {
        $targetNip = trim($nip);

        $appRole = AppRole::query()
            ->forSupervisiApp()
            ->where('nip', $targetNip)
            ->firstOrFail(['nip', 'nama', 'app_id', 'app_roles']);

        $currentUserNip = trim((string) $request->user()?->nip);

        if ($currentUserNip !== '' && $currentUserNip === $targetNip) {
            throw ValidationException::withMessages([
                'nip' => 'Anda tidak dapat menghapus hak akses akun Anda sendiri yang sedang aktif digunakan.',
            ]);
        }

        $deletedName = $appRole->displayName();

        if (AppRoleAssignment::usesTemporaryWriteTable()) {
            $readTable = (new AppRole)->getTable();
            $existsInBaseView = DB::connection('sqlsrv')
                ->table($readTable)
                ->where('app_id', AppRole::APP_ID)
                ->where('nip', $targetNip)
                ->exists();

            if ($existsInBaseView) {
                AppRoleAssignment::query()->updateOrCreate(
                    [
                        'app_id' => AppRole::APP_ID,
                        'nip' => $targetNip,
                    ],
                    [
                        'app_roles' => AppRoleAssignment::ROLE_REVOKED_DEV,
                    ]
                );
            } else {
                AppRoleAssignment::query()
                    ->forSupervisiApp()
                    ->where('nip', $targetNip)
                    ->delete();
            }
        } else {
            AppRoleAssignment::query()
                ->forSupervisiApp()
                ->where('nip', $targetNip)
                ->delete();
        }

        $targetUser = User::query()->where('nip', $targetNip)->first();
        if ($targetUser !== null) {
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $targetUser->id)
                    ->delete();
            }

            $targetUser->delete();
        }

        return redirect()->route('admin.roles.index')
            ->with('success', "Hak akses untuk {$deletedName} (NIP: {$targetNip}) berhasil dicabut dan dihapus.");
    }
}
