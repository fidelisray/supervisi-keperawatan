<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRole;
use App\Models\Employee;
use App\Models\RefAppRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('role_filter'), function (Builder $query) use ($request): void {
                $query->where('app_roles', (int) $request->input('role_filter'));
            })
            ->orderBy('app_roles')
            ->orderBy('description')
            ->get(['id', 'nip', 'app_id', 'app_roles', 'description']);

        $registeredNips = AppRole::query()
            ->forSupervisiApp()
            ->pluck('nip')
            ->map(fn ($nip) => trim((string) $nip))
            ->all();

        $unregisteredEmployees = Employee::query()
            ->whereNotIn('nip', $registeredNips)
            ->orderBy('nama')
            ->limit(500)
            ->get(['nip', 'nama']);

        return view('admin.roles.index', [
            'availableRoles' => $availableRoles,
            'appRoles' => $appRoles,
            'unregisteredEmployees' => $unregisteredEmployees,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'min:0', 'max:99'],
            'role_name' => ['required', 'string', 'max:100'],
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

        RefAppRole::clearRoleCache($roleId);

        return redirect()->route('admin.roles.index')
            ->with('success', "Definisi role [{$roleId} - {$roleName}] untuk aplikasi Supervisi Keperawatan berhasil disimpan.");
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

        AppRole::query()->updateOrCreate(
            [
                'app_id' => AppRole::APP_ID,
                'nip' => $employee->nip,
            ],
            [
                'app_roles' => $roleId,
                'description' => trim((string) $employee->nama),
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

    public function updateUserRole(Request $request, AppRole $appRole): RedirectResponse
    {
        abort_unless((int) $appRole->app_id === AppRole::APP_ID, 403);

        $validated = $request->validate([
            'app_roles' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $roleId = (int) $validated['app_roles'];

        $appRole->update([
            'app_roles' => $roleId,
        ]);

        User::query()
            ->where('nip', $appRole->nip)
            ->update([
                'app_roles' => $roleId,
            ]);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role untuk {$appRole->description} (NIP: {$appRole->nip}) berhasil diperbarui.");
    }

    public function destroyUserRole(Request $request, AppRole $appRole): RedirectResponse
    {
        abort_unless((int) $appRole->app_id === AppRole::APP_ID, 403);

        $currentUserNip = trim((string) $request->user()?->nip);
        $targetNip = trim((string) $appRole->nip);

        if ($currentUserNip !== '' && $currentUserNip === $targetNip) {
            throw ValidationException::withMessages([
                'nip' => 'Anda tidak dapat menghapus hak akses akun Anda sendiri yang sedang aktif digunakan.',
            ]);
        }

        $deletedDescription = $appRole->description;
        $appRole->delete();

        User::query()->where('nip', $targetNip)->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Hak akses untuk {$deletedDescription} (NIP: {$targetNip}) berhasil dicabut dan dihapus.");
    }
}
