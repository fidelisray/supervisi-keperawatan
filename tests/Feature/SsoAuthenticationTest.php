<?php

use App\Models\AppRole;
use App\Models\AppRoleAssignment;
use App\Models\RefAppRole;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    $appRole = AppRoleAssignment::query()->create([
        'nip' => '1355010892',
        'app_id' => AppRole::APP_ID,
        'app_roles' => 1,
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
        ->delete(route('admin.roles.destroy-user', $appRole->nip))
        ->assertRedirect(route('admin.roles.index'));

    expect(User::where('nip', '1355010892')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $targetUser->id)->exists())->toBeFalse();
});

test('employee pre-registered in app_id 6 without globalaccount receives specific confirmation message on login and can login after globalaccount is created', function () {
    $admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali Sastra, S.Kom',
        'app_roles' => 0,
    ]);

    // Step 1: Admin registers employee 1355010892 (who is in tb_employees, but NOT in tb_globalaccount yet)
    $this->actingAs($admin)
        ->post(route('admin.roles.assign-user'), [
            'nip' => '1355010892',
            'app_roles' => 1,
        ])
        ->assertRedirect(route('admin.roles.index'));

    auth()->logout();

    // Step 2: Employee 1355010892 attempts to login before having a GlobalAccount
    $preGlobalResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '1355010892',
        'password' => 'apapun123',
    ]);

    $preGlobalResponse->assertRedirect(route('login'));
    $preGlobalResponse->assertSessionHasErrors([
        'nip' => 'NIP Anda sudah terdaftar pada aplikasi Supervisi Keperawatan, namun belum memiliki akun SSO Rumah Sakit (Global Account). Silakan hubungi bagian IT / Administrator untuk aktivasi akun SSO Anda.',
    ]);
    $this->assertGuest();

    // Step 3: Base vAppRoles nurse 2351151100 (with NULL description in tb_approles and no GlobalAccount yet) also receives confirmation message
    $basePreGlobalResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => '2351151100',
        'password' => 'apapun123',
    ]);

    $basePreGlobalResponse->assertSessionHasErrors([
        'nip' => 'NIP Anda sudah terdaftar pada aplikasi Supervisi Keperawatan, namun belum memiliki akun SSO Rumah Sakit (Global Account). Silakan hubungi bagian IT / Administrator untuk aktivasi akun SSO Anda.',
    ]);

    // Step 4: IT registers 2351151100 in tb_globalaccount -> login succeeds and syncs name from vAppRoles.nama even when description is null
    DB::connection('sqlsrv')->table('tb_globalaccount')->insert([
        'nip' => '2351151100',
        'kata_sandi' => base64_encode('ners2026'),
        'device_id' => null,
    ]);

    $loginSuccessResponse = $this->post(route('login.store'), [
        'nip' => '2351151100',
        'password' => 'ners2026',
    ]);

    $loginSuccessResponse->assertRedirect(route('supervisi.create'));
    $this->assertAuthenticated();
    expect(User::where('nip', '2351151100')->value('name'))
        ->toBe('Anastasia Naning Sudiar, S.Kep.Ners');
});

test('login resists sql injection payloads on nip and password and preserves database integrity', function (string $maliciousNip, string $maliciousPassword) {
    $normalizedNip = Str::transliterate(Str::lower($maliciousNip));
    RateLimiter::clear("sso-login:{$normalizedNip}|127.0.0.1");
    RateLimiter::clear("sso-login-nip:{$normalizedNip}");

    $initialGlobalAccountCount = DB::connection('sqlsrv')->table('tb_globalaccount')->count();
    $initialAppRolesCount = DB::connection('sqlsrv')->table('tb_approles')->count();

    $response = $this->from(route('login'))->post(route('login.store'), [
        'nip' => $maliciousNip,
        'password' => $maliciousPassword,
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
    ]);
    $this->assertGuest();

    expect(DB::connection('sqlsrv')->table('tb_globalaccount')->count())->toBe($initialGlobalAccountCount)
        ->and(DB::connection('sqlsrv')->table('tb_approles')->count())->toBe($initialAppRolesCount)
        ->and(Schema::hasTable('users'))->toBeTrue();
})->with([
    'classic OR tautology' => ["' OR '1'='1", "' OR '1'='1"],
    'numeric OR tautology with SQL comment' => ["' OR 1=1 --", 'sembarang123'],
    'parenthesized tautology' => ["') OR ('1'='1", 'sembarang123'],
    'admin NIP comment truncation' => ["5508150824' --", 'sembarang123'],
    'admin NIP inline tautology' => ["5508150824' OR 'a'='a", 'sembarang123'],
    'supervisor NIP comment truncation' => ["1672010896'/*", 'sembarang123'],
    'valid NIP with SQLi in password' => ['1672010896', "' OR '1'='1' --"],
    'UNION SELECT 3 columns' => ["' UNION SELECT 1,2,3--", 'sembarang123'],
    'stacked query DROP TABLE attempt' => ["'; DROP TABLE users;--", 'sembarang123'],
]);

