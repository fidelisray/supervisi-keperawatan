<?php

namespace App\Models;

use App\Models\Concerns\ReadOnlyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppRole extends Model
{
    use ReadOnlyModel;

    public const APP_ID = 6;

    public const ROLE_ADMIN = 0;

    public const ROLE_SUPERVISOR = 1;

    protected $connection = 'sqlsrv';

    protected $table = 'vAppRoles';

    protected $primaryKey = 'nip';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    public function getTable(): string
    {
        return (string) config('supervisi.app_roles_read_table', 'vAppRoles');
    }

    /**
     * @return Builder<static>
     */
    public function newQuery(): Builder
    {
        $builder = parent::newQuery();

        if (AppRoleAssignment::usesTemporaryWriteTable()) {
            $readTable = $this->getTable();
            $writeTable = (new AppRoleAssignment)->getTable();

            if ($readTable !== $writeTable) {
                $connection = $this->getConnection();

                $baseQuery = $connection->table($readTable.' as v')
                    ->selectRaw('v.nip, v.nama, v.app_id, v.app_name, v.app_roles, v.description')
                    ->where('v.app_id', self::APP_ID)
                    ->whereNotExists(function ($sub) use ($writeTable): void {
                        $sub->selectRaw('1')
                            ->from($writeTable.' as m')
                            ->whereColumn('m.nip', 'v.nip')
                            ->where('m.app_id', self::APP_ID);
                    });

                $overlayQuery = $connection->table($writeTable.' as m')
                    ->join('tb_employees as e', 'e.nip', '=', 'm.nip')
                    ->selectRaw("m.nip, e.nama, m.app_id, 'Supervisi Keperawatan' as app_name, m.app_roles, m.description")
                    ->where('m.app_id', self::APP_ID)
                    ->where('m.app_roles', '>=', 0);

                $builder->fromSub($baseQuery->unionAll($overlayQuery), $readTable);
            }
        }

        return $builder;
    }

    public function displayName(): string
    {
        $nama = trim((string) ($this->nama ?? ''));

        return $nama !== '' ? $nama : trim((string) $this->nip);
    }

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
