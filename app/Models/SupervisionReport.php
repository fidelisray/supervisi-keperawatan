<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * Cast and normalize nurse_in_charge as a JSON array of collaborator strings
     * while remaining backward-compatible with legacy pipe-separated strings.
     *
     * @return Attribute<list<string>, array<int, mixed>|string|null>
     */
    protected function nurseInCharge(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): array => self::formatCollaborators($value),
            set: fn (mixed $value): string => json_encode(
                self::formatCollaborators($value),
                JSON_UNESCAPED_UNICODE
            ) ?: '[]',
        );
    }

    /**
     * @return HasMany<Incident, $this>
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Return the normalized list of collaborator names for this report.
     *
     * @return list<string>
     */
    public function collaboratorList(): array
    {
        return self::formatCollaborators($this->nurse_in_charge);
    }

    /**
     * Format the collaborator list into a display string (e.g., for CSV exports or JSON responses).
     */
    public function collaboratorsDisplayString(string $separator = ' | ', string $emptyFallback = '-'): string
    {
        $list = $this->collaboratorList();

        if ($list === []) {
            return $emptyFallback;
        }

        return implode($separator, $list);
    }

    /**
     * Normalize an array, JSON array string, or legacy pipe-separated string of collaborator names
     * into a clean array of unique strings.
     *
     * @param  array<int, mixed>|string|null  $collaborators
     * @return list<string>
     */
    public static function formatCollaborators(array|string|null $collaborators): array
    {
        if (is_string($collaborators)) {
            $trimmed = trim($collaborators);

            if ($trimmed === '') {
                return [];
            }

            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $collaborators = $decoded;
                } else {
                    $collaborators = explode('|', $trimmed);
                }
            } else {
                $collaborators = explode('|', $trimmed);
            }
        }

        if (! is_array($collaborators)) {
            return [];
        }

        $cleaned = [];
        foreach ($collaborators as $item) {
            if (is_array($item)) {
                foreach (self::formatCollaborators($item) as $nestedName) {
                    if (! in_array($nestedName, $cleaned, true)) {
                        $cleaned[] = $nestedName;
                    }
                }

                continue;
            }

            $name = trim((string) $item);
            if ($name !== '' && ! in_array($name, $cleaned, true)) {
                $cleaned[] = $name;
            }
        }

        return $cleaned;
    }

    /**
     * Determine whether the given user can view, edit, or update incidents on this report.
     *
     * Note: Scoped visibility is active (Admin / view_all_reports, Creator, or Collaborator).
     * The Global Visibility logic is preserved in comments below for easy switching.
     */
    public function isAccessibleBy(User $user): bool
    {
        /*
        // [GLOBAL VISIBILITY LOGIC - Aktifkan blok ini jika seluruh pengguna terdaftar boleh melihat detail semua logbook]
        return trim((string) $user->nip) !== '' || trim((string) $user->name) !== '';
        */

        if ($user->isAdmin() || $user->hasPermission('view_all_reports')) {
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
     * Determine whether the given user is authorized to modify this report
     * or update its incident follow-up statuses (Creator, Collaborator, or Administrator).
     */
    public function canBeModifiedBy(User $user): bool
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
     * Note: Scoped visibility is active for both logbook history and incident analytics.
     * The Global Visibility logic is preserved in comments below for easy switching.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        /*
        // [GLOBAL VISIBILITY LOGIC - Aktifkan blok ini jika daftar logbook & grafik analitik menampilkan seluruh data rumah sakit untuk semua user]
        return $query;
        */

        if ($user->isAdmin() || $user->hasPermission('view_all_reports')) {
            return $query;
        }

        $userName = trim((string) $user->name);

        return $query->where(function (Builder $subQuery) use ($userName): void {
            $subQuery->where('supervisor_name', $userName)
                ->orWhere('nurse_in_charge', 'like', '%'.$userName.'%');
        });
    }
}
