<?php

use App\Models\AppRole;
use App\Models\AppRoleAssignment;
use App\Models\RefAppRole;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\RefAppRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);

    $this->supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);
});

test('non-admin supervisor cannot access admin role management routes', function () {
    $this->actingAs($this->supervisor)
        ->get(route('admin.roles.index'))
        ->assertForbidden();

    $this->actingAs($this->supervisor)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KEPALA RUANG',
        ])
        ->assertForbidden();
});

test('administrator can create, update, and delete roles and user access on dedicated master tables', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('Manajemen Role & Hak Akses Pengguna')
        ->assertDontSee('name="role_id"', false)
        ->assertDontSee('App ID')
        ->assertDontSee('tb_master_nurse_supervision_approles')
        ->assertDontSee('ref_master_nurse_supervision_approles')
        ->assertDontSee('tb_employees');

    // 1. Create a new role definition in ref_master_nurse_supervision_approles with auto-generated role_id
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KEPALA RUANG',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('app_id', 6)->where('role_name', 'KEPALA RUANG')->value('role_id'))
        ->toBe(2);

    // 2. Assign role to an unregistered employee from tb_employees (1355010892 - Cicilia Rumini, S.Kep.Ners)
    $this->actingAs($this->admin)
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 1,
        ])
        ->assertRedirect(route('admin.roles.index'));

    $newAppRole = AppRole::where('app_id', 6)->where('nip', '1355010892')->first();
    expect($newAppRole)->not->toBeNull()
        ->and($newAppRole->nama)->toBe('Cicilia Rumini, S.Kep.Ners')
        ->and($newAppRole->displayName())->toBe('Cicilia Rumini, S.Kep.Ners')
        ->and($newAppRole->app_roles)->toBe(1)
        ->and(AppRoleAssignment::where('nip', '1355010892')->value('description'))->toBeNull();

    // 3. Update role of an existing user to role 2
    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update-user', $newAppRole->nip), [
            'app_roles' => 2,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(AppRole::where('app_id', 6)->where('nip', '1355010892')->value('app_roles'))->toBe(2);

    // 4. Cannot delete role 2 while a user is still assigned to it
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy-definition', 2))
        ->assertSessionHasErrors('role_id');

    // 5. Delete the user's access from tb_master_nurse_supervision_approles
    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-user', $newAppRole->nip))
        ->assertRedirect(route('admin.roles.index'));

    expect(AppRole::where('nip', '1355010892')->exists())->toBeFalse()
        ->and(AppRoleAssignment::where('nip', '1355010892')->exists())->toBeFalse();

    // 6. Now role 2 can be deleted cleanly
    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-definition', 2))
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('role_id', 2)->exists())->toBeFalse();

    // 7. Admin cannot delete their own active account
    $adminAppRole = AppRole::where('nip', '5508150824')->first();
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy-user', $adminAppRole->nip))
        ->assertSessionHasErrors('nip');
});

test('revoking base vAppRoles user in development mode marks app_roles as -1 in temporary table without deleting from tb_approles', function () {
    expect(AppRole::where('nip', '2351151100')->exists())->toBeTrue();

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-user', '2351151100'))
        ->assertRedirect(route('admin.roles.index'));

    expect(AppRole::where('nip', '2351151100')->exists())->toBeFalse()
        ->and(AppRoleAssignment::where('nip', '2351151100')->value('app_roles'))->toBe(AppRoleAssignment::ROLE_REVOKED_DEV)
        ->and(DB::connection('sqlsrv')->table('tb_approles')->where('nip', '2351151100')->exists())->toBeTrue();
});

