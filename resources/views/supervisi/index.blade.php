<x-layouts.app>
    <x-slot name="title">Riwayat Logbook Supervisi</x-slot>

    @php
        $roomsJson = $rooms
            ->map(
                fn($r) => [
                    'name' => $r->nama_ruangan,
                    'installation' => $r->instalasi ? 'Instalasi ' . $r->instalasi : '',
                ],
            )
            ->values();

        $nursesJson = $nurses
            ->filter(fn($n) => !empty($n->nama))
            ->map(
                fn($n) => [
                    'name' => $n->nama,
                    'nip' => $n->nip,
                ],
            )
            ->values();
    @endphp

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Page Title & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Supervisi Keperawatan</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Riwayat & Monitoring</li>
                    </ol>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                        Monitoring Supervisi Ruang Perawatan
                    </h1>
                    {{-- [GLOBAL VISIBILITY LOGIC - Badge cakupan ketika seluruh pengguna melihat semua logbook rumah sakit]
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                        Cakupan: Seluruh Rumah Sakit
                    </span>
                    --}}


                    {{-- @if (auth()->user()?->hasPermission('view_all_reports'))
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                            Cakupan: Seluruh Rumah Sakit
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                            Cakupan: Logbook Saya &amp; Kolaborator
                        </span>
                    @endif --}}

                </div>
                <p class="text-base text-slate-600 mt-1">
                    Daftar logbook supervisi, rekap insiden kejadian ruangan, dan tindak lanjut kolaborator.
                </p>
            </div>

            <div class="flex items-center space-x-3">
                @if (auth()->user()?->hasPermission('view_analytics'))
                    <a href="{{ route('supervisi.analytics') }}"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                        Grafik Insiden
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('create_report'))
                    <a href="{{ route('supervisi.create') }}"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-98 transition shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Input Logbook Baru
                    </a>
                @endif
            </div>
        </div>

        <!-- 4 Statistics Metrics Cards -->
        @php
            $isTodayPreset = ($activePreset ?? '') === 'today';
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-slate-400">
                            {{ $isTodayPreset ? 'Logbook Hari Ini' : 'Total Logbook' }}</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $totalReportsCount }}</h3>
                        <p class="text-sm text-slate-500 mt-1">Logbook Tersimpan</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-slate-400">
                            {{ $isTodayPreset ? 'Total Kejadian Hari Ini' : 'Total Kejadian' }}</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $totalIncidentsCount }}</h3>
                        <p class="text-sm text-slate-500 mt-1">Kejadian Terdata</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-slate-400">
                            {{ $isTodayPreset ? 'Komplain Pasien Hari Ini' : 'Komplain Pasien' }}
                        </p>
                        <h3 class="text-3xl font-extrabold text-rose-600 mt-1">{{ $totalComplaintsCount }}</h3>
                        <p class="text-sm text-slate-500 mt-1">Kejadian Komplain Pasien</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-slate-400">Terselesaikan</p>
                        <h3 class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $resolvedIncidentsCount }}</h3>
                        <p class="text-sm text-slate-500 mt-1">Tindak lanjut tuntas</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-2xs space-y-4">
            <!-- Quick Date Presets -->
            <div class="flex flex-wrap items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-sm font-bold uppercase tracking-wider text-slate-500 mr-1">Rentang Cepat:</span>
                <a href="{{ route('supervisi.index', array_merge(request()->except(['preset', 'date_from', 'date_to', 'page']), ['preset' => 'today'])) }}"
                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $activePreset === 'today' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Hari Ini
                </a>
                <a href="{{ route('supervisi.index', array_merge(request()->except(['preset', 'date_from', 'date_to', 'page']), ['preset' => 'last_7_days'])) }}"
                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $activePreset === 'last_7_days' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    7 Hari Terakhir
                </a>
                <a href="{{ route('supervisi.index', array_merge(request()->except(['preset', 'date_from', 'date_to', 'page']), ['preset' => 'this_month'])) }}"
                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $activePreset === 'this_month' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Bulan Ini
                </a>
                <a href="{{ route('supervisi.index', array_merge(request()->except(['preset', 'date_from', 'date_to', 'page']), ['preset' => 'last_month'])) }}"
                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $activePreset === 'last_month' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Bulan Lalu
                </a>
                <a href="{{ route('supervisi.index', array_merge(request()->except(['preset', 'date_from', 'date_to', 'page']), ['preset' => 'all'])) }}"
                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $activePreset === 'all' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Semua Data
                </a>
                @if (request()->hasAny(['preset', 'room', 'date_from', 'date_to', 'supervisor', 'collaborator']))
                    <a href="{{ route('supervisi.index') }}"
                        class="ml-auto inline-flex items-center gap-1 py-1 text-sm text-rose-600 hover:text-rose-700 font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset Semua Filter
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('supervisi.index') }}"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                <input type="hidden" name="preset" value="{{ $activePreset }}">

                <!-- Filter Ruangan (Searchable Combobox) -->
                <div class="sm:col-span-1 lg:col-span-3 relative" x-data="searchableRoomSelect({
                    rooms: {{ Js::from($roomsJson) }},
                    selected: @js(request('room', ''))
                })"
                    @click.outside="isOpen = false">
                    <label for="room_search"
                        class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">Ruangan</label>
                    <div class="relative">
                        <input type="text" id="room_search" role="combobox" autocomplete="off" x-model="search"
                            @focus="isOpen = true" @input="onInput()" placeholder="Semua Ruangan"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none shadow-2xs">
                        <input type="hidden" name="room" :value="selectedRoom">

                        <!-- Dropdown Result List -->
                        <div x-show="isOpen" x-cloak
                            class="absolute z-30 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-slate-200 shadow-lg py-1 divide-y divide-slate-100">
                            <button type="button" @click="selectRoom('', '')"
                                class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer text-slate-800"
                                :class="{ 'bg-teal-50 text-teal-900 font-semibold': selectedRoom === '' }">
                                <span class="font-semibold text-slate-600">Semua Ruangan</span>
                            </button>
                            <template x-for="room in filteredRooms" :key="room.name">
                                <button type="button" @click="selectRoom(room.name, room.name)"
                                    class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer text-slate-800"
                                    :class="{ 'bg-teal-50 text-teal-900 font-semibold': selectedRoom === room.name }">
                                    <span x-text="room.name" class="font-medium"></span>
                                    <span class="text-xs text-slate-400 font-normal ml-2"
                                        x-text="room.installation"></span>
                                </button>
                            </template>
                            <div x-show="filteredRooms.length === 0"
                                class="px-3.5 py-2.5 text-sm text-slate-400 italic text-center">
                                Ruangan tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label for="date_from"
                        class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">Dari
                        Tanggal</label>
                    <input type="date" id="date_from" name="date_from"
                        value="{{ $dateFrom ?? request('date_from') }}"
                        onchange="if(this.form.preset) this.form.preset.value=''"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label for="date_to"
                        class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">Sampai
                        Tanggal</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo ?? request('date_to') }}"
                        onchange="if(this.form.preset) this.form.preset.value=''"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>

                {{-- [GLOBAL VISIBILITY LOGIC - Filter supervisor ditampilkan untuk semua pengguna]
                <div class="sm:col-span-1 lg:col-span-2">
                    <label for="supervisor" class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">Supervisor</label>
                    <input type="text" id="supervisor" name="supervisor" list="filter_nurse_list" value="{{ request('supervisor') }}" placeholder="Nama supervisor..." class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
                --}}
                @if (auth()->user()?->hasPermission('view_all_reports'))
                    <!-- Filter Supervisor (Searchable Combobox) -->
                    <div class="sm:col-span-1 lg:col-span-2 relative" x-data="searchableNurseSelect({
                        nurses: {{ Js::from($nursesJson) }},
                        selected: @js(request('supervisor', '')),
                        allLabel: 'Semua Supervisor'
                    })"
                        @click.outside="isOpen = false">
                        <label for="supervisor_search"
                            class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">Supervisor</label>
                        <div class="relative">
                            <input type="text" id="supervisor_search" role="combobox" autocomplete="off"
                                x-model="search" @focus="isOpen = true" @input="onInput()"
                                placeholder="Nama supervisor..."
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none shadow-2xs">
                            <input type="hidden" name="supervisor" :value="selectedNurse">

                            <!-- Dropdown Result List -->
                            <div x-show="isOpen" x-cloak
                                class="absolute z-30 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-slate-200 shadow-lg py-1 divide-y divide-slate-100">
                                <button type="button" @click="selectNurse('', '')"
                                    class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer text-slate-800"
                                    :class="{ 'bg-teal-50 text-teal-900 font-semibold': selectedNurse === '' }">
                                    <span class="font-semibold text-slate-600">Semua Supervisor</span>
                                </button>
                                <template x-for="nurse in filteredNurses" :key="nurse.nip + nurse.name">
                                    <button type="button" @click="selectNurse(nurse.name, nurse.name)"
                                        class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer text-slate-800"
                                        :class="{ 'bg-teal-50 text-teal-900 font-semibold': selectedNurse === nurse.name }">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="font-medium" x-text="nurse.name"></span>
                                            <span class="text-xs font-mono text-slate-500"
                                                x-text="'(NIP: ' + nurse.nip + ')'"></span>
                                        </div>
                                    </button>
                                </template>
                                <div x-show="filteredNurses.length === 0"
                                    class="px-3.5 py-2.5 text-sm text-slate-400 italic text-center">
                                    Supervisor tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Filter Kolaborator (Searchable Combobox) -->
                <div class="sm:col-span-1 {{ auth()->user()?->hasPermission('view_all_reports') ? 'lg:col-span-2' : 'lg:col-span-3' }} relative"
                    x-data="searchableNurseSelect({
                        nurses: {{ Js::from($nursesJson) }},
                        selected: @js(request('collaborator', '')),
                        allLabel: 'Semua Kolaborator'
                    })" @click.outside="isOpen = false">
                    <label for="collaborator_search"
                        class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">Kolaborator</label>
                    <div class="relative">
                        <input type="text" id="collaborator_search" role="combobox" autocomplete="off"
                            x-model="search" @focus="isOpen = true" @input="onInput()"
                            placeholder="Nama kolaborator..."
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:outline-none shadow-2xs">
                        <input type="hidden" name="collaborator" :value="selectedNurse">

                        <!-- Dropdown Result List -->
                        <div x-show="isOpen" x-cloak
                            class="absolute z-30 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-slate-200 shadow-lg py-1 divide-y divide-slate-100">
                            <button type="button" @click="selectNurse('', '')"
                                class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer text-slate-800"
                                :class="{ 'bg-teal-50 text-teal-900 font-semibold': selectedNurse === '' }">
                                <span class="font-semibold text-slate-600">Semua Kolaborator</span>
                            </button>
                            <template x-for="nurse in filteredNurses" :key="nurse.nip + nurse.name">
                                <button type="button" @click="selectNurse(nurse.name, nurse.name)"
                                    class="w-full text-left px-3.5 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer text-slate-800"
                                    :class="{ 'bg-teal-50 text-teal-900 font-semibold': selectedNurse === nurse.name }">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="font-medium" x-text="nurse.name"></span>
                                        <span class="text-xs font-mono text-slate-500"
                                            x-text="'(NIP: ' + nurse.nip + ')'"></span>
                                    </div>
                                </button>
                            </template>
                            <div x-show="filteredNurses.length === 0"
                                class="px-3.5 py-2.5 text-sm text-slate-400 italic text-center">
                                Kolaborator tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="{{ auth()->user()?->hasPermission('view_all_reports') ? 'sm:col-span-2 lg:col-span-1' : 'sm:col-span-2 lg:col-span-2' }}">
                    <button type="submit"
                        class="w-full h-[42px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold transition cursor-pointer shadow-xs whitespace-nowrap">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Reports Table Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                    Daftar Laporan Supervisi Terkini
                </h2>
            </div>

            @if ($reports->isEmpty())
                <div class="text-center py-16 px-4">
                    <div
                        class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Belum Ada Laporan Supervisi</h3>
                    <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-4 leading-relaxed">
                        Data logbook supervisi yang disimpan akan muncul di halaman monitoring ini.
                    </p>
                    <a href="{{ route('supervisi.create') }}"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                        + Input Laporan Pertama
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-base">
                        <thead>
                            <tr
                                class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                <th class="px-6 py-3.5 text-center">No</th>
                                <th class="px-6 py-3.5 text-center">Tanggal</th>
                                <th class="px-6 py-3.5 text-center">Ruangan</th>
                                <th class="px-6 py-3.5 text-center">Pasien</th>
                                <th class="px-6 py-3.5 text-center">Perawat</th>
                                <th class="px-6 py-3.5 text-center">Kejadian Terlapor</th>
                                <th class="px-6 py-3.5 text-center">Supervisor & Kolaborator</th>
                                <th class="px-6 py-3.5 text-center">Status Tindak Lanjut</th>
                                <th class="px-6 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($reports as $report)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-4 text-center font-semibold text-slate-600 whitespace-nowrap">
                                        {{ $reports->firstItem() + $loop->index }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-bold text-slate-900">
                                            {{ $report->report_date->format('d/m/Y') }}</p>
                                        <span class="text-xs text-slate-500 font-mono">
                                            #RPT-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 font-semibold text-slate-800">
                                        {{ $report->room_name }}
                                    </td>

                                    <td class="px-6 py-4 text-center whitespace-nowrap font-medium text-slate-700">
                                        <span class="inline-block px-3 py-1 bg-slate-100 rounded-md text-sm">
                                            {{ $report->patient_count }} Pasien
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center whitespace-nowrap font-medium text-slate-700">
                                        <span class="inline-block px-3 py-1 bg-slate-100 rounded-md text-sm">
                                            {{ $report->nurse_count }} Ners
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($report->incidents->isEmpty())
                                            <span
                                                class="inline-flex items-center text-sm text-emerald-700 font-medium">
                                                <svg class="w-4 h-4 mr-1 text-emerald-600" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Nihil Kejadian
                                            </span>
                                        @else
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded text-sm font-bold bg-slate-800 text-white">
                                                    {{ $report->incidents->count() }} Insiden
                                                </span>

                                                @php
                                                    $cats = $report->incidents->groupBy('category');
                                                @endphp

                                                @foreach ($cats as $catId => $catIncidents)
                                                    @php
                                                        $firstIncident = $catIncidents->first();
                                                    @endphp
                                                    <span
                                                        class="px-2 py-0.5 rounded text-xs font-bold {{ $firstIncident->categoryBadgeClasses() }}">
                                                        {{ $catIncidents->count() }}
                                                        {{ $firstIncident->categoryLabel() }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <p class="font-semibold text-slate-900">Spv: {{ $report->supervisor_name }}
                                        </p>
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @foreach ($report->collaboratorList() as $collabName)
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded bg-teal-50 text-teal-800 border border-teal-200 text-xs font-medium">
                                                    {{ $collabName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @php
                                            $totalReportIncidents = $report->incidents->count();
                                            $resolvedReportIncidents = $report->incidents
                                                ->where('status', 'resolved')
                                                ->count();
                                            $resolvedPercentage =
                                                $totalReportIncidents > 0
                                                    ? ($resolvedReportIncidents / $totalReportIncidents) * 100
                                                    : 0;
                                        @endphp

                                        @if ($totalReportIncidents === 0)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-md text-sm font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Tidak Ada Insiden
                                            </span>
                                        @elseif ($resolvedReportIncidents === $totalReportIncidents)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Selesai
                                            </span>
                                        @elseif ($resolvedPercentage >= 50)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                {{ $resolvedReportIncidents }} dari {{ $totalReportIncidents }}
                                                terselesaikan
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                {{ $resolvedReportIncidents }} dari {{ $totalReportIncidents }}
                                                terselesaikan
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        {{-- [GLOBAL VISIBILITY LOGIC]: @if (auth()->user()?->hasPermission('edit_report') && $report->canBeModifiedBy(auth()->user())) --}}
                                        @if (auth()->user()?->hasPermission('edit_report'))
                                            <a href="{{ route('supervisi.edit', $report) }}"
                                                class="inline-flex items-center px-3.5 py-2 rounded-lg text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                                Edit
                                            </a>
                                        @endif
                                        <a href="{{ route('supervisi.show', $report) }}"
                                            class="inline-flex items-center px-3.5 py-2 rounded-lg text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                            Detail &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200">
                    {{ $reports->links(data: ['itemLabel' => 'laporan']) }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function searchableRoomSelect(config) {
            return {
                isOpen: false,
                rooms: config.rooms || [],
                selectedRoom: config.selected || '',
                search: config.selected || '',
                onInput() {
                    this.isOpen = true;
                    this.selectedRoom = this.search.trim();
                },
                get filteredRooms() {
                    if (!this.search || this.search === 'Semua Ruangan') {
                        return this.rooms;
                    }
                    const q = this.search.toLowerCase().trim();
                    return this.rooms.filter(r =>
                        r.name.toLowerCase().includes(q) ||
                        (r.installation && r.installation.toLowerCase().includes(q))
                    );
                },
                selectRoom(value, displayText) {
                    this.selectedRoom = value;
                    this.search = displayText;
                    this.isOpen = false;
                }
            };
        }

        function searchableNurseSelect(config) {
            return {
                isOpen: false,
                nurses: config.nurses || [],
                selectedNurse: config.selected || '',
                search: config.selected || '',
                onInput() {
                    this.isOpen = true;
                    this.selectedNurse = this.search.trim();
                },
                get filteredNurses() {
                    if (!this.search || this.search === config.allLabel) {
                        return this.nurses;
                    }
                    const q = this.search.toLowerCase().trim();
                    return this.nurses.filter(n =>
                        n.name.toLowerCase().includes(q) ||
                        (n.nip && String(n.nip).toLowerCase().includes(q))
                    );
                },
                selectNurse(value, displayText) {
                    this.selectedNurse = value;
                    this.search = displayText;
                    this.isOpen = false;
                }
            };
        }
    </script>
</x-layouts.app>