test('login rejects array type juggling injection and oversized sqli payloads at validation boundary', function () {
    $arrayResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => ['$ne' => null],
        'password' => ['$gt' => ''],
    ]);

    $arrayResponse->assertRedirect(route('login'));
    $arrayResponse->assertSessionHasErrors(['nip', 'password']);
    $this->assertGuest();

    $longSqlPayload = "' UNION SELECT id, nip, kata_sandi FROM tb_globalaccount WHERE '1'='1";
    $longResponse = $this->from(route('login'))->post(route('login.store'), [
        'nip' => $longSqlPayload,
        'password' => 'rahasia123',
    ]);

    $longResponse->assertRedirect(route('login'));
    $longResponse->assertSessionHasErrors([
        'nip' => 'Nomor Induk Pegawai (NIP) maksimal 30 karakter.',
    ]);
    $this->assertGuest();
});

test('login and logout endpoints enforce csrf token protection against cross-site request forgery', function () {
    $this->app->bind(
        PreventRequestForgery::class,
        fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        }
    );

    // TC-CSRF-01: Form login merender input hidden _token dan meta csrf-token
    $loginPage = $this->get(route('login'));
    $loginPage->assertSuccessful()
        ->assertSee('name="_token"', false)
        ->assertSee('name="csrf-token"', false);

    // TC-CSRF-02: Login CSRF tanpa _token ditolak dengan HTTP 419
    $missingTokenResponse = $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
        ->post(route('login.store'), [
            'nip' => '1672010896',
            'password' => 'rahasia123',
        ]);

    $missingTokenResponse->assertStatus(419);
    $this->assertGuest();

    // TC-CSRF-03: Login CSRF dengan _token palsu/dimanipulasi ditolak dengan HTTP 419
    $invalidTokenResponse = $this->withSession(['_token' => 'token-sesi-asli-123'])
        ->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
        ->post(route('login.store'), [
            '_token' => 'token-palsu-999',
            'nip' => '1672010896',
            'password' => 'rahasia123',
        ]);

    $invalidTokenResponse->assertStatus(419);
    $this->assertGuest();

    // TC-CSRF-04: Login dengan _token sesi yang valid berhasil
    $validTokenResponse = $this->withSession(['_token' => 'token-sesi-asli-123'])
        ->post(route('login.store'), [
            '_token' => 'token-sesi-asli-123',
            'nip' => '1672010896',
            'password' => 'rahasia123',
        ]);

    $validTokenResponse->assertRedirect(route('supervisi.create'));
    $this->assertAuthenticated();

    // TC-CSRF-05: Logout CSRF lintas situs dengan token palsu ditolak (HTTP 419) dan sesi tetap terjaga
    $forgedLogoutResponse = $this->withSession(['_token' => 'token-sesi-baru-456'])
        ->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
        ->post(route('logout'), [
            '_token' => 'token-palsu-logout',
        ]);

    $forgedLogoutResponse->assertStatus(419);
    $this->assertAuthenticated();
});

test('sso login in production mode reads directly from vAppRoles without temporary write table overlay', function () {
    config()->set('supervisi.app_roles_read_table', 'vAppRoles');
    config()->set('supervisi.app_roles_write_table', 'tb_approles');
    config()->set('supervisi.ref_app_roles_table', 'ref_approles');

    // Put a conflicting revoked row inside temporary dev table; production login must ignore it
    DB::connection('sqlsrv')->table('tb_master_nurse_supervision_approles')
        ->where('app_id', AppRole::APP_ID)
        ->where('nip', '1672010896')
        ->update([
            'app_roles' => AppRoleAssignment::ROLE_REVOKED_DEV,
        ]);

    $response = $this->post(route('login.store'), [
        'nip' => '1672010896',
        'password' => 'rahasia123',
    ]);

    $response->assertRedirect(route('supervisi.create'));
    $this->assertAuthenticated();
    expect(User::where('nip', '1672010896')->value('app_roles'))->toBe(AppRole::ROLE_SUPERVISOR);
});
