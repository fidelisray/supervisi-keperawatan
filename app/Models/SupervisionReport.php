<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisionReport extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

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

    /**
     * Parse the stored collaborator string into a clean list of collaborator names.
     *
     * @return list<string>
     */
    public function collaboratorList(): array
    {
        $raw = trim((string) $this->nurse_in_charge);

        if ($raw === '') {
            return [];
        }

        $parts = array_map('trim', explode('|', $raw));

        return array_values(array_filter($parts, fn (string $name): bool => $name !== ''));
    }

    /**
     * Format an array or string of collaborator names for storage in nurse_in_charge.
     *
     * @param  array<int, mixed>|string|null  $collaborators
     */
    public static function formatCollaborators(array|string|null $collaborators): string
    {
        if (is_string($collaborators)) {
            $collaborators = explode('|', $collaborators);
        }

        if (! is_array($collaborators)) {
            return '';
        }

        $cleaned = [];
        foreach ($collaborators as $item) {
            $name = trim((string) $item);
            if ($name !== '' && ! in_array($name, $cleaned, true)) {
                $cleaned[] = $name;
            }
        }

        return implode(' | ', $cleaned);
    }

    /**
     * Determine whether the given user can view, edit, or update incidents on this report.
     */
    public function isAccessibleBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $userName = trim((string) $user->name);

        if ($userName === '') {
            return false;
        }

        if (strcasecmp(trim((string) $this->supervisor_name), $userName) === 0) {
            return true;
        }

        foreach ($this->collaboratorList() as $collaborator) {
            if (strcasecmp($collaborator, $userName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope query to reports accessible by the given user (Admin sees all; Supervisor sees own or collaborated).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $userName = trim((string) $user->name);

        return $query->where(function (Builder $subQuery) use ($userName): void {
            $subQuery->where('supervisor_name', $userName)
                ->orWhere('nurse_in_charge', 'like', '%'.$userName.'%');
        });
    }
}
