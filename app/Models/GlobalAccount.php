<?php

namespace App\Models;

use App\Models\Concerns\ReadOnlyModel;
use Illuminate\Database\Eloquent\Model;

class GlobalAccount extends Model
{
    use ReadOnlyModel;

    protected $connection = 'sqlsrv';

    protected $table = 'tb_globalaccount';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'kata_sandi',
    ];

    /**
     * Verify plain-text password against the Base64-encoded hospital SSO credential.
     */
    public function verifyPassword(string $plainPassword): bool
    {
        $storedPassword = (string) ($this->attributes['kata_sandi'] ?? '');

        if ($storedPassword === '') {
            return false;
        }

        return hash_equals($storedPassword, base64_encode($plainPassword));
    }
}
