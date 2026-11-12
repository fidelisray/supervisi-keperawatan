<x-layouts.app>
    <x-slot name="title">Input Logbook Supervisi</x-slot>

    <div x-data="supervisionLogbook()" x-init="init()" class="max-w-6xl mx-auto space-y-8">
        <!-- Page Title & Context Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Supervisi Keperawatan</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Formulir Logbook Supervisi</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Logbook Supervisi Ruang Perawatan
                </h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Catat kondisi operasional bangsal dan laporkan kejadian/insiden selama dinas supervisi berlangsung.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('supervisi.index') }}"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Lihat Riwayat
                </a>
            </div>
        </div>

        <!-- Form Element -->
        <form id="supervisionForm" action="{{ route('supervisi.store') }}" method="POST" class="space-y-8"
            @submit.prevent="submitForm($event)">
            @csrf

            <!-- Section 1: Konteks Ruangan Operasional -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs relative z-20">
                <div
                    class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex items-center justify-between rounded-t-xl">
                    <div class="flex items-center space-x-2.5">
                        <div
                            class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">
                            1
                        </div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                            Data Operasional Ruangan
                        </h2>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- Tanggal Supervisi -->
                    <div class="md:col-span-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="report_date"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Tanggal Supervisi
                            </label>
                            <span class="text-rose-600 font-bold text-sm leading-none" title="Wajib diisi"
                                aria-label="Wajib diisi">*</span>
                        </div>
                        <input type="date" id="report_date" name="report_date"
                            value="{{ old('report_date', $today) }}" min="{{ $minReportDate }}"
                            max="{{ $today }}" required
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs">
                        @error('report_date')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Pilihan Shift (Disembunyikan sesuai konfigurasi saat ini) -->
                    <input type="hidden" name="shift" value="{{ old('shift', $defaultShift) }}">

                    <!-- Ruangan Instalasi Perawatan (Searchable Combobox) -->
                    <div class="md:col-span-4 relative z-30" @click.outside="closeRoomDropdown()">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="room_search_input"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Ruangan / Instalasi Perawatan
                            </label>
                            <span class="text-rose-600 font-bold text-sm leading-none" title="Wajib diisi"
                                aria-label="Wajib diisi">*</span>
                        </div>
                        <div class="relative">
                            <input type="text" id="room_search_input" autocomplete="off" x-model="roomSearch"
                                @focus="isRoomOpen = true; roomHighlightIndex = 0"
                                @input="isRoomOpen = true; roomHighlightIndex = 0"
                                @keydown.down.prevent="highlightNextRoom()" @keydown.up.prevent="highlightPrevRoom()"
                                @keydown.enter.prevent="selectHighlightedRoom()" @keydown.escape="closeRoomDropdown()"
                                placeholder="Ketik untuk cari ruangan / instalasi..."
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs">
                            <input type="hidden" name="room_name" :value="selectedRoom">

                            <!-- Dropdown Result List -->
                            <div x-show="isRoomOpen" x-cloak
                                class="absolute z-30 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-slate-200 shadow-lg py-1 divide-y divide-slate-100">
                                <template x-for="(room, rIdx) in filteredRooms" :key="room.name">
                                    <button type="button" @click="selectRoom(room.name)"
                                        @mouseenter="roomHighlightIndex = rIdx"
                                        :class="{
                                            'bg-blue-50 text-blue-900 font-semibold': roomHighlightIndex ===
                                                rIdx,
                                            'text-slate-800': roomHighlightIndex !== rIdx
                                        }"
                                        class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between hover:bg-blue-50 transition cursor-pointer">
                                        <span x-text="room.name"></span>
                                        <span class="text-[11px] text-slate-400 font-normal ml-2"
                                            x-text="room.installation"></span>
                                    </button>
                                </template>
                                <div x-show="filteredRooms.length === 0"
                                    class="px-3.5 py-2.5 text-xs text-slate-400 italic text-center">
                                    Ruangan tidak ditemukan
                                </div>
                            </div>
                        </div>
                        <p x-show="roomError" x-cloak class="text-xs text-rose-600 mt-1 font-medium" x-text="roomError">
                        </p>
                        @error('room_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jumlah Pasien Hari Ini -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="patient_count"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Jumlah Pasien
                            </label>
                            <span class="text-rose-600 font-bold text-sm leading-none" title="Wajib diisi"
                                aria-label="Wajib diisi">*</span>
                        </div>
                        <div class="relative">
                            <input type="number" id="patient_count" name="patient_count" min="0"
                                value="{{ old('patient_count', 0) }}" required
                                class="w-full pl-3.5 pr-16 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs font-semibold">
                            <span
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-medium">
                                Orang
                            </span>
                        </div>
                        @error('patient_count')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jumlah Perawat Bertugas -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="nurse_count"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Perawat Bertugas
                            </label>
                            <span class="text-rose-600 font-bold text-sm leading-none" title="Wajib diisi"
                                aria-label="Wajib diisi">*</span>
                        </div>
                        <div class="relative">
                            <input type="number" id="nurse_count" name="nurse_count" min="0"
                                value="{{ old('nurse_count', 2) }}" required
                                class="w-full pl-3.5 pr-16 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs font-semibold">
                            <span
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-medium">
                                Orang
                            </span>
                        </div>
                        @error('nurse_count')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supervisor (Penginput) -->
                    <div class="md:col-span-5">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="supervisor_name"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Supervisor / Pengawas Keperawatan
                            </label>
                            @auth
                                <span class="text-[11px] font-mono font-medium text-slate-500">
                                    NIP: {{ auth()->user()->nip }}
                                </span>
                            @endauth
                        </div>
                        <div class="relative">
                            <input type="text" id="supervisor_name" name="supervisor_name"
                                value="{{ old('supervisor_name', $defaultSupervisor) }}" readonly required
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-700 text-sm font-medium cursor-not-allowed shadow-2xs">
                        </div>
                        @error('supervisor_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Multi-Kolaborator Supervisi (Searchable Combobox) -->
                    <div class="md:col-span-7 relative z-20" @click.outside="isCollaboratorOpen = false">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="collaborator_search_input"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Kolaborator Supervisi (Dapat Lebih dari Satu)
                            </label>
                            <span class="text-[11px] text-slate-500 font-medium">
                                <span class="font-bold text-teal-700" x-text="collaborators.length">0</span> dipilih
                            </span>
                        </div>

                        <div class="relative">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" id="collaborator_search_input"
                                        x-ref="collaboratorSearchInput" autocomplete="off"
                                        x-model="collaboratorSearch"
                                        @focus="isCollaboratorOpen = true; collaboratorHighlightIndex = 0"
                                        @input="isCollaboratorOpen = true; collaboratorHighlightIndex = 0"
                                        @keydown.down.prevent="highlightNextCollaborator()"
                                        @keydown.up.prevent="highlightPrevCollaborator()"
                                        @keydown.enter.prevent="selectHighlightedCollaborator()"
                                        @keydown.escape="isCollaboratorOpen = false"
                                        placeholder="Ketik NIP atau nama rekan perawat..."
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs">
                                </div>
                                {{-- <button
                                    type="button"
                                    @click="selectHighlightedCollaborator()"
                                    class="inline-flex items-center px-4 py-2.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition shadow-2xs shrink-0 cursor-pointer"
                                >
                                    + Tambah Kolaborator
                                </button> --}}
                            </div>

                            <!-- Collaborators Dropdown Result List -->
                            <div x-show="isCollaboratorOpen" x-cloak
                                class="absolute z-30 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-slate-200 shadow-lg py-1 divide-y divide-slate-100">
                                <template x-for="(nurse, nIdx) in filteredNurses" :key="nurse.nip + nurse.name">
                                    <button type="button" @click="addCollaboratorDirect(nurse.name)"
                                        @mouseenter="collaboratorHighlightIndex = nIdx"
                                        :disabled="collaborators.includes(nurse.name)"
                                        :class="{
                                            'bg-teal-50 text-teal-900 font-semibold': collaboratorHighlightIndex ===
                                                nIdx && !collaborators.includes(nurse.name),
                                            'opacity-50 cursor-not-allowed bg-slate-50 text-slate-400': collaborators
                                                .includes(nurse.name),
                                            'text-slate-800': collaboratorHighlightIndex !== nIdx && !collaborators
                                                .includes(nurse.name)
                                        }"
                                        class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between transition cursor-pointer">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="font-medium" x-text="nurse.name"></span>
                                            <span class="text-[11px] font-mono text-slate-500"
                                                x-text="'(NIP: ' + nurse.nip + ')'"></span>
                                        </div>
                                        <span x-show="collaborators.includes(nurse.name)"
                                            class="text-[10px] uppercase font-bold text-teal-700 bg-teal-100 px-2 py-0.5 rounded">Sudah
                                            Dipilih</span>
                                    </button>
                                </template>
                                <div x-show="filteredNurses.length === 0"
                                    class="px-3.5 py-2.5 text-xs text-slate-400 italic text-center">
                                    Perawat tidak ditemukan dengan kata kunci tersebut
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Form Inputs for Submitting Collaborators -->
                        <input type="hidden" name="nurse_in_charge" :value="JSON.stringify(collaborators)">
                        <template x-for="(collab, cIndex) in collaborators" :key="cIndex">
                            <input type="hidden" :name="'collaborators[' + cIndex + ']'" :value="collab">
                        </template>

                        <!-- Selected Collaborator Chips -->
                        <div class="mt-2.5 flex flex-wrap gap-2" x-show="collaborators.length > 0">
                            <template x-for="(collab, cIndex) in collaborators" :key="cIndex">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-900 border border-teal-200">
                                    <span x-text="collab"></span>
                                    <button type="button" @click.stop="removeCollaborator(cIndex)"
                                        class="text-teal-600 hover:text-rose-600 focus:outline-none cursor-pointer"
                                        :aria-label="'Hapus kolaborator ' + collab">
                                        &times;
                                    </button>
                                </span>
                            </template>
                        </div>

                        <p x-show="collaboratorError" x-cloak class="text-xs text-rose-600 mt-1.5 font-medium"
                            x-text="collaboratorError"></p>
                        @error('collaborators')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                        @error('nurse_in_charge')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Log Kejadian di Ruangan Hari Ini (The Core Interactive Module) -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs relative z-10 overflow-hidden">
                <div
                    class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center space-x-2.5">
                        <div
                            class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                            2
                        </div>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                                Kejadian di Ruangan Selama Supervisi
                            </h2>
                            <p class="text-xs text-slate-500">
                                Catat insiden atau permasalahan yang ditemukan selama dinas supervisi.
                            </p>
                        </div>
                    </div>

                    <button type="button" @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-98 transition shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Kejadian
                    </button>
                </div>

                <!-- Incident Category Counters & Filter Chips -->
                <div
                    class="px-6 py-3 bg-slate-100/50 border-b border-slate-200 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-500 font-semibold mr-1">Ringkasan Masalah:</span>

                    @foreach ($incidentCategories as $cat)
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full {{ $cat->badgeClasses() }} font-medium">
                            {{ $cat->incident_category }}: <strong class="ml-1"
                                x-text="countByCategory({{ $cat->id }})">0</strong>
                        </span>
                    @endforeach

                    <span class="ml-auto text-slate-500 font-medium">
                        Total: <strong class="text-slate-900" x-text="incidents.length">0</strong> Butir Kejadian
                    </span>
                </div>

                <!-- Incidents List Container -->
                <div class="p-6">
                    <!-- Empty State -->
                    <template x-if="incidents.length === 0">
                        <div
                            class="text-center py-12 px-4 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                            <div
                                class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 mx-auto flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">Belum Ada Kejadian yang Dicatat</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-4 leading-relaxed">
                                Jika terjadi keluhan pasien, kendala alat medis, atau catatan klinis penting selama
                                dinas ini, klik tombol di bawah untuk mencatat.
                            </p>
                            <button type="button" @click="openModal()"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Kejadian Baru
                            </button>
                        </div>
                    </template>

                    <!-- Populated Incident Cards -->
                    <div class="space-y-3.5" x-show="incidents.length > 0">
                        <template x-for="(item, index) in incidents" :key="item.id || index">
                            <div
                                class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-white shadow-2xs transition flex flex-col md:flex-row md:items-start justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <!-- Badges & Timing Header -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Category Badge -->
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold"
                                            :class="getCategoryStyle(item.category)"
                                            x-text="getCategoryLabel(item.category)"></span>

                                        <!-- Time Badge -->
                                        <template x-if="item.incident_time">
                                            <span
                                                class="inline-flex items-center text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium">
                                                <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Pukul <span class="ml-1" x-text="item.incident_time"></span> WIB
                                            </span>
                                        </template>

                                        <!-- Status Badge -->
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold"
                                            :class="getStatusStyle(item.status)"
                                            x-text="getStatusLabel(item.status)"></span>
                                    </div>

                                    <!-- Uraian Kejadian / Description -->
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi
                                            Kejadian</h4>
                                        <p class="text-sm text-slate-800 whitespace-pre-line mt-0.5 font-medium leading-relaxed"
                                            x-text="item.description"></p>
                                    </div>

                                    <!-- Tindak Lanjut / Solusi (If Any) -->
                                    <template x-if="item.action_taken && item.action_taken.trim() !== ''">
                                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200/80 mt-2">
                                            <h5
                                                class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Tindak Lanjut / Solusi yang Diberikan:
                                            </h5>
                                            <p class="text-xs text-slate-700 whitespace-pre-line mt-1"
                                                x-text="item.action_taken"></p>
                                        </div>
                                    </template>
                                </div>

                                <!-- Action Buttons (Edit / Remove) -->
                                <div
                                    class="flex items-center space-x-2 md:self-start pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                    <button type="button" @click="editIncident(index)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                                        title="Ubah butir kejadian">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <button type="button" @click="removeIncident(index)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus butir kejadian">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Hidden Inputs for Standard Form Submission -->
                                <input type="hidden" :name="'incidents[' + index + '][category]'"
                                    :value="item.category">
                                <input type="hidden" :name="'incidents[' + index + '][incident_time]'"
                                    :value="item.incident_time">
                                <input type="hidden" :name="'incidents[' + index + '][description]'"
                                    :value="item.description">
                                <input type="hidden" :name="'incidents[' + index + '][action_taken]'"
                                    :value="item.action_taken">
                                <input type="hidden" :name="'incidents[' + index + '][status]'"
                                    :value="item.status">
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Section 3: Catatan Umum / Handover -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex items-center space-x-2.5">
                    <div
                        class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-xs">
                        3
                    </div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Catatan Khusus Supervisi &amp; Handover
                    </h2>
                </div>
                <div class="p-6">
                    <textarea name="general_notes" rows="3"
                        placeholder="Tuliskan catatan evaluasi supervisor atau pesan penting (opsional)..."
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs">{{ old('general_notes') }}</textarea>
                </div>
            </div>

            <!-- Bottom Sticky Action Bar -->
            <div
                class="sticky bottom-4 z-20 bg-white p-4 rounded-xl border border-slate-300 shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3 text-xs text-slate-600">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div>
                        <span class="font-bold text-slate-800" x-text="incidents.length">0</span> kejadian tercatat
                        <span class="text-slate-400 mx-1">•</span>
                        Siap disimpan.
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('supervisi.index') }}"
                        class="px-4 py-2.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-98 transition shadow-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Simpan Laporan Supervisi
                    </button>
                </div>
            </div>
        </form>

        <!-- Incident Modal Form (Add & Edit - Clean M3 Stacking Context) -->
        <div x-show="showIncidentModal" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <!-- Backdrop (Clean Dark Scrim - Zero blur, 100% crisp) -->
            <div x-show="showIncidentModal" @click="handleBackdropClick()" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/50 transition-opacity"
                aria-hidden="true"></div>

            <!-- Modal Positioning Container -->
            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <!-- Modal Panel (Sharp, Crisp, Independent Stacking) -->
                    <div x-show="showIncidentModal" @click.stop x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        @keydown.escape.window="handleEscapeKey()"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200">
                        <div class="bg-blue-900 px-6 py-4 flex items-center justify-between text-white">
                            <div class="flex items-center space-x-2.5">
                                <div
                                    class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center font-bold text-amber-300 text-sm">
                                    +
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold tracking-tight" id="modal-title"
                                        x-text="editingIndex !== null ? 'Ubah Butir Kejadian' : 'Tambah Kejadian Ruangan'">
                                        Tambah Kejadian Ruangan
                                    </h3>
                                    <p class="text-xs text-blue-200">Isi detail insiden dan penanganan yang dilakukan.
                                    </p>
                                </div>
                            </div>

                            <button type="button" @click="handleBackdropClick()"
                                class="p-1.5 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Category Selection -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Kategori Masalah / Poin Kejadian
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach ($incidentCategories as $cat)
                                        <label class="cursor-pointer" title="{{ $cat->description }}">
                                            <input type="radio" x-model.number="modalData.category"
                                                value="{{ $cat->id }}" class="peer sr-only">
                                            <div
                                                class="p-2.5 rounded-lg border border-slate-200 text-center transition {{ $cat->radioPeerClasses() }} font-semibold text-xs hover:bg-slate-50">
                                                {{ $cat->incident_category }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5"
                                    x-show="getCategoryDescription(modalData.category)"
                                    x-text="getCategoryDescription(modalData.category)"></p>
                            </div>

                            <!-- Jam Kejadian & Status -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                        Perkiraan Jam Kejadian
                                    </label>
                                    <input type="time" x-model="modalData.incident_time"
                                        class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                        Status Penanganan
                                    </label>
                                    <select x-model="modalData.status"
                                        class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                                        <option value="in_progress">Sedang Ditangani</option>
                                        <option value="resolved">Selesai Ditangani</option>
                                        {{-- <option value="escalated">Perlu Eskalasi Supervisor</option> --}}
                                    </select>
                                </div>
                            </div>

                            <!-- Deskripsi Kejadian -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    Uraian Masalah / Kejadian <span class="text-rose-600">*</span>
                                </label>
                                <textarea x-ref="descriptionInput" x-model="modalData.description" rows="3"
                                    placeholder="Jelaskan secara ringkas dan objektif apa yang terjadi di ruangan..."
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"></textarea>
                                <p x-show="modalError" class="text-xs text-rose-600 mt-1" x-text="modalError"></p>
                            </div>

                            <!-- Tindak Lanjut / Solusi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    Tindak Lanjut / Solusi yang Dilakukan
                                </label>
                                <textarea x-model="modalData.action_taken" rows="2"
                                    placeholder="Apa tindakan cepat yang sudah dilakukan perawat, atau instruksi tindak lanjutnya..."
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"></textarea>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end space-x-2.5">
                            <button type="button" @click="handleBackdropClick()"
                                class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
                                Batal
                            </button>
                            <button type="button" @click="saveIncidentFromModal()"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs">
                                <span
                                    x-text="editingIndex !== null ? 'Simpan Perubahan' : 'Tambahkan ke Logbook'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unsaved Changes Navigation Blocking Modal -->
        <div x-show="showLeaveModal" x-cloak @keydown.escape.window="cancelLeaveNavigation()" class="relative z-50"
            aria-labelledby="leave-modal-title" role="dialog" aria-modal="true">
            <div x-show="showLeaveModal" @click="cancelLeaveNavigation()" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/50 transition-opacity"
                aria-hidden="true"></div>

            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showLeaveModal" @click.stop x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
                        <div class="p-6">
                            <div class="flex items-start space-x-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 id="leave-modal-title" class="text-sm font-bold text-slate-900">
                                        Perubahan Belum Disimpan
                                    </h3>
                                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                                        Anda memiliki perubahan data logbook supervisi yang belum disimpan. Jika
                                        meninggalkan halaman ini sekarang, data yang sedang Anda kerjakan akan hilang.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                            <button type="button" @click="confirmLeaveNavigation()"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 transition cursor-pointer focus:ring-2 focus:ring-rose-500 focus:outline-none">
                                Tinggalkan Tanpa Menyimpan
                            </button>
                            <button type="button" @click="cancelLeaveNavigation()"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs cursor-pointer focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                Lanjutkan Pengisian
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js Component Script -->
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
            ->filter(fn($n) => !empty($n->description))
            ->map(
                fn($n) => [
                    'name' => $n->description,
                    'nip' => $n->nip,
                ],
            )
            ->values();

        $categoryMeta = $incidentCategories->mapWithKeys(
            fn($cat) => [
                $cat->id => [
                    'label' => $cat->incident_category,
                    'description' => $cat->description,
                    'style' => $cat->badgeClasses(),
                ],
            ],
        );
        $defaultCategoryId = $incidentCategories->first()?->id ?? 1;
    @endphp
    <script>
        function supervisionLogbook() {
            const categoryMeta = @json($categoryMeta);
            const defaultCategoryId = @json($defaultCategoryId);
            const allRooms = @json($roomsJson);
            const allNurses = @json($nursesJson);
            const initialRoom = @json(old('room_name', ''));

            return {
                rooms: allRooms,
                roomSearch: initialRoom,
                selectedRoom: initialRoom,
                isRoomOpen: false,
                roomHighlightIndex: 0,
                roomError: '',

                nurses: allNurses,
                collaboratorSearch: '',
                isCollaboratorOpen: false,
                collaboratorHighlightIndex: 0,
                collaborators: @json(old('collaborators', [])),
                collaboratorError: '',

                incidents: [],
                showIncidentModal: false,
                editingIndex: null,
                modalError: '',
                modalData: {
                    category: defaultCategoryId,
                    incident_time: '',
                    description: '',
                    action_taken: '',
                    status: 'in_progress'
                },

                initialStateSnapshot: '',
                hasServerValidationErrors: @json($errors->any()),
                isSubmitting: false,
                showLeaveModal: false,
                pendingNavigationUrl: null,
                pendingLogoutForm: null,

                get filteredRooms() {
                    const q = (this.roomSearch || '').trim().toLowerCase();
                    if (!q) return this.rooms;
                    return this.rooms.filter(r =>
                        r.name.toLowerCase().includes(q) ||
                        (r.installation && r.installation.toLowerCase().includes(q))
                    );
                },

                get filteredNurses() {
                    const q = (this.collaboratorSearch || '').trim().toLowerCase();
                    if (!q) return this.nurses;
                    return this.nurses.filter(n =>
                        (n.name && n.name.toLowerCase().includes(q)) ||
                        (n.nip && n.nip.toLowerCase().includes(q))
                    );
                },

                init() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    this.modalData.incident_time = `${hours}:${minutes}`;
                    this.setupNavigationGuard();
                },

                getFormStateSnapshot(useDefaults = false) {
                    const form = document.getElementById('supervisionForm');
                    const reportDateEl = form?.querySelector('[name="report_date"]');
                    const patientCountEl = form?.querySelector('[name="patient_count"]');
                    const nurseCountEl = form?.querySelector('[name="nurse_count"]');
                    const generalNotesEl = form?.querySelector('[name="general_notes"]');

                    const reportDate = (useDefaults ? reportDateEl?.defaultValue : reportDateEl?.value) || '';
                    const patientCount = String((useDefaults ? patientCountEl?.defaultValue : patientCountEl?.value) ?? '');
                    const nurseCount = String((useDefaults ? nurseCountEl?.defaultValue : nurseCountEl?.value) ?? '');
                    const generalNotes = ((useDefaults ? generalNotesEl?.defaultValue : generalNotesEl?.value) || '')
                        .trim();

                    return JSON.stringify({
                        reportDate,
                        selectedRoom: (this.selectedRoom || '').trim(),
                        roomSearch: (this.roomSearch || '').trim(),
                        patientCount,
                        nurseCount,
                        collaborators: this.collaborators,
                        collaboratorSearch: (this.collaboratorSearch || '').trim(),
                        incidents: this.incidents,
                        generalNotes,
                        modalDraft: this.showIncidentModal ? (this.modalData.description || '').trim() : ''
                    });
                },

                isFormDirty() {
                    if (this.isSubmitting) return false;
                    if (this.hasServerValidationErrors) return true;
                    if (!this.initialStateSnapshot) return false;
                    return this.getFormStateSnapshot() !== this.initialStateSnapshot;
                },

                setupNavigationGuard() {
                    this.initialStateSnapshot = this.getFormStateSnapshot(true);
                    this.$nextTick(() => {
                        if (!this.initialStateSnapshot) {
                            this.initialStateSnapshot = this.getFormStateSnapshot(true);
                        }
                    });

                    window.addEventListener('beforeunload', (e) => {
                        if (this.isFormDirty()) {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    });

                    document.addEventListener('click', (e) => {
                        if (this.isSubmitting) return;
                        const anchor = e.target.closest('a[href]');
                        if (!anchor) return;
                        const href = anchor.getAttribute('href');
                        if (!href || href.startsWith('#') || href.startsWith('javascript:') || anchor.target ===
                            '_blank' || anchor.hasAttribute('download')) {
                            return;
                        }
                        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) {
                            return;
                        }
                        const targetUrl = new URL(anchor.href, window.location.origin);
                        if (targetUrl.href === window.location.href) {
                            return;
                        }
                        if (this.isFormDirty()) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.pendingNavigationUrl = targetUrl.href;
                            this.pendingLogoutForm = null;
                            this.showLeaveModal = true;
                        }
                    }, true);

                    document.addEventListener('submit', (e) => {
                        if (this.isSubmitting) return;
                        const form = e.target;
                        if (!form || form.id === 'supervisionForm') {
                            return;
                        }
                        if (this.isFormDirty()) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.pendingNavigationUrl = null;
                            this.pendingLogoutForm = form;
                            this.showLeaveModal = true;
                        }
                    }, true);
                },

                cancelLeaveNavigation() {
                    this.showLeaveModal = false;
                    this.pendingNavigationUrl = null;
                    this.pendingLogoutForm = null;
                },

                confirmLeaveNavigation() {
                    this.isSubmitting = true;
                    const targetUrl = this.pendingNavigationUrl;
                    const logoutForm = this.pendingLogoutForm;
                    this.showLeaveModal = false;
                    this.pendingNavigationUrl = null;
                    this.pendingLogoutForm = null;

                    if (logoutForm) {
                        logoutForm.submit();
                    } else if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                },

                selectRoom(name) {
                    this.selectedRoom = name;
                    this.roomSearch = name;
                    this.roomError = '';
                    this.isRoomOpen = false;
                },

                selectHighlightedRoom() {
                    if (this.filteredRooms.length > 0 && this.roomHighlightIndex >= 0 && this.roomHighlightIndex < this
                        .filteredRooms.length) {
                        this.selectRoom(this.filteredRooms[this.roomHighlightIndex].name);
                    } else if (this.filteredRooms.length > 0) {
                        this.selectRoom(this.filteredRooms[0].name);
                    }
                },

                highlightNextRoom() {
                    if (!this.isRoomOpen) {
                        this.isRoomOpen = true;
                        this.roomHighlightIndex = 0;
                        return;
                    }
                    if (this.roomHighlightIndex < this.filteredRooms.length - 1) {
                        this.roomHighlightIndex++;
                    }
                },

                highlightPrevRoom() {
                    if (this.roomHighlightIndex > 0) {
                        this.roomHighlightIndex--;
                    }
                },

                closeRoomDropdown() {
                    this.isRoomOpen = false;
                    if (!this.roomSearch || this.roomSearch.trim() === '') {
                        this.selectedRoom = '';
                        return;
                    }
                    const exact = this.rooms.find(r => r.name.toLowerCase() === this.roomSearch.trim().toLowerCase());
                    if (exact) {
                        this.selectedRoom = exact.name;
                        this.roomSearch = exact.name;
                        this.roomError = '';
                    } else if (this.selectedRoom) {
                        this.roomSearch = this.selectedRoom;
                    } else {
                        this.selectedRoom = '';
                    }
                },

                highlightNextCollaborator() {
                    if (!this.isCollaboratorOpen) {
                        this.isCollaboratorOpen = true;
                        this.collaboratorHighlightIndex = 0;
                        return;
                    }
                    if (this.collaboratorHighlightIndex < this.filteredNurses.length - 1) {
                        this.collaboratorHighlightIndex++;
                    }
                },

                highlightPrevCollaborator() {
                    if (this.collaboratorHighlightIndex > 0) {
                        this.collaboratorHighlightIndex--;
                    }
                },

                addCollaboratorDirect(name) {
                    const value = (name || '').trim();
                    if (!value) return;
                    if (this.collaborators.includes(value)) {
                        this.collaboratorError = `"${value}" sudah ada dalam daftar kolaborator.`;
                        return;
                    }
                    /*
                    if (this.collaborators.length >= 2) {
                        this.collaboratorError = 'Maksimal hanya 2 kolaborator yang dapat ditambahkan.';
                        this.collaboratorSearch = '';
                        this.isCollaboratorOpen = false;
                        return;
                    }
                    */
                    this.collaborators.push(value);
                    this.collaboratorSearch = '';
                    this.collaboratorError = '';
                    this.isCollaboratorOpen = false;
                    this.collaboratorHighlightIndex = 0;
                },

                selectHighlightedCollaborator() {
                    if (this.filteredNurses.length > 0) {
                        let candidate = this.filteredNurses[this.collaboratorHighlightIndex];
                        if (candidate && this.collaborators.includes(candidate.name)) {
                            candidate = this.filteredNurses.find(n => !this.collaborators.includes(n.name)) || candidate;
                        }
                        if (candidate) {
                            this.addCollaboratorDirect(candidate.name);
                            return;
                        }
                    }
                    if (this.collaboratorSearch && this.collaboratorSearch.trim() !== '') {
                        this.addCollaboratorDirect(this.collaboratorSearch.trim());
                    }
                },

                removeCollaborator(index) {
                    this.collaborators.splice(index, 1);
                    this.collaboratorError = '';
                    this.isCollaboratorOpen = false;
                },

                openModal() {
                    this.editingIndex = null;
                    this.modalError = '';
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');

                    this.modalData = {
                        category: defaultCategoryId,
                        incident_time: `${hours}:${minutes}`,
                        description: '',
                        action_taken: '',
                        status: 'in_progress'
                    };
                    this.showIncidentModal = true;
                    this.$nextTick(() => {
                        this.$refs.descriptionInput?.focus();
                    });
                },

                handleBackdropClick() {
                    if (this.modalData.description && this.modalData.description.trim() !== '') {
                        if (confirm('Uraian kejadian sedang ditulis. Yakin ingin membatalkan dan menutup form?')) {
                            this.closeModal();
                        }
                    } else {
                        this.closeModal();
                    }
                },

                handleEscapeKey() {
                    if (!this.showIncidentModal) return;
                    this.handleBackdropClick();
                },

                closeModal() {
                    this.showIncidentModal = false;
                    this.editingIndex = null;
                    this.modalError = '';
                },

                editIncident(index) {
                    this.editingIndex = index;
                    this.modalError = '';
                    this.modalData = JSON.parse(JSON.stringify(this.incidents[index]));
                    this.showIncidentModal = true;
                },

                saveIncidentFromModal() {
                    if (!this.modalData.description || this.modalData.description.trim() === '') {
                        this.modalError = 'Uraian masalah kejadian wajib diisi!';
                        return;
                    }

                    this.modalData.category = Number(this.modalData.category);

                    if (this.editingIndex !== null) {
                        this.incidents[this.editingIndex] = JSON.parse(JSON.stringify(this.modalData));
                    } else {
                        this.incidents.push(JSON.parse(JSON.stringify(this.modalData)));
                    }

                    this.closeModal();
                },

                removeIncident(index) {
                    if (confirm('Hapus butir kejadian ini dari daftar?')) {
                        this.incidents.splice(index, 1);
                    }
                },

                countByCategory(catId) {
                    return this.incidents.filter(i => Number(i.category) === Number(catId)).length;
                },

                getCategoryLabel(catId) {
                    return categoryMeta[catId]?.label || `Kategori #${catId}`;
                },

                getCategoryDescription(catId) {
                    return categoryMeta[catId]?.description || '';
                },

                getCategoryStyle(catId) {
                    return categoryMeta[catId]?.style || 'bg-slate-100 text-slate-800 border border-slate-300';
                },

                getStatusLabel(status) {
                    const labels = {
                        'in_progress': 'Sedang Ditangani',
                        'resolved': 'Selesai',
                        'escalated': 'Butuh Eskalasi'
                    };
                    return labels[status] || status;
                },

                getStatusStyle(status) {
                    const styles = {
                        'in_progress': 'bg-amber-50 text-amber-700 border border-amber-200',
                        'resolved': 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                        'escalated': 'bg-rose-50 text-rose-700 border border-rose-200'
                    };
                    return styles[status] || styles['in_progress'];
                },

                submitForm(event) {
                    // if (this.collaboratorSearch && this.collaboratorSearch.trim() !== '' && this.collaborators.length < 2) {
                    if (this.collaboratorSearch && this.collaboratorSearch.trim() !== '') {
                        const found = this.filteredNurses.find(n => !this.collaborators.includes(n.name));
                        if (found) {
                            this.addCollaboratorDirect(found.name);
                        }
                    }
                    if (!this.selectedRoom || this.selectedRoom.trim() === '') {
                        this.roomError = 'Ruangan / Instalasi perawatan wajib dipilih.';
                        document.getElementById('room_search_input')?.focus();
                        return;
                    }
                    if (!event.target.reportValidity()) {
                        return;
                    }
                    /*
                    if (this.collaborators.length > 2) {
                        this.collaboratorError = 'Maksimal hanya 2 kolaborator yang dapat ditambahkan.';
                        this.$refs.collaboratorSearchInput?.focus();
                        return;
                    }
                    */
                    this.isSubmitting = true;
                    this.$nextTick(() => {
                        event.target.submit();
                    });
                }
            }
        }
    </script>
</x-layouts.app>
