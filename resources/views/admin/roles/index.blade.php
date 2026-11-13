<x-layouts.app>
    <x-slot name="title">Manajemen Role & Hak Akses Pengguna</x-slot>

    @php
        $unregisteredEmployeesJson = $unregisteredEmployees->map(fn ($emp) => [
            'nip' => $emp->nip,
            'name' => $emp->nama,
        ])->values();
    @endphp

    <div class="max-w-7xl mx-auto space-y-8" x-data="adminRolesPage()">
        <!-- Page Title & Context Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li>Administrasi Sistem</li>
                        <li><span class="mx-1 text-slate-400">/</span></li>
                        <li class="text-teal-700 font-semibold">Manajemen Role & Hak Akses</li>
                    </ol>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Manajemen Role &amp; Hak Akses Pengguna
                </h1>
                <p class="text-base text-slate-600 mt-1">
                    Kelola definisi role, hak akses, dan penugasan pegawai aplikasi.
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-base font-medium" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Top Grid: 1. Definisi Role & 2. Tambah Pegawai Baru -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Panel 1: Definisi Role & Hak Akses -->
            <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70">
                        <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                            1. Daftar &amp; Buat Role Baru
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="overflow-x-auto border border-slate-200 rounded-xl">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-xs font-bold uppercase text-slate-500 border-b border-slate-200">
                                        <th class="px-4 py-3">Role</th>
                                        <th class="px-4 py-3">Hak Akses</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($availableRoles as $roleId => $roleName)
                                        @php
                                            $rolePerms = $rolePermissionsMap[(int) $roleId] ?? [];
                                            $permCount = count($rolePerms);
                                        @endphp
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-xs">
                                                        {{ $roleId }}
                                                    </span>
                                                    <span class="font-semibold text-slate-800 text-sm">
                                                        {{ $roleName }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if((int) $roleId === 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200" title="Administrator memiliki seluruh 7 hak akses (Super-Admin)">
                                                        7 Akses (Full)
                                                    </span>
                                                @elseif((int) $roleId === 1)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200" title="Supervisor memiliki 4 hak akses standar keperawatan">
                                                        4 Akses (Default)
                                                    </span>
                                                @else
                                                    <button
                                                        type="button"
                                                        @click="openPermissionModal({{ $roleId }}, '{{ addslashes($roleName) }}', {{ json_encode($rolePerms) }})"
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold {{ $permCount > 0 ? 'bg-teal-50 text-teal-800 border border-teal-200 hover:bg-teal-100' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }} transition cursor-pointer"
                                                        title="Klik untuk melihat dan mengatur hak akses"
                                                    >
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                        </svg>
                                                        {{ $permCount > 0 ? "{$permCount} Izin" : '0 Izin (Atur)' }}
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                @if((int) $roleId > 1)
                                                    <div class="inline-flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            @click="openPermissionModal({{ $roleId }}, '{{ addslashes($roleName) }}', {{ json_encode($rolePerms) }})"
                                                            class="px-2.5 py-1 rounded-md text-xs font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition cursor-pointer"
                                                            title="Atur hak akses role ini"
                                                        >
                                                            Hak Akses
                                                        </button>
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
                                                                class="px-2.5 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer"
                                                            >
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-slate-400 italic">Terkunci</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Form Tambah / Perbarui Definisi Role -->
                        <form method="POST" action="{{ route('admin.roles.store-definition') }}" class="pt-3 border-t border-slate-200 space-y-4">
                            @csrf
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-bold uppercase tracking-wider text-slate-700">
                                    Tambah / Perbarui Definisi Role
                                </p>
                                <span class="text-xs text-slate-500">Gunakan ID Role &gt; 1 untuk role baru</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <div>
                                    <label for="role_id" class="block text-sm font-medium text-slate-600 mb-1">Kode Role (Angka)</label>
                                    <input
                                        type="number"
                                        id="role_id"
                                        name="role_id"
                                        min="0"
                                        max="99"
                                        required
                                        placeholder="Misal: 2"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm font-mono text-slate-900 focus:ring-2 focus:ring-teal-500"
                                    >
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="role_name" class="block text-sm font-medium text-slate-600 mb-1">Nama Role</label>
                                    <input
                                        type="text"
                                        id="role_name"
                                        name="role_name"
                                        maxlength="24"
                                        required
                                        placeholder="Contoh: KEPALA RUANG"
                                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 uppercase focus:ring-2 focus:ring-teal-500"
                                    >
                                </div>
                            </div>

                            <!-- Permissions Selection for New Role -->
                            <div class="space-y-2.5 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide">
                                        Hak Akses Awal Role:
                                    </label>
                                    <div class="flex items-center space-x-2 text-xs">
                                        <button type="button" @click="selectAllNewRole()" class="text-teal-600 hover:text-teal-800 font-semibold cursor-pointer">Pilih Semua</button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" @click="clearAllNewRole()" class="text-slate-500 hover:text-slate-700 cursor-pointer">Kosongkan</button>
                                    </div>
                                </div>

                                <div class="max-h-56 overflow-y-auto space-y-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    @foreach($groupedPermissions as $groupName => $perms)
                                        <div class="space-y-1.5">
                                            <div class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                                {{ $groupName }}
                                            </div>
                                            <div class="grid grid-cols-1 gap-2 pl-2">
                                                @foreach($perms as $pKey => $pMeta)
                                                    <label class="flex items-start space-x-2.5 p-2 rounded-lg bg-white border border-slate-200/70 hover:border-teal-300 transition cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            name="permissions[]"
                                                            value="{{ $pKey }}"
                                                            x-model="newRolePermissions"
                                                            class="w-4 h-4 mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                        >
                                                        <div class="text-sm">
                                                            <span class="font-medium text-slate-800">{{ $pMeta['label'] }}</span>
                                                            <span class="block text-xs text-slate-500 leading-tight mt-0.5">{{ $pMeta['description'] }}</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-xs text-slate-500 italic">
                                    * Role 0 (Admin) dan Role 1 (Supervisor) memiliki hak akses bawaan sistem yang terlindungi.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition cursor-pointer shadow-xs"
                            >
                                Simpan Definisi Role
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Berikan Role ke Pegawai yang Belum Terdaftar -->
            <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-2xs relative z-20">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 rounded-t-xl">
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                        2. Berikan Hak Akses kepada Pegawai Baru
                    </h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Pilih perawat dari data pegawai untuk diberi hak akses.
                    </p>
                </div>

                <div class="p-6">
                    <form 
                        method="POST" 
                        action="{{ route('admin.roles.assign-user') }}" 
                        class="space-y-4"
                        x-data="adminAssignEmployeeRole()"
                        @submit="handleSubmit($event)"
                    >
                        @csrf

                        <div class="relative" @click.outside="closeDropdown()">
                            <label for="assign_employee_search" class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                Cari Pegawai (NIP / Nama)
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="assign_employee_search"
                                    x-ref="employeeSearchInput"
                                    autocomplete="off"
                                    x-model="employeeSearch"
                                    @focus="isOpen = true; highlightIndex = 0"
                                    @input="isOpen = true; highlightIndex = 0; onSearchInput()"
                                    @keydown.down.prevent="highlightNext()"
                                    @keydown.up.prevent="highlightPrev()"
                                    @keydown.enter.prevent="selectHighlighted()"
                                    @keydown.escape="closeDropdown()"
                                    placeholder="Ketik NIP atau nama pegawai..." 
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 text-base focus:ring-2 focus:ring-teal-500 focus:border-teal-600 transition shadow-2xs"
                                >
                                <input type="hidden" name="nip" :value="selectedNip" required>

                                <!-- Dropdown List -->
                                <div 
                                    x-show="isOpen" 
                                    x-cloak
                                    class="absolute z-30 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-lg py-1 divide-y divide-slate-100"
                                >
                                    <template x-for="(emp, eIdx) in filteredEmployees" :key="emp.nip">
                                        <button
                                            type="button"
                                            @click="selectEmployee(emp)"
                                            @mouseenter="highlightIndex = eIdx"
                                            :class="{ 'bg-teal-50 text-teal-900 font-semibold': highlightIndex === eIdx, 'text-slate-800': highlightIndex !== eIdx }"
                                            class="w-full text-left px-4 py-2.5 text-sm flex items-center justify-between hover:bg-teal-50 transition cursor-pointer"
                                        >
                                            <span class="font-medium truncate" x-text="emp.name"></span>
                                            <span class="text-xs font-mono text-slate-500 ml-2 shrink-0" x-text="'(NIP: ' + emp.nip + ')'"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredEmployees.length === 0" class="px-4 py-3 text-sm text-slate-400 italic text-center">
                                        Pegawai tidak ditemukan
                                    </div>
                                </div>
                            </div>
                            <p x-show="employeeError" x-cloak class="text-sm text-rose-600 mt-1.5 font-medium" x-text="employeeError"></p>
                            <p class="text-xs text-slate-500 mt-1.5">
                                Nama pegawai terisi otomatis.
                            </p>
                        </div>

                        <div>
                            <label for="assign_role" class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                Pilih Hak Akses (Role)
                            </label>
                            <select
                                id="assign_role"
                                name="app_roles"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 text-base focus:ring-2 focus:ring-teal-500 focus:border-teal-600"
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
                                class="inline-flex items-center px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold shadow-xs transition cursor-pointer"
                            >
                                + Daftarkan &amp; Berikan Role Pegawai
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengguna Terdaftar -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs relative z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                        3. Daftar Pengguna Terdaftar ({{ $appRoles->count() }} Pegawai)
                    </h2>
                    <p class="text-sm text-slate-500">
                        Ubah role atau cabut hak akses pengguna di bawah ini.
                    </p>
                </div>

                <form method="GET" action="{{ route('admin.roles.index') }}" class="flex flex-wrap items-center gap-2.5">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari NIP atau Nama..."
                        class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-800"
                    >
                    <select name="role_filter" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-800">
                        <option value="">Semua Role</option>
                        @foreach($availableRoles as $roleId => $roleName)
                            <option value="{{ $roleId }}" {{ request('role_filter') !== null && (string) request('role_filter') === (string) $roleId ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold cursor-pointer">
                        Cari
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-base">
                    <thead>
                        <tr class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="px-6 py-3.5">NIP</th>
                            <th class="px-6 py-3.5">Nama Pegawai / Perawat</th>
                            <th class="px-6 py-3.5">Role Saat Ini</th>
                            <th class="px-6 py-3.5 text-right">Kelola Role &amp; Akses</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($appRoles as $userRole)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 font-mono text-sm font-bold text-slate-900">
                                    {{ $userRole->nip }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $userRole->displayName() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                        {{ (int) $userRole->app_roles === 0 ? 'bg-purple-50 text-purple-800 border border-purple-200' : ((int) $userRole->app_roles === 1 ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-teal-50 text-teal-800 border border-teal-200') }}
                                    ">
                                        {{ $userRole->roleLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.roles.update-user', $userRole->nip) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select
                                                name="app_roles"
                                                aria-label="Ubah role untuk {{ $userRole->displayName() }}"
                                                class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-900"
                                            >
                                                @foreach($availableRoles as $roleId => $roleName)
                                                    <option value="{{ $roleId }}" {{ (int) $userRole->app_roles === (int) $roleId ? 'selected' : '' }}>
                                                        {{ $roleId }} - {{ $roleName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button
                                                type="submit"
                                                class="px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition cursor-pointer"
                                            >
                                                Simpan
                                            </button>
                                        </form>

                                        @if(trim((string) auth()->user()?->nip) !== trim((string) $userRole->nip))
                                            <form
                                                method="POST"
                                                action="{{ route('admin.roles.destroy-user', $userRole->nip) }}"
                                                onsubmit="return confirm('Cabut dan hapus hak akses untuk {{ $userRole->displayName() }} (NIP: {{ $userRole->nip }})?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-sm font-semibold transition cursor-pointer"
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

        <!-- Modal Kelola Hak Akses Role -->
        <div
            x-show="modalOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Background backdrop -->
            <div
                x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="closePermissionModal()"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    x-show="modalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-200"
                >
                    <form :action="modalActionUrl" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Modal Header -->
                        <div class="px-7 py-5 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-slate-900" id="modal-title">
                                    Kelola Hak Akses Role: <span class="text-teal-700" x-text="modalRoleName"></span>
                                </h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    Kode Role: <span class="font-mono font-bold" x-text="modalRoleId"></span> &middot; Tentukan modul dan tindakan apa saja yang dapat diakses oleh role ini.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="closePermissionModal()"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-2 transition cursor-pointer"
                            >
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-7 py-6 space-y-6 max-h-[65vh] overflow-y-auto">
                            <!-- Quick Actions -->
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                                <span class="text-sm font-semibold text-slate-600">Daftar Modul &amp; Hak Akses:</span>
                                <div class="flex items-center space-x-2.5 text-sm">
                                    <button
                                        type="button"
                                        @click="selectAllModalPermissions()"
                                        class="text-teal-600 hover:text-teal-800 font-semibold cursor-pointer"
                                    >
                                        Pilih Semua
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        type="button"
                                        @click="clearAllModalPermissions()"
                                        class="text-slate-500 hover:text-slate-700 cursor-pointer"
                                    >
                                        Kosongkan
                                    </button>
                                </div>
                            </div>

                            @foreach($groupedPermissions as $groupName => $perms)
                                <div class="space-y-2.5">
                                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                                        {{ $groupName }}
                                    </h4>
                                    <div class="grid grid-cols-1 gap-2.5 pl-3">
                                        @foreach($perms as $pKey => $pMeta)
                                            <label class="flex items-start space-x-3.5 p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50/80 transition cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $pKey }}"
                                                    x-model="modalPermissions"
                                                    class="w-4 h-4 mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                >
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-base font-bold text-slate-800">{{ $pMeta['label'] }}</span>
                                                    </div>
                                                    <p class="text-sm text-slate-500 mt-0.5">{{ $pMeta['description'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-7 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
                            <button
                                type="button"
                                @click="closePermissionModal()"
                                class="px-5 py-2.5 rounded-xl text-base font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2.5 rounded-xl text-base font-bold text-white bg-teal-600 hover:bg-teal-700 transition cursor-pointer shadow-xs"
                            >
                                Simpan Perubahan Hak Akses
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function adminRolesPage() {
            return {
                modalOpen: false,
                modalRoleId: null,
                modalRoleName: '',
                modalActionUrl: '',
                modalPermissions: [],
                newRolePermissions: [],

                openPermissionModal(roleId, roleName, currentPermissions) {
                    this.modalRoleId = roleId;
                    this.modalRoleName = roleName;
                    this.modalPermissions = Array.isArray(currentPermissions) ? [...currentPermissions] : [];
                    this.modalActionUrl = "{{ url('/admin/roles/definitions') }}/" + roleId + "/permissions";
                    this.modalOpen = true;
                },

                closePermissionModal() {
                    this.modalOpen = false;
                },

                selectAllModalPermissions() {
                    this.modalPermissions = @json(array_keys(\App\Models\RolePermission::AVAILABLE_PERMISSIONS));
                },

                clearAllModalPermissions() {
                    this.modalPermissions = [];
                },

                selectAllNewRole() {
                    this.newRolePermissions = @json(array_keys(\App\Models\RolePermission::AVAILABLE_PERMISSIONS));
                },

                clearAllNewRole() {
                    this.newRolePermissions = [];
                }
            };
        }

        function adminAssignEmployeeRole() {
            const allEmployees = @json($unregisteredEmployeesJson);
            const oldNip = @json(old('nip', ''));
            const matchedOld = allEmployees.find(e => e.nip === oldNip);

            return {
                employees: allEmployees,
                employeeSearch: matchedOld ? `${matchedOld.name} (NIP: ${matchedOld.nip})` : (oldNip ? `NIP: ${oldNip}` : ''),
                selectedNip: oldNip,
                isOpen: false,
                highlightIndex: 0,
                employeeError: '',

                get filteredEmployees() {
                    const q = (this.employeeSearch || '').trim().toLowerCase();
                    if (!q) return this.employees;
                    return this.employees.filter(e => 
                        e.name.toLowerCase().includes(q) || 
                        (e.nip && e.nip.toLowerCase().includes(q))
                    );
                },

                onSearchInput() {
                    this.employeeError = '';
                    if (!this.employeeSearch || this.employeeSearch.trim() === '') {
                        this.selectedNip = '';
                    }
                },

                selectEmployee(emp) {
                    this.selectedNip = emp.nip;
                    this.employeeSearch = `${emp.name} (NIP: ${emp.nip})`;
                    this.employeeError = '';
                    this.isOpen = false;
                },

                selectHighlighted() {
                    if (this.filteredEmployees.length > 0 && this.highlightIndex >= 0 && this.highlightIndex < this.filteredEmployees.length) {
                        this.selectEmployee(this.filteredEmployees[this.highlightIndex]);
                    } else if (this.filteredEmployees.length > 0) {
                        this.selectEmployee(this.filteredEmployees[0]);
                    }
                },

                highlightNext() {
                    if (!this.isOpen) {
                        this.isOpen = true;
                        this.highlightIndex = 0;
                        return;
                    }
                    if (this.highlightIndex < this.filteredEmployees.length - 1) {
                        this.highlightIndex++;
                    }
                },

                highlightPrev() {
                    if (this.highlightIndex > 0) {
                        this.highlightIndex--;
                    }
                },

                closeDropdown() {
                    this.isOpen = false;
                    if (!this.employeeSearch || this.employeeSearch.trim() === '') {
                        this.selectedNip = '';
                        return;
                    }
                    const exact = this.employees.find(e => 
                        e.nip.toLowerCase() === this.employeeSearch.trim().toLowerCase() ||
                        e.name.toLowerCase() === this.employeeSearch.trim().toLowerCase()
                    );
                    if (exact) {
                        this.selectEmployee(exact);
                    } else if (!this.selectedNip) {
                        this.employeeSearch = '';
                    }
                },

                handleSubmit(event) {
                    if (!this.selectedNip && this.filteredEmployees.length > 0) {
                        const exact = this.employees.find(e => 
                            e.nip.toLowerCase() === this.employeeSearch.trim().toLowerCase() ||
                            e.name.toLowerCase() === this.employeeSearch.trim().toLowerCase()
                        );
                        if (exact) {
                            this.selectEmployee(exact);
                        } else if (this.filteredEmployees.length === 1) {
                            this.selectEmployee(this.filteredEmployees[0]);
                        }
                    }

                    if (!this.selectedNip || this.selectedNip.trim() === '') {
                        event.preventDefault();
                        this.employeeError = 'Silakan pilih salah satu pegawai dari hasil pencarian.';
                        this.$refs.employeeSearchInput?.focus();
                        return;
                    }
                }
            };
        }
    </script>
</x-layouts.app>
