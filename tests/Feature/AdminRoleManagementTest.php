<?php

use App\Models\AppRole;
use App\Models\RefAppRole;
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
            'role_id' => 2,
            'role_name' => 'KEPALA RUANG',
        ])
        ->assertForbidden();
});

test('administrator can create, update, and delete roles and user access on dedicated master tables', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('Manajemen Role & Hak Akses Pengguna (App ID: 6)')
        ->assertSee('tb_master_nurse_supervision_approles')
        ->assertSee('ref_master_nurse_supervision_approles');

    // 1. Create a new role definition in ref_master_nurse_supervision_approles
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store-definition'), [
            'role_id' => 2,
            'role_name' => 'KEPALA RUANG',
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('app_id', 6)->where('role_id', 2)->value('role_name'))
        ->toBe('KEPALA RUANG');

    // 2. Assign role to an unregistered employee from tb_employees (1355010892 - Cicilia Rumini, S.Kep.Ners)
    $this->actingAs($this->admin)
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 1,
        ])
        ->assertRedirect(route('admin.roles.index'));

    $newAppRole = AppRole::where('app_id', 6)->where('nip', '1355010892')->first();
    expect($newAppRole)->not->toBeNull()
        ->and($newAppRole->description)->toBe('Cicilia Rumini, S.Kep.Ners')
        ->and($newAppRole->app_roles)->toBe(1);

    // 3. Update role of an existing user to role 2
    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update-user', $newAppRole), [
            'app_roles' => 2,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($newAppRole->fresh()->app_roles)->toBe(2);

    // 4. Cannot delete role 2 while a user is still assigned to it
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy-definition', 2))
        ->assertSessionHasErrors('role_id');

    // 5. Delete the user's access from tb_master_nurse_supervision_approles
    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-user', $newAppRole))
        ->assertRedirect(route('admin.roles.index'));

    expect(AppRole::where('nip', '1355010892')->exists())->toBeFalse();

    // 6. Now role 2 can be deleted cleanly
    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy-definition', 2))
        ->assertRedirect(route('admin.roles.index'));

    expect(RefAppRole::where('role_id', 2)->exists())->toBeFalse();

    // 7. Admin cannot delete their own active account
    $adminAppRole = AppRole::where('nip', '5508150824')->first();
    $this->actingAs($this->admin)
        ->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy-user', $adminAppRole))
        ->assertSessionHasErrors('nip');
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
