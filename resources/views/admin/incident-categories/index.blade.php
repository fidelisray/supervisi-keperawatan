<x-layouts.app>
    <x-slot name="title">Manajemen Kategori Insiden</x-slot>

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Administrasi Sistem</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-teal-700 font-semibold">Manajemen Kategori Insiden</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Manajemen Kategori Masalah &amp; Poin Kejadian
                </h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Kelola daftar pilihan kategori masalah secara dinamis yang tampil pada formulir input logbook supervisi.
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                <p class="font-bold">Terdapat kesalahan validasi:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Column: Form Tambah Kategori Baru -->
            <div class="lg:col-span-4 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Tambah Kategori Masalah Baru
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tambahkan opsi poin kejadian baru untuk dipilih oleh Supervisor.
                    </p>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('admin.incident-categories.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="incident_category" class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Nama Kategori Insiden <span class="text-rose-600">*</span>
                            </label>
                            <input
                                type="text"
                                id="incident_category"
                                name="incident_category"
                                value="{{ old('incident_category') }}"
                                required
                                placeholder="Contoh: Kendala Alat Medis"
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus:ring-2 focus:ring-teal-600 focus:border-teal-600"
                            >
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Deskripsi Kategori <span class="text-rose-600">*</span>
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                required
                                placeholder="Jelaskan kriteria atau cakupan dari kategori masalah ini..."
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus:ring-2 focus:ring-teal-600 focus:border-teal-600"
                            >{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label for="is_active" class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Status Tampil di Form Logbook
                            </label>
                            <select
                                id="is_active"
                                name="is_active"
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus:ring-2 focus:ring-teal-600 focus:border-teal-600"
                            >
                                <option value="true" {{ old('is_active', 'true') === 'true' ? 'selected' : '' }}>Aktif (Tampil di Form Logbook)</option>
                                <option value="false" {{ old('is_active') === 'false' ? 'selected' : '' }}>Disembunyikan (Tidak Tampil)</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="w-full min-h-10 px-4 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-xs cursor-pointer"
                        >
                            + Simpan Kategori Baru
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Daftar Kategori Masalah -->
            <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                            Daftar Kategori Masalah Terdaftar ({{ $categories->count() }})
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Kategori dengan status <strong>Aktif (`true`)</strong> akan muncul sebagai pilihan radio pada modal Tambah Kejadian.
                        </p>
                    </div>
                </div>

                @if($categories->isEmpty())
                    <div class="p-10 text-center text-sm text-slate-500">
                        Belum ada kategori masalah yang terdaftar.
                    </div>
                @else
                    <div class="divide-y divide-slate-200">
                        @foreach($categories as $category)
                            <div x-data="{ editing: false }" class="p-6 space-y-4 hover:bg-slate-50/60 transition">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded bg-slate-900 text-white font-mono text-xs font-bold">
                                                ID: {{ $category->id }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold {{ $category->badgeClasses() }}">
                                                {{ $category->incident_category }}
                                            </span>

                                            @if($category->isActive())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Aktif (is_active: true)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    Disembunyikan (is_active: false)
                                                </span>
                                            @endif

                                            <span class="text-xs text-slate-500 font-medium">
                                                &middot; {{ $category->incidents_count }} laporan terkait
                                            </span>
                                        </div>

                                        <p class="text-sm text-slate-700 leading-relaxed">
                                            {{ $category->description ?: 'Tidak ada deskripsi.' }}
                                        </p>

                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-400 pt-1">
                                            <span>Dibuat oleh NIP: <strong class="text-slate-600 font-mono">{{ $category->created_by ?: '-' }}</strong></span>
                                            @if($category->created_at)
                                                <span>Pada: {{ $category->created_at->format('d/m/Y H:i') }}</span>
                                            @endif
                                            @if($category->updated_by)
                                                <span>Diperbarui oleh NIP: <strong class="text-slate-600 font-mono">{{ $category->updated_by }}</strong></span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                                        <button
                                            type="button"
                                            @click="editing = !editing"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition cursor-pointer"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('admin.incident-categories.toggle-active', $category) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {{ $category->isActive() ? 'text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' }}"
                                            >
                                                {{ $category->isActive() ? 'Sembunyikan' : 'Tampilkan' }}
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.incident-categories.destroy', $category) }}"
                                            onsubmit="return confirm('Hapus kategori {{ $category->incident_category }}? Kategori ini akan dinonaktifkan dan dicatat pada deleted_by / deleted_at.');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Inline Edit Form -->
                                <div x-show="editing" x-cloak class="p-4 rounded-xl bg-slate-100/80 border border-slate-200">
                                    <form method="POST" action="{{ route('admin.incident-categories.update', $category) }}" class="space-y-3">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                            <div class="sm:col-span-7">
                                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Kategori Insiden</label>
                                                <input
                                                    type="text"
                                                    name="incident_category"
                                                    value="{{ $category->incident_category }}"
                                                    required
                                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-900"
                                                >
                                            </div>

                                            <div class="sm:col-span-5">
                                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status Tampil (`is_active`)</label>
                                                <select name="is_active" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-900">
                                                    <option value="true" {{ $category->isActive() ? 'selected' : '' }}>Aktif (true)</option>
                                                    <option value="false" {{ ! $category->isActive() ? 'selected' : '' }}>Disembunyikan (false)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Kategori</label>
                                            <textarea
                                                name="description"
                                                rows="2"
                                                required
                                                class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-900"
                                            >{{ $category->description }}</textarea>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="editing = false"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-200 cursor-pointer"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="submit"
                                                class="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 cursor-pointer"
                                            >
                                                Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>

