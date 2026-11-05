<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\SupervisionReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisionAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reportsQuery = SupervisionReport::query()
            ->accessibleBy($user)
            ->when($request->filled('room'), function (Builder $query) use ($request): void {
                $query->where('room_name', (string) $request->string('room'));
            })
            ->when($request->filled('date_from'), function (Builder $query) use ($request): void {
                $query->whereDate('report_date', '>=', (string) $request->string('date_from'));
            })
            ->when($request->filled('date_to'), function (Builder $query) use ($request): void {
                $query->whereDate('report_date', '<=', (string) $request->string('date_to'));
            });

        $reports = $reportsQuery
            ->with('incidents')
            ->orderBy('report_date')
            ->get();

        $allIncidents = $reports->flatMap(fn (SupervisionReport $report) => $report->incidents);
        $totalReports = $reports->count();
        $totalIncidents = $allIncidents->count();

        $categories = [
            'complain' => ['label' => 'Complain Pasien/Keluarga', 'color' => 'bg-rose-600', 'badge' => 'bg-rose-50 text-rose-800 border-rose-200'],
            'fasilitas' => ['label' => 'Kendala Fasilitas & Sarpras', 'color' => 'bg-amber-500', 'badge' => 'bg-amber-50 text-amber-800 border-amber-200'],
            'jkn_6_hari' => ['label' => 'Pasien JKN >= 6 Hari', 'color' => 'bg-teal-600', 'badge' => 'bg-teal-50 text-teal-800 border-teal-200'],
            'lain_lain' => ['label' => 'Kejadian Lain-Lain', 'color' => 'bg-slate-600', 'badge' => 'bg-slate-100 text-slate-800 border-slate-300'],
        ];

        $categoryStats = [];
        foreach ($categories as $key => $meta) {
            $count = $allIncidents->where('category', $key)->count();
            $categoryStats[$key] = [
                'label' => $meta['label'],
                'color' => $meta['color'],
                'badge' => $meta['badge'],
                'count' => $count,
                'percentage' => $totalIncidents > 0 ? round(($count / $totalIncidents) * 100, 1) : 0.0,
            ];
        }

        $statuses = [
            'in_progress' => ['label' => 'Sedang Ditangani', 'color' => 'bg-amber-500', 'text' => 'text-amber-700'],
            'resolved' => ['label' => 'Selesai Ditangani', 'color' => 'bg-emerald-600', 'text' => 'text-emerald-700'],
            'escalated' => ['label' => 'Perlu Eskalasi', 'color' => 'bg-rose-600', 'text' => 'text-rose-700'],
        ];

        $statusStats = [];
        foreach ($statuses as $key => $meta) {
            $count = $allIncidents->where('status', $key)->count();
            $statusStats[$key] = [
                'label' => $meta['label'],
                'color' => $meta['color'],
                'text' => $meta['text'],
                'count' => $count,
                'percentage' => $totalIncidents > 0 ? round(($count / $totalIncidents) * 100, 1) : 0.0,
            ];
        }

        $roomStats = $reports
            ->groupBy('room_name')
            ->map(function ($roomReports, string $roomName): array {
                $incidents = $roomReports->flatMap(fn (SupervisionReport $r) => $r->incidents);

                return [
                    'room_name' => $roomName,
                    'reports_count' => $roomReports->count(),
                    'incidents_count' => $incidents->count(),
                    'complain_count' => $incidents->where('category', 'complain')->count(),
                    'resolved_count' => $incidents->where('status', 'resolved')->count(),
                ];
            })
            ->sortByDesc('incidents_count')
            ->take(10)
            ->values();

        $dailyTrend = $reports
            ->groupBy(fn (SupervisionReport $report) => $report->report_date->format('Y-m-d'))
            ->map(function ($dayReports, string $date): array {
                $incidents = $dayReports->flatMap(fn (SupervisionReport $r) => $r->incidents);

                return [
                    'date' => $date,
                    'formatted_date' => $dayReports->first()->report_date->format('d M Y'),
                    'reports_count' => $dayReports->count(),
                    'incidents_count' => $incidents->count(),
                ];
            })
            ->sortKeys()
            ->values();

        $rooms = Room::orderBy('nama_ruangan')->get(['id_ruang', 'nama_ruangan', 'instalasi']);

        return view('supervisi.analytics', [
            'totalReports' => $totalReports,
            'totalIncidents' => $totalIncidents,
            'categoryStats' => $categoryStats,
            'statusStats' => $statusStats,
            'roomStats' => $roomStats,
            'dailyTrend' => $dailyTrend,
            'rooms' => $rooms,
        ]);
    }
}
