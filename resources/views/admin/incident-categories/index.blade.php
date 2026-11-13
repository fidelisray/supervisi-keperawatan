<x-layouts.app>
    <x-slot name="title">Manajemen Kategori Insiden</x-slot>

    <div x-data="incidentCategoryPage()" class="max-w-7xl mx-auto space-y-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Administrasi Sistem</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-teal-700 font-semibold">Manajemen Kategori Insiden</li>
                    </ol>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Manajemen Kategori Masalah &amp; Poin Kejadian
                </h1>
                <p class="text-base text-slate-600 mt-1">
                    Kelola daftar pilihan kategori masalah secara dinamis yang tampil pada formulir input logbook
                    supervisi.
                </p>
            </div>

            <div class="flex items-center shrink-0">
                <button type="button" @click="openCreateCategoryModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 active:scale-98 transition shadow-xs focus:ring-2 focus:ring-teal-500 focus:outline-none cursor-pointer">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Kategori Baru</span>
                </button>
            </div>
        </div>

        @if ($errors->any() && old('_modal') !== 'create_category')
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm space-y-1">
                <p class="font-bold">Terdapat kesalahan validasi:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Daftar Kategori Masalah (Full Width) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div
                class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                        Daftar Kategori Masalah Terdaftar ({{ $categories->count() }})
                    </h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Kategori dengan status <strong>Aktif</strong> akan muncul sebagai pilihan radio pada modal
                        Tambah Kejadian.
                    </p>
                </div>

                @if ($categories->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" x-model="searchCategory" placeholder="Cari kategori..."
                            aria-label="Cari kategori masalah"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-800 focus:ring-2 focus:ring-teal-500 focus:border-teal-600">
                        <div
                            class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs font-semibold">
                            <button type="button" @click="statusFilter = 'all'"
                                :class="statusFilter === 'all' ? 'bg-slate-800 text-white' :
                                    'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-md transition cursor-pointer">
                                Semua
                            </button>
                            <button type="button" @click="statusFilter = 'active'"
                                :class="statusFilter === 'active' ? 'bg-emerald-600 text-white' :
                                    'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-md transition cursor-pointer">
                                Aktif
                            </button>
                            <button type="button" @click="statusFilter = 'hidden'"
                                :class="statusFilter === 'hidden' ? 'bg-amber-600 text-white' :
                                    'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-md transition cursor-pointer">
                                Disembunyikan
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if ($categories->isEmpty())
                <div class="p-10 text-center text-base text-slate-500">
                    Belum ada kategori masalah yang terdaftar.
                </div>
            @else
                <div class="divide-y divide-slate-200">
                    @foreach ($categories as $category)
                        @php
                            $catStatus = $category->isActive() ? 'active' : 'hidden';
                        @endphp
                        <div x-data="{ editing: false }"
                            x-show="matchesFilter(@js(strtolower((string) $category->incident_category)), @js(strtolower((string) $category->description)), '{{ $catStatus }}')"
                            class="p-6 space-y-4 hover:bg-slate-50/60 transition">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div class="space-y-2 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="px-2.5 py-1 rounded bg-slate-900 text-white font-mono text-xs font-bold">
                                            ID: {{ $category->id }}
                                        </span>
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded text-sm font-bold {{ $category->badgeClasses() }}">
                                            {{ $category->incident_category }}
                                        </span>

                                        @if ($category->isActive())
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Aktif
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Disembunyikan
                                            </span>
                                        @endif

                                        <span class="text-sm text-slate-500 font-medium">
                                            &middot; {{ $category->incidents_count }} laporan terkait
                                        </span>
                                    </div>

                                    <p class="text-base text-slate-700 leading-relaxed">
                                        {{ $category->description ?: 'Tidak ada deskripsi.' }}
                                    </p>

                                    <div
                                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400 pt-1">
                                        <span>Dibuat oleh NIP: <strong
                                                class="text-slate-600 font-mono">{{ $category->created_by ?: '-' }}</strong></span>
                                        @if ($category->created_at)
                                            <span>Pada: {{ $category->created_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                        @if ($category->updated_by)
                                            <span>Diperbarui oleh NIP: <strong
                                                    class="text-slate-600 font-mono">{{ $category->updated_by }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <button type="button" @click="editing = !editing"
                                        class="px-3.5 py-2 rounded-lg text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition cursor-pointer">
                                        Edit
                                    </button>

                                    <form method="POST"
                                        action="{{ route('admin.incident-categories.toggle-active', $category) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-3.5 py-2 rounded-lg text-sm font-semibold transition cursor-pointer {{ $category->isActive() ? 'text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' }}">
                                            {{ $category->isActive() ? 'Sembunyikan' : 'Tampilkan' }}
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('admin.incident-categories.destroy', $category) }}"
                                        onsubmit="return confirm('Hapus kategori {{ addslashes($category->incident_category) }}? Kategori ini akan dinonaktifkan dari pilihan formulir logbook.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3.5 py-2 rounded-lg text-sm font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline Edit Form -->
                            <div x-show="editing" x-cloak
                                class="p-5 rounded-xl bg-slate-100/80 border border-slate-200">
                                <form method="POST"
                                    action="{{ route('admin.incident-categories.update', $category) }}"
                                    class="space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                        <div class="sm:col-span-7">
                                            <label class="block text-sm font-bold text-slate-700 uppercase mb-1.5">Nama
                                                Kategori Insiden</label>
                                            <input type="text" name="incident_category"
                                                value="{{ $category->incident_category }}" required
                                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900">
                                        </div>

                                        <div class="sm:col-span-5">
                                            <label
                                                class="block text-sm font-bold text-slate-700 uppercase mb-1.5">Status
                                                Tampil</label>
                                            <select name="is_active"
                                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900">
                                                <option value="true" {{ $category->isActive() ? 'selected' : '' }}>
                                                    Aktif (Tampil di Form Logbook)</option>
                                                <option value="false" {{ !$category->isActive() ? 'selected' : '' }}>
                                                    Disembunyikan (Tidak Tampil)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 uppercase mb-1.5">Deskripsi
                                            Kategori</label>
                                        <textarea name="description" rows="2" required
                                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900">{{ $category->description }}</textarea>
                                    </div>

                                    <div class="flex justify-end gap-2.5">
                                        <button type="button" @click="editing = false"
                                            class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-200 cursor-pointer">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 cursor-pointer">
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

        <!-- Modal Tambah Kategori Masalah Baru -->
        <div x-show="createCategoryModalOpen" x-cloak @keydown.escape.window="closeCreateCategoryModal()"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="create-category-modal-title" role="dialog"
            aria-modal="true">
            <div x-show="createCategoryModalOpen" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="closeCreateCategoryModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="createCategoryModalOpen" x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    <form method="POST" action="{{ route('admin.incident-categories.store') }}">
                        @csrf
                        <input type="hidden" name="_modal" value="create_category">

                        <div
                            class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="create-category-modal-title">
                                    Tambah Kategori Masalah Baru
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Tambahkan opsi poin kejadian baru untuk dipilih oleh Supervisor.
                                </p>
                            </div>
                            <button type="button" @click="closeCreateCategoryModal()"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-4">
                            @if ($errors->any() && old('_modal') === 'create_category')
                                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm space-y-1"
                                    role="alert">
                                    <p class="font-bold">Terdapat kesalahan validasi:</p>
                                    <ul class="list-disc list-inside">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div>
                                <label for="incident_category"
                                    class="block text-sm font-bold text-slate-700 uppercase mb-1.5">
                                    Nama Kategori Insiden <span class="text-rose-600">*</span>
                                </label>
                                <input type="text" id="incident_category" name="incident_category"
                                    x-ref="categoryNameInput"
                                    value="{{ old('_modal') === 'create_category' ? old('incident_category') : '' }}"
                                    required placeholder="Contoh: Kendala Alat Medis"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-base text-slate-900 focus:ring-2 focus:ring-teal-600 focus:border-teal-600">
                            </div>

                            <div>
                                <label for="description"
                                    class="block text-sm font-bold text-slate-700 uppercase mb-1.5">
                                    Deskripsi Kategori <span class="text-rose-600">*</span>
                                </label>
                                <textarea id="description" name="description" rows="3" required
                                    placeholder="Jelaskan kriteria atau cakupan dari kategori masalah ini..."
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-base text-slate-900 focus:ring-2 focus:ring-teal-600 focus:border-teal-600">{{ old('_modal') === 'create_category' ? old('description') : '' }}</textarea>
                            </div>

                            <div>
                                <label for="is_active"
                                    class="block text-sm font-bold text-slate-700 uppercase mb-1.5">
                                    Status Tampil di Form Logbook
                                </label>
                                <select id="is_active" name="is_active"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-base text-slate-900 focus:ring-2 focus:ring-teal-600 focus:border-teal-600">
                                    <option value="true"
                                        {{ (old('_modal') === 'create_category' ? old('is_active', 'true') : 'true') === 'true' ? 'selected' : '' }}>
                                        Aktif (Tampil di Form Logbook)</option>
                                    <option value="false"
                                        {{ old('_modal') === 'create_category' && old('is_active') === 'false' ? 'selected' : '' }}>
                                        Disembunyikan (Tidak Tampil)</option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
                            <button type="button" @click="closeCreateCategoryModal()"
                                class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 transition cursor-pointer shadow-xs">
                                + Simpan Kategori Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function incidentCategoryPage() {
            return {
                createCategoryModalOpen: @json($errors->any() && old('_modal') === 'create_category'),
                searchCategory: '',
                statusFilter: 'all',

                openCreateCategoryModal() {
                    this.createCategoryModalOpen = true;
                    this.$nextTick(() => this.$refs.categoryNameInput?.focus());
                },

                closeCreateCategoryModal() {
                    this.createCategoryModalOpen = false;
                },

                matchesFilter(name, desc, status) {
                    const q = this.searchCategory.trim().toLowerCase();
                    const matchSearch = !q || name.includes(q) || desc.includes(q);
                    const matchStatus = this.statusFilter === 'all' || this.statusFilter === status;
                    return matchSearch && matchStatus;
                }
            };
        }
    </script>
</x-layouts.app>
