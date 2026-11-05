<?php

namespace App\Models;

use App\Models\Concerns\ScopedToAppSixModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RefAppRole extends Model
{
    use ScopedToAppSixModel;

    protected $connection = 'sqlsrv';

    protected $table = 'ref_master_nurse_supervision_approles';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'role_name',
        'app_id',
    ];

    /**
     * @var array<int, string>
     */
    public const DEFAULT_ROLE_LABELS = [
        AppRole::ROLE_ADMIN => 'ADMINISTRATOR',
        AppRole::ROLE_SUPERVISOR => 'SUPERVISOR KEPERAWATAN',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
            'app_id' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSupervisiApp(Builder $query): Builder
    {
        return $query->where('app_id', AppRole::APP_ID);
    }

    /**
     * Resolve the human-readable role name for the Supervision app (app_id = 6),
     * falling back to DEFAULT_ROLE_LABELS if ref_master_nurse_supervision_approles rows have not been seeded yet.
     */
    public static function resolveRoleName(int $roleId): string
    {
        $cacheKey = "ref_master_nurse_supervision_approles.app_6.role_{$roleId}";

        try {
            $roleName = Cache::remember($cacheKey, now()->addHour(), function () use ($roleId): ?string {
                return static::query()
                    ->where('app_id', AppRole::APP_ID)
                    ->where('role_id', $roleId)
                    ->value('role_name');
            });

            if (is_string($roleName) && trim($roleName) !== '') {
                return trim($roleName);
            }
        } catch (Throwable) {
            // Fall through to default role map when database is unreachable.
        }

        return self::DEFAULT_ROLE_LABELS[$roleId] ?? 'PENGGUNA';
    }

    /**
     * Return all available role definitions for app_id = 6 (merging database rows with default labels).
     *
     * @return array<int, string>
     */
    public static function availableRoles(): array
    {
        $roles = self::DEFAULT_ROLE_LABELS;

        try {
            $dbRoles = static::query()
                ->forSupervisiApp()
                ->orderBy('role_id')
                ->pluck('role_name', 'role_id')
                ->all();

            foreach ($dbRoles as $roleId => $roleName) {
                $roles[(int) $roleId] = trim((string) $roleName);
            }
        } catch (Throwable) {
            // Keep default roles if query fails.
        }

        ksort($roles);

        return $roles;
    }

    public static function clearRoleCache(?int $roleId = null): void
    {
        if ($roleId !== null) {
            Cache::forget("ref_master_nurse_supervision_approles.app_6.role_{$roleId}");

            return;
        }

        foreach (array_keys(self::availableRoles()) as $id) {
            Cache::forget("ref_master_nurse_supervision_approles.app_6.role_{$id}");
        }
    }
}
