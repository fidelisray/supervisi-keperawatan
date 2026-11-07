<x-layouts.app>
    <x-slot name="title">Analisis & Grafik Insiden Supervisi</x-slot>

    <div 
        x-data="analyticsDashboard(@js($chartPayload), '{{ route('supervisi.analytics.drilldown') }}')"
        class="max-w-7xl mx-auto space-y-8 print:p-0 print:space-y-4"
    >
        <!-- Page Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200 print:border-b-2 print:border-slate-800">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1 print:hidden" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Supervisi Keperawatan</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Analisis &amp; Grafik Insiden</li>
                    </ol>
                </nav>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 print:text-xl">
                        Analisis &amp; Grafik Kejadian Ruangan
                    </h1>
                    @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('view_all_reports'))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200 print:border-slate-400">
                            Cakupan: Seluruh Rumah Sakit
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200 print:border-slate-400">
                            Cakupan: Logbook Saya &amp; Kolaborator
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 mt-0.5 print:text-xs">
                    Rekapitulasi distribusi masalah, status penyelesaian tindak lanjut, dan frekuensi kejadian per ruangan.
                </p>
                <p class="hidden print:block text-xs text-slate-500 mt-1 font-mono">
                    Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB &middot; RS St. Elisabeth Semarang
                </p>
            </div>

            <div class="flex items-center space-x-2 print:hidden">
                <button 
                    type="button"
                    @click="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 hover:text-slate-900 transition shadow-2xs cursor-pointer"
                    title="Cetak format ringkas untuk laporan rapat komite"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Laporan</span>
                </button>

                <a 
                    href="{{ route('supervisi.index') }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs"
                >
                    Lihat Daftar Logbook
                </a>
            </div>
        </div>

        <!-- Filter Bar with Quick Presets -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs space-y-4 print:hidden">
            <!-- Quick Date Presets -->
            <div class="flex flex-wrap items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1">Rentang Cepat:</span>
                <a 
                    href="{{ route('supervisi.analytics', array_merge(request()->except(['preset', 'date_from', 'date_to']), ['preset' => 'today'])) }}"
                    class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ request('preset') === 'today' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    Hari Ini
                </a>
                <a 
                    href="{{ route('supervisi.analytics', array_merge(request()->except(['preset', 'date_from', 'date_to']), ['preset' => 'last_7_days'])) }}"
                    class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ request('preset') === 'last_7_days' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    7 Hari Terakhir
                </a>
                <a 
                    href="{{ route('supervisi.analytics', array_merge(request()->except(['preset', 'date_from', 'date_to']), ['preset' => 'this_month'])) }}"
                    class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ request('preset') === 'this_month' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    Bulan Ini
                </a>
                <a 
                    href="{{ route('supervisi.analytics', array_merge(request()->except(['preset', 'date_from', 'date_to']), ['preset' => 'last_month'])) }}"
                    class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ request('preset') === 'last_month' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    Bulan Lalu
                </a>
                @if(request()->hasAny(['preset', 'room', 'category_id', 'status', 'date_from', 'date_to']))
                    <a 
                        href="{{ route('supervisi.analytics') }}" 
                        class="ml-auto inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-700 font-semibold"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset Semua Filter
                    </a>
                @endif
            </div>

            <!-- Detailed Multi-dimension Filters Form -->
            <form method="GET" action="{{ route('supervisi.analytics') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                <input type="hidden" name="preset" value="{{ request('preset') }}">

                <!-- Filter Ruangan -->
                <div class="lg:col-span-3">
                    <label for="room" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Ruangan / Bangsal</label>
                    <select id="room" name="room" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <option value="">Semua Ruangan</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->nama_ruangan }}" {{ $selectedRoom === $r->nama_ruangan ? 'selected' : '' }}>
                                {{ $r->nama_ruangan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kategori Insiden -->
                <div class="lg:col-span-3">
                    <label for="category_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Kategori Masalah</label>
                    <select id="category_id" name="category_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <option value="">Semua Kategori</option>
                        @foreach($filterCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) $selectedCategoryId === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->incident_category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Penanganan -->
                <div class="lg:col-span-2">
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Status Tindak Lanjut</label>
                    <select id="status" name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <option value="all">Semua Status</option>
                        <option value="resolved" {{ $selectedStatus === 'resolved' ? 'selected' : '' }}>Selesai Ditangani</option>
                        <option value="in_progress" {{ $selectedStatus === 'in_progress' ? 'selected' : '' }}>Sedang Ditangani</option>
                        <option value="escalated" {{ $selectedStatus === 'escalated' ? 'selected' : '' }}>Perlu Eskalasi</option>
                    </select>
                </div>

                <!-- Rentang Tanggal -->
                <div class="lg:col-span-2">
                    <label for="date_from" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Dari Tanggal</label>
                    <input 
                        type="date" 
                        id="date_from" 
                        name="date_from" 
                        value="{{ $dateFrom }}" 
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-2">
                    <label for="date_to" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Sampai Tanggal</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="date" 
                            id="date_to" 
                            name="date_to" 
                            value="{{ $dateTo }}" 
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                        >
                        <button 
                            type="submit" 
                            class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition cursor-pointer shadow-xs whitespace-nowrap"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Top Summary KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Laporan -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Logbook</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $totalReports }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Laporan dalam periode terpilih</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>

            <!-- Total Insiden -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Butir Insiden</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $totalIncidents }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Seluruh kategori kejadian</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <!-- Tingkat Penyelesaian -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Tingkat Penyelesaian</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1 font-mono">{{ $resolutionRate }}%</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $resolvedCount }} insiden selesai ditangani</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Perlu Tindak Lanjut -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Perlu Tindak Lanjut</p>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">{{ $pendingCount }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $statusStats['in_progress']['count'] ?? 0 }} proses &middot; {{ $statusStats['escalated']['count'] ?? 0 }} eskalasi</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Section 1: Distribusi Proporsi (Kategori & Status Masalah) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Donut Chart: Proporsi Kategori Masalah -->
            <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                            Distribusi Kategori Masalah
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Proporsi insiden berdasarkan kategori supervisi. Klik kategori untuk melihat rincian kejadian.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        Donut Visual
                    </span>
                </div>

                @if($totalIncidents === 0)
                    <div class="p-8 text-center text-xs text-slate-500 rounded-lg bg-slate-50 border border-dashed border-slate-200">
                        Belum ada data insiden pada periode ini.
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                        <div class="md:col-span-6 flex justify-center py-2">
                            <div class="relative w-52 h-52">
                                <canvas id="categoryDonutChart"></canvas>
                            </div>
                        </div>
                        <div class="md:col-span-6 space-y-2">
                            @foreach($categoryStats as $stat)
                                <button 
                                    type="button"
                                    @click="openDrilldown({ category_id: '{{ $stat['id'] }}', title: 'Kategori: {{ addslashes($stat['label']) }}' })"
                                    class="w-full p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 text-left transition flex items-center justify-between group cursor-pointer"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $stat['hex_color'] }}"></span>
                                        <span class="text-xs font-semibold text-slate-800 truncate group-hover:text-blue-700">{{ $stat['label'] }}</span>
                                    </div>
                                    <div class="text-right shrink-0 font-mono text-xs text-slate-600">
                                        <strong class="text-slate-900">{{ $stat['count'] }}</strong> ({{ $stat['percentage'] }}%)
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Doughnut Chart: Status Penanganan -->
            <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                            Status Tindak Lanjut Insiden
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Status penyelesaian masalah seluruh bangsal.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Progres
                    </span>
                </div>

                @if($totalIncidents === 0)
                    <div class="p-8 text-center text-xs text-slate-500 rounded-lg bg-slate-50 border border-dashed border-slate-200">
                        Belum ada status insiden untuk ditampilkan.
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 items-center">
                        <div class="flex justify-center py-2">
                            <div class="relative w-44 h-44">
                                <canvas id="statusDoughnutChart"></canvas>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($statusStats as $key => $stat)
                                <button
                                    type="button"
                                    @click="openDrilldown({ status: '{{ $key }}', title: 'Status: {{ addslashes($stat['label']) }}' })"
                                    class="p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-center transition cursor-pointer"
                                >
                                    <div class="w-2.5 h-2.5 mx-auto rounded-full mb-1" style="background-color: {{ $stat['hex'] }}"></div>
                                    <p class="text-[11px] font-bold text-slate-800 leading-tight">{{ $stat['label'] }}</p>
                                    <p class="text-xs font-mono font-bold text-slate-900 mt-1">{{ $stat['count'] }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono">{{ $stat['percentage'] }}%</p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Section 2: Tren Waktu Harian (Line / Area Chart) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                        Tren Kejadian &amp; Laporan Logbook Harian
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Fluktuasi jumlah laporan supervisi dan butir insiden per hari.
                    </p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-3 h-3 rounded-full bg-teal-600 inline-block"></span>
                        Butir Insiden
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
                        Laporan Supervisi
                    </span>
                </div>
            </div>

            @if($dailyTrend->isEmpty())
                <div class="p-10 text-center text-xs text-slate-500 rounded-lg bg-slate-50 border border-dashed border-slate-200">
                    Belum ada data tren harian pada rentang tanggal yang dipilih.
                </div>
            @else
                <div class="relative h-72 w-full pt-2">
                    <canvas id="trendLineChart"></canvas>
                </div>
            @endif
        </div>

        <!-- Section 3: Analisis Komparatif Ruangan (Bar Chart & Ranking Table) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                            Frekuensi Insiden &amp; Rekap per Ruangan / Bangsal
                        </h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                            Interaktif
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Perbandingan beban insiden, laporan tercatat, dan komplain pasien. Klik nama ruangan untuk melihat rincian butir kejadian.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-3 h-3 rounded-xs bg-teal-600 inline-block"></span>
                        Total Insiden
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-3 h-3 rounded-xs bg-blue-500 inline-block"></span>
                        Logbook
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-3 h-3 rounded-xs bg-rose-500 inline-block"></span>
                        Complain
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-3 h-3 rounded-xs bg-emerald-500 inline-block"></span>
                        Selesai
                    </span>
                </div>
            </div>

            @if($roomStats->isEmpty())
                <div class="p-10 text-center text-xs text-slate-500">
                    Belum ada data ruangan pada periode ini.
                </div>
            @else
                <!-- Grouped Bar Chart Ruangan -->
                <div class="p-6 border-b border-slate-200">
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">Grafik Komparasi 8 Ruangan Teratas</p>
                    <div class="relative h-64 w-full">
                        <canvas id="roomBarChart"></canvas>
                    </div>
                </div>

                <!-- Tabel Interaktif Ranking Ruangan -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] font-bold uppercase text-slate-600 border-b border-slate-200">
                                <th class="px-5 py-3">Nama Ruangan</th>
                                <th class="px-4 py-3 text-center">Logbook</th>
                                <th class="px-4 py-3 text-center">Total Insiden</th>
                                <th class="px-4 py-3 text-center">Complain</th>
                                <th class="px-4 py-3 text-center">Selesai</th>
                                <th class="px-4 py-3 text-center">Tindak Lanjut</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($roomStats as $roomStat)
                                <tr class="hover:bg-slate-50/80 transition group">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ $roomStat['incidents_count'] > 0 ? 'bg-teal-500' : 'bg-slate-300' }}"></span>
                                        <span>{{ $roomStat['room_name'] }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-mono">{{ $roomStat['reports_count'] }}</td>
                                    <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-900">{{ $roomStat['incidents_count'] }}</td>
                                    <td class="px-4 py-3.5 text-center font-mono text-rose-600 font-semibold">{{ $roomStat['complain_count'] }}</td>
                                    <td class="px-4 py-3.5 text-center font-mono text-emerald-600 font-semibold">{{ $roomStat['resolved_count'] }}</td>
                                    <td class="px-4 py-3.5 text-center font-mono text-amber-600 font-semibold">{{ $roomStat['pending_count'] }}</td>
                                    <td class="px-4 py-3.5 text-right">
                                        <button 
                                            type="button"
                                            @click="openDrilldown({ room: '{{ addslashes($roomStat['room_name']) }}', title: 'Ruangan: {{ addslashes($roomStat['room_name']) }}' })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 hover:bg-teal-50 hover:text-teal-700 border border-slate-200 hover:border-teal-300 transition cursor-pointer"
                                        >
                                            <span>Rincian</span>
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Slide-over Drawer untuk Drill-down Detail Insiden -->
        <div 
            x-show="drawerOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-hidden" 
            aria-labelledby="slide-over-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div class="absolute inset-0 overflow-hidden">
                <!-- Background backdrop -->
                <div 
                    x-show="drawerOpen"
                    x-transition:enter="ease-in-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in-out duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="drawerOpen = false"
                    class="absolute inset-0 bg-slate-950/60 backdrop-blur-2xs transition-opacity"
                ></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div 
                        x-show="drawerOpen"
                        x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-md bg-white shadow-2xl flex flex-col"
                    >
                        <!-- Drawer Header -->
                        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                            <div>
                                <h3 id="slide-over-title" class="text-sm font-bold text-slate-900" x-text="drawerTitle"></h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Ditemukan <strong class="text-slate-800 font-mono" x-text="drawerTotal"></strong> butir insiden terkait
                                </p>
                            </div>
                            <button 
                                type="button" 
                                @click="drawerOpen = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition focus:outline-none"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Drawer Content Body -->
                        <div class="flex-1 overflow-y-auto p-5 space-y-4">
                            <!-- Loading State -->
                            <template x-if="drawerLoading">
                                <div class="py-16 text-center space-y-3">
                                    <div class="w-8 h-8 border-3 border-teal-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                    <p class="text-xs font-semibold text-slate-600">Memuat data rincian kejadian...</p>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <template x-if="!drawerLoading && drawerIncidents.length === 0">
                                <div class="py-16 text-center text-xs text-slate-500 rounded-xl bg-slate-50 border border-dashed border-slate-200 p-6">
                                    Tidak ditemukan butir kejadian pada kriteria ini.
                                </div>
                            </template>

                            <!-- Incident Cards List -->
                            <template x-if="!drawerLoading && drawerIncidents.length > 0">
                                <div class="space-y-3.5">
                                    <template x-for="item in drawerIncidents" :key="item.id">
                                        <div class="p-4 rounded-xl border border-slate-200 bg-white hover:border-teal-300 transition shadow-2xs space-y-2.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <span 
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                                                    :class="item.category_badge"
                                                    x-text="item.category_name"
                                                ></span>
                                                <span 
                                                    class="text-[11px] font-mono font-bold px-2 py-0.5 rounded"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status === 'resolved',
                                                        'bg-amber-50 text-amber-700 border border-amber-200': item.status === 'in_progress',
                                                        'bg-rose-50 text-rose-700 border border-rose-200': item.status === 'escalated'
                                                    }"
                                                    x-text="item.status_label"
                                                ></span>
                                            </div>

                                            <div>
                                                <p class="text-xs font-bold text-slate-900 leading-snug" x-text="item.description"></p>
                                                <p class="text-[11px] text-slate-600 mt-1" x-show="item.action_taken">
                                                    <strong class="text-slate-700">Tindakan:</strong> <span x-text="item.action_taken"></span>
                                                </p>
                                            </div>

                                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                                <div class="space-y-0.5">
                                                    <p>
                                                        <span class="font-semibold text-slate-700" x-text="item.room_name"></span> &middot; 
                                                        <span x-text="item.report_date"></span> (<span x-text="item.shift"></span>)
                                                    </p>
                                                    <p class="text-[10px] text-slate-400">
                                                        Waktu: <span class="font-mono" x-text="item.incident_time"></span> &middot; 
                                                        Spv: <span x-text="item.supervisor_name"></span>
                                                    </p>
                                                </div>

                                                <a 
                                                    :href="item.report_url"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold transition"
                                                >
                                                    <span>Logbook</span>
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Alpine Dashboard Controller Script -->
    <script>
        function analyticsDashboard(chartData, drilldownUrl) {
            return {
                chartData: chartData,
                drilldownUrl: drilldownUrl,
                drawerOpen: false,
                drawerLoading: false,
                drawerTitle: '',
                drawerIncidents: [],
                drawerTotal: 0,

                init() {
                    this.$nextTick(() => {
                        this.initCategoryChart();
                        this.initStatusChart();
                        this.initTrendChart();
                        this.initRoomChart();
                    });
                },

                openDrilldown(params) {
                    this.drawerOpen = true;
                    this.drawerLoading = true;
                    this.drawerIncidents = [];
                    this.drawerTotal = 0;
                    this.drawerTitle = params.title || 'Rincian Insiden Supervisi';

                    const url = new URL(this.drilldownUrl, window.location.origin);
                    if (params.room) url.searchParams.set('room', params.room);
                    if (params.category_id) url.searchParams.set('category_id', params.category_id);
                    if (params.status) url.searchParams.set('status', params.status);
                    if (params.date) url.searchParams.set('date', params.date);

                    const curFrom = '{{ $dateFrom }}';
                    const curTo = '{{ $dateTo }}';
                    if (curFrom && !params.date) url.searchParams.set('date_from', curFrom);
                    if (curTo && !params.date) url.searchParams.set('date_to', curTo);

                    fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.drawerLoading = false;
                        if (data.success) {
                            this.drawerIncidents = data.incidents || [];
                            this.drawerTotal = data.total || 0;
                        }
                    })
                    .catch(() => {
                        this.drawerLoading = false;
                    });
                },

                initCategoryChart() {
                    const ctx = document.getElementById('categoryDonutChart');
                    if (!ctx || !window.Chart) return;
                    const catData = this.chartData.category;
                    if (!catData || !catData.labels || catData.labels.length === 0) return;

                    new window.Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: catData.labels,
                            datasets: [{
                                data: catData.data,
                                backgroundColor: catData.colors,
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const val = context.raw || 0;
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                            return ` ${context.label}: ${val} kejadian (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '68%'
                        }
                    });
                },

                initStatusChart() {
                    const ctx = document.getElementById('statusDoughnutChart');
                    if (!ctx || !window.Chart) return;
                    const statusData = this.chartData.status;
                    if (!statusData || !statusData.labels || statusData.labels.length === 0) return;

                    new window.Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: statusData.labels,
                            datasets: [{
                                data: statusData.data,
                                backgroundColor: statusData.colors,
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const val = context.raw || 0;
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                            return ` ${context.label}: ${val} (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '72%'
                        }
                    });
                },

                initTrendChart() {
                    const ctx = document.getElementById('trendLineChart');
                    if (!ctx || !window.Chart) return;
                    const trend = this.chartData.trend;
                    if (!trend || !trend.labels || trend.labels.length === 0) return;

                    new window.Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: trend.labels,
                            datasets: [
                                {
                                    label: 'Butir Insiden',
                                    data: trend.incidents,
                                    borderColor: '#0d9488',
                                    backgroundColor: 'rgba(13, 148, 136, 0.08)',
                                    tension: 0.35,
                                    fill: true,
                                    pointBackgroundColor: '#0d9488',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    borderWidth: 2.5
                                },
                                {
                                    label: 'Laporan Supervisi',
                                    data: trend.reports,
                                    borderColor: '#2563eb',
                                    backgroundColor: 'rgba(37, 99, 235, 0.04)',
                                    tension: 0.35,
                                    fill: true,
                                    pointBackgroundColor: '#2563eb',
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    borderWidth: 2
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        font: { family: 'Outfit, sans-serif', size: 11 }
                                    },
                                    grid: {
                                        color: '#f1f5f9'
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        font: { family: 'Outfit, sans-serif', size: 11 }
                                    }
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                },

                initRoomChart() {
                    const ctx = document.getElementById('roomBarChart');
                    if (!ctx || !window.Chart) return;
                    const rooms = this.chartData.rooms;
                    if (!rooms || !rooms.labels || rooms.labels.length === 0) return;

                    new window.Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: rooms.labels,
                            datasets: [
                                {
                                    label: 'Total Insiden',
                                    data: rooms.incidents,
                                    backgroundColor: '#0d9488',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Jumlah Logbook',
                                    data: rooms.reports,
                                    backgroundColor: '#3b82f6',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Komplain Pasien',
                                    data: rooms.complain,
                                    backgroundColor: '#f43f5e',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Selesai Ditangani',
                                    data: rooms.resolved,
                                    backgroundColor: '#10b981',
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        font: { family: 'Outfit, sans-serif', size: 11 }
                                    },
                                    grid: {
                                        color: '#f1f5f9'
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        font: { family: 'Outfit, sans-serif', size: 11 }
                                    }
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }
            };
        }
    </script>
</x-layouts.app>
