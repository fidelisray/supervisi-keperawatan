<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    /**
     * @return BelongsTo<SupervisionReport, $this>
     */
    public function supervisionReport(): BelongsTo
    {
        return $this->belongsTo(SupervisionReport::class);
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'complain' => 'Complain',
            'fasilitas' => 'Fasilitas',
            'jkn_6_hari' => 'JKN ≥ 6 Hari',
            default => 'Lain-Lain',
        };
    }
}
