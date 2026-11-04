<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisionReport extends Model
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
    protected $table = 'tb_nurse_supervision_reports';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report_date',
        'shift',
        'room_name',
        'patient_count',
        'nurse_count',
        'supervisor_name',
        'nurse_in_charge',
        'general_notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'patient_count' => 'integer',
            'nurse_count' => 'integer',
        ];
    }

    /**
     * @return HasMany<Incident, $this>
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
