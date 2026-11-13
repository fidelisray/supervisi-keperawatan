<x-layouts.app>
    <x-slot name="title">Manajemen Role & Hak Akses Pengguna</x-slot>

    @php
        $unregisteredEmployeesJson = $unregisteredEmployees
            ->map(
                fn($emp) => [
                    'nip' => $emp->nip,
                    'name' => $emp->nama,
                ],
            )
            ->values();
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

            <div class="flex items-center shrink-0">
                <button type="button" @click="openAssignUserModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 active:scale-98 transition shadow-xs focus:ring-2 focus:ring-teal-500 focus:outline-none cursor-pointer">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Daftarkan Pegawai</span>
                </button>
            </div>
        </div>

        @if ($errors->any() && !in_array(old('_modal'), ['create_role', 'assign_user', 'rename_role'], true))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-base font-medium"
                role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Panel 1: Daftar Definisi Role & Hak Akses -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div
                class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                        1. Daftar Definisi Role ({{ count($availableRoles) }} Role)
                    </h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Kelola daftar role, konfigurasi izin modul, dan ubah nama custom role.
                    </p>
                </div>

                <button type="button" @click="openCreateRoleModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-500 cursor-pointer shrink-0">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Role Baru</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr
                            class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="px-6 py-3.5">Role</th>
                            <th class="px-6 py-3.5">Hak Akses</th>
                            <th class="px-6 py-3.5">Pengguna Aktif</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($availableRoles as $roleId => $roleName)
                            @php
                                $rolePerms = $rolePermissionsMap[(int) $roleId] ?? [];
                                $permCount = count($rolePerms);
                                $assignedCount = $roleUserCounts[(int) $roleId] ?? 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded text-xs">
                                            {{ $roleId }}
                                        </span>
                                        <span class="font-semibold text-slate-800 text-sm">
                                            {{ $roleName }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    @if ((int) $roleId === 0)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200"
                                            title="Administrator memiliki seluruh 7 hak akses (Super-Admin)">
                                            7 Akses (Full)
                                        </span>
                                    @elseif((int) $roleId === 1)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200"
                                            title="Supervisor memiliki 4 hak akses standar keperawatan">
                                            4 Akses (Default)
                                        </span>
                                    @else
                                        <button type="button"
                                            @click="openPermissionModal({{ $roleId }}, '{{ addslashes($roleName) }}', {{ json_encode($rolePerms) }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold shadow-2xs {{ $permCount > 0 ? 'bg-teal-50 text-teal-800 border border-teal-300 hover:bg-teal-100' : 'bg-amber-50 text-amber-800 border border-amber-300 hover:bg-amber-100' }} transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500"
                                            title="Klik untuk mengatur hak akses role ini">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <span>Atur Izin ({{ $permCount }})</span>
                                        </button>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $assignedCount }} Pegawai
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    @if ((int) $roleId > 1)
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button"
                                                @click="openRenameModal({{ $roleId }}, '{{ addslashes($roleName) }}')"
                                                class="px-2.5 py-1 rounded-md text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition cursor-pointer"
                                                title="Ubah nama role ini">
                                                Edit
                                            </button>
                                            <form method="POST"
                                                action="{{ route('admin.roles.destroy-definition', $roleId) }}"
                                                onsubmit="return confirm('Hapus definisi role {{ addslashes($roleName) }}?');"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-2.5 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer">
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

            <div class="px-6 py-3 bg-slate-50/60 border-t border-slate-100">
                <p class="text-xs text-slate-500 italic">
                    * Role 0 (Administrator) dan Role 1 (Supervisor Keperawatan) merupakan role bawaan sistem yang
                    terlindungi.
                </p>
            </div>
        </div>

        <!-- Panel 2: Tabel Daftar Pengguna Terdaftar -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs relative z-10 overflow-hidden">
            <div
                class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-800">
                        2. Daftar Pengguna Terdaftar ({{ $appRoles->total() }} Pegawai)
                    </h2>
                    <p class="text-sm text-slate-500">
                        Ubah role atau cabut hak akses pengguna di bawah ini.
                    </p>
                </div>

                <form method="GET" action="{{ route('admin.roles.index') }}"
                    class="flex flex-wrap items-center gap-2.5">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari NIP atau Nama..."
                        class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-800">
                    <select name="role_filter"
                        class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-800">
                        <option value="">Semua Role</option>
                        @foreach ($availableRoles as $roleId => $roleName)
                            <option value="{{ $roleId }}"
                                {{ request('role_filter') !== null && (string) request('role_filter') === (string) $roleId ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold cursor-pointer transition">
                        Cari
                    </button>
                    @if (request()->filled('search') || request()->filled('role_filter'))
                        <a href="{{ route('admin.roles.index') }}"
                            class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-base">
                    <thead>
                        <tr
                            class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="px-6 py-3.5 text-center w-16">No</th>
                            <th class="px-6 py-3.5">NIP</th>
                            <th class="px-6 py-3.5">Nama Pegawai / Perawat</th>
                            <th class="px-6 py-3.5">Role Saat Ini</th>
                            <th class="px-6 py-3.5 text-right">Kelola Role &amp; Akses</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($appRoles as $userRole)
                            @php
                                $isCurrentUser = trim((string) auth()->user()?->nip) === trim((string) $userRole->nip);
                                $isProtectedAdmin =
                                    !auth()->user()?->isAdmin() &&
                                    (int) $userRole->app_roles === \App\Models\AppRole::ROLE_ADMIN;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 text-center font-semibold text-slate-600 whitespace-nowrap">
                                    {{ ($appRoles->firstItem() ?? 1) + $loop->index }}
                                </td>
                                <td class="px-6 py-4 font-mono text-sm font-bold text-slate-900">
                                    {{ $userRole->nip }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $userRole->displayName() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                        {{ (int) $userRole->app_roles === 0 ? 'bg-purple-50 text-purple-800 border border-purple-200' : ((int) $userRole->app_roles === 1 ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-teal-50 text-teal-800 border border-teal-200') }}
                                    ">
                                        {{ $userRole->roleLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if ($isCurrentUser)
                                        <span
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            Akun Anda (Aktif)
                                        </span>
                                    @elseif($isProtectedAdmin)
                                        <span
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            Terlindungi (Administrator)
                                        </span>
                                    @else
                                        <div class="inline-flex items-center justify-end gap-2"
                                            x-data="{ selectedRole: '{{ (int) $userRole->app_roles }}', initialRole: '{{ (int) $userRole->app_roles }}' }">
                                            <form method="POST"
                                                action="{{ route('admin.roles.update-user', $userRole->nip) }}"
                                                class="inline-flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="app_roles" x-model="selectedRole"
                                                    aria-label="Ubah role untuk {{ $userRole->displayName() }}"
                                                    class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm text-slate-900">
                                                    @foreach ($availableRoles as $roleId => $roleName)
                                                        @if ((int) $roleId !== \App\Models\AppRole::ROLE_ADMIN || auth()->user()?->isAdmin())
                                                            <option value="{{ $roleId }}"
                                                                {{ (int) $userRole->app_roles === (int) $roleId ? 'selected' : '' }}>
                                                                {{ $roleId }} - {{ $roleName }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <button type="submit" :disabled="selectedRole === initialRole"
                                                    class="px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition cursor-pointer disabled:opacity-45 disabled:cursor-not-allowed">
                                                    Simpan
                                                </button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('admin.roles.destroy-user', $userRole->nip) }}"
                                                onsubmit="return confirm('Cabut dan hapus hak akses untuk {{ addslashes($userRole->displayName()) }} (NIP: {{ $userRole->nip }})?');"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-sm font-semibold transition cursor-pointer"
                                                    title="Hapus hak akses pengguna">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Tidak ada pegawai terdaftar yang sesuai dengan kriteria pencarian atau filter saat
                                    ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($appRoles->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $appRoles->links(data: ['itemLabel' => 'pegawai']) }}
                </div>
            @endif
        </div>

        <!-- Modal Kelola Hak Akses Role -->
        <div x-show="modalOpen" x-cloak @keydown.escape.window="closePermissionModal()"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <!-- Background backdrop -->
            <div x-show="modalOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="closePermissionModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="modalOpen" x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-200">
                    <form :action="modalActionUrl" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Modal Header -->
                        <div
                            class="px-7 py-5 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-slate-900" id="modal-title">
                                    Kelola Hak Akses Role: <span class="text-teal-700" x-text="modalRoleName"></span>
                                </h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    Kode Role: <span class="font-mono font-bold" x-text="modalRoleId"></span> &middot;
                                    Tentukan modul dan tindakan apa saja yang dapat diakses oleh role ini.
                                </p>
                            </div>
                            <button type="button" @click="closePermissionModal()"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-2 transition cursor-pointer">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-7 py-6 space-y-6 max-h-[65vh] overflow-y-auto">
                            <!-- Quick Actions -->
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                                <span class="text-sm font-semibold text-slate-600">Daftar Modul &amp; Hak Akses:</span>
                                <div class="flex items-center space-x-2.5 text-sm">
                                    <button type="button" @click="selectAllModalPermissions()"
                                        class="text-teal-600 hover:text-teal-800 font-semibold cursor-pointer">
                                        Pilih Semua
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="clearAllModalPermissions()"
                                        class="text-slate-500 hover:text-slate-700 cursor-pointer">
                                        Kosongkan
                                    </button>
                                </div>
                            </div>

                            @foreach ($groupedPermissions as $groupName => $perms)
                                <div class="space-y-2.5">
                                    <h4
                                        class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                                        {{ $groupName }}
                                    </h4>
                                    <div class="grid grid-cols-1 gap-2.5 pl-3">
                                        @foreach ($perms as $pKey => $pMeta)
                                            <label
                                                class="flex items-start space-x-3.5 p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50/80 transition cursor-pointer">
                                                <input type="checkbox" name="permissions[]"
                                                    value="{{ $pKey }}" x-model="modalPermissions"
                                                    class="w-4 h-4 mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <span
                                                            class="text-base font-bold text-slate-800">{{ $pMeta['label'] }}</span>
                                                    </div>
                                                    <p class="text-sm text-slate-500 mt-0.5">
                                                        {{ $pMeta['description'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Modal Footer -->
                        <div
                            class="px-7 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
                            <button type="button" @click="closePermissionModal()"
                                class="px-5 py-2.5 rounded-xl text-base font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 rounded-xl text-base font-bold text-white bg-teal-600 hover:bg-teal-700 transition cursor-pointer shadow-xs">
                                Simpan Perubahan Hak Akses
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Ubah Nama Role -->
        <div x-show="renameModalOpen" x-cloak @keydown.escape.window="closeRenameModal()"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="rename-modal-title" role="dialog"
            aria-modal="true">
            <div x-show="renameModalOpen" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeRenameModal()">
            </div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="renameModalOpen" x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
                    <form :action="renameActionUrl" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_modal" value="rename_role">
                        <input type="hidden" name="_rename_role_id" :value="renameRoleId">

                        <div
                            class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="rename-modal-title">
                                    Ubah Nama Role
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kode Role: <span class="font-mono font-bold text-slate-700"
                                        x-text="renameRoleId"></span> (tetap / tidak berubah)
                                </p>
                            </div>
                            <button type="button" @click="closeRenameModal()"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-3">
                            @if ($errors->any() && old('_modal') === 'rename_role')
                                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm font-medium"
                                    role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="rename_role_name"
                                        class="block text-sm font-medium text-slate-700">Nama Role Baru</label>
                                    <span class="text-xs text-slate-400">Maks. 24 karakter</span>
                                </div>
                                <input type="text" id="rename_role_name" name="role_name"
                                    x-model="renameRoleName" x-ref="renameRoleInput" maxlength="24" required
                                    placeholder="Contoh: KEPALA RUANG"
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 uppercase focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
                            <button type="button" @click="closeRenameModal()"
                                class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 transition cursor-pointer shadow-xs">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Role Baru -->
        <div x-show="createRoleModalOpen" x-cloak @keydown.escape.window="closeCreateRoleModal()"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="create-role-modal-title" role="dialog"
            aria-modal="true">
            <div x-show="createRoleModalOpen" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="closeCreateRoleModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="createRoleModalOpen" x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    <form action="{{ route('admin.roles.store-definition') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_modal" value="create_role">

                        <div
                            class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="create-role-modal-title">
                                    Tambah Role Baru
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Atur nama role dan hak akses yang akan diberikan oleh role yang dibuat.
                                </p>
                            </div>
                            <button type="button" @click="closeCreateRoleModal()"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            @if ($errors->any() && old('_modal') === 'create_role')
                                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm font-medium"
                                    role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="role_name" class="block text-sm font-medium text-slate-700">Nama
                                        Role</label>
                                    <span class="text-xs text-slate-400">Maks. 24 karakter</span>
                                </div>
                                <input type="text" id="role_name" name="role_name" x-ref="createRoleNameInput"
                                    value="{{ old('_modal') === 'create_role' ? old('role_name') : '' }}"
                                    maxlength="24" required placeholder="Contoh: KEPALA BIDANG"
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 uppercase focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div class="pt-1 border-t border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-600">Hak Akses Awal
                                        Role:</p>
                                    <div class="flex items-center gap-2 text-xs">
                                        <button type="button" @click="selectAllNewRole()"
                                            class="text-teal-700 font-semibold hover:underline cursor-pointer">Pilih
                                            Semua</button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" @click="clearAllNewRole()"
                                            class="text-slate-500 font-medium hover:underline cursor-pointer">Kosongkan</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    @foreach (\App\Models\RolePermission::AVAILABLE_PERMISSIONS as $permKey => $permMeta)
                                        <label
                                            class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 hover:border-teal-300 hover:bg-teal-50/30 transition cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}"
                                                x-model="newRolePermissions"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                            <span
                                                class="text-xs font-medium text-slate-700 leading-snug">{{ $permMeta['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
                            <button type="button" @click="closeCreateRoleModal()"
                                class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 transition cursor-pointer shadow-xs">
                                Simpan Role Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Berikan Hak Akses Pegawai Baru -->
        <div x-show="assignUserModalOpen" x-cloak @keydown.escape.window="closeAssignUserModal()"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="assign-user-modal-title" role="dialog"
            aria-modal="true">
            <div x-show="assignUserModalOpen" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="closeAssignUserModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="assignUserModalOpen" x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-visible rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    <form x-data="adminAssignEmployeeRole()" action="{{ route('admin.roles.assign-user') }}" method="POST"
                        @submit="handleSubmit($event)">
                        @csrf
                        <input type="hidden" name="_modal" value="assign_user">
                        <input type="hidden" name="nip" :value="selectedNip">

                        <div
                            class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 rounded-t-2xl flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="assign-user-modal-title">
                                    Berikan Hak Akses Pegawai Baru
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Cari pegawai dan tentukan role aplikasi yang diberikan.
                                </p>
                            </div>
                            <button type="button" @click="closeAssignUserModal()"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            @if ($errors->any() && old('_modal') === 'assign_user')
                                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm font-medium"
                                    role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <!-- Searchable Employee Autocomplete -->
                            <div>
                                <label for="assign_employee_search"
                                    class="block text-sm font-medium text-slate-700 mb-1">
                                    Cari &amp; Pilih Pegawai
                                </label>
                                <div class="relative" @click.outside="closeDropdown()">
                                    <div class="relative">
                                        <input type="text" id="assign_employee_search" x-ref="employeeSearchInput"
                                            x-model="employeeSearch" @focus="isOpen = true"
                                            @input="onSearchInput(); isOpen = true; highlightIndex = 0"
                                            @keydown.arrow-down.prevent="highlightNext()"
                                            @keydown.arrow-up.prevent="highlightPrev()"
                                            @keydown.enter.prevent="selectHighlighted()"
                                            @keydown.escape="if (isOpen) { $event.stopPropagation(); closeDropdown(); }"
                                            autocomplete="off" placeholder="Ketik Nama atau NIP pegawai..."
                                            class="w-full px-3.5 py-2.5 pr-9 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus:ring-2 focus:ring-teal-500">
                                        <button type="button" x-show="employeeSearch && employeeSearch.length > 0"
                                            @click="selectedNip = ''; employeeSearch = ''; isOpen = true; $refs.employeeSearchInput.focus()"
                                            class="absolute inset-y-0 right-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                                            aria-label="Hapus pilihan pegawai">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Autocomplete Dropdown -->
                                    <div x-show="isOpen" x-cloak
                                        class="absolute left-0 right-0 z-30 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto divide-y divide-slate-100">
                                        <template x-if="filteredEmployees.length === 0">
                                            <div class="px-4 py-3 text-xs text-slate-500 text-center">
                                                Pegawai tidak ditemukan atau seluruhnya sudah terdaftar.
                                            </div>
                                        </template>
                                        <template x-for="(emp, idx) in filteredEmployees.slice(0, 80)"
                                            :key="emp.nip">
                                            <button type="button" @click="selectEmployee(emp)"
                                                :class="{
                                                    'bg-teal-50 text-teal-900': idx === highlightIndex ||
                                                        selectedNip === emp.nip,
                                                    'text-slate-800 hover:bg-slate-50': idx !== highlightIndex &&
                                                        selectedNip !== emp.nip
                                                }"
                                                class="w-full px-3.5 py-2.5 text-left flex items-center justify-between gap-2 transition cursor-pointer">
                                                <span class="text-xs font-semibold truncate" x-text="emp.name"></span>
                                                <span
                                                    class="font-mono text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded shrink-0"
                                                    x-text="'NIP: ' + emp.nip"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <p x-show="employeeError" x-text="employeeError"
                                    class="text-xs font-medium text-rose-600 mt-1.5"></p>
                            </div>

                            <!-- Role Selection -->
                            <div>
                                <label for="assign_role" class="block text-sm font-medium text-slate-700 mb-1">
                                    Pilih Hak Akses (Role)
                                </label>
                                <select id="assign_role" name="app_roles" x-model="assignRoleId" required
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus:ring-2 focus:ring-teal-500">
                                    @foreach ($availableRoles as $roleId => $roleName)
                                        <option value="{{ $roleId }}">{{ $roleId }} —
                                            {{ $roleName }}</option>
                                    @endforeach
                                </select>

                                <!-- Live Preview Hak Akses Role Terpilih -->
                                <div class="mt-3 p-3 rounded-lg bg-slate-50 border border-slate-200/80">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                        Pratinjau Hak Akses Role:
                                    </p>
                                    <template x-if="Number(assignRoleId) === 0">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            7 Akses Penuh (Super-Admin)
                                        </span>
                                    </template>
                                    <template
                                        x-if="Number(assignRoleId) !== 0 && selectedRolePermissionLabels.length > 0">
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="label in selectedRolePermissionLabels"
                                                :key="label">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-800 border border-teal-200"
                                                    x-text="label"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template
                                        x-if="Number(assignRoleId) !== 0 && selectedRolePermissionLabels.length === 0">
                                        <span class="text-xs text-slate-400 italic">Role ini belum memiliki hak akses
                                            modul aktif.</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl flex items-center justify-end space-x-3">
                            <button type="button" @click="closeAssignUserModal()"
                                class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 transition cursor-pointer shadow-xs">
                                Daftarkan User
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
                createRoleModalOpen: @json($errors->any() && old('_modal') === 'create_role'),
                assignUserModalOpen: @json($errors->any() && old('_modal') === 'assign_user'),
                modalOpen: false,
                modalRoleId: null,
                modalRoleName: '',
                modalActionUrl: '',
                modalPermissions: [],
                renameModalOpen: @json($errors->any() && old('_modal') === 'rename_role'),
                renameRoleId: @json($errors->any() && old('_modal') === 'rename_role' ? old('_rename_role_id') : null),
                renameRoleName: @json($errors->any() && old('_modal') === 'rename_role' ? old('role_name', '') : ''),
                renameActionUrl: @json(
                    $errors->any() && old('_modal') === 'rename_role' && is_numeric(old('_rename_role_id'))
                        ? url('/admin/roles/definitions/' . (int) old('_rename_role_id'))
                        : ''),
                newRolePermissions: @json(old('_modal') === 'create_role' ? array_values((array) old('permissions', [])) : []),

                openCreateRoleModal() {
                    this.createRoleModalOpen = true;
                    this.$nextTick(() => this.$refs.createRoleNameInput?.focus());
                },

                closeCreateRoleModal() {
                    this.createRoleModalOpen = false;
                },

                openAssignUserModal() {
                    this.assignUserModalOpen = true;
                    this.$nextTick(() => document.getElementById('assign_employee_search')?.focus());
                },

                closeAssignUserModal() {
                    this.assignUserModalOpen = false;
                },

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

                openRenameModal(roleId, currentRoleName) {
                    this.renameRoleId = roleId;
                    this.renameRoleName = currentRoleName;
                    this.renameActionUrl = "{{ url('/admin/roles/definitions') }}/" + roleId;
                    this.renameModalOpen = true;
                    this.$nextTick(() => this.$refs.renameRoleInput?.focus());
                },

                closeRenameModal() {
                    this.renameModalOpen = false;
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
            const isAssignModalValidationError = @json($errors->any() && old('_modal') === 'assign_user');
            const oldNip = isAssignModalValidationError ? @json((string) old('nip', '')) : '';
            const matchedOld = allEmployees.find(e => e.nip === oldNip);
            const rolePermissionsMap = @json($rolePermissionsMap);
            const permissionLabels = @json($permissionLabels);

            return {
                employees: allEmployees,
                employeeSearch: matchedOld ? `${matchedOld.name} (NIP: ${matchedOld.nip})` : (oldNip ? `NIP: ${oldNip}` :
                    ''),
                selectedNip: oldNip,
                assignRoleId: isAssignModalValidationError ? @json((string) old('app_roles', '1')) : '1',
                isOpen: false,
                highlightIndex: 0,
                employeeError: '',

                get selectedRolePermissionLabels() {
                    const keys = rolePermissionsMap[Number(this.assignRoleId)] || [];
                    return keys.map(k => permissionLabels[k] || k);
                },

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
                    const visible = this.filteredEmployees.slice(0, 80);
                    if (visible.length > 0 && this.highlightIndex >= 0 && this.highlightIndex < visible.length) {
                        this.selectEmployee(visible[this.highlightIndex]);
                    } else if (visible.length > 0) {
                        this.selectEmployee(visible[0]);
                    }
                },

                highlightNext() {
                    const visibleLength = Math.min(this.filteredEmployees.length, 80);
                    if (!this.isOpen) {
                        this.isOpen = true;
                        this.highlightIndex = 0;
                        return;
                    }
                    if (this.highlightIndex < visibleLength - 1) {
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
