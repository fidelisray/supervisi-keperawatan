<x-layouts.app>
    <x-slot name="title">Manajemen Role & Hak Akses Pengguna (App ID: 6)</x-slot>

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Page Title & Context Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Administrasi Sistem</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-teal-700 font-semibold">Manajemen Role & Hak Akses</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Manajemen Role &amp; Hak Akses Pengguna (App ID: 6)
                </h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Kelola definisi role aplikasi (<code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">ref_master_nurse_supervision_approles</code>) dan hak akses pegawai (<code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">tb_master_nurse_supervision_approles</code>) secara mandiri tanpa menyentuh tabel master SIMRS.
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm font-medium" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Top Grid: 1. Definisi Role & 2. Tambah Pegawai Baru -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Panel 1: Definisi Role (ref_master_nurse_supervision_approles) -->
            <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                            1. Daftar &amp; Buat Role Baru
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tabel: <span class="font-mono font-semibold">ref_master_nurse_supervision_approles</span>
                        </p>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-[11px] font-bold uppercase text-slate-500 border-b border-slate-200">
                                        <th class="px-4 py-2.5">Role ID</th>
                                        <th class="px-4 py-2.5">Nama Role</th>
                                        <th class="px-4 py-2.5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($availableRoles as $roleId => $roleName)
                                        <tr>
                                            <td class="px-4 py-2.5 font-mono font-bold text-slate-900">{{ $roleId }}</td>
                                            <td class="px-4 py-2.5 font-semibold text-slate-800">{{ $roleName }}</td>
                                            <td class="px-4 py-2.5 text-right">
                                                @if((int) $roleId > 1)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.roles.destroy-definition', $roleId) }}"
                                                        onsubmit="return confirm('Hapus definisi role {{ $roleName }}?');"
                                                        class="inline"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="px-2 py-1 rounded text-[11px] font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer"
                                                        >
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-[11px] text-slate-400 italic">Default</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="{{ route('admin.roles.store-definition') }}" class="pt-3 border-t border-slate-200 space-y-3">
                            @csrf
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                Tambah / Perbarui Definisi Role
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label for="role_id" class="block text-xs font-medium text-slate-600 mb-1">Kode Role (Angka)</label>
                                    <input
                                        type="number"
                                        id="role_id"
                                        name="role_id"
                                        min="0"
                                        max="99"
                                        required
                                        placeholder="Misal: 2"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs font-mono text-slate-900"
                                    >
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="role_name" class="block text-xs font-medium text-slate-600 mb-1">Nama Role</label>
                                    <input
                                        type="text"
                                        id="role_name"
                                        name="role_name"
                                        required
                                        placeholder="Contoh: KEPALA RUANG"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs text-slate-900 uppercase"
                                    >
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="w-full px-4 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer"
                            >
                                Simpan Definisi Role
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Berikan Role ke Pegawai yang Belum Terdaftar -->
            <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        2. Berikan Hak Akses kepada Pegawai Baru
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pilih pegawai dari master <span class="font-mono">tb_employees</span> untuk ditambahkan ke <span class="font-mono font-semibold">tb_master_nurse_supervision_approles</span>.
                    </p>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('admin.roles.assign-user') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="assign_nip" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                Cari Pegawai (NIP) dari Master Karyawan
                            </label>
                            <input
                                list="unregistered_employees_list"
                                type="text"
                                id="assign_nip"
                                name="nip"
                                required
                                placeholder="Ketik NIP atau nama pegawai..."
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm font-mono focus:ring-2 focus:ring-teal-500 focus:border-teal-600"
                            >
                            <datalist id="unregistered_employees_list">
                                @foreach($unregisteredEmployees as $emp)
                                    <option value="{{ $emp->nip }}">{{ $emp->nama }} ({{ $emp->nip }})</option>
                                @endforeach
                            </datalist>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Nama pegawai akan diambil otomatis dari kolom <code class="font-mono">nama</code> pada <code class="font-mono">tb_employees</code>.
                            </p>
                        </div>

                        <div>
                            <label for="assign_role" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                Pilih Hak Akses (Role)
                            </label>
                            <select
                                id="assign_role"
                                name="app_roles"
                                required
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-600"
                            >
                                @foreach($availableRoles as $roleId => $roleName)
                                    <option value="{{ $roleId }}" {{ (int) $roleId === 1 ? 'selected' : '' }}>
                                        {{ $roleId }} - {{ $roleName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="inline-flex items-center px-5 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition cursor-pointer"
                            >
                                + Daftarkan &amp; Berikan Role Pegawai
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengguna Terdaftar -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        3. Daftar Pengguna Terdaftar ({{ $appRoles->count() }} Pegawai)
                    </h2>
                    <p class="text-xs text-slate-500">
                        Anda dapat mengubah role atau mencabut hak akses pengguna pada tabel <code class="font-mono">tb_master_nurse_supervision_approles</code> di bawah ini.
                    </p>
                </div>

                <form method="GET" action="{{ route('admin.roles.index') }}" class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari NIP atau Nama..."
                        class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs text-slate-800"
                    >
                    <select name="role_filter" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs text-slate-800">
                        <option value="">Semua Role</option>
                        @foreach($availableRoles as $roleId => $roleName)
                            <option value="{{ $roleId }}" {{ request('role_filter') !== null && (string) request('role_filter') === (string) $roleId ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-semibold cursor-pointer">
                        Cari
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="px-6 py-3">NIP</th>
                            <th class="px-6 py-3">Nama Pegawai / Perawat</th>
                            <th class="px-6 py-3">Role Saat Ini</th>
                            <th class="px-6 py-3 text-right">Kelola Role &amp; Akses</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($appRoles as $userRole)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-3.5 font-mono text-xs font-bold text-slate-900">
                                    {{ $userRole->nip }}
                                </td>
                                <td class="px-6 py-3.5 font-semibold text-slate-800">
                                    {{ $userRole->description }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        {{ (int) $userRole->app_roles === 0 ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}
                                    ">
                                        {{ $userRole->roleLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.roles.update-user', $userRole) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select
                                                name="app_roles"
                                                aria-label="Ubah role untuk {{ $userRole->description }}"
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs text-slate-900"
                                            >
                                                @foreach($availableRoles as $roleId => $roleName)
                                                    <option value="{{ $roleId }}" {{ (int) $userRole->app_roles === (int) $roleId ? 'selected' : '' }}>
                                                        {{ $roleId }} - {{ $roleName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button
                                                type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition cursor-pointer"
                                            >
                                                Simpan
                                            </button>
                                        </form>

                                        @if(trim((string) auth()->user()?->nip) !== trim((string) $userRole->nip))
                                            <form
                                                method="POST"
                                                action="{{ route('admin.roles.destroy-user', $userRole) }}"
                                                onsubmit="return confirm('Cabut dan hapus hak akses untuk {{ $userRole->description }} (NIP: {{ $userRole->nip }})?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition cursor-pointer"
                                                    title="Hapus hak akses pengguna"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
