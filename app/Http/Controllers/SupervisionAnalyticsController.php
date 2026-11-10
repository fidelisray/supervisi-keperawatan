<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Room;
use App\Models\SupervisionReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupervisionAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('view_analytics'), 403);

        $user = $request->user();

        // 1. Tangani Quick Date Preset jika dipilih
        $preset = (string) $request->string('preset')->trim();
        $dateFrom = (string) $request->string('date_from')->trim();
        $dateTo = (string) $request->string('date_to')->trim();

        if ($preset !== '') {
            [$dateFrom, $dateTo] = match ($preset) {
                'today' => [now()->toDateString(), now()->toDateString()],
                'last_7_days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
                'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                'last_month' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
                default => [$dateFrom, $dateTo],
            };
        }

        $room = (string) $request->string('room')->trim();
        $status = (string) $request->string('status')->trim();
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        // 2. Query Laporan Ter-scope dengan Filter Multi-Dimensi
        $reportsQuery = SupervisionReport::query()
            ->accessibleBy($user)
            ->when($room !== '', function (Builder $query) use ($room): void {
                $query->where('room_name', $room);
            })
            ->when($dateFrom !== '', function (Builder $query) use ($dateFrom): void {
                $query->whereDate('report_date', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function (Builder $query) use ($dateTo): void {
                $query->whereDate('report_date', '<=', $dateTo);
            })
            ->when($categoryId !== null, function (Builder $query) use ($categoryId): void {
                $query->whereHas('incidents', function (Builder $q) use ($categoryId): void {
                    $q->where('category', $categoryId);
                });
            })
            ->when($status !== '' && $status !== 'all', function (Builder $query) use ($status): void {
                $query->whereHas('incidents', function (Builder $q) use ($status): void {
                    $q->where('status', $status);
                });
            });

        $reports = $reportsQuery
            ->with(['incidents' => function ($query) use ($categoryId, $status): void {
                $query->when($categoryId !== null, fn ($q) => $q->where('category', $categoryId))
                    ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
                    ->with('incidentCategory');
            }])
            ->orderBy('report_date')
            ->get();

        $allIncidents = $reports->flatMap(fn (SupervisionReport $report) => $report->incidents);
        $totalReports = $reports->count();
        $totalIncidents = $allIncidents->count();

        // 3. Kategori Insiden & Statistiknya
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

        $categoryHexColors = [
            'complain' => '#e11d48', // rose-600
            'komplain' => '#e11d48',
            'fasilitas' => '#d97706', // amber-600
            'sarpras' => '#d97706',
            'jkn' => '#0d9488', // teal-600
            'bpjs' => '#0d9488',
            'obat' => '#8b5cf6', // purple-500
            'farmasi' => '#8b5cf6',
            'pasien' => '#2563eb', // blue-600
            'pelayanan' => '#0284c7', // sky-600
            'lain' => '#64748b', // slate-500 (abu-abu)
        ];

        $fallbackPalette = [
            '#2563eb', '#0d9488', '#d97706', '#64748b', '#8b5cf6',
            '#0284c7', '#059669', '#ea580c', '#334155', '#475569',
        ];

        $categoryStats = [];
        $categoryChartLabels = [];
        $categoryChartData = [];
        $categoryChartColors = [];

        foreach ($categories as $index => $category) {
            $categoryIncidents = $allIncidents->where('category', (int) $category->id);
            $count = $categoryIncidents->count();
            $resolvedInCategory = $categoryIncidents->where('status', 'resolved')->count();
            $pendingInCategory = $count - $resolvedInCategory;
            $percentage = $totalIncidents > 0 ? round(($count / $totalIncidents) * 100, 1) : 0.0;

            // Tentukan warna hex
            $catName = strtolower(trim((string) $category->incident_category));
            $hexColor = $fallbackPalette[$index % count($fallbackPalette)];
            foreach ($categoryHexColors as $keyword => $hex) {
                if (str_contains($catName, $keyword)) {
                    $hexColor = $hex;
                    break;
                }
            }

            $categoryStats[$category->id] = [
                'id' => $category->id,
                'label' => $category->incident_category,
                'color' => $category->chartColorClass(),
                'hex_color' => $hexColor,
                'badge' => $category->badgeClasses(),
                'count' => $count,
                'resolved_count' => $resolvedInCategory,
                'pending_count' => $pendingInCategory,
                'percentage' => $percentage,
            ];

            if ($count > 0 || count($categories) <= 6) {
                $categoryChartLabels[] = $category->incident_category;
                $categoryChartData[] = $count;
                $categoryChartColors[] = $hexColor;
            }
        }

        // 4. Status Penanganan Insiden
        $statuses = [
            'resolved' => ['label' => 'Selesai Ditangani', 'color' => 'bg-emerald-600', 'hex' => '#059669', 'text' => 'text-emerald-700'],
            'in_progress' => ['label' => 'Sedang Ditangani', 'color' => 'bg-amber-500', 'hex' => '#d97706', 'text' => 'text-amber-700'],
            'escalated' => ['label' => 'Perlu Eskalasi', 'color' => 'bg-rose-600', 'hex' => '#dc2626', 'text' => 'text-rose-700'],
        ];

        $statusStats = [];
        $statusChartLabels = [];
        $statusChartData = [];
        $statusChartColors = [];

        foreach ($statuses as $key => $meta) {
            $count = $allIncidents->where('status', $key)->count();
            $percentage = $totalIncidents > 0 ? round(($count / $totalIncidents) * 100, 1) : 0.0;

            $statusStats[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'hex' => $meta['hex'],
                'text' => $meta['text'],
                'count' => $count,
                'percentage' => $percentage,
            ];

            $statusChartLabels[] = $meta['label'];
            $statusChartData[] = $count;
            $statusChartColors[] = $meta['hex'];
        }

        // 5. Statistik per Ruangan
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
                    'pending_count' => $incidents->where('status', '!=', 'resolved')->count(),
                ];
            })
            ->sortByDesc('incidents_count')
            ->values();

        // 6. Tren Waktu (Harian)
        $dailyTrend = $reports
            ->groupBy(fn (SupervisionReport $report) => $report->report_date->format('Y-m-d'))
            ->map(function ($dayReports, string $date): array {
                $incidents = $dayReports->flatMap(fn (SupervisionReport $r) => $r->incidents);

                return [
                    'date' => $date,
                    'formatted_date' => $dayReports->first()->report_date->format('d M'),
                    'full_date' => $dayReports->first()->report_date->format('d M Y'),
                    'reports_count' => $dayReports->count(),
                    'incidents_count' => $incidents->count(),
                ];
            })
            ->sortKeys()
            ->values();

        // 7. Master Ruangan & Kategori Lengkap untuk Filter Bar
        $rooms = Room::orderBy('nama_ruangan')->get(['id_ruang', 'nama_ruangan', 'instalasi']);
        $filterCategories = IncidentCategory::active()->orderBy('incident_category')->get(['id', 'incident_category']);

        // 8. Struktur Data Chart.js Lengkap
        $topRoomsForChart = $roomStats->take(8);
        $chartPayload = [
            'category' => [
                'labels' => $categoryChartLabels,
                'data' => $categoryChartData,
                'colors' => $categoryChartColors,
            ],
            'status' => [
                'labels' => $statusChartLabels,
                'data' => $statusChartData,
                'colors' => $statusChartColors,
            ],
            'trend' => [
                'labels' => $dailyTrend->pluck('formatted_date')->all(),
                'full_dates' => $dailyTrend->pluck('full_date')->all(),
                'incidents' => $dailyTrend->pluck('incidents_count')->all(),
                'reports' => $dailyTrend->pluck('reports_count')->all(),
            ],
            'rooms' => [
                'labels' => $topRoomsForChart->pluck('room_name')->all(),
                'incidents' => $topRoomsForChart->pluck('incidents_count')->all(),
                'reports' => $topRoomsForChart->pluck('reports_count')->all(),
                'resolved' => $topRoomsForChart->pluck('resolved_count')->all(),
                'complain' => $topRoomsForChart->pluck('complain_count')->all(),
            ],
        ];

        // 9. Kompatibilitas Legacy & Bar Items
        $maxRoomCount = max(
            1,
            (int) $roomStats->max('incidents_count'),
            (int) $roomStats->max('reports_count')
        );
        $roomBarItems = $roomStats->take(10)->map(function (array $roomStat) use ($maxRoomCount): array {
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

        $incidentBarItems = array_values(array_map(function (array $stat): array {
            return [
                'label' => $stat['label'],
                'count' => $stat['count'],
                'color' => $stat['color'],
                'subtitle' => $stat['percentage'].'% ('.$stat['resolved_count'].' selesai, '.$stat['pending_count'].' tindak lanjut)',
            ];
        }, $categoryStats));

        $barChartData = [
            'all' => $incidentBarItems,
            'incidents' => $incidentBarItems,
            'rooms' => $roomBarItems,
        ];

        // Hitung Rasio Resolusi
        $resolvedCount = $statusStats['resolved']['count'] ?? 0;
        $pendingCount = ($statusStats['in_progress']['count'] ?? 0) + ($statusStats['escalated']['count'] ?? 0);
        $resolutionRate = $totalIncidents > 0 ? round(($resolvedCount / $totalIncidents) * 100, 1) : 0.0;

        return view('supervisi.analytics', [
            'totalReports' => $totalReports,
            'totalIncidents' => $totalIncidents,
            'resolvedCount' => $resolvedCount,
            'pendingCount' => $pendingCount,
            'resolutionRate' => $resolutionRate,
            'categoryStats' => $categoryStats,
            'statusStats' => $statusStats,
            'roomStats' => $roomStats,
            'dailyTrend' => $dailyTrend,
            'rooms' => $rooms,
            'filterCategories' => $filterCategories,
            'chartPayload' => $chartPayload,
            'barChartData' => $barChartData,
            'currentPreset' => $preset,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedRoom' => $room,
            'selectedStatus' => $status,
            'selectedCategoryId' => $categoryId,
        ]);
    }

    /**
     * Endpoint API Drill-down untuk mengambil detail insiden terkait
     * saat baris ruangan, grafik, atau status diklik.
     */
    public function drilldown(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('view_analytics'), 403);

        $user = $request->user();

        $room = (string) $request->string('room')->trim();
        $dateFrom = (string) $request->string('date_from')->trim();
        $dateTo = (string) $request->string('date_to')->trim();
        $date = (string) $request->string('date')->trim();
        $status = (string) $request->string('status')->trim();
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $incidents = Incident::query()
            ->whereHas('supervisionReport', function (Builder $query) use ($user, $room, $dateFrom, $dateTo, $date): void {
                $query->accessibleBy($user)
                    ->when($room !== '', function (Builder $q) use ($room): void {
                        $q->where('room_name', $room);
                    })
                    ->when($dateFrom !== '', function (Builder $q) use ($dateFrom): void {
                        $q->whereDate('report_date', '>=', $dateFrom);
                    })
                    ->when($dateTo !== '', function (Builder $q) use ($dateTo): void {
                        $q->whereDate('report_date', '<=', $dateTo);
                    })
                    ->when($date !== '', function (Builder $q) use ($date): void {
                        $q->whereDate('report_date', $date);
                    });
            })
            ->when($categoryId !== null, function (Builder $query) use ($categoryId): void {
                $query->where('category', $categoryId);
            })
            ->when($status !== '' && $status !== 'all', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->with(['supervisionReport', 'incidentCategory'])
            ->latest('id')
            ->get();

        $mapped = $incidents->map(function (Incident $incident): array {
            $report = $incident->supervisionReport;

            return [
                'id' => $incident->id,
                'report_id' => $incident->supervision_report_id,
                'report_date' => $report?->report_date?->format('d M Y') ?? '-',
                'shift' => ucfirst((string) ($report?->shift ?? '-')),
                'room_name' => $report?->room_name ?? '-',
                'supervisor_name' => $report?->supervisor_name ?? '-',
                'nurse_in_charge' => $report?->collaboratorsDisplayString() ?? '-',
                'incident_time' => $incident->incident_time ? date('H:i', strtotime($incident->incident_time)) : '-',
                'category_name' => $incident->categoryLabel(),
                'category_badge' => $incident->categoryBadgeClasses(),
                'description' => $incident->description,
                'action_taken' => $incident->action_taken,
                'status' => $incident->status,
                'status_label' => match ($incident->status) {
                    'resolved' => 'Selesai Ditangani',
                    'in_progress' => 'Sedang Ditangani',
                    'escalated' => 'Perlu Eskalasi',
                    default => ucfirst((string) $incident->status),
                },
                'report_url' => route('supervisi.show', $incident->supervision_report_id),
            ];
        });

        return response()->json([
            'success' => true,
            'title' => $room !== '' ? 'Rincian Insiden Ruangan: '.$room : 'Daftar Rincian Insiden',
            'room' => $room,
            'total' => $mapped->count(),
            'incidents' => $mapped,
        ]);
    }

    /**
     * Ekspor data gabungan laporan supervisi dan butir insiden ke berkas CSV ramah Excel.
     * Menggunakan StreamedResponse dan UTF-8 BOM dengan delimiter titik koma (;).
     */
    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->hasPermission('view_analytics'), 403);

        $user = $request->user();
        $scope = (string) $request->string('scope')->trim();

        $reportsQuery = SupervisionReport::query()->accessibleBy($user);

        if ($scope !== 'all') {
            $preset = (string) $request->string('preset')->trim();
            $dateFrom = (string) $request->string('date_from')->trim();
            $dateTo = (string) $request->string('date_to')->trim();

            if ($preset !== '') {
                [$dateFrom, $dateTo] = match ($preset) {
                    'today' => [now()->toDateString(), now()->toDateString()],
                    'last_7_days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
                    'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                    'last_month' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
                    default => [$dateFrom, $dateTo],
                };
            }

            $room = (string) $request->string('room')->trim();
            $status = (string) $request->string('status')->trim();
            $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

            $reportsQuery
                ->when($room !== '', fn (Builder $q) => $q->where('room_name', $room))
                ->when($dateFrom !== '', fn (Builder $q) => $q->whereDate('report_date', '>=', $dateFrom))
                ->when($dateTo !== '', fn (Builder $q) => $q->whereDate('report_date', '<=', $dateTo))
                ->when($categoryId !== null, function (Builder $q) use ($categoryId): void {
                    $q->whereHas('incidents', fn ($sub) => $sub->where('category', $categoryId));
                })
                ->when($status !== '' && $status !== 'all', function (Builder $q) use ($status): void {
                    $q->whereHas('incidents', fn ($sub) => $sub->where('status', $status));
                });
        }

        $filename = sprintf(
            'supervisi_keperawatan_%s_%s.csv',
            $scope === 'all' ? 'semua_data' : 'terfilter',
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function () use ($reportsQuery, $request, $scope): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM untuk kompatibilitas otomatis Microsoft Excel Windows
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = [
                'No',
                'ID Logbook',
                'Tanggal Supervisi',
                'Shift',
                'Ruangan',
                'Jumlah Pasien',
                'Jumlah Perawat',
                'Supervisor (Pembuat)',
                'Perawat Jaga / Kolaborator',
                'Catatan Umum Logbook',
                'Status Logbook',
                'ID Insiden',
                'Waktu Insiden',
                'Kategori Insiden',
                'Uraian Masalah / Kejadian',
                'Tindakan Dilakukan',
                'Status Penanganan Insiden',
            ];
            fputcsv($handle, $headers, ';');

            $rowNumber = 1;
            $categoryId = ($scope !== 'all' && $request->filled('category_id')) ? (int) $request->input('category_id') : null;
            $status = ($scope !== 'all' && $request->filled('status')) ? (string) $request->string('status') : '';

            $reportsQuery
                ->with(['incidents' => function ($q) use ($categoryId, $status): void {
                    $q->when($categoryId !== null, fn ($sub) => $sub->where('category', $categoryId))
                        ->when($status !== '' && $status !== 'all', fn ($sub) => $sub->where('status', $status))
                        ->with('incidentCategory');
                }])
                ->orderBy('report_date')
                ->orderBy('id')
                ->chunk(100, function ($reports) use ($handle, &$rowNumber): void {
                    foreach ($reports as $report) {
                        $incidents = $report->incidents;

                        if ($incidents->isEmpty()) {
                            fputcsv($handle, [
                                $rowNumber++,
                                $report->id,
                                $report->report_date?->format('d/m/Y') ?? '-',
                                ucfirst((string) $report->shift),
                                $report->room_name,
                                $report->patient_count,
                                $report->nurse_count,
                                $report->supervisor_name,
                                $report->collaboratorsDisplayString(),
                                $report->general_notes ?: '-',
                                ucfirst((string) $report->status),
                                '-',
                                '-',
                                'Nihil / Tidak Ada Insiden',
                                'Tidak ada kejadian khusus yang dilaporkan',
                                '-',
                                '-',
                            ], ';');
                        } else {
                            foreach ($incidents as $incident) {
                                $statusLabel = match ($incident->status) {
                                    'resolved' => 'Selesai Ditangani',
                                    'in_progress' => 'Sedang Ditangani',
                                    'escalated' => 'Perlu Eskalasi',
                                    default => ucfirst((string) $incident->status),
                                };

                                fputcsv($handle, [
                                    $rowNumber++,
                                    $report->id,
                                    $report->report_date?->format('d/m/Y') ?? '-',
                                    ucfirst((string) $report->shift),
                                    $report->room_name,
                                    $report->patient_count,
                                    $report->nurse_count,
                                    $report->supervisor_name,
                                    $report->collaboratorsDisplayString(),
                                    $report->general_notes ?: '-',
                                    ucfirst((string) $report->status),
                                    $incident->id,
                                    $incident->incident_time ? date('H:i', strtotime($incident->incident_time)) : '-',
                                    $incident->categoryLabel(),
                                    $incident->description ?: '-',
                                    $incident->action_taken ?: '-',
                                    $statusLabel,
                                ], ';');
                            }
                        }
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
