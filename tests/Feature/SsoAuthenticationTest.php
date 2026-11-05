<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest user is redirected to login page when accessing supervision routes', function () {
    $this->get(route('supervisi.create'))
        ->assertRedirect(route('login'));

    $this->get(route('supervisi.index'))
        ->assertRedirect(route('login'));
});

test('login page renders split-screen sso authentication form', function () {
    $response = $this->get(route('login'));

    $response->assertSuccessful();
    $response->assertSee('E-Supervisi Keperawatan');
    $response->assertSee('Nomor Induk Pegawai (NIP)');
    $response->assertSee('Kata Sandi SSO');
});

test('user registered in app_id 6 can login with valid base64 sso credentials and syncs to local users table', function () {
    $response = $this->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => 'rahasia123',
    ]);

    $response->assertRedirect(route('supervisi.create'));
    $this->assertAuthenticated();

    $user = User::where('nip', '1672010896')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep')
        ->and($user->app_roles)->toBe(1)
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->roleLabel())->toBe('SUPERVISOR KEPERAWATAN');
});

test('user with wrong password cannot login', function () {
    $response = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => 'password-salah',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('nip');
    $this->assertGuest();
});

test('user with valid globalaccount credentials but not registered in app_id 6 is denied access', function () {
    $response = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '9000000001',
        'password' => '123456',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('nip');
    $this->assertGuest();
});

test('authenticated user can logout and session is invalidated', function () {
    $user = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
