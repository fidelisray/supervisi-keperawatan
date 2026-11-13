<?php

namespace App\Models;

use App\Models\Concerns\ScopedToAppSixModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppRoleAssignment extends Model
{
    use ScopedToAppSixModel;

    public const ROLE_REVOKED_DEV = -1;

    protected $connection = 'sqlsrv';

    protected $table = 'tb_master_nurse_supervision_approles';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'app_id',
        'app_roles',
        'push_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app_id' => 'integer',
            'app_roles' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return (string) config('supervisi.app_roles_write_table', 'tb_master_nurse_supervision_approles');
    }

    public static function usesTemporaryWriteTable(): bool
    {
        return (new self)->getTable() !== 'tb_approles';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSupervisiApp(Builder $query): Builder
    {
        return $query->where('app_id', AppRole::APP_ID);
    }
}