test('role management supports switching write tables to tb_approles and ref_approles in production mode and enforces 24 char role_name limit', function () {
    config()->set('supervisi.app_roles_write_table', 'tb_approles');
    config()->set('supervisi.ref_app_roles_table', 'ref_approles');

    // Role name > 24 chars is rejected
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'NAMA ROLE YANG SANGAT PANJANG MELEBIHI BATAS',
        ])
        ->assertSessionHasErrors('role_name');

    // Valid <= 24 char role is stored in ref_approles with auto-generated role_id (2)
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KEPALA INSTALASI',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(DB::connection('sqlsrv')->table('ref_approles')->where('app_id', 6)->where('role_id', 2)->value('role_name'))
        ->toBe('KEPALA INSTALASI');

    // Assigning user writes directly to tb_approles and appears in vAppRoles
    $this->actingAs($this->admin)
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 2,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(DB::connection('sqlsrv')->table('tb_approles')->where('app_id', 6)->where('nip', '1355010892')->value('app_roles'))
        ->toBe(2)
        ->and(AppRole::where('nip', '1355010892')->value('nama'))->toBe('Cicilia Rumini, S.Kep.Ners');

    // Deleting user removes row directly from tb_approles
    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-user', '1355010892'))
        ->assertRedirect(route('admin.roles.index'));

    expect(DB::connection('sqlsrv')->table('tb_approles')->where('app_id', 6)->where('nip', '1355010892')->exists())
        ->toBeFalse()
        ->and(AppRole::where('nip', '1355010892')->exists())->toBeFalse();
});

test('RefAppRoleSeeder initializes dedicated tables from tb_approles and ref_approles where app_id is 6', function () {
    DB::connection('sqlsrv')->table('tb_master_nurse_supervision_approles')->truncate();
    DB::connection('sqlsrv')->table('ref_master_nurse_supervision_approles')->truncate();

    $this->seed(RefAppRoleSeeder::class);

    expect(RefAppRole::where('app_id', 6)->count())->toBe(2)
        ->and(AppRole::where('app_id', 6)->count())->toBe(3)
        ->and(AppRole::where('nip', '9000000001')->exists())->toBeFalse();
});

test('admin roles index only pulls unregistered employees who have nursing titles', function () {
    DB::connection('sqlsrv')->table('tb_employees')->insert([
        [
            'nip' => '7788990001',
            'nama' => 'Sri Rahayu, AMK',
            'status_karyawan' => 'AKTIF',
        ],
        [
            'nip' => '7788990002',
            'nama' => 'Bambang Sudarsono, S.E.',
            'status_karyawan' => 'AKTIF',
        ],
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));

    $response->assertSuccessful();

    // S.Kep.Ners nurse and AMK nurse must be present
    $response->assertSee('Cicilia Rumini, S.Kep.Ners');
    $response->assertSee('Sri Rahayu, AMK');

    // Non-nurse employee (S.E.) must NOT be present
    $response->assertDontSee('Bambang Sudarsono, S.E.');
});

