<x-layouts.app>
    <x-slot name="title">Riwayat Logbook Supervisi</x-slot>

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Page Title & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Supervisi Keperawatan</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Riwayat & Monitoring</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Monitoring Supervisi Ruang Perawatan
                </h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Daftar logbook supervisi, rekap insiden kejadian ruangan, dan tindak lanjut perawat.
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <a 
                    href="{{ route('supervisi.create') }}" 
                    class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-98 transition shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Input Logbook Shift Baru
                </a>
            </div>
        </div>

        <!-- 4 Statistics Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Laporan Hari Ini</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1">{{ $todayReportsCount }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Shift aktif terpantau</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Kejadian</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalIncidentsCount }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Insiden terdata keseluruhan</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Komplain Pasien</p>
                        <h3 class="text-2xl font-extrabold text-rose-600 mt-1">{{ $totalComplaintsCount }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Perlu perhatian mutu</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Terselesaikan</p>
                        <h3 class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $resolvedIncidentsCount }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Tindak lanjut tuntas</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Table Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center space-x-2">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Daftar Laporan Supervisi Terkini
                    </h2>
                </div>
            </div>

            @if($reports->isEmpty())
                <div class="text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Belum Ada Laporan Supervisi</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4 leading-relaxed">
                        Data logbook shift yang disimpan akan muncul di halaman monitoring ini.
                    </p>
                    <a 
                        href="{{ route('supervisi.create') }}" 
                        class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition"
                    >
                        + Input Laporan Pertama
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                <th class="px-6 py-3">Tanggal & Shift</th>
                                <th class="px-6 py-3">Ruangan</th>
                                <th class="px-6 py-3 text-center">BOR (Pasien)</th>
                                <th class="px-6 py-3 text-center">Perawat</th>
                                <th class="px-6 py-3">Kejadian Terlapor</th>
                                <th class="px-6 py-3">Pengawas & Katim</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($reports as $report)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-bold text-slate-900">{{ $report->report_date->format('d/m/Y') }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold mt-1 uppercase
                                            @if($report->shift === 'pagi') bg-sky-50 text-sky-800 border border-sky-200
                                            @elseif($report->shift === 'sore') bg-amber-50 text-amber-800 border border-amber-200
                                            @else bg-indigo-50 text-indigo-800 border border-indigo-200
                                            @endif
                                        ">
                                            Shift {{ ucfirst($report->shift) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 font-semibold text-slate-800">
                                        {{ $report->room_name }}
                                    </td>

                                    <td class="px-6 py-4 text-center whitespace-nowrap font-medium text-slate-700">
                                        <span class="inline-block px-2.5 py-1 bg-slate-100 rounded-md text-xs">
                                            {{ $report->patient_count }} Pasien
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center whitespace-nowrap font-medium text-slate-700">
                                        <span class="inline-block px-2.5 py-1 bg-slate-100 rounded-md text-xs">
                                            {{ $report->nurse_count }} Ners
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        @if($report->incidents->isEmpty())
                                            <span class="inline-flex items-center text-xs text-emerald-700 font-medium">
                                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Nihil Kejadian
                                            </span>
                                        @else
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-800 text-white">
                                                    {{ $report->incidents->count() }} Insiden
                                                </span>

                                                @php
                                                    $cats = $report->incidents->groupBy('category');
                                                @endphp

                                                @if(isset($cats['complain']))
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800" title="{{ $cats['complain']->count() }} Complain">
                                                        {{ $cats['complain']->count() }} Complain
                                                    </span>
                                                @endif
                                                @if(isset($cats['fasilitas']))
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800" title="{{ $cats['fasilitas']->count() }} Fasilitas">
                                                        {{ $cats['fasilitas']->count() }} Fasilitas
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">
                                        <p class="font-medium text-slate-900">Spv: {{ $report->supervisor_name }}</p>
                                        <p class="text-slate-500 mt-0.5">Katim: {{ $report->nurse_in_charge }}</p>
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a 
                                            href="{{ route('supervisi.show', $report) }}" 
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition"
                                        >
                                            Detail & Cetak &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

