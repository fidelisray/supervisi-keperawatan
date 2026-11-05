<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SsoLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nip.required' => 'Nomor Induk Pegawai (NIP) wajib diisi.',
            'nip.max' => 'Nomor Induk Pegawai (NIP) maksimal 30 karakter.',
            'password.required' => 'Kata sandi SSO wajib diisi.',
        ];
    }
}