test('administrator can define custom role with specific permissions and update permissions later', function () {
    // 1. Create custom role (auto-assigned role_id 2) with permissions 'view_all_reports' and 'view_analytics'
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'AUDITOR MUTU',
            'permissions' => ['view_all_reports', 'view_analytics'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RolePermission::where('role_id', 2)->pluck('permission_key')->all())
        ->toEqualCanonicalizing(['view_all_reports', 'view_analytics'])
        ->and(RefAppRole::permissionKeysForRole(2))
        ->toEqualCanonicalizing(['view_all_reports', 'view_analytics']);

    // 2. Update role 2 permissions via update-permissions endpoint
    $this->actingAs($this->admin)
        ->put(route('admin.roles.update-permissions', 2), [
            'permissions' => ['view_all_reports', 'manage_roles'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RolePermission::where('role_id', 2)->pluck('permission_key')->all())
        ->toEqualCanonicalizing(['view_all_reports', 'manage_roles'])
        ->and(RefAppRole::permissionKeysForRole(2))
        ->toEqualCanonicalizing(['view_all_reports', 'manage_roles']);

    // 3. Verify rendered role definition table shows a single explicit permission button without duplicate action trigger
    $indexResponse = $this->actingAs($this->admin)->get(route('admin.roles.index'));

    $indexResponse->assertSuccessful()
        ->assertSee('7 Akses (Full)')
        ->assertSee('4 Akses (Default)')
        ->assertSee('Atur Izin (2)')
        ->assertDontSee('title="Atur hak akses role ini"', false);

    expect(substr_count($indexResponse->getContent(), 'openPermissionModal(2,'))->toBe(1);
});

test('user assigned custom role receives corresponding granular permissions and access boundaries', function () {
    // Define role 2 with only view_all_reports and view_analytics
    RefAppRole::create([
        'app_id' => 6,
        'role_id' => 2,
        'role_name' => 'AUDITOR KLINIS',
    ]);
    RefAppRole::syncPermissions(2, ['view_all_reports', 'view_analytics']);

    $auditorUser = User::factory()->create([
        'nip' => '9988776655',
        'name' => 'Auditor Klinis Test',
        'app_roles' => 2,
    ]);

    expect($auditorUser->hasPermission('view_all_reports'))->toBeTrue()
        ->and($auditorUser->hasPermission('view_analytics'))->toBeTrue()
        ->and($auditorUser->hasPermission('create_report'))->toBeFalse()
        ->and($auditorUser->hasPermission('edit_report'))->toBeFalse()
        ->and($auditorUser->hasPermission('manage_roles'))->toBeFalse();

    // Can access analytics
    $this->actingAs($auditorUser)
        ->get(route('supervisi.analytics'))
        ->assertSuccessful();

    // Can access index
    $this->actingAs($auditorUser)
        ->get(route('supervisi.index'))
        ->assertSuccessful();

    // Forbidden from create and store report
    $this->actingAs($auditorUser)
        ->get(route('supervisi.create'))
        ->assertForbidden();

    $this->actingAs($auditorUser)
        ->post(route('supervisi.store'), [
            'report_date' => now()->toDateString(),
            'room_name' => 'ANGELA',
            'patient_count' => 10,
            'nurse_count' => 2,
        ])
        ->assertForbidden();

    // Forbidden from admin role management
    $this->actingAs($auditorUser)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('system default roles 0 and 1 are protected from manual permission override', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.roles.update-permissions', 0), [
            'permissions' => ['create_report'],
        ])
        ->assertSessionHasErrors('role_id');

    $this->actingAs($this->admin)
        ->put(route('admin.roles.update-permissions', 1), [
            'permissions' => ['create_report'],
        ])
        ->assertSessionHasErrors('role_id');
});

test('deleting custom role also cleans up its associated role permissions', function () {
    RefAppRole::create([
        'app_id' => 6,
        'role_id' => 5,
        'role_name' => 'ROLE SEMENTARA',
    ]);
    RefAppRole::syncPermissions(5, ['create_report', 'view_analytics']);

    expect(RolePermission::where('role_id', 5)->count())->toBe(2);

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-definition', 5))
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('role_id', 5)->exists())->toBeFalse()
        ->and(RolePermission::where('role_id', 5)->count())->toBe(0);
});

test('user cannot modify their own active role and storeRole blocks system roles 0 and 1', function () {
    // Admin cannot self-demote via updateUserRole
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->patch(route('admin.roles.update-user', $this->admin->nip), [
            'app_roles' => 1,
        ])
        ->assertSessionHasErrors('nip');

    // Admin cannot self-demote via assignUserRole
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.assign-user'), [
            'nip' => $this->admin->nip,
            'app_roles' => 1,
        ])
        ->assertSessionHasErrors('nip');

    // storeRole rejects any client-supplied role_id (prohibited rule prevents tampering / silent overwrite)
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.store-definition'), [
            'role_id' => 0,
            'role_name' => 'FAKE ADMIN',
        ])
        ->assertSessionHasErrors('role_id');

    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.store-definition'), [
            'role_id' => 1,
            'role_name' => 'FAKE SUPERVISOR',
        ])
        ->assertSessionHasErrors('role_id');

    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.store-definition'), [
            'role_id' => 50,
            'role_name' => 'TAMPERED CODE',
        ])
        ->assertSessionHasErrors('role_id');
});

