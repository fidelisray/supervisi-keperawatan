<?php

namespace App\Http\Controllers;

use App\Models\AppRole;
use App\Models\Incident;
use App\Models\Room;
use App\Models\SupervisionReport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupervisionReportController extends Controller
{
    /**
     * Display a listing of recent supervision reports.
     */
    public function index(): View
    {
        $today = Carbon::today();

        $reports = SupervisionReport::with('incidents')
            ->latest('report_date')
            ->latest('id')
            ->paginate(10);

        $todayReportsCount = SupervisionReport::whereDate('report_date', $today)->count();
        $totalIncidentsCount = Incident::count();
        $totalComplaintsCount = Incident::where('category', 'complain')->count();
        $resolvedIncidentsCount = Incident::where('status', 'resolved')->count();

        return view('supervisi.index', [
            'reports' => $reports,
            'todayReportsCount' => $todayReportsCount,
            'totalIncidentsCount' => $totalIncidentsCount,
            'totalComplaintsCount' => $totalComplaintsCount,
            'resolvedIncidentsCount' => $resolvedIncidentsCount,
        ]);
    }

    /**
     * Show the form for creating a new supervision logbook report.
     */
    public function create(Request $request): View
    {
        $rooms = Room::orderBy('nama_ruangan')->get(['id_ruang', 'nama_ruangan', 'instalasi']);
        $nurses = AppRole::query()
            ->operationalNurses()
            ->orderBy('description')
            ->get(['id', 'nip', 'description', 'app_roles']);

        $shifts = [
            'pagi' => 'Shift Pagi (07:00 - 14:00)',
            'sore' => 'Shift Sore (14:00 - 21:00)',
            'malam' => 'Shift Malam (21:00 - 07:00)',
        ];

        $currentHour = (int) now()->format('H');
        $defaultShift = match (true) {
            $currentHour >= 7 && $currentHour < 14 => 'pagi',
            $currentHour >= 14 && $currentHour < 21 => 'sore',
            default => 'malam',
        };

        return view('supervisi.create', [
            'rooms' => $rooms,
            'nurses' => $nurses,
            'shifts' => $shifts,
            'defaultShift' => $defaultShift,
            'defaultSupervisor' => $request->user()?->name ?? '',
            'today' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Store a newly created supervision report and its incidents.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_date' => ['required', 'date'],
            'shift' => ['required', 'in:pagi,sore,malam'],
            'room_name' => ['required', 'string', 'max:255'],
            'patient_count' => ['required', 'integer', 'min:0'],
            'nurse_count' => ['required', 'integer', 'min:0'],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'nurse_in_charge' => ['required', 'string', 'max:255'],
            'general_notes' => ['nullable', 'string', 'max:2000'],
            'incidents' => ['nullable', 'array'],
            'incidents.*.category' => ['required_with:incidents', 'in:complain,fasilitas,jkn_6_hari,lain_lain'],
            'incidents.*.incident_time' => ['nullable', 'string', 'max:20'],
            'incidents.*.description' => ['required_with:incidents', 'string'],
            'incidents.*.action_taken' => ['nullable', 'string'],
            'incidents.*.status' => ['nullable', 'in:in_progress,resolved,escalated'],
        ]);

        $supervisorName = $request->user()?->name ?: $validated['supervisor_name'];

        $report = DB::connection('sqlsrv')->transaction(function () use ($validated, $supervisorName) {
            $report = SupervisionReport::create([
                'report_date' => $validated['report_date'],
                'shift' => $validated['shift'],
                'room_name' => $validated['room_name'],
                'patient_count' => $validated['patient_count'],
                'nurse_count' => $validated['nurse_count'],
                'supervisor_name' => $supervisorName,
                'nurse_in_charge' => $validated['nurse_in_charge'],
                'general_notes' => $validated['general_notes'] ?? null,
                'status' => 'submitted',
            ]);

            if (! empty($validated['incidents'])) {
                foreach ($validated['incidents'] as $incidentData) {
                    $report->incidents()->create([
                        'category' => $incidentData['category'],
                        'incident_time' => $incidentData['incident_time'] ?? null,
                        'description' => $incidentData['description'],
                        'action_taken' => $incidentData['action_taken'] ?? null,
                        'status' => $incidentData['status'] ?? 'in_progress',
                    ]);
                }
            }

            return $report;
        });

        return redirect()->route('supervisi.show', $report)
            ->with('success', 'Laporan supervisi keperawatan berhasil disimpan dengan aman.');
    }

    /**
     * Display the specified supervision report.
     */
    public function show(SupervisionReport $supervisionReport): View
    {
        $supervisionReport->load('incidents');

        return view('supervisi.show', [
            'report' => $supervisionReport,
        ]);
    }
}
