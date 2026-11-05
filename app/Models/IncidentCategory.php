<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentCategory extends Model
{
    use SoftDeletes;

    protected $connection = 'sqlsrv';

    protected $table = 'tb_master_nurse_supervision_incidents_category';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'incident_category',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 'true');
    }

    /**
     * @return HasMany<Incident, $this>
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'category');
    }

    public function isActive(): bool
    {
        return strtolower(trim((string) $this->is_active)) === 'true';
    }

    public function isComplaintCategory(): bool
    {
        return str_contains(strtolower((string) $this->incident_category), 'complain')
            || str_contains(strtolower((string) $this->incident_category), 'komplain');
    }

    public function badgeClasses(): string
    {
        $name = strtolower(trim((string) $this->incident_category));

        return match (true) {
            str_contains($name, 'complain') || str_contains($name, 'komplain') => 'bg-rose-100 text-rose-800 border border-rose-200',
            str_contains($name, 'fasilitas') || str_contains($name, 'sarpras') => 'bg-amber-100 text-amber-800 border border-amber-200',
            str_contains($name, 'jkn') || str_contains($name, 'bpjs') => 'bg-teal-100 text-teal-800 border border-teal-200',
            default => 'bg-slate-100 text-slate-800 border border-slate-200',
        };
    }

    public function chartColorClass(): string
    {
        $name = strtolower(trim((string) $this->incident_category));

        return match (true) {
            str_contains($name, 'complain') || str_contains($name, 'komplain') => 'bg-rose-600',
            str_contains($name, 'fasilitas') || str_contains($name, 'sarpras') => 'bg-amber-500',
            str_contains($name, 'jkn') || str_contains($name, 'bpjs') => 'bg-teal-600',
            default => 'bg-blue-600',
        };
    }

    public function radioPeerClasses(): string
    {
        $name = strtolower(trim((string) $this->incident_category));

        return match (true) {
            str_contains($name, 'complain') || str_contains($name, 'komplain') => 'peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-800',
            str_contains($name, 'fasilitas') || str_contains($name, 'sarpras') => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800',
            str_contains($name, 'jkn') || str_contains($name, 'bpjs') => 'peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-800',
            default => 'peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-800',
        };
    }
}

