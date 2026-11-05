<?php

namespace App\Models;

use App\Models\Concerns\ReadOnlyModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RefAppRole extends Model
{
    use ReadOnlyModel;

    protected $connection = 'sqlsrv';

    protected $table = 'ref_approles';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @var array<int, string>
     */
    public const DEFAULT_ROLE_LABELS = [
        AppRole::ROLE_ADMIN => 'ADMINISTRATOR',
        AppRole::ROLE_SUPERVISOR => 'SUPERVISOR KEPERAWATAN',
    ];

    /**
     * Resolve the human-readable role name for the Supervision app (app_id = 6),
     * falling back to DEFAULT_ROLE_LABELS if ref_approles rows have not been seeded yet.
     */
    public static function resolveRoleName(int $roleId): string
    {
        $cacheKey = "ref_approles.app_6.role_{$roleId}";

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
}
