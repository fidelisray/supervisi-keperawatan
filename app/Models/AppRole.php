<?php

namespace App\Models;

use App\Models\Concerns\ScopedToAppSixModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppRole extends Model
{
    use ScopedToAppSixModel;

    public const APP_ID = 6;

    public const ROLE_ADMIN = 0;

    public const ROLE_SUPERVISOR = 1;

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
        'description',
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

    /**
     * Scope query to users registered for the Nursing Supervision application (app_id = 6).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSupervisiApp(Builder $query): Builder
    {
        return $query->where('app_id', self::APP_ID);
    }

    /**
     * Scope query to operational nurses/supervisors (app_id = 6 and app_roles = 1).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOperationalNurses(Builder $query): Builder
    {
        return $query
            ->where('app_id', self::APP_ID)
            ->where('app_roles', self::ROLE_SUPERVISOR);
    }

    /**
     * @return BelongsTo<RefAppRole, $this>
     */
    public function refRole(): BelongsTo
    {
        return $this->belongsTo(RefAppRole::class, 'app_roles', 'role_id')
            ->where('app_id', self::APP_ID);
    }

    public function roleLabel(): string
    {
        return RefAppRole::resolveRoleName((int) $this->app_roles);
    }
}
