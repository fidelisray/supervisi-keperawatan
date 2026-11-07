<x-layouts.app>
    <x-slot name="title">Analisis & Grafik Insiden Supervisi</x-slot>

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Supervisi Keperawatan</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Analisis & Grafik Insiden</li>
                    </ol>
                </nav>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Analisis &amp; Grafik Kejadian Ruangan
                    </h1>
                    {{-- [GLOBAL VISIBILITY LOGIC - Badge cakupan ketika grafik menghitung seluruh logbook rumah sakit untuk semua pengguna]
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                        Cakupan: Seluruh Rumah Sakit
                    </span>
                    --}}
                    @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('view_all_reports'))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                            Cakupan: Seluruh Rumah Sakit
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                            Cakupan: Logbook Saya &amp; Kolaborator
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 mt-0.5">
                    Rekapitulasi distribusi masalah, status penyelesaian tindak lanjut, dan frekuensi kejadian per ruangan.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('supervisi.index') }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs"
                >
                    Lihat Daftar Logbook
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
            <form method="GET" action="{{ route('supervisi.analytics') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-4">
                    <label for="room" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Filter Ruangan</label>
                    <select id="room" name="room" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800">
                        <option value="">Semua Ruangan</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->nama_ruangan }}" {{ request('room') === $room->nama_ruangan ? 'selected' : '' }}>
                                {{ $room->nama_ruangan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-3">
                    <label for="date_from" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Dari Tanggal</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800">
                </div>

                <div class="lg:col-span-3">
                    <label for="date_to" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Sampai Tanggal</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800">
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition cursor-pointer">
                        Terapkan
                    </button>
                    @if(request()->hasAny(['room', 'date_from', 'date_to']))
                        <a href="{{ route('supervisi.analytics') }}" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Top Summary KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Laporan Supervisi</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $totalReports }}</p>
                <p class="text-xs text-slate-500 mt-1">Logbook dalam periode terpilih</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Butir Insiden</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $totalIncidents }}</p>
                <p class="text-xs text-slate-500 mt-1">Seluruh kategori masalah</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Selesai Ditangani</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1 font-mono">{{ $statusStats['resolved']['count'] ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $statusStats['resolved']['percentage'] ?? 0 }}% dari total kejadian</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Perlu Tindak Lanjut</p>
                <p class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">
                    {{ ($statusStats['in_progress']['count'] ?? 0) + ($statusStats['escalated']['count'] ?? 0) }}
                </p>
                <p class="text-xs text-slate-500 mt-1">Sedang ditangani / eskalasi</p>
            </div>
        </div>

        <!-- Dynamic Interactive Bar Chart -->
        <div
            x-data="{
                activeMode: 'all',
                datasets: @js($barChartData),
                get currentItems() {
                    return this.datasets[this.activeMode] || [];
                },
                get modeDescription() {
                    if (this.activeMode === 'incidents') {
                        return 'Perbandingan jumlah kejadian berdasarkan masing-masing kategori insiden supervisi.';
                    }
                    if (this.activeMode === 'rooms') {
                        return 'Perbandingan frekuensi total insiden dan jumlah laporan logbook pada tiap ruangan/bangsal.';
                    }
                    return 'Ringkasan komparatif seluruh data laporan logbook, total insiden, dan status penyelesaian tindak lanjut.';
                }
            }"
            class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-6"
        >
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-200">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                            Grafik Batang (Bar Chart) Dinamis Supervisi
                        </h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            Interaktif
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 mt-1" x-text="modeDescription">
                        Ringkasan komparatif seluruh data laporan logbook, total insiden, dan status penyelesaian tindak lanjut.
                    </p>
                </div>

                <!-- Mode Switcher Tabs -->
                <div role="tablist" aria-label="Filter tampilan Bar Chart" class="inline-flex flex-wrap items-center gap-1.5 p-1 rounded-xl bg-slate-100 border border-slate-200">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeMode === 'all'"
                        @click="activeMode = 'all'"
                        :class="activeMode === 'all'
                            ? 'bg-white text-slate-900 shadow-2xs border-slate-200/80'
                            : 'text-slate-600 hover:text-slate-900 border-transparent'"
                        class="min-h-11 sm:min-h-9 px-3.5 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                    >
                        Semua Data
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeMode === 'incidents'"
                        @click="activeMode = 'incidents'"
                        :class="activeMode === 'incidents'
                            ? 'bg-white text-slate-900 shadow-2xs border-slate-200/80'
                            : 'text-slate-600 hover:text-slate-900 border-transparent'"
                        class="min-h-11 sm:min-h-9 px-3.5 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                    >
                        Berdasarkan Incidents
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeMode === 'rooms'"
                        @click="activeMode = 'rooms'"
                        :class="activeMode === 'rooms'
                            ? 'bg-white text-slate-900 shadow-2xs border-slate-200/80'
                            : 'text-slate-600 hover:text-slate-900 border-transparent'"
                        class="min-h-11 sm:min-h-9 px-3.5 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                    >
                        Berdasarkan Ruangan
                    </button>
                </div>
            </div>

            <!-- Legend for Rooms mode -->
            <div x-show="activeMode === 'rooms'" x-cloak class="flex flex-wrap items-center gap-4 text-xs text-slate-700 bg-slate-50 px-4 py-2.5 rounded-lg border border-slate-200">
                <span class="font-bold text-slate-800">Keterangan Batang:</span>
                <span class="inline-flex items-center gap-1.5 font-medium">
                    <span class="w-3 h-3 rounded-xs bg-teal-600 inline-block"></span>
                    Total Insiden Ruangan
                </span>
                <span class="inline-flex items-center gap-1.5 font-medium">
                    <span class="w-3 h-3 rounded-xs bg-blue-500 inline-block"></span>
                    Jumlah Laporan Logbook
                </span>
            </div>

            <!-- Empty State when selected mode has no items -->
            <template x-if="currentItems.length === 0">
                <div class="p-10 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200">
                    <p class="text-sm font-bold text-slate-800">Belum Ada Data untuk Ditampilkan</p>
                    <p class="text-xs text-slate-600 mt-1">
                        Data grafik akan muncul setelah terdapat laporan logbook supervisi pada periode yang dipilih.
                    </p>
                </div>
            </template>

            <!-- Vertical Bar Chart Canvas -->
            <template x-if="currentItems.length > 0">
                <div class="overflow-x-auto pb-2">
                    <div class="min-w-[540px] pt-6">
                        <!-- Plot Area with Horizontal Grid Lines -->
                        <div class="relative h-64 border-b border-slate-300 px-4 flex items-end justify-around gap-4">
                            <!-- Background Reference Grid Lines -->
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none" aria-hidden="true">
                                <div class="border-b border-dashed border-slate-200 w-full"></div>
                                <div class="border-b border-dashed border-slate-200 w-full"></div>
                                <div class="border-b border-dashed border-slate-200 w-full"></div>
                                <div class="border-b border-dashed border-slate-200 w-full"></div>
                                <div class="w-full"></div>
                            </div>

                            <!-- Bars Loop -->
                            <template x-for="(item, idx) in currentItems" :key="activeMode + '-' + idx">
                                <div class="relative z-10 flex-1 max-w-36 h-full flex flex-col justify-end items-center group">
                                    <!-- Value Label Above Bar -->
                                    <div class="mb-2 text-center">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-900 text-white shadow-2xs"
                                            x-text="activeMode === 'rooms' ? (item.count + ' insiden') : item.count"
                                        ></span>
                                    </div>

                                    <!-- Bar Track Container -->
                                    <div class="w-full h-48 flex items-end justify-center gap-1.5 px-2">
                                        <!-- Primary Bar -->
                                        <div
                                            class="w-full max-w-12 rounded-t-lg transition-all duration-300"
                                            :class="item.color"
                                            :style="'height: ' + Math.max(item.height_pct, item.count > 0 ? 8 : 2) + '%'"
                                            :title="item.label + ': ' + item.count"
                                        ></div>

                                        <!-- Secondary Bar (Only in Rooms Mode for Logbook Count) -->
                                        <template x-if="activeMode === 'rooms'">
                                            <div
                                                class="w-full max-w-10 rounded-t-lg transition-all duration-300 bg-blue-500"
                                                :style="'height: ' + Math.max(item.secondary_height_pct || 0, (item.reports_count || 0) > 0 ? 8 : 2) + '%'"
                                                :title="'Logbook ' + item.label + ': ' + item.reports_count"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- X-Axis Labels & Subtitles -->
                        <div class="px-4 pt-3 flex items-start justify-around gap-4">
                            <template x-for="(item, idx) in currentItems" :key="'label-' + activeMode + '-' + idx">
                                <div class="flex-1 max-w-36 text-center">
                                    <p class="text-xs font-bold text-slate-900 leading-snug break-words" x-text="item.label"></p>
                                    <p class="text-[11px] text-slate-600 mt-0.5 leading-tight" x-text="item.subtitle"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Chart Row 1: Category Distribution & Status Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Chart 1: Distribusi Kategori Insiden -->
            <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-5">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        1. Grafik Distribusi Kategori Masalah
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Perbandingan jumlah kejadian berdasarkan 4 kategori utama supervisi.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach($categoryStats as $stat)
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-800">{{ $stat['label'] }}</span>
                                <span class="font-mono font-bold text-slate-900">
                                    {{ $stat['count'] }} kejadian ({{ $stat['percentage'] }}%)
                                </span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $stat['color'] }} rounded-full transition-all duration-300" style="width: {{ max($stat['percentage'], $stat['count'] > 0 ? 4 : 0) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Chart 2: Distribusi Status Penanganan -->
            <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-5">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        2. Status Penanganan Insiden
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Proporsi penyelesaian tindak lanjut masalah ruangan.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach($statusStats as $stat)
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold {{ $stat['text'] }}">{{ $stat['label'] }}</span>
                                <span class="font-mono font-bold text-slate-900">{{ $stat['count'] }} ({{ $stat['percentage'] }}%)</span>
                            </div>
                            <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full {{ $stat['color'] }} rounded-full" style="width: {{ max($stat['percentage'], $stat['count'] > 0 ? 4 : 0) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Chart Row 2: Top Rooms & Daily Trend -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Chart 3: Frekuensi Kejadian per Ruangan -->
            <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        3. Frekuensi Insiden per Ruangan / Bangsal
                    </h2>
                </div>

                @if($roomStats->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-500">
                        Belum ada data ruangan pada periode ini.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-bold uppercase text-slate-500 border-b border-slate-200">
                                    <th class="px-5 py-3">Ruangan</th>
                                    <th class="px-4 py-3 text-center">Logbook</th>
                                    <th class="px-4 py-3 text-center">Total Insiden</th>
                                    <th class="px-4 py-3 text-center">Complain</th>
                                    <th class="px-4 py-3 text-center">Selesai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($roomStats as $roomStat)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-5 py-3 font-semibold text-slate-900">{{ $roomStat['room_name'] }}</td>
                                        <td class="px-4 py-3 text-center font-mono">{{ $roomStat['reports_count'] }}</td>
                                        <td class="px-4 py-3 text-center font-mono font-bold text-slate-900">{{ $roomStat['incidents_count'] }}</td>
                                        <td class="px-4 py-3 text-center font-mono text-rose-600 font-semibold">{{ $roomStat['complain_count'] }}</td>
                                        <td class="px-4 py-3 text-center font-mono text-emerald-600 font-semibold">{{ $roomStat['resolved_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Chart 4: Tren Kejadian Harian -->
            <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        4. Tren Laporan & Kejadian Harian
                    </h2>
                </div>

                @if($dailyTrend->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-500">
                        Belum ada data tren harian pada periode ini.
                    </div>
                @else
                    @php
                        $maxDailyIncidents = max(1, (int) $dailyTrend->max('incidents_count'));
                    @endphp
                    <div class="p-6 space-y-3">
                        @foreach($dailyTrend as $day)
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-800">{{ $day['formatted_date'] }}</span>
                                    <span class="font-mono text-slate-600">
                                        {{ $day['reports_count'] }} logbook &middot; <strong class="text-slate-900">{{ $day['incidents_count'] }} insiden</strong>
                                    </span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-teal-600 rounded-full"
                                        style="width: {{ round(($day['incidents_count'] / $maxDailyIncidents) * 100) }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>

