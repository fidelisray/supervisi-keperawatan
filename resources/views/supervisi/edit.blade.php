<x-layouts.app>
    <x-slot name="title">Edit Logbook Supervisi - {{ $report->room_name }}</x-slot>

    @php
        $initialCollaborators = old('collaborators', $report->collaboratorList());
        $initialIncidents = old('incidents', $report->incidents->map(fn ($inc) => [
            'id' => $inc->id,
            'category' => (int) $inc->category,
            'incident_time' => $inc->incident_time ?? '',
            'description' => $inc->description,
            'action_taken' => $inc->action_taken ?? '',
            'status' => $inc->status,
        ])->values()->all());
        $categoryMeta = $incidentCategories->mapWithKeys(fn ($cat) => [
            $cat->id => [
                'label' => $cat->incident_category,
                'description' => $cat->description,
                'style' => $cat->badgeClasses(),
            ],
        ]);
        $defaultCategoryId = $incidentCategories->first()?->id ?? 1;
    @endphp

    <div 
        x-data="editSupervisionLogbook()"
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
                        <li><a href="{{ route('supervisi.show', $report) }}" class="hover:text-blue-600">#RPT-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }}</a></li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-blue-700 font-semibold">Edit Logbook</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Edit Logbook Supervisi Ruang Perawatan
                </h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Perbarui data operasional bangsal, daftar kolaborator, atau rincian tindak lanjut kejadian.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('supervisi.show', $report) }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs"
                >
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Detail
                </a>
            </div>
        </div>

        <form 
            id="editSupervisionForm" 
            action="{{ route('supervisi.update', $report) }}" 
            method="POST" 
            class="space-y-8"
            @submit.prevent="submitForm($event)"
        >
            @csrf
            @method('PUT')

            <!-- Section 1: Data Operasional Ruangan -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">
                            1
                        </div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                            Data Operasional Ruangan
                        </h2>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">*Wajib diisi</span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    <input type="hidden" name="shift" value="{{ old('shift', $report->shift) }}">

                    <!-- Tanggal Supervisi -->
                    <div class="md:col-span-4">
                        <label for="report_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Tanggal Supervisi
                        </label>
                        <input 
                            type="date" 
                            id="report_date" 
                            name="report_date" 
                            value="{{ old('report_date', $report->report_date->format('Y-m-d')) }}"
                            required 
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs"
                        >
                        @error('report_date')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ruangan Instalasi -->
                    <div class="md:col-span-4">
                        <label for="room_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Ruangan / Instalasi Perawatan
                        </label>
                        <input 
                            list="room_list" 
                            id="room_name" 
                            name="room_name" 
                            value="{{ old('room_name', $report->room_name) }}"
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

                    <!-- Jumlah Pasien -->
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
                                value="{{ old('patient_count', $report->patient_count) }}"
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
                                value="{{ old('nurse_count', $report->nurse_count) }}"
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

                    <!-- Supervisor Pembuat -->
                    <div class="md:col-span-5">
                        <label for="supervisor_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Supervisor Pembuat Laporan
                        </label>
                        <input 
                            type="text" 
                            id="supervisor_name" 
                            name="supervisor_name" 
                            value="{{ $report->supervisor_name }}"
                            readonly
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-700 text-sm font-medium cursor-not-allowed shadow-2xs"
                        >
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
                    </div>
                </div>
            </div>

            <!-- Section 2: Log Kejadian Ruangan -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                            2
                        </div>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                                Rincian Kejadian & Tindak Lanjut
                            </h2>
                            <p class="text-xs text-slate-500">
                                Tambah, ubah, atau perbarui status penanganan insiden ruangan.
                            </p>
                        </div>
                    </div>

                    <button 
                        type="button" 
                        @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs cursor-pointer"
                    >
                        + Tambah Kejadian
                    </button>
                </div>

                <div class="p-6">
                    <template x-if="incidents.length === 0">
                        <div class="text-center py-10 px-4 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                            <p class="text-sm font-semibold text-slate-700">Tidak ada butir kejadian pada laporan ini.</p>
                        </div>
                    </template>

                    <div class="space-y-3.5" x-show="incidents.length > 0">
                        <template x-for="(item, index) in incidents" :key="index">
                            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs flex flex-col md:flex-row md:items-start justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold"
                                            :class="getCategoryStyle(item.category)"
                                            x-text="getCategoryLabel(item.category)"
                                        ></span>

                                        <template x-if="item.incident_time">
                                            <span class="inline-flex items-center text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium">
                                                Pukul <span class="ml-1" x-text="item.incident_time"></span> WIB
                                            </span>
                                        </template>

                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold"
                                            :class="getStatusStyle(item.status)"
                                            x-text="getStatusLabel(item.status)"
                                        ></span>
                                    </div>

                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi Kejadian</h4>
                                        <p class="text-sm text-slate-800 whitespace-pre-line mt-0.5 font-medium" x-text="item.description"></p>
                                    </div>

                                    <template x-if="item.action_taken && item.action_taken.trim() !== ''">
                                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200/80 mt-2">
                                            <h5 class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                                                Tindak Lanjut / Solusi:
                                            </h5>
                                            <p class="text-xs text-slate-700 whitespace-pre-line mt-1" x-text="item.action_taken"></p>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex items-center space-x-2 md:self-start">
                                    <button 
                                        type="button" 
                                        @click="editIncident(index)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                                        title="Ubah butir kejadian"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="removeIncident(index)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                        title="Hapus butir kejadian"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

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

            <!-- Section 3: Catatan Evaluasi / Handover -->
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
                        placeholder="Tuliskan catatan evaluasi supervisor atau pesan penting..."
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition shadow-2xs"
                    >{{ old('general_notes', $report->general_notes) }}</textarea>
                </div>
            </div>

            <!-- Bottom Sticky Action Bar -->
            <div class="sticky bottom-4 z-20 bg-white p-4 rounded-xl border border-slate-300 shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-xs text-slate-600">
                    Memperbarui logbook ruangan <strong class="text-slate-900">{{ $report->room_name }}</strong>
                </div>

                <div class="flex items-center space-x-3">
                    <a 
                        href="{{ route('supervisi.show', $report) }}" 
                        class="px-4 py-2.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                    >
                        Batal
                    </a>
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm cursor-pointer"
                    >
                        Simpan Perubahan Logbook
                    </button>
                </div>
            </div>
        </form>

        <!-- Incident Modal -->
        <div 
            x-show="showIncidentModal" 
            x-cloak
            class="relative z-50"
            role="dialog" 
            aria-modal="true"
        >
            <div 
                x-show="showIncidentModal" 
                @click="closeModal()"
                class="fixed inset-0 bg-slate-950/50 transition-opacity"
            ></div>

            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div 
                        x-show="showIncidentModal" 
                        @click.stop
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200"
                    >
                        <div class="bg-blue-900 px-6 py-4 flex items-center justify-between text-white">
                            <h3 class="text-sm font-bold tracking-tight" x-text="editingIndex !== null ? 'Ubah Butir Kejadian' : 'Tambah Kejadian Ruangan'"></h3>
                            <button type="button" @click="closeModal()" class="p-1.5 rounded-lg text-blue-200 hover:text-white">
                                &times;
                            </button>
                        </div>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Kategori Masalah</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach($incidentCategories as $cat)
                                        <label class="cursor-pointer" title="{{ $cat->description }}">
                                            <input type="radio" x-model.number="modalData.category" value="{{ $cat->id }}" class="peer sr-only">
                                            <div class="p-2.5 rounded-lg border border-slate-200 text-center transition {{ $cat->radioPeerClasses() }} font-semibold text-xs hover:bg-slate-50">
                                                {{ $cat->incident_category }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5" x-show="getCategoryDescription(modalData.category)" x-text="getCategoryDescription(modalData.category)"></p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Perkiraan Jam Kejadian</label>
                                    <input type="time" x-model="modalData.incident_time" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Status Penanganan</label>
                                    <select x-model="modalData.status" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm">
                                        <option value="in_progress">Sedang Ditangani (In Progress)</option>
                                        <option value="resolved">Selesai Ditangani (Resolved)</option>
                                        <option value="escalated">Perlu Eskalasi Supervisor</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Uraian Masalah / Kejadian *</label>
                                <textarea x-model="modalData.description" rows="3" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm"></textarea>
                                <p x-show="modalError" class="text-xs text-rose-600 mt-1" x-text="modalError"></p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Tindak Lanjut / Solusi</label>
                                <textarea x-model="modalData.action_taken" rows="2" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end space-x-2.5">
                            <button type="button" @click="closeModal()" class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-200">Batal</button>
                            <button type="button" @click="saveIncidentFromModal()" class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editSupervisionLogbook() {
            const categoryMeta = @json($categoryMeta);
            const defaultCategoryId = @json($defaultCategoryId);

            return {
                collaborators: @json($initialCollaborators),
                collaboratorInput: '',
                collaboratorError: '',
                incidents: @json($initialIncidents),
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

                init() {},

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
                        category: defaultCategoryId,
                        incident_time: `${hours}:${minutes}`,
                        description: '',
                        action_taken: '',
                        status: 'in_progress'
                    };
                    this.showIncidentModal = true;
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
                    this.incidents.splice(index, 1);
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
                    if (this.collaboratorInput && this.collaboratorInput.trim() !== '') {
                        this.addCollaborator();
                    }
                    if (this.collaborators.length === 0) {
                        this.collaboratorError = 'Minimal satu kolaborator wajib dipilih.';
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