test('non-admin user with manage_roles cannot escalate to admin, manage role 0 accounts, assign undefined roles, or assign non-nurses', function () {
    RefAppRole::create([
        'app_id' => 6,
        'role_id' => 2,
        'role_name' => 'KOORDINATOR ROLE',
    ]);
    RefAppRole::syncPermissions(2, ['manage_roles']);

    $coordinator = User::factory()->create([
        'nip' => '2351151100',
        'name' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'app_roles' => 2,
    ]);

    // 1. Cannot promote another user to Role 0 (Admin) via assignUserRole or updateUserRole
    $this->actingAs($coordinator)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 0,
        ])
        ->assertSessionHasErrors('app_roles');

    $this->actingAs($coordinator)
        ->from(route('admin.roles.index'))
        ->patch(route('admin.roles.update-user', '1672010896'), [
            'app_roles' => 0,
        ])
        ->assertSessionHasErrors('app_roles');

    // 2. Cannot alter or revoke an existing Role 0 Administrator account
    $this->actingAs($coordinator)
        ->from(route('admin.roles.index'))
        ->patch(route('admin.roles.update-user', $this->admin->nip), [
            'app_roles' => 1,
        ])
        ->assertSessionHasErrors('app_roles');

    $this->actingAs($coordinator)
        ->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy-user', $this->admin->nip))
        ->assertSessionHasErrors('nip');

    // 3. Cannot assign or update to an undefined role ID (orphan role)
    $this->actingAs($coordinator)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 88,
        ])
        ->assertSessionHasErrors('app_roles');

    // 4. Cannot assign a non-nursing employee
    $this->actingAs($coordinator)
        ->from(route('admin.roles.index'))
        ->post(route('admin.roles.assign-user'), [
            'nip' => '9000000001',
            'app_roles' => 1,
        ])
        ->assertSessionHasErrors('nip');

    // 5. Can legitimately assign a nurse to Role 1
    $this->actingAs($coordinator)
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 1,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(AppRole::where('nip', '1355010892')->value('app_roles'))->toBe(1);
});

test('storeRole generates monotonic role_id without reusing deleted gaps and rejects duplicate role names', function () {
    RefAppRole::create([
        'app_id' => 6,
        'role_id' => 3,
        'role_name' => 'ROLE TIGA',
    ]);
    RefAppRole::create([
        'app_id' => 6,
        'role_id' => 9,
        'role_name' => 'ROLE SEMBILAN',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KEPALA SHIFT',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('app_id', 6)->where('role_name', 'KEPALA SHIFT')->value('role_id'))
        ->toBe(10);

    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KATIM',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('app_id', 6)->where('role_name', 'KATIM')->value('role_id'))
        ->toBe(11);

    // Reject duplicate custom role names and reserved system role names (case-insensitive)
    foreach (['kepala shift', 'ADMINISTRATOR', 'supervisor keperawatan'] as $duplicateName) {
        $this->actingAs($this->admin)
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store-definition'), [
                'role_name' => $duplicateName,
            ])
            ->assertSessionHasErrors('role_name');
    }
});

test('administrator can rename custom role while preserving permissions and protecting system roles and other apps', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KEPALA RUANG',
            'permissions' => ['create_report', 'view_analytics'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_name' => 'KATIM JAGA',
        ])
        ->assertRedirect(route('admin.roles.index'));

    $roleTwoUser = User::factory()->create([
        'nip' => '1355010892',
        'name' => 'Cicilia Rumini, S.Kep.Ners',
        'app_roles' => 2,
    ]);

    // Prime cache before rename
    expect($roleTwoUser->roleLabel())->toBe('KEPALA RUANG');

    // Renaming to another existing role name or system role name fails
    foreach (['katim jaga', 'administrator'] as $conflictingName) {
        $this->actingAs($this->admin)
            ->from(route('admin.roles.index'))
            ->patch(route('admin.roles.update-definition', 2), [
                'role_name' => $conflictingName,
            ])
            ->assertSessionHasErrors('role_name');
    }

    // Valid rename updates role_name, preserves permissions, and clears label cache
    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update-definition', 2), [
            'role_name' => '  koordinator ruang  ',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('app_id', 6)->where('role_id', 2)->value('role_name'))->toBe('KOORDINATOR RUANG')
        ->and(RefAppRole::permissionKeysForRole(2))->toEqualCanonicalizing(['create_report', 'view_analytics'])
        ->and($roleTwoUser->roleLabel())->toBe('KOORDINATOR RUANG');

    // System roles 0 and 1 cannot be renamed, and non-existent role returns 404
    foreach ([0, 1] as $systemRoleId) {
        $this->actingAs($this->admin)
            ->from(route('admin.roles.index'))
            ->patch(route('admin.roles.update-definition', $systemRoleId), [
                'role_name' => 'UBAH SISTEM',
            ])
            ->assertSessionHasErrors('role_id');
    }

    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update-definition', 99), [
            'role_name' => 'TIDAK ADA',
        ])
        ->assertNotFound();

    // Production table switch: renaming app_id = 6 does not alter other apps in ref_approles
    config()->set('supervisi.ref_app_roles_table', 'ref_approles');
    DB::connection('sqlsrv')->table('ref_approles')->insert([
        ['role_id' => '2', 'role_name' => 'APP LAIN ROLE', 'app_id' => 1],
        ['role_id' => '2', 'role_name' => 'KEPALA UNIT', 'app_id' => 6],
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update-definition', 2), [
            'role_name' => 'KEPALA INSTALASI',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(DB::connection('sqlsrv')->table('ref_approles')->where('app_id', 6)->where('role_id', '2')->value('role_name'))
        ->toBe('KEPALA INSTALASI')
        ->and(DB::connection('sqlsrv')->table('ref_approles')->where('app_id', 1)->where('role_id', '2')->value('role_name'))
        ->toBe('APP LAIN ROLE');
});

