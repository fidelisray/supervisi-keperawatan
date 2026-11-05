<x-layouts.app>
    <x-slot name="title">Input Logbook Supervisi</x-slot>

    <div 
        x-data="supervisionLogbook()"
        x-init="init()"
        class="max-w-6xl mx-auto space-y-8"
    >
        <!-- Page Title & Context Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Supervisi Keperawatan</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Formulir Logbook Shift</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Logbook Supervisi Ruang Perawatan
                </h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Catat kondisi operasional bangsal dan laporkan kejadian/insiden selama dinas shift berlangsung.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('supervisi.index') }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs"
                >
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Lihat Riwayat
                </a>
            </div>
        </div>

        <!-- Form Element -->
        <form 
            id="supervisionForm" 
            action="{{ route('supervisi.store') }}" 
            method="POST" 
            class="space-y-8"
            @submit.prevent="submitForm($event)"
        >
            @csrf

            <!-- Section 1: Konteks Ruangan & Shift Operasional -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">
                            1
                        </div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                            Data Operasional Ruangan & Dinas Shift
                        </h2>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">*Wajib diisi</span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- Tanggal Supervisi -->
                    <div class="md:col-span-4">
                        <label for="report_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Tanggal Supervisi
                        </label>
                        <input 
                            type="date" 
                            id="report_date" 
                            name="report_date" 
                            value="{{ old('report_date', $today) }}"
                            required 
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs"
                        >
                        @error('report_date')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Pilihan Shift (Disembunyikan sesuai konfigurasi saat ini) -->
                    <input type="hidden" name="shift" value="{{ old('shift', $defaultShift) }}">

                    <!-- Ruangan Instalasi -->
                    <div class="md:col-span-4">
                        <label for="room_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Ruangan / Instalasi Perawatan
                        </label>
                        <input 
                            list="room_list" 
                            id="room_name" 
                            name="room_name" 
                            value="{{ old('room_name') }}"
                            placeholder="Ketik atau pilih ruangan bangsal..."
                            required 
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs"
                        >
                        <datalist id="room_list">
                            @foreach($rooms as $room)
                                <option value="{{ $room->nama_ruangan }}">{{ $room->instalasi ? 'Instalasi ' . $room->instalasi : '' }}</option>
                            @endforeach
                        </datalist>
                        @error('room_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jumlah Pasien Hari Ini -->
                    <div class="md:col-span-2">
                        <label for="patient_count" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Jumlah Pasien (BOR)
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="patient_count" 
                                name="patient_count" 
                                min="0" 
                                value="{{ old('patient_count', 0) }}"
                                required 
                                class="w-full pl-3.5 pr-10 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs font-semibold"
                            >
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-medium">
                                Orang
                            </span>
                        </div>
                        @error('patient_count')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jumlah Perawat Bertugas -->
                    <div class="md:col-span-2">
                        <label for="nurse_count" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Perawat Bertugas
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="nurse_count" 
                                name="nurse_count" 
                                min="0" 
                                value="{{ old('nurse_count', 2) }}"
                                required 
                                class="w-full pl-3.5 pr-10 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs font-semibold"
                            >
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-medium">
                                Ners
                            </span>
                        </div>
                        @error('nurse_count')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supervisor (Penginput) -->
                    <div class="md:col-span-5">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="supervisor_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Supervisor / Pengawas Keperawatan
                            </label>
                            @auth
                                <span class="text-[11px] font-mono font-medium text-slate-500">
                                    NIP: {{ auth()->user()->nip }}
                                </span>
                            @endauth
                        </div>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="supervisor_name" 
                                name="supervisor_name" 
                                value="{{ old('supervisor_name', $defaultSupervisor) }}"
                                readonly
                                required 
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-700 text-sm font-medium cursor-not-allowed shadow-2xs"
                            >
                        </div>
                        @error('supervisor_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Multi-Kolaborator Supervisi -->
                    <div class="md:col-span-7">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="collaborator_picker" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Kolaborator Supervisi (Dapat Lebih dari Satu)
                            </label>
                            <span class="text-[11px] text-slate-500 font-medium">
                                <span class="font-bold text-teal-700" x-text="collaborators.length">0</span> dipilih
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <input 
                                list="nurse_list"
                                type="text" 
                                id="collaborator_picker" 
                                x-model="collaboratorInput"
                                @keydown.enter.prevent="addCollaborator()"
                                placeholder="Ketik atau pilih nama kolaborator terdaftar lalu klik Tambah..."
                                class="flex-1 px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs"
                            >
                            <button
                                type="button"
                                @click="addCollaborator()"
                                class="inline-flex items-center px-4 py-2.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition shadow-2xs shrink-0 cursor-pointer"
                            >
                                + Tambah Kolaborator
                            </button>
                        </div>

                        <datalist id="nurse_list">
                            @foreach($nurses as $nurse)
                                <option value="{{ $nurse->description }}">{{ $nurse->nip }}</option>
                            @endforeach
                        </datalist>

                        <input type="hidden" name="nurse_in_charge" :value="collaborators.join(' | ')">
                        <template x-for="(collab, cIndex) in collaborators" :key="cIndex">
                            <input type="hidden" :name="'collaborators[' + cIndex + ']'" :value="collab">
                        </template>

                        <!-- Selected Collaborator Chips -->
                        <div class="mt-2.5 flex flex-wrap gap-2" x-show="collaborators.length > 0">
                            <template x-for="(collab, cIndex) in collaborators" :key="cIndex">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-900 border border-teal-200">
                                    <span x-text="collab"></span>
                                    <button
                                        type="button"
                                        @click="removeCollaborator(cIndex)"
                                        class="text-teal-600 hover:text-rose-600 focus:outline-none cursor-pointer"
                                        :aria-label="'Hapus kolaborator ' + collab"
                                    >
                                        &times;
                                    </button>
                                </span>
                            </template>
                        </div>

                        <p x-show="collaboratorError" x-cloak class="text-xs text-rose-600 mt-1.5 font-medium" x-text="collaboratorError"></p>
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
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                            2
                        </div>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                                Kejadian di Ruangan Selama Dinas Shift
                            </h2>
                            <p class="text-xs text-slate-500">
                                Laporkan insiden berdasarkan poin masalahnya (Complain, Fasilitas, JKN &ge; 6 Hari, Lain-lain).
                            </p>
                        </div>
                    </div>

                    <button 
                        type="button" 
                        @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-98 transition shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        + Tambah Kejadian
                    </button>
                </div>

                <!-- Incident Category Counters & Filter Chips -->
                <div class="px-6 py-3 bg-slate-100/50 border-b border-slate-200 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-500 font-semibold mr-1">Ringkasan Masalah:</span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-50 text-rose-800 border border-rose-200 font-medium">
                        <span class="w-2 h-2 rounded-full bg-rose-600 mr-1.5"></span>
                        Complain: <strong class="ml-1" x-text="countByCategory('complain')">0</strong>
                    </span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                        <span class="w-2 h-2 rounded-full bg-amber-600 mr-1.5"></span>
                        Fasilitas: <strong class="ml-1" x-text="countByCategory('fasilitas')">0</strong>
                    </span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-teal-50 text-teal-800 border border-teal-200 font-medium">
                        <span class="w-2 h-2 rounded-full bg-teal-600 mr-1.5"></span>
                        JKN &ge; 6 Hari: <strong class="ml-1" x-text="countByCategory('jkn_6_hari')">0</strong>
                    </span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-200 text-slate-800 border border-slate-300 font-medium">
                        <span class="w-2 h-2 rounded-full bg-slate-600 mr-1.5"></span>
                        Lain-Lain: <strong class="ml-1" x-text="countByCategory('lain_lain')">0</strong>
                    </span>

                    <span class="ml-auto text-slate-500 font-medium">
                        Total: <strong class="text-slate-900" x-text="incidents.length">0</strong> Butir Kejadian
                    </span>
                </div>

                <!-- Incidents List Container -->
                <div class="p-6">
                    <!-- Empty State -->
                    <template x-if="incidents.length === 0">
                        <div class="text-center py-12 px-4 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 mx-auto flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">Belum Ada Kejadian yang Dicatat</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-4 leading-relaxed">
                                Jika terjadi keluhan pasien, kendala alat medis, atau catatan klinis penting selama dinas ini, klik tombol di bawah untuk mencatat.
                            </p>
                            <button 
                                type="button" 
                                @click="openModal()"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition"
                            >
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Kejadian Baru
                            </button>
                        </div>
                    </template>

                    <!-- Populated Incident Cards -->
                    <div class="space-y-3.5" x-show="incidents.length > 0">
                        <template x-for="(item, index) in incidents" :key="item.id || index">
                            <div class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-white shadow-2xs transition flex flex-col md:flex-row md:items-start justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <!-- Badges & Timing Header -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Category Badge -->
                                        <span 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold"
                                            :class="getCategoryStyle(item.category)"
                                            x-text="getCategoryLabel(item.category)"
                                        ></span>

                                        <!-- Time Badge -->
                                        <template x-if="item.incident_time">
                                            <span class="inline-flex items-center text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium">
                                                <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Pukul <span class="ml-1" x-text="item.incident_time"></span> WIB
                                            </span>
                                        </template>

                                        <!-- Status Badge -->
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold"
                                            :class="getStatusStyle(item.status)"
                                            x-text="getStatusLabel(item.status)"
                                        ></span>
                                    </div>

                                    <!-- Uraian Kejadian / Description -->
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi Kejadian</h4>
                                        <p class="text-sm text-slate-800 whitespace-pre-line mt-0.5 font-medium leading-relaxed" x-text="item.description"></p>
                                    </div>

                                    <!-- Tindak Lanjut / Solusi (If Any) -->
                                    <template x-if="item.action_taken && item.action_taken.trim() !== ''">
                                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200/80 mt-2">
                                            <h5 class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Tindak Lanjut / Solusi yang Diberikan:
                                            </h5>
                                            <p class="text-xs text-slate-700 whitespace-pre-line mt-1" x-text="item.action_taken"></p>
                                        </div>
                                    </template>
                                </div>

                                <!-- Action Buttons (Edit / Remove) -->
                                <div class="flex items-center space-x-2 md:self-start pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                    <button 
                                        type="button" 
                                        @click="editIncident(index)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                                        title="Ubah butir kejadian"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="removeIncident(index)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus butir kejadian"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Hidden Inputs for Standard Form Submission -->
                                <input type="hidden" :name="'incidents[' + index + '][category]'" :value="item.category">
                                <input type="hidden" :name="'incidents[' + index + '][incident_time]'" :value="item.incident_time">
                                <input type="hidden" :name="'incidents[' + index + '][description]'" :value="item.description">
                                <input type="hidden" :name="'incidents[' + index + '][action_taken]'" :value="item.action_taken">
                                <input type="hidden" :name="'incidents[' + index + '][status]'" :value="item.status">
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Section 3: Catatan Umum / Handover Shift -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex items-center space-x-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-xs">
                        3
                    </div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Catatan Khusus Supervisi / Pesan Operan (Handover)
                    </h2>
                </div>
                <div class="p-6">
                    <textarea 
                        name="general_notes" 
                        rows="3" 
                        placeholder="Tuliskan catatan evaluasi supervisor atau pesan penting untuk shift berikutnya (opsional)..."
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs"
                    >{{ old('general_notes') }}</textarea>
                </div>
            </div>

            <!-- Bottom Sticky Action Bar -->
            <div class="sticky bottom-4 z-20 bg-white p-4 rounded-xl border border-slate-300 shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3 text-xs text-slate-600">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div>
                        <span class="font-bold text-slate-800" x-text="incidents.length">0</span> kejadian tercatat
                        <span class="text-slate-400 mx-1">•</span>
                        Siap disimpan ke database rumah sakit.
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a 
                        href="{{ route('supervisi.index') }}" 
                        class="px-4 py-2.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                    >
                        Batal
                    </a>
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-98 transition shadow-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Simpan Laporan Supervisi
                    </button>
                </div>
            </div>
        </form>

        <!-- Incident Modal Form (Add & Edit - Clean M3 Stacking Context) -->
        <div 
            x-show="showIncidentModal" 
            x-cloak
            class="relative z-50"
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <!-- Backdrop (Clean Dark Scrim - Zero blur, 100% crisp) -->
            <div 
                x-show="showIncidentModal" 
                @click="handleBackdropClick()"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/50 transition-opacity"
                aria-hidden="true"
            ></div>

            <!-- Modal Positioning Container -->
            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <!-- Modal Panel (Sharp, Crisp, Independent Stacking) -->
                    <div 
                        x-show="showIncidentModal" 
                        @click.stop
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        @keydown.escape.window="handleEscapeKey()"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200"
                    >
                        <div class="bg-blue-900 px-6 py-4 flex items-center justify-between text-white">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center font-bold text-amber-300 text-sm">
                                    +
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold tracking-tight" id="modal-title" x-text="editingIndex !== null ? 'Ubah Butir Kejadian' : 'Tambah Kejadian Ruangan'">
                                        Tambah Kejadian Ruangan
                                    </h3>
                                    <p class="text-xs text-blue-200">Isi detail insiden dan penanganan yang dilakukan.</p>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                @click="handleBackdropClick()"
                                class="p-1.5 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 transition"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                                    <label class="cursor-pointer">
                                        <input type="radio" x-model="modalData.category" value="complain" class="peer sr-only">
                                        <div class="p-2.5 rounded-lg border border-slate-200 text-center transition peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-800 font-semibold text-xs hover:bg-slate-50">
                                            Complain
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" x-model="modalData.category" value="fasilitas" class="peer sr-only">
                                        <div class="p-2.5 rounded-lg border border-slate-200 text-center transition peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800 font-semibold text-xs hover:bg-slate-50">
                                            Fasilitas
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" x-model="modalData.category" value="jkn_6_hari" class="peer sr-only">
                                        <div class="p-2.5 rounded-lg border border-slate-200 text-center transition peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-800 font-semibold text-xs hover:bg-slate-50">
                                            JKN &ge; 6 Hari
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" x-model="modalData.category" value="lain_lain" class="peer sr-only">
                                        <div class="p-2.5 rounded-lg border border-slate-200 text-center transition peer-checked:border-slate-500 peer-checked:bg-slate-100 peer-checked:text-slate-800 font-semibold text-xs hover:bg-slate-50">
                                            Lain-Lain
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Jam Kejadian & Status -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                        Perkiraan Jam Kejadian
                                    </label>
                                    <input 
                                        type="time" 
                                        x-model="modalData.incident_time"
                                        class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                        Status Penanganan
                                    </label>
                                    <select 
                                        x-model="modalData.status"
                                        class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                                    >
                                        <option value="in_progress">Sedang Ditangani (In Progress)</option>
                                        <option value="resolved">Selesai Ditangani (Resolved)</option>
                                        <option value="escalated">Perlu Eskalasi Supervisor</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Deskripsi Kejadian -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    Uraian Masalah / Kejadian <span class="text-rose-600">*</span>
                                </label>
                                <textarea 
                                    x-ref="descriptionInput"
                                    x-model="modalData.description" 
                                    rows="3" 
                                    placeholder="Jelaskan secara ringkas dan objektif apa yang terjadi di ruangan..."
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                                ></textarea>
                                <p x-show="modalError" class="text-xs text-rose-600 mt-1" x-text="modalError"></p>
                            </div>

                            <!-- Tindak Lanjut / Solusi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    Tindak Lanjut / Solusi yang Dilakukan
                                </label>
                                <textarea 
                                    x-model="modalData.action_taken" 
                                    rows="2" 
                                    placeholder="Apa tindakan cepat yang sudah dilakukan perawat, atau instruksi tindak lanjutnya..."
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end space-x-2.5">
                            <button 
                                type="button" 
                                @click="handleBackdropClick()"
                                class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-200 transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="saveIncidentFromModal()"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs"
                            >
                                <span x-text="editingIndex !== null ? 'Simpan Perubahan' : 'Tambahkan ke Logbook'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js Component Script -->
    <script>
        function supervisionLogbook() {
            return {
                collaborators: @json(old('collaborators', [])),
                collaboratorInput: '',
                collaboratorError: '',
                incidents: [],
                showIncidentModal: false,
                editingIndex: null,
                modalError: '',
                modalData: {
                    category: 'complain',
                    incident_time: '',
                    description: '',
                    action_taken: '',
                    status: 'in_progress'
                },

                init() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    this.modalData.incident_time = `${hours}:${minutes}`;
                },

                addCollaborator() {
                    const value = (this.collaboratorInput || '').trim();
                    if (!value) {
                        return;
                    }
                    if (!this.collaborators.includes(value)) {
                        this.collaborators.push(value);
                    }
                    this.collaboratorInput = '';
                    this.collaboratorError = '';
                },

                removeCollaborator(index) {
                    this.collaborators.splice(index, 1);
                },

                openModal() {
                    this.editingIndex = null;
                    this.modalError = '';
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');

                    this.modalData = {
                        category: 'complain',
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

                countByCategory(cat) {
                    return this.incidents.filter(i => i.category === cat).length;
                },

                getCategoryLabel(cat) {
                    const labels = {
                        'complain': 'Complain',
                        'fasilitas': 'Fasilitas',
                        'jkn_6_hari': 'JKN ≥ 6 Hari',
                        'lain_lain': 'Lain-Lain'
                    };
                    return labels[cat] || 'Lain-Lain';
                },

                getCategoryStyle(cat) {
                    const styles = {
                        'complain': 'bg-rose-100 text-rose-800 border border-rose-300',
                        'fasilitas': 'bg-amber-100 text-amber-800 border border-amber-300',
                        'jkn_6_hari': 'bg-teal-100 text-teal-800 border border-teal-300',
                        'lain_lain': 'bg-slate-100 text-slate-800 border border-slate-300'
                    };
                    return styles[cat] || styles['lain_lain'];
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
                    if (this.collaboratorInput && this.collaboratorInput.trim() !== '') {
                        this.addCollaborator();
                    }
                    if (this.collaborators.length === 0) {
                        this.collaboratorError = 'Silakan pilih minimal satu Kolaborator sebelum menyimpan laporan.';
                        return;
                    }
                    this.$nextTick(() => {
                        event.target.submit();
                    });
                }
            }
        }
    </script>
</x-layouts.app>

