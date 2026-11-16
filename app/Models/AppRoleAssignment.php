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
     * Determine whether queries target legacy SQL Server varchar(24) columns on tb_approles or vAppRoles.
     */
    public static function usesLegacyVarcharColumns(?string $table = null): bool
    {
        $instance = new self;
        $targetTable = $table ?? $instance->getTable();

        return $instance->getConnection()->getDriverName() === 'sqlsrv'
            && in_array($targetTable, ['tb_approles', 'vAppRoles'], true);
    }

    public static function appIdFilterValue(?string $table = null): int|string
    {
        return self::usesLegacyVarcharColumns($table)
            ? (string) AppRole::APP_ID
            : AppRole::APP_ID;
    }

    public static function roleFilterValue(int $roleId, ?string $table = null): int|string
    {
        return self::usesLegacyVarcharColumns($table)
            ? (string) $roleId
            : $roleId;
    }

    /**
     * Insert or update a user role assignment strictly scoped by app_id + nip
     * so duplicate legacy identity values across apps are never overwritten.
     *
     * @param  array<string, mixed>  $extraAttributes
     */
    public static function upsertForSupervisiUser(string $nip, int $roleId, array $extraAttributes = []): void
    {
        $normalizedNip = trim($nip);
        $attributes = array_merge($extraAttributes, [
            'app_roles' => self::roleFilterValue($roleId),
        ]);

        $query = self::query()
            ->forSupervisiApp()
            ->where('nip', $normalizedNip);

        if ($query->exists()) {
            $query->update($attributes);

            return;
        }

        self::query()->create(array_merge($attributes, [
            'app_id' => AppRole::APP_ID,
            'nip' => $normalizedNip,
        ]));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSupervisiApp(Builder $query): Builder
    {
        return $query->where('app_id', self::appIdFilterValue());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function setKeysForSaveQuery($query)
    {
        return parent::setKeysForSaveQuery($query)
            ->where('app_id', self::appIdFilterValue());
    }
}
