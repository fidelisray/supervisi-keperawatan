<?php

namespace App\Models;

use App\Models\Concerns\ReadOnlyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Employee extends Model
{
    use ReadOnlyModel;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'sqlsrv';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tb_employees';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'nip';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'nip';
    }

    /**
     * Scope a query to only include active employees.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status_karyawan', 'AKTIF');
    }

    /**
     * Scope a query to only include nursing employees via ref_profesi or nursing degree titles.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNurses(Builder $query): Builder
    {
        $model = $query->getModel();
        $connection = $model->getConnection();
        $driver = $connection->getDriverName();
        $hasProfesiTable = Schema::connection($model->getConnectionName())->hasTable('ref_profesi');

        return $query->where(function (Builder $sub) use ($driver, $hasProfesiTable): void {
            if ($hasProfesiTable) {
                $sub->whereExists(function ($profesiQuery) use ($driver): void {
                    $profesiJoinExpr = match ($driver) {
                        'sqlsrv' => "p.id = TRY_CAST(NULLIF(LTRIM(RTRIM(tb_employees.id_profesi)), '') AS INT)",
                        'sqlite' => "LTRIM(RTRIM(tb_employees.id_profesi)) != '' AND LTRIM(RTRIM(tb_employees.id_profesi)) NOT GLOB '*[^0-9]*' AND p.id = CAST(LTRIM(RTRIM(tb_employees.id_profesi)) AS INTEGER)",
                        default => "p.id = CAST(NULLIF(LTRIM(RTRIM(tb_employees.id_profesi)), '') AS INTEGER)",
                    };

                    $profesiQuery->selectRaw('1')
                        ->from('ref_profesi as p')
                        ->whereRaw($profesiJoinExpr)
                        ->where(function ($nameQuery): void {
                            $nameQuery->whereRaw("LOWER(LTRIM(RTRIM(p.nama))) = 'perawat'")
                                ->orWhereRaw("LOWER(LTRIM(RTRIM(p.nama))) LIKE 'perawat pk%'")
                                ->orWhereRaw("LOWER(LTRIM(RTRIM(p.nama))) LIKE 'perawat staf%'");
                        });
                });
            }

            $sub->orWhere('nama', 'like', '%S.Kep.Ners%')
                ->orWhere('nama', 'like', '%S.Kep. Ners%')
                ->orWhere('nama', 'like', '%A.Md.Kep%')
                ->orWhere('nama', 'like', '%A.Md. Kep%')
                ->orWhere('nama', 'like', '%S.Tr.Kep%')
                ->orWhere(function (Builder $amkQuery): void {
                    $amkQuery->where('nama', 'like', '%AMK%')
                        ->where('nama', 'not like', '%AMKg%');
                });
        });
    }
}