test('admin roles page renders modal triggers, active user counts, and paginates registered users while preserving search query', function () {
    foreach (range(1, 12) as $i) {
        $suffix = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $nip = '88000000'.$suffix;

        DB::connection('sqlsrv')->table('tb_employees')->insert([
            'nip' => $nip,
            'nama' => "Perawat Pagination {$suffix}, S.Kep.Ners",
            'status_karyawan' => 'AKTIF',
        ]);

        AppRoleAssignment::create([
            'nip' => $nip,
            'app_id' => AppRole::APP_ID,
            'app_roles' => AppRole::ROLE_SUPERVISOR,
        ]);
    }

    // Page 1: renders 10 of 15 users, modal triggers, role user counts, and page 2 link
    $pageOne = $this->actingAs($this->admin)->get(route('admin.roles.index'));

    $pageOne->assertSuccessful()
        ->assertSee('+ Tambah Role Baru')
        ->assertSee('+ Daftarkan Pegawai')
        ->assertSee('create-role-modal-title', false)
        ->assertSee('assign-user-modal-title', false)
        ->assertSee('Pengguna Aktif')
        ->assertSee('14 Pegawai')
        ->assertSee('2. Daftar Pengguna Terdaftar (15 Pegawai)')
        ->assertSee('?page=2', false);

    expect($pageOne->viewData('appRoles')->total())->toBe(15)
        ->and($pageOne->viewData('appRoles')->count())->toBe(10);

    // Page 2: renders remaining 5 users
    $pageTwo = $this->actingAs($this->admin)->get(route('admin.roles.index', ['page' => 2]));
    $pageTwo->assertSuccessful()
        ->assertSee('Perawat Pagination 12, S.Kep.Ners');

    expect($pageTwo->viewData('appRoles')->count())->toBe(5);

    // Search + pagination preserves search query string on pagination links
    $filteredPageOne = $this->actingAs($this->admin)->get(route('admin.roles.index', ['search' => 'Pagination']));
    $filteredPageOne->assertSuccessful()
        ->assertSee('2. Daftar Pengguna Terdaftar (12 Pegawai)')
        ->assertSee('search=Pagination', false);

    expect($filteredPageOne->viewData('appRoles')->total())->toBe(12)
        ->and($filteredPageOne->viewData('appRoles')->count())->toBe(10);

    $filteredPageTwo = $this->actingAs($this->admin)->get(route('admin.roles.index', ['search' => 'Pagination', 'page' => 2]));
    $filteredPageTwo->assertSuccessful()
        ->assertSee('Perawat Pagination 11, S.Kep.Ners')
        ->assertSee('Perawat Pagination 12, S.Kep.Ners');

    expect($filteredPageTwo->viewData('appRoles')->count())->toBe(2);
});
