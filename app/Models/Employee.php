<?php

namespace App\Models;

use App\Models\Concerns\ReadOnlyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * Scope a query to only include employees with nursing titles/degrees.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNurses(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('nama', 'like', '%S.Kep.Ners%')
                ->orWhere('nama', 'like', '%AMK%');
        });
    }
}
