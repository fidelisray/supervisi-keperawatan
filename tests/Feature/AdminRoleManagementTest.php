<?php

use App\Models\AppRole;
use App\Models\RefAppRole;
use App\Models\User;
use Database\Seeders\RefAppRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('administrator can view role management page, create a new role for app_id 6, and assign or update user roles', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('Manajemen Role & Hak Akses Pengguna (App ID: 6)');

    // 1. Create a new role definition for app_id = 6
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

    // 3. Update role of an existing user in app_id = 6
    $this->actingAs($this->admin)
        ->patch(route('admin.roles.update-user', $newAppRole), [
            'app_roles' => 2,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($newAppRole->fresh()->app_roles)->toBe(2);
});

test('RefAppRoleSeeder seeds default roles for app_id 6', function () {
    $this->seed(RefAppRoleSeeder::class);

    expect(RefAppRole::where('app_id', 6)->where('role_id', 0)->value('role_name'))
        ->toBe('ADMINISTRATOR')
        ->and(RefAppRole::where('app_id', 6)->where('role_id', 1)->value('role_name'))
        ->toBe('SUPERVISOR KEPERAWATAN');
});
