<?php

use App\Models\AppRole;
use App\Models\RefAppRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('guest user is redirected to login page when accessing supervision routes', function () {
    $this->get(route('supervisi.create'))
        ->assertRedirect(route('login'));

    $this->get(route('supervisi.index'))
        ->assertRedirect(route('login'));
});

test('login page renders split-screen sso authentication form with autocomplete disabled and no-store cache headers', function () {
    $response = $this->get(route('login'));

    $response->assertSuccessful();
    $response->assertSee('E-Supervisi Keperawatan');
    $response->assertSee('Nomor Induk Pegawai (NIP)');
    $response->assertSee('Kata Sandi');
    $response->assertSee('autocomplete="off"', false);

    $cacheControl = (string) $response->headers->get('Cache-Control');
    expect($cacheControl)
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0')
        ->and($response->headers->get('Pragma'))->toBe('no-cache');
});

test('authenticated user pressing back or visiting login page is redirected away from login and protected pages send no-store headers', function () {
    $supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    $loginResponse = $this->actingAs($supervisor)->get(route('login'));
    $loginResponse->assertRedirect(route('supervisi.create'));

    $protectedResponse = $this->actingAs($supervisor)->get(route('supervisi.create'));
    $protectedResponse->assertSuccessful();
    expect((string) $protectedResponse->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache');
});

test('authenticated user without create_report permission is redirected to supervisi index when visiting login', function () {
    RefAppRole::create([
        'app_id' => 6,
        'role_id' => 2,
        'role_name' => 'AUDITOR KLINIS',
    ]);
    RefAppRole::syncPermissions(2, ['view_all_reports']);

    $auditor = User::factory()->create([
        'nip' => '9988776655',
        'name' => 'Auditor Klinis Test',
        'app_roles' => 2,
    ]);

    $this->actingAs($auditor)
        ->get(route('login'))
        ->assertRedirect(route('supervisi.index'));
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
    $response->assertSessionHasErrors([
        'nip' => 'Akun NIP Anda belum terdaftar untuk mengakses aplikasi Supervisi Keperawatan. Silakan hubungi Administrator.',
    ]);
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

test('non-existent nip and wrong password return identical error message to prevent nip enumeration', function () {
    $unknownNipResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '0000000000',
        'password' => 'sembarang123',
    ]);

    $wrongPasswordResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => 'password-salah',
    ]);

    $unknownNipResponse->assertSessionHasErrors([
        'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
    ]);

    $wrongPasswordResponse->assertSessionHasErrors([
        'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
    ]);
});

test('login endpoint enforces rate limiting per nip and ip after 5 failed attempts and clears on success', function () {
    RateLimiter::clear('sso-login:1672010896|127.0.0.1');
    RateLimiter::clear('sso-login-nip:1672010896');

    for ($i = 0; $i < 5; $i++) {
        $this->from(route('login'))->post(route('login.store'), [
            'nip' => '1672010896',
            'password' => 'password-salah',
        ])->assertSessionHasErrors([
            'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
        ]);
    }

    $lockedResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => 'rahasia123',
    ]);

    $lockedResponse->assertRedirect(route('login'));
    $lockedResponse->assertInvalid([
        'nip' => 'Terlalu banyak percobaan login yang gagal',
    ]);
    $this->assertGuest();

    RateLimiter::clear('sso-login:1672010896|127.0.0.1');
    RateLimiter::clear('sso-login-nip:1672010896');

    $this->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => 'rahasia123',
    ])->assertRedirect(route('supervisi.create'));

    $this->assertAuthenticated();
});

test('login endpoint enforces global per-nip rate limit even across different ip addresses', function () {
    RateLimiter::clear('sso-login-nip:1672010896');

    for ($i = 1; $i <= 10; $i++) {
        $ip = "10.0.0.{$i}";
        RateLimiter::clear("sso-login:1672010896|{$ip}");

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from(route('login'))
            ->post(route('login.store'), [
                'nip' => '1672010896',
                'password' => 'password-salah',
            ])
            ->assertSessionHasErrors([
                'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
            ]);
    }

    $lockedResponse = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->from(route('login'))
        ->post(route('login.store'), [
            'nip' => '1672010896',
            'password' => 'rahasia123',
        ]);

    $lockedResponse->assertRedirect(route('login'));
    $lockedResponse->assertInvalid([
        'nip' => 'Terlalu banyak percobaan login yang gagal',
    ]);
    $this->assertGuest();
});

test('login rejects passwords exceeding 255 characters', function () {
    $response = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => str_repeat('a', 256),
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'password' => 'Kata sandi SSO maksimal 255 karakter.',
    ]);
    $this->assertGuest();
});

test('revoking user role deletes active database sessions and local user record immediately', function () {
    $admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);

    $targetUser = User::factory()->create([
        'nip' => '1355010892',
        'name' => 'Cicilia Rumini, S.Kep.Ners',
        'app_roles' => 1,
    ]);

    $appRole = AppRole::query()->create([
        'nip' => '1355010892',
        'app_id' => AppRole::APP_ID,
        'app_roles' => 1,
        'description' => 'Cicilia Rumini, S.Kep.Ners',
    ]);

    config()->set('session.driver', 'database');

    DB::table('sessions')->insert([
        'id' => 'active-session-token-123',
        'user_id' => $targetUser->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PestTest',
        'payload' => base64_encode('test'),
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy-user', $appRole))
        ->assertRedirect(route('admin.roles.index'));

    expect(User::where('nip', '1355010892')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $targetUser->id)->exists())->toBeFalse();
});
