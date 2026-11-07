<?php

namespace App\Http\Controllers;

use App\Models\IncidentCategory;
use App\Models\Room;
use App\Models\SupervisionReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisionAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('view_analytics'), 403);

        $user = $request->user();

        // Cakupan data menggunakan scopeAccessibleBy() pada SupervisionReport (saat ini difilter untuk Administrator,
        // Pembuat Logbook, dan Kolaborator; logika Global Visibility tetap tersimpan di SupervisionReport::scopeAccessibleBy).
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
            ->with(['incidents.incidentCategory'])
            ->orderBy('report_date')
            ->get();

        $allIncidents = $reports->flatMap(fn (SupervisionReport $report) => $report->incidents);
        $totalReports = $reports->count();
        $totalIncidents = $allIncidents->count();

        $usedCategoryIds = $allIncidents->pluck('category')->map(fn ($id) => (int) $id)->unique()->all();

        $categories = IncidentCategory::withTrashed()
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

        $categoryStats = [];
        foreach ($categories as $category) {
            $categoryIncidents = $allIncidents->where('category', (int) $category->id);
            $count = $categoryIncidents->count();
            $resolvedInCategory = $categoryIncidents->where('status', 'resolved')->count();
            $pendingInCategory = $count - $resolvedInCategory;

            $categoryStats[$category->id] = [
                'label' => $category->incident_category,
                'color' => $category->chartColorClass(),
                'badge' => $category->badgeClasses(),
                'count' => $count,
                'resolved_count' => $resolvedInCategory,
                'pending_count' => $pendingInCategory,
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

        $complaintCategoryIds = $categories
            ->filter(fn (IncidentCategory $cat): bool => $cat->isComplaintCategory())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $roomStats = $reports
            ->groupBy('room_name')
            ->map(function ($roomReports, string $roomName) use ($complaintCategoryIds): array {
                $incidents = $roomReports->flatMap(fn (SupervisionReport $r) => $r->incidents);

                return [
                    'room_name' => $roomName,
                    'reports_count' => $roomReports->count(),
                    'incidents_count' => $incidents->count(),
                    'complain_count' => $incidents->whereIn('category', $complaintCategoryIds)->count(),
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

        $allModeRaw = [
            [
                'label' => 'Total Logbook',
                'count' => $totalReports,
                'color' => 'bg-blue-600',
                'subtitle' => 'Laporan supervisi tercatat',
            ],
            [
                'label' => 'Total Insiden',
                'count' => $totalIncidents,
                'color' => 'bg-slate-800',
                'subtitle' => 'Akumulasi seluruh kategori',
            ],
            [
                'label' => 'Selesai Ditangani',
                'count' => $statusStats['resolved']['count'] ?? 0,
                'color' => 'bg-emerald-600',
                'subtitle' => ($statusStats['resolved']['percentage'] ?? 0).'% dari total insiden',
            ],
            [
                'label' => 'Sedang Ditangani',
                'count' => $statusStats['in_progress']['count'] ?? 0,
                'color' => 'bg-amber-500',
                'subtitle' => ($statusStats['in_progress']['percentage'] ?? 0).'% dari total insiden',
            ],
            [
                'label' => 'Perlu Eskalasi',
                'count' => $statusStats['escalated']['count'] ?? 0,
                'color' => 'bg-rose-600',
                'subtitle' => ($statusStats['escalated']['percentage'] ?? 0).'% dari total insiden',
            ],
        ];
        $maxAllCount = max(1, (int) collect($allModeRaw)->max('count'));
        $allBarItems = array_map(function (array $item) use ($maxAllCount): array {
            $item['height_pct'] = $item['count'] > 0 ? max(8, (int) round(($item['count'] / $maxAllCount) * 100)) : 0;

            return $item;
        }, $allModeRaw);

        $maxCategoryCount = max(1, (int) collect($categoryStats)->max('count'));
        $incidentBarItems = array_values(array_map(function (array $stat) use ($maxCategoryCount): array {
            return [
                'label' => $stat['label'],
                'count' => $stat['count'],
                'color' => $stat['color'],
                'height_pct' => $stat['count'] > 0 ? max(8, (int) round(($stat['count'] / $maxCategoryCount) * 100)) : 0,
                'subtitle' => $stat['percentage'].'% ('.$stat['resolved_count'].' selesai, '.$stat['pending_count'].' tindak lanjut)',
            ];
        }, $categoryStats));

        $maxRoomCount = max(
            1,
            (int) $roomStats->max('incidents_count'),
            (int) $roomStats->max('reports_count')
        );
        $roomBarItems = $roomStats->map(function (array $roomStat) use ($maxRoomCount): array {
            return [
                'label' => $roomStat['room_name'],
                'count' => $roomStat['incidents_count'],
                'reports_count' => $roomStat['reports_count'],
                'resolved_count' => $roomStat['resolved_count'],
                'color' => 'bg-teal-600',
                'secondary_color' => 'bg-blue-500',
                'height_pct' => $roomStat['incidents_count'] > 0
                    ? max(8, (int) round(($roomStat['incidents_count'] / $maxRoomCount) * 100))
                    : 0,
                'secondary_height_pct' => $roomStat['reports_count'] > 0
                    ? max(8, (int) round(($roomStat['reports_count'] / $maxRoomCount) * 100))
                    : 0,
                'subtitle' => $roomStat['reports_count'].' logbook · '.$roomStat['resolved_count'].' selesai',
            ];
        })->values()->all();

        $barChartData = [
            'all' => $allBarItems,
            'incidents' => $incidentBarItems,
            'rooms' => $roomBarItems,
        ];

        return view('supervisi.analytics', [
            'totalReports' => $totalReports,
            'totalIncidents' => $totalIncidents,
            'categoryStats' => $categoryStats,
            'statusStats' => $statusStats,
            'roomStats' => $roomStats,
            'dailyTrend' => $dailyTrend,
            'rooms' => $rooms,
            'barChartData' => $barChartData,
        ]);
    }
}
