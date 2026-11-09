<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SsoLoginRequest;
use App\Models\AppRole;
use App\Models\GlobalAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SsoAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(SsoLoginRequest $request): RedirectResponse
    {
        $nip = trim((string) $request->validated('nip'));
        $password = (string) $request->validated('password');

        $globalAccount = GlobalAccount::query()
            ->where('nip', $nip)
            ->first(['id', 'nip', 'kata_sandi']);

        if ($globalAccount === null || ! $globalAccount->verifyPassword($password)) {
            throw ValidationException::withMessages([
                'nip' => 'NIP atau kata sandi SSO yang Anda masukkan tidak sesuai.',
            ]);
        }

        $appRole = AppRole::query()
            ->forSupervisiApp()
            ->where('nip', $nip)
            ->first(['id', 'nip', 'app_id', 'app_roles', 'description']);

        if ($appRole === null) {
            throw ValidationException::withMessages([
                'nip' => 'Akun NIP Anda belum terdaftar untuk mengakses aplikasi Supervisi Keperawatan (App ID: 6). Silakan hubungi Administrator.',
            ]);
        }

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

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
