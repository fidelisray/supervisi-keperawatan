<x-layouts.app>
    <x-slot name="title">Detail Logbook Supervisi - {{ $report->room_name }}</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Top Actions & Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
            <div class="flex items-center space-x-3">
                <a 
                    href="{{ route('supervisi.index') }}" 
                    class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                    title="Kembali ke Riwayat"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">
                        Detail Laporan Supervisi Keperawatan
                    </h1>
                    <p class="text-xs text-slate-500">
                        ID Laporan: #RPT-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }} &middot; Dicatat {{ $report->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a 
                    href="{{ route('supervisi.edit', $report) }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-teal-800 bg-teal-50 border border-teal-200 hover:bg-teal-100 transition shadow-2xs"
                >
                    Edit Logbook
                </a>

                <button 
                    type="button" 
                    onclick="window.print()" 
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Laporan
                </button>

                <a 
                    href="{{ route('supervisi.create') }}" 
                    class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs"
                >
                    + Input Laporan Baru
                </a>
            </div>
        </div>

        <!-- Report Summary Header Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="bg-blue-900 text-white p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-400 text-slate-900 uppercase tracking-wider">
                        Shift {{ ucfirst($report->shift) }}
                    </span>
                    <h2 class="text-2xl font-bold mt-2 tracking-tight">{{ $report->room_name }}</h2>
                    <p class="text-xs text-blue-200 mt-0.5">
                        Tanggal: <strong>{{ $report->report_date->translatedFormat('l, d F Y') }}</strong>
                    </p>
                </div>

                <div class="flex items-center space-x-6 text-center border-t md:border-t-0 md:border-l border-blue-800 pt-4 md:pt-0 md:pl-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-200">Jumlah Pasien</p>
                        <p class="text-2xl font-black mt-0.5">{{ $report->patient_count }}</p>
                        <p class="text-[10px] text-blue-300">Orang</p>
                    </div>
                    <div class="w-px h-10 bg-blue-800"></div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-200">Perawat Dinas</p>
                        <p class="text-2xl font-black mt-0.5">{{ $report->nurse_count }}</p>
                        <p class="text-[10px] text-blue-300">Ners</p>
                    </div>
                </div>
            </div>

            <!-- Petugas & Kolaborator Info Bar -->
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 border-b border-slate-200 bg-slate-50/70 p-4 text-xs">
                <div class="flex items-center space-x-3 px-3 py-1">
                    <span class="text-slate-400 font-bold uppercase tracking-wider shrink-0">Supervisor Pengawas:</span>
                    <span class="font-bold text-slate-900">{{ $report->supervisor_name }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2 px-3 py-1">
                    <span class="text-slate-400 font-bold uppercase tracking-wider shrink-0">Kolaborator:</span>
                    @foreach($report->collaboratorList() as $collabName)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-900 border border-teal-200 font-semibold">
                            {{ $collabName }}
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- Incidents Section -->
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 mr-2"></span>
                        Rincian Kejadian & Tindak Lanjut ({{ $report->incidents->count() }})
                    </h3>
                </div>

                @if($report->incidents->isEmpty())
                    <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <svg class="w-8 h-8 text-emerald-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-sm font-bold text-slate-800">Dinas Berjalan Aman & Kondusif</h4>
                        <p class="text-xs text-slate-500 mt-1">Tidak ada insiden, komplain, atau kendala fasilitas yang dilaporkan.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($report->incidents as $index => $incident)
                            <div x-data="{ openStatusUpdate: false }" class="p-5 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold">
                                            {{ $index + 1 }}
                                        </span>

                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold {{ $incident->categoryBadgeClasses() }}">
                                            {{ $incident->categoryLabel() }}
                                        </span>

                                        @if($incident->incident_time)
                                            <span class="text-xs text-slate-500 font-medium bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                                Pukul {{ $incident->incident_time }} WIB
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold
                                            @if($incident->status === 'resolved') bg-emerald-50 text-emerald-700 border border-emerald-200
                                            @elseif($incident->status === 'escalated') bg-rose-50 text-rose-700 border border-rose-200
                                            @else bg-amber-50 text-amber-700 border border-amber-200
                                            @endif
                                        ">
                                            {{ $incident->status === 'resolved' ? 'Selesai' : ($incident->status === 'escalated' ? 'Butuh Eskalasi' : 'Sedang Ditangani') }}
                                        </span>

                                        <button
                                            type="button"
                                            @click="openStatusUpdate = !openStatusUpdate"
                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition cursor-pointer"
                                        >
                                            Update Tindak Lanjut
                                        </button>
                                    </div>
                                </div>

                                <div class="pl-8 space-y-3">
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Uraian Masalah</h4>
                                        <p class="text-sm text-slate-900 mt-1 whitespace-pre-line leading-relaxed">
                                            {{ $incident->description }}
                                        </p>
                                    </div>

                                    @if($incident->action_taken)
                                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200/80">
                                            <h5 class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Tindak Lanjut & Penanganan:
                                            </h5>
                                            <p class="text-xs text-slate-700 mt-1 whitespace-pre-line leading-relaxed">
                                                {{ $incident->action_taken }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Inline Follow-up Status Update Form -->
                                    <div x-show="openStatusUpdate" x-cloak class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                        <form method="POST" action="{{ route('supervisi.incidents.update-status', [$report, $incident]) }}" class="space-y-3">
                                            @csrf
                                            @method('PATCH')

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status Penanganan Baru</label>
                                                    <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-900">
                                                        <option value="in_progress" {{ $incident->status === 'in_progress' ? 'selected' : '' }}>Sedang Ditangani (In Progress)</option>
                                                        <option value="resolved" {{ $incident->status === 'resolved' ? 'selected' : '' }}>Selesai Ditangani (Resolved)</option>
                                                        <option value="escalated" {{ $incident->status === 'escalated' ? 'selected' : '' }}>Perlu Eskalasi Supervisor</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Tindak Lanjut / Evaluasi</label>
                                                <textarea name="action_taken" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-900" placeholder="Tuliskan perkembangan penanganan insiden ini...">{{ $incident->action_taken }}</textarea>
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="openStatusUpdate = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-200 cursor-pointer">
                                                    Batal
                                                </button>
                                                <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 cursor-pointer">
                                                    Simpan Status Insiden
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- General Notes if any -->
                @if($report->general_notes)
                    <div class="mt-6 pt-4 border-t border-slate-200">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                            Pesan Khusus Operan (Handover) / Catatan Evaluasi:
                        </h4>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-800 whitespace-pre-line leading-relaxed">
                            {{ $report->general_notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
