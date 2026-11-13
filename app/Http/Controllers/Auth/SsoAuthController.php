<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SsoLoginRequest;
use App\Models\AppRole;
use App\Models\GlobalAccount;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SsoAuthController extends Controller
{
    private const MAX_ATTEMPTS_PER_IP = 5;

    private const MAX_ATTEMPTS_PER_NIP = 10;

    private const DECAY_SECONDS = 60;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(SsoLoginRequest $request): RedirectResponse
    {
        $nip = trim((string) $request->validated('nip'));
        $password = (string) $request->validated('password');

        $this->ensureIsNotRateLimited($request, $nip);

        $globalAccount = GlobalAccount::query()
            ->where('nip', $nip)
            ->first(['id', 'nip', 'kata_sandi']);

        if ($globalAccount === null || ! $globalAccount->verifyPassword($password)) {
            $this->hitRateLimiters($request, $nip);

            throw ValidationException::withMessages([
                'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
            ]);
        }

        $appRole = AppRole::query()
            ->forSupervisiApp()
            ->where('nip', $nip)
            ->first(['id', 'nip', 'app_id', 'app_roles', 'description']);

        if ($appRole === null) {
            $this->hitRateLimiters($request, $nip);

            throw ValidationException::withMessages([
                'nip' => 'Akun NIP Anda belum terdaftar untuk mengakses aplikasi Supervisi Keperawatan. Silakan hubungi Administrator.',
            ]);
        }

        $this->clearRateLimiters($request, $nip);

        $user = User::query()->updateOrCreate(
            ['nip' => $appRole->nip],
            [
                'name' => trim((string) $appRole->description),
                'app_roles' => (int) $appRole->app_roles,
            ]
        );

        Auth::login($user);

        $request->session()->regenerate();

        $defaultRoute = $user->hasPermission('create_report')
            ? route('supervisi.create')
            : route('supervisi.index');

        return redirect()->intended($defaultRoute);
    }

    protected function ensureIsNotRateLimited(Request $request, string $nip): void
    {
        [$ipKey, $nipKey] = $this->throttleKeys($request, $nip);

        $ipExceeded = RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS_PER_IP);
        $nipExceeded = RateLimiter::tooManyAttempts($nipKey, self::MAX_ATTEMPTS_PER_NIP);

        if (! $ipExceeded && ! $nipExceeded) {
            return;
        }

        event(new Lockout($request));

        $seconds = max(
            $ipExceeded ? RateLimiter::availableIn($ipKey) : 0,
            $nipExceeded ? RateLimiter::availableIn($nipKey) : 0
        );

        throw ValidationException::withMessages([
            'nip' => "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function hitRateLimiters(Request $request, string $nip): void
    {
        [$ipKey, $nipKey] = $this->throttleKeys($request, $nip);

        RateLimiter::hit($ipKey, self::DECAY_SECONDS);
        RateLimiter::hit($nipKey, self::DECAY_SECONDS);
    }

    protected function clearRateLimiters(Request $request, string $nip): void
    {
        [$ipKey, $nipKey] = $this->throttleKeys($request, $nip);

        RateLimiter::clear($ipKey);
        RateLimiter::clear($nipKey);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function throttleKeys(Request $request, string $nip): array
    {
        $normalizedNip = Str::transliterate(Str::lower($nip));

        return [
            "sso-login:{$normalizedNip}|{$request->ip()}",
            "sso-login-nip:{$normalizedNip}",
        ];
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
