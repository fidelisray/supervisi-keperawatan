<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Incident extends Model
{
    use HasFactory;

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
    protected $table = 'tb_nurse_supervision_incidents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supervision_report_id',
        'category',
        'incident_time',
        'description',
        'action_taken',
        'status',
        'is_category_locked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supervision_report_id' => 'integer',
            'category' => 'integer',
            'is_category_locked' => 'boolean',
        ];
    }

    /**
     * Cegah status tindak lanjut yang sudah selesai dan kategori yang sudah pernah diubah diganti kembali pada level model.
     */
    protected static function booted(): void
    {
        static::updating(function (Incident $incident): void {
            if ($incident->getOriginal('status') === 'resolved' && $incident->status !== 'resolved') {
                throw ValidationException::withMessages([
                    'status' => 'Status tindak lanjut yang sudah selesai tidak dapat diubah kembali.',
                ]);
            }

            if ($incident->getOriginal('is_category_locked') && $incident->isDirty('category')) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori insiden yang sudah pernah diubah tidak dapat diganti kembali.',
                ]);
            }
        });
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isCategoryLocked(): bool
    {
        return (bool) ($this->attributes['is_category_locked'] ?? false);
    }

    /**
     * @return BelongsTo<SupervisionReport, $this>
     */
    public function supervisionReport(): BelongsTo
    {
        return $this->belongsTo(SupervisionReport::class);
    }

    /**
     * @return BelongsTo<IncidentCategory, $this>
     */
    public function incidentCategory(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category')->withTrashed();
    }

    public function categoryLabel(): string
    {
        if ($this->relationLoaded('incidentCategory') && $this->incidentCategory !== null) {
            return (string) $this->incidentCategory->incident_category;
        }

        if ($this->incidentCategory !== null) {
            return (string) $this->incidentCategory->incident_category;
        }

        return 'Kategori #'.$this->category;
    }

    public function categoryBadgeClasses(): string
    {
        if ($this->incidentCategory !== null) {
            return $this->incidentCategory->badgeClasses();
        }

        return 'bg-slate-100 text-slate-800 border border-slate-200';
    }
}
