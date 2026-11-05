<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nip', 'name', 'app_roles', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app_roles' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return (int) $this->app_roles === AppRole::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return RefAppRole::resolveRoleName((int) $this->app_roles);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';

        foreach ($words as $word) {
            $cleanWord = preg_replace('/[^A-Za-z]/', '', $word);
            if ($cleanWord !== '' && $cleanWord !== null) {
                $initials .= strtoupper($cleanWord[0]);
            }
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'SK';
    }
}
