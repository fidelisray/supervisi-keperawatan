<?php

namespace App\Http\Controllers;

use App\Models\AppRole;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Room;
use App\Models\SupervisionReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SupervisionReportController extends Controller
{
    /**
     * Display a listing of supervision reports scoped to the authenticated user's role.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $preset = (string) $request->string('preset')->trim();
        $dateFrom = (string) $request->string('date_from')->trim();
        $dateTo = (string) $request->string('date_to')->trim();

        if ($preset === '' && $dateFrom === '' && $dateTo === '' && ! $request->hasAny(['preset', 'date_from', 'date_to'])) {
            $preset = 'today';
        }

        if ($preset !== '') {
            [$dateFrom, $dateTo] = match ($preset) {
                'all' => ['', ''],
                'today' => [now()->toDateString(), now()->toDateString()],
                'last_7_days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
                'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                'last_month' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
                default => [$dateFrom, $dateTo],
            };
        }

        $accessibleReportsQuery = SupervisionReport::query()->accessibleBy($user);

        $filteredQuery = (clone $accessibleReportsQuery)
            ->when($request->filled('room'), function (Builder $query) use ($request): void {
                $query->where('room_name', (string) $request->string('room'));
            })
            ->when($dateFrom !== '', function (Builder $query) use ($dateFrom): void {
                $query->whereDate('report_date', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function (Builder $query) use ($dateTo): void {
                $query->whereDate('report_date', '<=', $dateTo);
            })
            /*
            // [GLOBAL VISIBILITY LOGIC - Aktifkan blok ini jika semua pengguna dapat memfilter berdasarkan nama supervisor]
            ->when($request->filled('supervisor'), function (Builder $query) use ($request): void {
                $query->where('supervisor_name', 'like', '%'.trim((string) $request->string('supervisor')).'%');
            })
            */
            ->when(($user->isAdmin() || $user->hasPermission('view_all_reports')) && $request->filled('supervisor'), function (Builder $query) use ($request): void {
                $query->where('supervisor_name', 'like', '%'.trim((string) $request->string('supervisor')).'%');
            })
            ->when($request->filled('collaborator'), function (Builder $query) use ($request): void {
                $query->where('nurse_in_charge', 'like', '%'.trim((string) $request->string('collaborator')).'%');
            });

        $reports = (clone $filteredQuery)
            ->with(['incidents.incidentCategory'])
            ->latest('report_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $accessibleIdsSubquery = (clone $filteredQuery)->select('id');

        $complaintCategoryIds = IncidentCategory::withTrashed()
            ->get(['id', 'incident_category'])
            ->filter(fn (IncidentCategory $cat): bool => $cat->isComplaintCategory())
            ->pluck('id')
            ->all();

        $totalReportsCount = (clone $filteredQuery)->count();
        $totalIncidentsCount = Incident::query()->whereIn('supervision_report_id', $accessibleIdsSubquery)->count();
        $totalComplaintsCount = count($complaintCategoryIds) > 0
            ? Incident::query()
                ->whereIn('supervision_report_id', $accessibleIdsSubquery)
                ->whereIn('category', $complaintCategoryIds)
                ->count()
            : 0;
        $resolvedIncidentsCount = Incident::query()
            ->whereIn('supervision_report_id', $accessibleIdsSubquery)
            ->where('status', 'resolved')
            ->count();

        $rooms = Room::orderBy('nama_ruangan')->get(['id_ruang', 'nama_ruangan', 'instalasi']);
        $nurses = AppRole::query()
            ->forSupervisiApp()
            ->orderBy('description')
            ->get(['id', 'nip', 'description', 'app_roles']);

        return view('supervisi.index', [
            'reports' => $reports,
            'totalReportsCount' => $totalReportsCount,
            'activePreset' => $preset,
            'totalIncidentsCount' => $totalIncidentsCount,
            'totalComplaintsCount' => $totalComplaintsCount,
            'resolvedIncidentsCount' => $resolvedIncidentsCount,
            'rooms' => $rooms,
            'nurses' => $nurses,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Show the form for creating a new supervision logbook report.
     */
    public function create(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('create_report'), 403);

        $rooms = Room::orderBy('nama_ruangan')->get(['id_ruang', 'nama_ruangan', 'instalasi']);
        $nurses = AppRole::query()
            ->operationalNurses()
            ->orderBy('description')
            ->get(['id', 'nip', 'description', 'app_roles']);
        $incidentCategories = IncidentCategory::query()
            ->active()
            ->orderBy('id')
            ->get();

        return view('supervisi.create', [
            'rooms' => $rooms,
            'nurses' => $nurses,
            'incidentCategories' => $incidentCategories,
            'defaultShift' => $this->resolveDefaultShift(),
            'defaultSupervisor' => $request->user()?->name ?? '',
            'today' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Store a newly created supervision report and its incidents.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('create_report'), 403);

        $validated = $this->validateReportPayload($request);
        $collaboratorsList = $this->extractCollaboratorsList($request);

        $supervisorName = $request->user()?->name ?: ($validated['supervisor_name'] ?? '');
        $shift = $validated['shift'] ?? $this->resolveDefaultShift();

        $report = DB::connection('sqlsrv')->transaction(function () use ($validated, $supervisorName, $collaboratorsList, $shift) {
            $report = SupervisionReport::create([
                'report_date' => $validated['report_date'],
                'shift' => $shift,
                'room_name' => $validated['room_name'],
                'patient_count' => $validated['patient_count'],
                'nurse_count' => $validated['nurse_count'],
                'supervisor_name' => $supervisorName,
                'nurse_in_charge' => $collaboratorsList,
                'general_notes' => $validated['general_notes'] ?? null,
                'status' => 'submitted',
            ]);

            $this->syncReportIncidents($report, $validated['incidents'] ?? []);

            return $report;
        });

        return redirect()->route('supervisi.show', $report)
            ->with('success', 'Laporan supervisi keperawatan berhasil disimpan dengan aman.');
    }

    /**
     * Display the specified supervision report.
     */
    public function show(Request $request, SupervisionReport $supervisionReport): View
    {
        abort_unless($supervisionReport->isAccessibleBy($request->user()), 403);

        $supervisionReport->load(['incidents.incidentCategory']);

        return view('supervisi.show', [
            'report' => $supervisionReport,
        ]);
    }

    /**
     * Generate and stream a formal A4 PDF document for the specified supervision report.
     */
    public function exportPdf(Request $request, SupervisionReport $supervisionReport): Response
    {
        abort_unless($supervisionReport->isAccessibleBy($request->user()), 403);

        $supervisionReport->load(['incidents.incidentCategory']);

        $logoBase64 = $this->resolvePdfLogo();

        $pdf = Pdf::loadView('supervisi.pdf', [
            'report' => $supervisionReport,
            'logoBase64' => $logoBase64,
            'printedAt' => now(),
            'printedBy' => $request->user()?->name ?: '-',
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'Laporan-Supervisi-RPT-%s.pdf',
            str_pad((string) $supervisionReport->id, 5, '0', STR_PAD_LEFT)
        );

        return $pdf->stream($filename);
    }

    /**
     * Show the form for editing an existing supervision report.
     */
    public function edit(Request $request, SupervisionReport $supervisionReport): View
    {
        /*
        // [GLOBAL VISIBILITY LOGIC - Gunakan canBeModifiedBy() ketika isAccessibleBy() dibuka untuk semua user]
        abort_unless(
            $request->user()?->hasPermission('edit_report')
            && $supervisionReport->canBeModifiedBy($request->user()),
            403
        );
        */
        abort_unless($request->user()?->hasPermission('edit_report') && $supervisionReport->isAccessibleBy($request->user()), 403);

        $supervisionReport->load(['incidents.incidentCategory']);

        $rooms = Room::orderBy('nama_ruangan')->get(['id_ruang', 'nama_ruangan', 'instalasi']);
        $nurses = AppRole::query()
            ->operationalNurses()
            ->orderBy('description')
            ->get(['id', 'nip', 'description', 'app_roles']);

        $usedCategoryIds = $supervisionReport->incidents->pluck('category')->map(fn ($id) => (int) $id)->unique()->all();

        $incidentCategories = IncidentCategory::withTrashed()
            ->where(function (Builder $query) use ($usedCategoryIds): void {
                $query->where(function (Builder $sub): void {
                    $sub->whereNull('deleted_at')->where('is_active', 'true');
                });
                if (count($usedCategoryIds) > 0) {
                    $query->orWhereIn('id', $usedCategoryIds);
                }
            })
            ->orderBy('id')
            ->get();

        return view('supervisi.edit', [
            'report' => $supervisionReport,
            'rooms' => $rooms,
            'nurses' => $nurses,
            'incidentCategories' => $incidentCategories,
        ]);
    }

    /**
     * Update the specified supervision report and its incidents.
     */
    public function update(Request $request, SupervisionReport $supervisionReport): RedirectResponse
    {
        /*
        // [GLOBAL VISIBILITY LOGIC - Gunakan canBeModifiedBy() ketika isAccessibleBy() dibuka untuk semua user]
        abort_unless(
            $request->user()?->hasPermission('edit_report')
            && $supervisionReport->canBeModifiedBy($request->user()),
            403
        );
        */
        abort_unless($request->user()?->hasPermission('edit_report') && $supervisionReport->isAccessibleBy($request->user()), 403);

        $validated = $this->validateReportPayload($request);
        $collaboratorsList = $this->extractCollaboratorsList($request);

        $existingIncidents = $supervisionReport->incidents()->get()->keyBy('id');
        $lockedIncidents = $existingIncidents->filter(fn (Incident $incident) => $incident->isResolved());

        $submittedIncidents = collect($validated['incidents'] ?? [])->values();
        $hasExplicitIds = $submittedIncidents->contains(fn ($item) => ! empty($item['id']));

        if ($hasExplicitIds) {
            $submittedIds = $submittedIncidents
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($lockedIncidents->keys()->diff($submittedIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'incidents' => 'Kejadian yang sudah selesai ditangani tidak dapat dihapus.',
                ]);
            }
        } elseif ($submittedIncidents->count() < $lockedIncidents->count()) {
            throw ValidationException::withMessages([
                'incidents' => 'Kejadian yang sudah selesai ditangani tidak dapat dihapus.',
            ]);
        }

        $lockedIncidentsByIndex = $lockedIncidents->values();

        $resolveExistingIncident = fn (int $idx, array $incidentData): ?Incident => $hasExplicitIds
            ? $existingIncidents->get((int) ($incidentData['id'] ?? 0))
            : $lockedIncidentsByIndex->get($idx);

        foreach ($submittedIncidents as $idx => $incidentData) {
            $existingIncident = $resolveExistingIncident($idx, $incidentData);

            if ($existingIncident === null) {
                continue;
            }

            if ($existingIncident->isResolved() && isset($incidentData['status']) && $incidentData['status'] !== 'resolved') {
                throw ValidationException::withMessages([
                    'status' => 'Status tindak lanjut yang sudah selesai tidak dapat diubah kembali.',
                ]);
            }

            if ($existingIncident->isCategoryLocked() && (int) $incidentData['category'] !== (int) $existingIncident->category) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori insiden yang sudah pernah diubah tidak dapat diganti kembali.',
                ]);
            }
        }

        DB::connection('sqlsrv')->transaction(function () use ($supervisionReport, $validated, $collaboratorsList, $submittedIncidents, $resolveExistingIncident) {
            $supervisionReport->update([
                'report_date' => $validated['report_date'],
                'shift' => $validated['shift'] ?? $supervisionReport->shift,
                'room_name' => $validated['room_name'],
                'patient_count' => $validated['patient_count'],
                'nurse_count' => $validated['nurse_count'],
                'nurse_in_charge' => $collaboratorsList,
                'general_notes' => $validated['general_notes'] ?? null,
            ]);

            $newIncidents = [];
            $keptIncidentIds = [];

            foreach ($submittedIncidents as $idx => $incidentData) {
                $existingIncident = $resolveExistingIncident($idx, $incidentData);

                if ($existingIncident === null) {
                    $newIncidents[] = $incidentData;

                    continue;
                }

                $keptIncidentIds[] = $existingIncident->id;
                $newCategory = (int) $incidentData['category'];
                $categoryChanged = $newCategory !== (int) $existingIncident->category;

                $existingIncident->update([
                    'category' => $newCategory,
                    'incident_time' => $incidentData['incident_time'] ?? null,
                    'description' => $incidentData['description'],
                    'action_taken' => $incidentData['action_taken'] ?? null,
                    'is_category_locked' => $existingIncident->isCategoryLocked() || $categoryChanged,
                ]);
            }

            $supervisionReport->incidents()
                ->whereNotIn('id', $keptIncidentIds)
                ->delete();

            $this->syncReportIncidents($supervisionReport, $newIncidents);
        });

        return redirect()->route('supervisi.show', $supervisionReport)
            ->with('success', 'Perubahan logbook supervisi keperawatan berhasil disimpan.');
    }

    /**
     * Update the follow-up status and action taken of a specific incident.
     */
    public function updateIncidentStatus(
        Request $request,
        SupervisionReport $supervisionReport,
        Incident $incident
    ): RedirectResponse {
        /*
        // [GLOBAL VISIBILITY LOGIC - Gunakan canBeModifiedBy() ketika isAccessibleBy() dibuka untuk semua user]
        abort_unless(
            $request->user()?->hasPermission('update_incident_status')
            && $supervisionReport->canBeModifiedBy($request->user()),
            403
        );
        */
        abort_unless($request->user()?->hasPermission('update_incident_status') && $supervisionReport->isAccessibleBy($request->user()), 403);
        abort_unless((int) $incident->supervision_report_id === (int) $supervisionReport->id, 404);

        if ($incident->isResolved()) {
            throw ValidationException::withMessages([
                'status' => 'Status tindak lanjut yang sudah selesai tidak dapat diubah kembali.',
            ]);
        }

        $validated = $request->validate([
            // 'status' => ['required', 'in:in_progress,resolved,escalated'],
            'status' => ['required', 'in:in_progress,resolved'],
            'action_taken' => ['nullable', 'string', 'max:2000'],
        ]);

        $incident->update([
            'status' => $validated['status'],
            'action_taken' => $validated['action_taken'] ?? $incident->action_taken,
        ]);

        return redirect()->route('supervisi.show', $supervisionReport)
            ->with('success', 'Status tindak lanjut kejadian berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReportPayload(Request $request): array
    {
        $validCategoryIds = IncidentCategory::withTrashed()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $request->validate([
            'report_date' => ['required', 'date'],
            'shift' => ['nullable', 'in:pagi,siang/sore,sore,malam'],
            'room_name' => ['required', 'string', 'max:255'],
            'patient_count' => ['required', 'integer', 'min:0'],
            'nurse_count' => ['required', 'integer', 'min:0'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            // 'collaborators' => ['nullable', 'array', 'max:2'],
            'collaborators' => ['nullable', 'array'],
            'collaborators.*' => ['nullable', 'string', 'max:255'],
            // 'nurse_in_charge' => ['nullable', 'string', 'max:255'],
            'nurse_in_charge' => ['nullable'],
            'general_notes' => ['nullable', 'string', 'max:2000'],
            'incidents' => ['nullable', 'array'],
            'incidents.*.id' => ['nullable', 'integer'],
            'incidents.*.category' => ['required_with:incidents', 'integer', Rule::in($validCategoryIds)],
            'incidents.*.incident_time' => ['nullable', 'string', 'max:20'],
            'incidents.*.description' => ['required_with:incidents', 'string'],
            'incidents.*.action_taken' => ['nullable', 'string'],
            // 'incidents.*.status' => ['nullable', 'in:in_progress,resolved,escalated'],
            'incidents.*.status' => ['nullable', 'in:in_progress,resolved'],
        ], [
            // 'collaborators.max' => 'Maksimal hanya 2 kolaborator yang dapat ditambahkan.',
            'collaborators.*.max' => 'Nama kolaborator maksimal 255 karakter.',
            // 'nurse_in_charge.max' => 'Maksimal hanya 2 kolaborator yang dapat ditambahkan.',
        ]);
    }

    /**
     * @return list<string>
     */
    private function extractCollaboratorsList(Request $request): array
    {
        $rawCollaborators = $request->input('collaborators');

        if (is_array($rawCollaborators) && count($rawCollaborators) > 0) {
            $formatted = SupervisionReport::formatCollaborators($rawCollaborators);
        } else {
            $formatted = SupervisionReport::formatCollaborators($request->input('nurse_in_charge'));
        }

        /*
        // [COLLABORATOR LIMIT LOGIC - Aktifkan blok ini jika pembatasan jumlah maksimal kolaborator diberlakukan kembali]
        $collaboratorCount = count($formatted);

        if ($collaboratorCount > 2) {
            throw ValidationException::withMessages([
                'collaborators' => 'Maksimal hanya 2 kolaborator yang dapat ditambahkan.',
                'nurse_in_charge' => 'Maksimal hanya 2 kolaborator yang dapat ditambahkan.',
            ]);
        }
        */

        return $formatted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $incidents
     */
    private function syncReportIncidents(SupervisionReport $report, array $incidents): void
    {
        foreach ($incidents as $incidentData) {
            $report->incidents()->create([
                'category' => (int) $incidentData['category'],
                'incident_time' => $incidentData['incident_time'] ?? null,
                'description' => $incidentData['description'],
                'action_taken' => $incidentData['action_taken'] ?? null,
                'status' => $incidentData['status'] ?? 'in_progress',
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function availableShifts(): array
    {
        return [
            'pagi' => 'Shift Pagi (07:00 - 13:59)',
            'siang/sore' => 'Shift Siang/Sore (14:00 - 20:59)',
            'malam' => 'Shift Malam (21:00 - 06:59)',
        ];
    }

    private function resolveDefaultShift(): string
    {
        $currentHour = (int) now()->format('H');

        return match (true) {
            $currentHour >= 7 && $currentHour < 14 => 'pagi',
            $currentHour >= 14 && $currentHour < 21 => 'siang/sore',
            default => 'malam',
        };
    }

    private function resolvePdfLogo(): ?string
    {
        $logoPath = resource_path('images/web-icon.webp');

        if (! is_file($logoPath)) {
            return null;
        }

        $cacheDir = storage_path('framework/cache');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir.'/pdf-logo-web-icon.png';

        if (! file_exists($cacheFile) || filemtime($cacheFile) < filemtime($logoPath)) {
            $src = @imagecreatefromwebp($logoPath);
            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);
                $targetW = 200;
                $targetH = (int) round(($h / max($w, 1)) * $targetW);
                $thumb = imagecreatetruecolor($targetW, $targetH);
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                imagecopyresampled($thumb, $src, 0, 0, 0, 0, $targetW, $targetH, $w, $h);
                imagepng($thumb, $cacheFile);
            }
        }

        if (file_exists($cacheFile)) {
            return 'data:image/png;base64,'.base64_encode((string) file_get_contents($cacheFile));
        }

        return 'data:image/webp;base64,'.base64_encode((string) file_get_contents($logoPath));
    }
}
