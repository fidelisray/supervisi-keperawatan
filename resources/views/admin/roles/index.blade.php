<x-layouts.app>
    <x-slot name="title">Manajemen Role & Hak Akses Pengguna</x-slot>

    @php
        $unregisteredEmployeesJson = $unregisteredEmployees
            ->map(
                fn($emp) => [
                    'nip' => trim((string) $emp->nip),
                    'name' => trim((string) $emp->nama),
                ],
            )
            ->values();

        $isCurrentUserAdmin = (bool) auth()->user()?->isAdmin();
        $assignableRoles = collect($availableRoles)
            ->filter(
                fn(string $roleName, int|string $roleId): bool => $isCurrentUserAdmin ||
                    (int) $roleId !== \App\Models\AppRole::ROLE_ADMIN,
            )
            ->all();

        $defaultAssignRoleId = (string) (array_key_exists(\App\Models\AppRole::ROLE_SUPERVISOR, $assignableRoles)
            ? \App\Models\AppRole::ROLE_SUPERVISOR
            : (array_key_first($assignableRoles) ?? \App\Models\AppRole::ROLE_SUPERVISOR));

        $oldAssignRoleRaw = old('app_roles');
        $oldAssignRoleStr = is_string($oldAssignRoleRaw) || is_int($oldAssignRoleRaw)
            ? trim((string) $oldAssignRoleRaw)
            : '';
        $initialAssignRoleId =
            $errors->any() &&
            old('_modal') === 'assign_user' &&
            $oldAssignRoleStr !== '' &&
            ctype_digit($oldAssignRoleStr) &&
            array_key_exists((int) $oldAssignRoleStr, $assignableRoles)
                ? (string) ((int) $oldAssignRoleStr)
                : $defaultAssignRoleId;

        $assignableRoleMeta = collect($assignableRoles)
            ->mapWithKeys(
                fn(string $roleName, int|string $roleId): array => [
                    (int) $roleId => [
                        'name' => $roleName,
                        'user_count' => (int) ($roleUserCounts[(int) $roleId] ?? 0),
                    ],
                ],
            )
            ->all();

        $totalPermissionsCount = count(\App\Models\RolePermission::AVAILABLE_PERMISSIONS);
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
                <button type="button" @click="openAssignUserModal($event)"
                    class="inline-flex items-center justify-center gap-2 min-h-11 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 active:scale-98 transition shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-2 cursor-pointer">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        aria-hidden="true">
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

                <button type="button" @click="openCreateRoleModal($event)"
                    class="inline-flex items-center justify-center gap-1.5 min-h-11 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-2 cursor-pointer shrink-0">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        aria-hidden="true">
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
                                            @click="openPermissionModal({{ (int) $roleId }}, @js($roleName), @js($rolePerms), $event)"
                                            class="inline-flex items-center gap-1.5 min-h-11 px-3 py-2 rounded-lg text-xs font-semibold shadow-2xs {{ $permCount > 0 ? 'bg-teal-50 text-teal-800 border border-teal-700 hover:bg-teal-100' : 'bg-amber-50 text-amber-950 border border-amber-700 hover:bg-amber-100' }} transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700"
                                            title="Klik untuk mengatur hak akses role ini">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" aria-hidden="true">
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
                                                @click="openRenameModal({{ (int) $roleId }}, @js($roleName), $event)"
                                                class="inline-flex items-center justify-center min-h-11 px-3 py-2 rounded-lg text-xs font-semibold text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-700 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer"
                                                title="Ubah nama role ini">
                                                Edit
                                            </button>
                                            <form method="POST"
                                                action="{{ route('admin.roles.destroy-definition', $roleId) }}"
                                                onsubmit="return confirm(@js('Hapus definisi role '.$roleName.'?'));"
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
                                                onsubmit="return confirm(@js('Cabut dan hapus hak akses untuk '.$userRole->displayName().' (NIP: '.$userRole->nip.')?'));"
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
            @keydown.window="if (modalOpen && $event.key === 'Tab') trapFocusInRef($event, 'permissionModalPanel')"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            aria-describedby="permission-modal-desc" role="dialog" aria-modal="true">
            <div x-show="modalOpen" @click="closePermissionModal()"
                x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/55 transition-opacity motion-reduce:transition-none"
                aria-hidden="true"></div>

            <div @click.self="closePermissionModal()"
                class="relative flex min-h-full items-center justify-center p-2 sm:p-6">
                <div x-show="modalOpen" x-ref="permissionModalPanel" tabindex="-1"
                    x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative flex flex-col w-full max-h-[calc(100dvh-1rem)] sm:max-h-[90dvh] sm:max-w-3xl overflow-hidden rounded-2xl bg-white text-left shadow-2xl border border-slate-200 transition-all motion-reduce:transition-none focus:outline-none">
                    <form :action="modalActionUrl" method="POST" class="flex flex-col flex-1 min-h-0">
                        @csrf
                        @method('PUT')

                        <!-- Modal Header -->
                        <div
                            class="bg-blue-900 px-4 py-3.5 sm:px-6 sm:py-4 text-white shrink-0 flex items-start sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-bold tracking-tight text-white break-words"
                                    id="modal-title">
                                    Kelola Hak Akses Role: <span class="text-teal-200" x-text="modalRoleName"></span>
                                </h3>
                                <p id="permission-modal-desc" class="text-xs sm:text-sm text-blue-100 mt-1">
                                    Kode Role: <span class="font-mono font-bold text-white"
                                        x-text="modalRoleId"></span> &middot;
                                    Tentukan modul dan tindakan apa saja yang dapat diakses oleh role ini.
                                </p>
                            </div>
                            <button type="button" @click="closePermissionModal()"
                                class="min-w-11 min-h-11 inline-flex items-center justify-center rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white cursor-pointer shrink-0"
                                aria-label="Tutup modal kelola hak akses role">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-4 py-4 sm:px-6 sm:py-5 space-y-5 flex-1 min-h-0 overflow-y-auto">
                            <!-- Quick Actions -->
                            <div
                                class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-200">
                                <span class="text-sm font-semibold text-slate-700">Daftar Modul &amp; Hak Akses:</span>
                                <div class="flex items-center gap-1.5 text-sm">
                                    <button type="button" @click="selectAllModalPermissions()"
                                        class="inline-flex items-center min-h-11 px-2.5 rounded-lg text-teal-700 hover:text-teal-800 hover:bg-teal-50 font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                        Pilih Semua
                                    </button>
                                    <span class="text-slate-300" aria-hidden="true">|</span>
                                    <button type="button" @click="clearAllModalPermissions()"
                                        class="inline-flex items-center min-h-11 px-2.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                        Kosongkan
                                    </button>
                                </div>
                            </div>

                            @foreach ($groupedPermissions as $groupName => $perms)
                                <div class="space-y-2.5">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                        {{ $groupName }}
                                    </h4>
                                    <div class="grid grid-cols-1 gap-2.5">
                                        @foreach ($perms as $pKey => $pMeta)
                                            <label
                                                class="flex items-start gap-3.5 min-h-11 p-3.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 transition cursor-pointer">
                                                <input type="checkbox" name="permissions[]"
                                                    value="{{ $pKey }}" x-model="modalPermissions"
                                                    class="w-4 h-4 mt-1 rounded border-slate-500 text-teal-700 accent-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                                <div class="flex-1 min-w-0">
                                                    <span
                                                        class="block text-sm sm:text-base font-bold text-slate-900">{{ $pMeta['label'] }}</span>
                                                    <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
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
                            class="px-4 py-3 sm:px-6 sm:py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 shrink-0">
                            <button type="button" @click="closePermissionModal()"
                                class="min-h-11 px-5 py-2.5 rounded-xl text-sm sm:text-base font-semibold text-slate-700 bg-white border border-slate-500 hover:bg-slate-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="min-h-11 px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-1 cursor-pointer">
                                Simpan Perubahan Hak Akses
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Ubah Nama Role -->
        <div x-show="renameModalOpen" x-cloak @keydown.escape.window="closeRenameModal()"
            @keydown.window="if (renameModalOpen && $event.key === 'Tab') trapFocusInRef($event, 'renameModalPanel')"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="rename-modal-title"
            aria-describedby="rename-modal-desc" role="dialog" aria-modal="true">
            <div x-show="renameModalOpen" @click="closeRenameModal()"
                x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/55 transition-opacity motion-reduce:transition-none"
                aria-hidden="true"></div>

            <div @click.self="closeRenameModal()"
                class="relative flex min-h-full items-center justify-center p-2 sm:p-6">
                <div x-show="renameModalOpen" x-ref="renameModalPanel" tabindex="-1"
                    x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative flex flex-col w-full max-h-[calc(100dvh-1rem)] sm:max-h-[90dvh] sm:max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl border border-slate-200 transition-all motion-reduce:transition-none focus:outline-none">
                    <form :action="renameActionUrl" method="POST" class="flex flex-col flex-1 min-h-0">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_modal" value="rename_role">
                        <input type="hidden" name="_rename_role_id" :value="renameRoleId">

                        <div
                            class="bg-blue-900 px-4 py-3.5 sm:px-6 sm:py-4 text-white shrink-0 flex items-start sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-bold tracking-tight text-white"
                                    id="rename-modal-title">
                                    Ubah Nama Role
                                </h3>
                                <p id="rename-modal-desc" class="text-xs text-blue-100 mt-0.5">
                                    Kode Role: <span class="font-mono font-bold text-white"
                                        x-text="renameRoleId"></span> (tetap / tidak berubah)
                                </p>
                            </div>
                            <button type="button" @click="closeRenameModal()"
                                class="min-w-11 min-h-11 inline-flex items-center justify-center rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white cursor-pointer shrink-0"
                                aria-label="Tutup modal ubah nama role">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-4 py-4 sm:px-6 sm:py-5 space-y-3 flex-1 min-h-0 overflow-y-auto">
                            @if ($errors->any() && old('_modal') === 'rename_role')
                                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 text-sm font-medium"
                                    role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <label for="rename_role_name"
                                        class="block text-sm font-semibold text-slate-800">Nama Role Baru</label>
                                    <span class="text-xs font-medium text-slate-600">Maks. 24 karakter</span>
                                </div>
                                <input type="text" id="rename_role_name" name="role_name"
                                    x-model="renameRoleName" x-ref="renameRoleInput" maxlength="24" required
                                    placeholder="Contoh: KEPALA RUANG"
                                    class="w-full min-h-11 px-3.5 py-2.5 rounded-xl border border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-600 uppercase focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:border-teal-700">
                            </div>
                        </div>

                        <div
                            class="px-4 py-3 sm:px-6 sm:py-3.5 bg-slate-50 border-t border-slate-200 grid grid-cols-2 sm:flex sm:items-center sm:justify-end gap-2.5 shrink-0">
                            <button type="button" @click="closeRenameModal()"
                                class="min-h-11 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-500 hover:bg-slate-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="min-h-11 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-1 cursor-pointer">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Role Baru -->
        <div x-show="createRoleModalOpen" x-cloak @keydown.escape.window="closeCreateRoleModal()"
            @keydown.window="if (createRoleModalOpen && $event.key === 'Tab') trapFocusInRef($event, 'createRoleModalPanel')"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="create-role-modal-title"
            aria-describedby="create-role-modal-desc" role="dialog" aria-modal="true">
            <div x-show="createRoleModalOpen" @click="closeCreateRoleModal()"
                x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/55 transition-opacity motion-reduce:transition-none"
                aria-hidden="true"></div>

            <div @click.self="closeCreateRoleModal()"
                class="relative flex min-h-full items-center justify-center p-2 sm:p-6">
                <div x-show="createRoleModalOpen" x-ref="createRoleModalPanel" tabindex="-1"
                    x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative flex flex-col w-full max-h-[calc(100dvh-1rem)] sm:max-h-[90dvh] sm:max-w-lg overflow-hidden rounded-2xl bg-white text-left shadow-2xl border border-slate-200 transition-all motion-reduce:transition-none focus:outline-none">
                    <form action="{{ route('admin.roles.store-definition') }}" method="POST"
                        class="flex flex-col flex-1 min-h-0">
                        @csrf
                        <input type="hidden" name="_modal" value="create_role">

                        <div
                            class="bg-blue-900 px-4 py-3.5 sm:px-6 sm:py-4 text-white shrink-0 flex items-start sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-bold tracking-tight text-white"
                                    id="create-role-modal-title">
                                    Tambah Role Baru
                                </h3>
                                <p id="create-role-modal-desc" class="text-xs text-blue-100 mt-0.5">
                                    Atur nama role dan hak akses yang akan diberikan oleh role yang dibuat.
                                </p>
                            </div>
                            <button type="button" @click="closeCreateRoleModal()"
                                class="min-w-11 min-h-11 inline-flex items-center justify-center rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white cursor-pointer shrink-0"
                                aria-label="Tutup modal tambah role baru">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-4 py-4 sm:px-6 sm:py-5 space-y-5 flex-1 min-h-0 overflow-y-auto">
                            @if ($errors->any() && old('_modal') === 'create_role')
                                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 text-sm font-medium"
                                    role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <label for="role_name"
                                        class="block text-sm font-semibold text-slate-800">Nama Role</label>
                                    <span class="text-xs font-medium text-slate-600">Maks. 24 karakter</span>
                                </div>
                                <input type="text" id="role_name" name="role_name" x-ref="createRoleNameInput"
                                    value="{{ old('_modal') === 'create_role' ? old('role_name') : '' }}"
                                    maxlength="24" required placeholder="Contoh: KEPALA BIDANG"
                                    class="w-full min-h-11 px-3.5 py-2.5 rounded-xl border border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-600 uppercase focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:border-teal-700">
                            </div>

                            <div class="pt-2 border-t border-slate-200">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-700">Hak Akses Awal
                                        Role:</p>
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <button type="button" @click="selectAllNewRole()"
                                            class="inline-flex items-center min-h-11 px-2 rounded-lg text-teal-700 hover:text-teal-800 hover:bg-teal-50 font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">Pilih
                                            Semua</button>
                                        <span class="text-slate-300" aria-hidden="true">|</span>
                                        <button type="button" @click="clearAllNewRole()"
                                            class="inline-flex items-center min-h-11 px-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">Kosongkan</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    @foreach (\App\Models\RolePermission::AVAILABLE_PERMISSIONS as $permKey => $permMeta)
                                        <label
                                            class="flex items-start gap-2.5 min-h-11 p-3 rounded-xl border border-slate-300 hover:border-teal-700 hover:bg-teal-50/40 transition cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}"
                                                x-model="newRolePermissions"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-500 text-teal-700 accent-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                            <span
                                                class="text-xs font-medium text-slate-800 leading-snug">{{ $permMeta['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-4 py-3 sm:px-6 sm:py-3.5 bg-slate-50 border-t border-slate-200 grid grid-cols-2 sm:flex sm:items-center sm:justify-end gap-2.5 shrink-0">
                            <button type="button" @click="closeCreateRoleModal()"
                                class="min-h-11 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-500 hover:bg-slate-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="min-h-11 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-1 cursor-pointer">
                                Simpan Role Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Berikan Hak Akses Pegawai Baru -->
        <div x-show="assignUserModalOpen" x-cloak @keydown.escape.window="closeAssignUserModal()"
            @keydown.window="if (assignUserModalOpen && $event.key === 'Tab') trapFocusInRef($event, 'assignModalPanel')"
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="assign-user-modal-title"
            aria-describedby="assign-user-modal-desc" role="dialog" aria-modal="true">
            <div x-show="assignUserModalOpen" @click="closeAssignUserModal()"
                x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/55 transition-opacity motion-reduce:transition-none"
                aria-hidden="true"></div>

            <div @click.self="closeAssignUserModal()"
                class="relative flex min-h-full items-center justify-center p-2 sm:p-6">
                <div x-show="assignUserModalOpen" x-ref="assignModalPanel" tabindex="-1"
                    x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative flex flex-col w-full max-h-[calc(100dvh-1rem)] sm:max-h-[90dvh] sm:max-w-3xl lg:max-w-4xl overflow-hidden rounded-2xl bg-white text-left shadow-2xl border border-slate-200 transition-all motion-reduce:transition-none focus:outline-none">
                    <form x-data="adminAssignEmployeeRole()" x-init="initAssignForm()"
                        action="{{ route('admin.roles.assign-user') }}" method="POST"
                        class="flex flex-col flex-1 min-h-0" @submit="handleSubmit($event)"
                        @open-assign-user-modal.window="handleModalOpen()"
                        @reset-assign-user-modal.window="resetState()">
                        @csrf
                        <input type="hidden" name="_modal" value="assign_user">
                        <input type="hidden" name="nip" :value="selectedNip">

                        <!-- Modal Header (Primary Navy) -->
                        <div
                            class="bg-blue-900 px-4 py-3.5 sm:px-6 sm:py-4 text-white shrink-0 flex items-start sm:items-center justify-between gap-3">
                            <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                                <div class="hidden sm:flex w-10 h-10 rounded-xl bg-white/10 items-center justify-center shrink-0"
                                    aria-hidden="true">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white"
                                        id="assign-user-modal-title">
                                        Berikan Hak Akses Pegawai Baru
                                    </h3>
                                    <p id="assign-user-modal-desc"
                                        class="text-xs text-blue-100 mt-0.5 hidden sm:block">
                                        Pilih perawat aktif yang belum terdaftar dan tinjau cakupan izin role sebelum
                                        menyimpan.
                                    </p>
                                </div>
                            </div>
                            <button type="button" x-ref="assignCloseButton" @click="closeAssignUserModal()"
                                class="min-w-11 min-h-11 inline-flex items-center justify-center rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white cursor-pointer shrink-0"
                                aria-label="Tutup modal pendaftaran pegawai">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Scrollable Two-Pane Body -->
                        <div class="flex-1 min-h-0 overflow-y-auto">
                            @if ($errors->any() && old('_modal') === 'assign_user')
                                <div x-show="showServerError" class="px-4 pt-3 sm:px-6 sm:pt-4">
                                    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 text-sm font-medium"
                                        role="alert">
                                        @if ($errors->count() === 1)
                                            {{ $errors->first() }}
                                        @else
                                            <ul class="list-disc list-inside space-y-0.5">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-200">
                                <!-- Left Pane: Step 1 Pilih Pegawai -->
                                <div class="lg:col-span-7 p-4 sm:p-5 space-y-3 min-w-0">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="w-6 h-6 rounded-full bg-teal-700 text-white text-xs font-bold inline-flex items-center justify-center shrink-0"
                                            aria-hidden="true">1</span>
                                        <h4 class="text-sm font-bold text-slate-900">
                                            Pilih Pegawai (Perawat Aktif)
                                        </h4>
                                    </div>

                                    <!-- Empty Candidate Pool State -->
                                    <template x-if="employees.length === 0">
                                        <div
                                            class="p-5 rounded-xl border border-slate-300 bg-slate-50 text-center space-y-1.5">
                                            <p class="text-sm font-bold text-slate-900">
                                                Semua perawat aktif sudah terdaftar.
                                            </p>
                                            <p class="text-xs text-slate-600 leading-relaxed">
                                                Seluruh pegawai perawat aktif saat ini telah memiliki akses aplikasi.
                                                Gunakan tabel Daftar Pengguna Terdaftar untuk mengubah role.
                                            </p>
                                        </div>
                                    </template>

                                    <!-- Search, Selected Confirmation Card & In-Flow Candidate Listbox -->
                                    <div x-show="employees.length > 0" class="space-y-3"
                                        @click.outside="handleSearchClickOutside()">
                                        <div>
                                            <label for="assign_employee_search" class="sr-only">
                                                Cari Nama atau NIP Pegawai
                                            </label>
                                            <div class="relative flex items-center">
                                                <svg class="w-4 h-4 text-slate-600 pointer-events-none absolute left-3.5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                                <input type="text" id="assign_employee_search"
                                                    x-ref="employeeSearchInput" x-model="employeeSearch"
                                                    role="combobox" aria-autocomplete="list"
                                                    :aria-expanded="isOpen.toString()"
                                                    aria-controls="assign_employee_listbox"
                                                    :aria-activedescendant="activeOptionId"
                                                    :aria-describedby="employeeError ? 'assign_employee_error' : 'assign_employee_hint'"
                                                    @focus="if (!selectedNip && !isOpen) { openDropdown(); }"
                                                    @click="if (!isOpen) { openDropdown(); }"
                                                    @input="onSearchInput()"
                                                    @keydown.arrow-down.prevent="highlightNext()"
                                                    @keydown.arrow-up.prevent="highlightPrev()"
                                                    @keydown.enter="if (isOpen) { $event.preventDefault(); selectHighlighted(); }"
                                                    @keydown.escape="handleSearchEscape($event)" autocomplete="off"
                                                    :placeholder="selectedEmployee ? 'Ketik nama atau NIP lain untuk mengganti...' :
                                                        'Ketik Nama atau NIP pegawai...'"
                                                    class="w-full min-h-11 pl-10 pr-12 py-2.5 rounded-xl border border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:border-teal-700">
                                                <button type="button" x-show="employeeSearch && employeeSearch.length > 0"
                                                    x-cloak @click="clearSearchInput()"
                                                    class="absolute right-0.5 inset-y-0 my-auto min-w-11 min-h-11 inline-flex items-center justify-center text-slate-600 hover:text-slate-900 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer"
                                                    aria-label="Hapus kata kunci pencarian">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Selected Employee Confirmation Card -->
                                        <div x-show="selectedEmployee !== null" x-cloak
                                            class="p-3.5 rounded-xl border border-teal-700 bg-teal-50 flex items-start justify-between gap-3">
                                            <div class="flex items-start gap-3 min-w-0 flex-1">
                                                <div class="w-10 h-10 rounded-xl bg-teal-700 text-white font-bold text-sm flex items-center justify-center shrink-0"
                                                    aria-hidden="true"
                                                    x-text="getInitials(selectedEmployee?.name)"></div>
                                                <div class="min-w-0 flex-1 space-y-1">
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-white text-teal-800 border border-teal-700">
                                                            Terpilih
                                                        </span>
                                                        <span x-show="isDuplicateName(selectedEmployee?.name)"
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-950 border border-amber-300">
                                                            Nama sama, cek NIP
                                                        </span>
                                                    </div>
                                                    <p class="text-sm font-bold text-slate-900 break-words leading-snug"
                                                        x-text="selectedEmployee?.name"></p>
                                                    <span
                                                        class="inline-block font-mono text-xs font-semibold text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-300"
                                                        x-text="selectedEmployee ? ('NIP: ' + selectedEmployee.nip) : ''"></span>
                                                </div>
                                            </div>
                                            <button type="button" @click="clearSelection()"
                                                class="min-h-11 px-3 py-2 rounded-lg text-xs font-semibold text-teal-800 bg-white hover:bg-teal-50 border border-teal-700 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer shrink-0">
                                                Ganti
                                            </button>
                                        </div>

                                        <!-- Keyboard Hint & Clear Action (strictly outside div[x-show="isOpen"]) -->
                                        <div id="assign_employee_hint"
                                            :class="(isOpen && filteredEmployees.length === 0 && (employeeSearch || '').trim() !== '') ? 'flex' : 'hidden sm:flex'"
                                            class="flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                                            <span class="hidden sm:inline font-medium text-slate-600">
                                                Panah Atas/Bawah pilih, Enter konfirmasi
                                            </span>
                                            <button type="button"
                                                x-show="isOpen && filteredEmployees.length === 0 && (employeeSearch || '').trim() !== ''"
                                                x-cloak @click="clearSearchInput()"
                                                class="inline-flex items-center min-h-11 px-2 text-xs font-semibold text-teal-800 underline hover:text-teal-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 rounded cursor-pointer">
                                                Bersihkan pencarian
                                            </button>
                                        </div>

                                        <!-- In-Flow Scrollable Candidate Listbox -->
                                        <div id="assign_employee_listbox" role="listbox" x-show="isOpen" x-cloak
                                            class="rounded-xl border border-slate-500 bg-white max-h-52 sm:max-h-64 lg:max-h-72 overflow-y-auto divide-y divide-slate-200">
                                            <template x-if="filteredEmployees.length === 0">
                                                <div class="px-4 py-5 text-center space-y-1">
                                                    <p class="text-xs font-semibold text-slate-700">
                                                        Pegawai tidak ditemukan atau seluruhnya sudah terdaftar.
                                                    </p>
                                                    <p class="text-xs text-slate-600">
                                                        Periksa kembali ejaan nama atau ketik langsung angka NIP pegawai
                                                        aktif.
                                                    </p>
                                                </div>
                                            </template>
                                            <template x-for="(emp, idx) in filteredEmployees.slice(0, 80)"
                                                :key="emp.nip">
                                                <button type="button" role="option" :id="'assign-emp-opt-' + idx"
                                                    :aria-selected="(selectedNip === emp.nip).toString()"
                                                    @click="selectEmployee(emp)" @mouseenter="highlightIndex = idx"
                                                    :class="(idx === highlightIndex || selectedNip === emp.nip) ?
                                                    'bg-teal-50 border-l-teal-700 text-slate-900' :
                                                    'bg-white border-l-transparent text-slate-900 hover:bg-slate-50'"
                                                    class="w-full min-h-12 px-3.5 py-2.5 text-left flex items-center justify-between gap-3 border-l-4 transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-700">
                                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                                        <span
                                                            class="w-9 h-9 rounded-lg text-xs font-bold flex items-center justify-center shrink-0"
                                                            :class="(idx === highlightIndex || selectedNip === emp.nip) ?
                                                            'bg-teal-700 text-white' :
                                                            'bg-slate-100 text-slate-700 border border-slate-300'"
                                                            aria-hidden="true"
                                                            x-text="getInitials(emp.name)"></span>
                                                        <div class="min-w-0 flex-1">
                                                            <span
                                                                class="block text-sm font-semibold text-slate-900 break-words leading-snug"
                                                                x-text="emp.name"></span>
                                                            <span x-show="isDuplicateName(emp.name)"
                                                                class="inline-flex items-center mt-0.5 px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-950 border border-amber-300">
                                                                Nama sama, cek NIP
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="shrink-0 flex flex-col items-end gap-1">
                                                        <span
                                                            class="font-mono text-xs font-semibold text-slate-800 bg-slate-100 border border-slate-300 px-2 py-0.5 rounded"
                                                            x-text="'NIP: ' + emp.nip"></span>
                                                        <span x-show="selectedNip === emp.nip"
                                                            class="text-xs font-bold text-teal-800">Terpilih</span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>

                                        <p id="assign_employee_error" x-show="employeeError" x-text="employeeError"
                                            role="alert" class="text-xs font-semibold text-rose-700"></p>
                                    </div>
                                </div>

                                <!-- Right Pane: Step 2 Pilih Role & Cakupan Izin -->
                                <div class="lg:col-span-5 p-4 sm:p-5 bg-slate-50/70 space-y-3.5 min-w-0">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="w-6 h-6 rounded-full bg-blue-900 text-white text-xs font-bold inline-flex items-center justify-center shrink-0"
                                            aria-hidden="true">2</span>
                                        <label for="assign_role" class="text-sm font-bold text-slate-900">
                                            Pilih Hak Akses (Role)
                                        </label>
                                    </div>

                                    <div>
                                        <select id="assign_role" name="app_roles" x-ref="assignRoleSelect"
                                            x-model="assignRoleId" @change="showServerError = false"
                                            aria-describedby="assign_role_meta" required
                                            class="w-full min-h-11 px-3.5 py-2.5 rounded-xl border border-slate-500 bg-white text-sm font-semibold text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:border-teal-700">
                                            @foreach ($assignableRoles as $roleId => $roleName)
                                                <option value="{{ $roleId }}">{{ $roleId }} - {{ $roleName }}</option>
                                            @endforeach
                                        </select>
                                        <p id="assign_role_meta" class="text-xs font-medium text-slate-600 mt-1.5">
                                            <span
                                                x-text="selectedRoleMeta.user_count + ' pengguna aktif · ' + selectedRolePermissionKeys.length + ' dari ' + totalPermissionsCount + ' izin modul aktif'"></span>
                                        </p>
                                    </div>

                                    <!-- Conditional Governance Alerts -->
                                    <div x-show="assignRoleId !== '' && Number(assignRoleId) === 0" x-cloak
                                        class="p-3 rounded-xl bg-purple-50 border border-purple-300 text-purple-950 text-xs leading-relaxed">
                                        <strong>Akses Penuh Administrator:</strong> Role 0 memiliki seluruh akses
                                        operasional, analitik, dan administrasi sistem tanpa pembatasan modul.
                                    </div>

                                    <div x-show="assignRoleId !== '' && Number(assignRoleId) !== 0 && selectedRolePermissionKeys.length === 0"
                                        x-cloak
                                        class="p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 text-xs leading-relaxed">
                                        <strong>Belum ada izin modul aktif:</strong> Pegawai dapat masuk aplikasi, namun
                                        seluruh menu terkunci hingga izin role diatur.
                                    </div>

                                    <!-- Compact 3-Domain Permission Scope Matrix -->
                                    <div class="space-y-2">
                                        <p class="text-xs font-bold text-slate-700">Cakupan Izin Role Terpilih</p>
                                        <div class="space-y-2">
                                            @foreach ($groupedPermissions as $groupName => $perms)
                                                <div
                                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 space-y-1">
                                                    <p
                                                        class="text-xs font-bold text-slate-800 pb-1 border-b border-slate-100">
                                                        {{ $groupName }}
                                                    </p>
                                                    @foreach ($perms as $pKey => $pMeta)
                                                        <div class="flex items-center justify-between gap-2 py-0.5 text-xs"
                                                            title="{{ $pMeta['description'] }}">
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                <svg x-show="hasPermissionKey(@js($pKey))"
                                                                    class="w-4 h-4 text-teal-700 shrink-0"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor" aria-hidden="true">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2.5"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                <svg x-show="!hasPermissionKey(@js($pKey))"
                                                                    x-cloak class="w-4 h-4 text-slate-500 shrink-0"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor" aria-hidden="true">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M20 12H4" />
                                                                </svg>
                                                                <span class="leading-snug break-words"
                                                                    :class="hasPermissionKey(@js($pKey)) ?
                                                                        'font-semibold text-slate-900' :
                                                                        'text-slate-600'">{{ $pMeta['label'] }}</span>
                                                            </div>
                                                            <span class="shrink-0 text-xs font-semibold"
                                                                :class="hasPermissionKey(@js($pKey)) ?
                                                                    'text-teal-800' : 'text-slate-600'"
                                                                x-text="hasPermissionKey(@js($pKey)) ? 'Aktif' : 'Tidak'"></span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pinned Footer -->
                        <div
                            class="px-4 py-3 sm:px-6 sm:py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shrink-0">
                            <p class="hidden sm:block text-xs font-medium text-slate-600 min-w-0 truncate"
                                x-text="readinessSummary"></p>
                            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2.5 shrink-0">
                                <button type="button" @click="closeAssignUserModal()"
                                    class="min-h-11 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-500 hover:bg-slate-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" :disabled="employees.length === 0 || isSubmitting"
                                    class="min-h-11 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-1 cursor-pointer inline-flex items-center justify-center gap-2">
                                    <span x-show="!isSubmitting">Daftarkan User</span>
                                    <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Menyimpan...
                                    </span>
                                </button>
                            </div>
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
                activeTriggerEl: null,

                init() {
                    if (this.createRoleModalOpen) {
                        this.$nextTick(() => this.$refs.createRoleNameInput?.focus());
                    } else if (this.renameModalOpen) {
                        this.$nextTick(() => this.$refs.renameRoleInput?.focus());
                    }
                },

                captureTrigger(event = null) {
                    const target = event?.currentTarget;
                    if (target instanceof HTMLElement) {
                        this.activeTriggerEl = target;
                        return;
                    }
                    this.activeTriggerEl = document.activeElement instanceof HTMLElement ? document.activeElement : null;
                },

                restoreTrigger() {
                    const trigger = this.activeTriggerEl;
                    this.activeTriggerEl = null;
                    if (trigger && trigger.isConnected) {
                        this.$nextTick(() => trigger.focus());
                    }
                },

                getFocusableElements(container) {
                    if (!container) {
                        return [];
                    }

                    const selector = [
                        'a[href]',
                        'button:not([disabled])',
                        'input:not([disabled]):not([type="hidden"])',
                        'select:not([disabled])',
                        'textarea:not([disabled])',
                        '[tabindex]:not([tabindex="-1"])',
                    ].join(', ');

                    return Array.from(container.querySelectorAll(selector)).filter((el) => {
                        if (!(el instanceof HTMLElement)) {
                            return false;
                        }
                        if (el.hasAttribute('disabled') || el.getAttribute('aria-hidden') === 'true') {
                            return false;
                        }
                        const style = window.getComputedStyle(el);
                        if (style.visibility === 'hidden' || style.display === 'none') {
                            return false;
                        }
                        return el.getClientRects().length > 0;
                    });
                },

                trapFocusInRef(event, refName) {
                    if (event.key !== 'Tab') {
                        return;
                    }

                    const container = this.$refs[refName];
                    if (!container) {
                        return;
                    }

                    const focusable = this.getFocusableElements(container);
                    if (focusable.length === 0) {
                        event.preventDefault();
                        container.focus();
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    const active = document.activeElement;

                    if (!container.contains(active) || active === container) {
                        event.preventDefault();
                        (event.shiftKey ? last : first).focus();
                        return;
                    }

                    if (event.shiftKey) {
                        if (active === first) {
                            event.preventDefault();
                            last.focus();
                        }
                    } else if (active === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },

                openCreateRoleModal(event = null) {
                    this.captureTrigger(event);
                    this.createRoleModalOpen = true;
                    this.$nextTick(() => this.$refs.createRoleNameInput?.focus());
                },

                closeCreateRoleModal() {
                    if (!this.createRoleModalOpen) {
                        return;
                    }
                    this.createRoleModalOpen = false;
                    this.restoreTrigger();
                },

                openAssignUserModal(event = null) {
                    this.captureTrigger(event);
                    this.assignUserModalOpen = true;
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('open-assign-user-modal'));
                    });
                },

                closeAssignUserModal() {
                    if (!this.assignUserModalOpen) {
                        return;
                    }
                    this.assignUserModalOpen = false;
                    window.dispatchEvent(new CustomEvent('reset-assign-user-modal'));
                    this.restoreTrigger();
                },

                openPermissionModal(roleId, roleName, currentPermissions, event = null) {
                    this.captureTrigger(event);
                    this.modalRoleId = roleId;
                    this.modalRoleName = roleName;
                    this.modalPermissions = Array.isArray(currentPermissions) ? [...currentPermissions] : [];
                    this.modalActionUrl = "{{ url('/admin/roles/definitions') }}/" + roleId + "/permissions";
                    this.modalOpen = true;
                    this.$nextTick(() => {
                        const focusable = this.getFocusableElements(this.$refs.permissionModalPanel);
                        (focusable[0] || this.$refs.permissionModalPanel)?.focus();
                    });
                },

                closePermissionModal() {
                    if (!this.modalOpen) {
                        return;
                    }
                    this.modalOpen = false;
                    this.restoreTrigger();
                },

                openRenameModal(roleId, currentRoleName, event = null) {
                    this.captureTrigger(event);
                    this.renameRoleId = roleId;
                    this.renameRoleName = currentRoleName;
                    this.renameActionUrl = "{{ url('/admin/roles/definitions') }}/" + roleId;
                    this.renameModalOpen = true;
                    this.$nextTick(() => this.$refs.renameRoleInput?.focus());
                },

                closeRenameModal() {
                    if (!this.renameModalOpen) {
                        return;
                    }
                    this.renameModalOpen = false;
                    this.restoreTrigger();
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
            const oldNip = isAssignModalValidationError ? @json(is_scalar(old('nip')) ? trim((string) old('nip')) : '') : '';
            const matchedOld = oldNip !== '' ? allEmployees.find(e => e.nip === oldNip) : undefined;
            const defaultAssignRoleId = @json($defaultAssignRoleId);
            const initialAssignRoleId = @json($initialAssignRoleId);
            const rolePermissionsMap = @json((object) $rolePermissionsMap);
            const assignableRoleMeta = @json((object) $assignableRoleMeta);
            const totalPermissionsCount = @json($totalPermissionsCount);

            const normalizeName = (name) => String(name || '').trim().replace(/\s+/g, ' ').toLowerCase();
            const nameCounts = new Map();
            for (const emp of allEmployees) {
                const key = normalizeName(emp.name);
                if (key !== '') {
                    nameCounts.set(key, (nameCounts.get(key) || 0) + 1);
                }
            }
            const duplicateNameSet = new Set(
                Array.from(nameCounts.entries())
                    .filter(([, count]) => count > 1)
                    .map(([key]) => key)
            );

            return {
                employees: allEmployees,
                employeeSearch: '',
                selectedNip: matchedOld ? matchedOld.nip : '',
                assignRoleId: initialAssignRoleId,
                isOpen: isAssignModalValidationError ? (allEmployees.length > 0 && !matchedOld) : false,
                highlightIndex: 0,
                employeeError: '',
                showServerError: isAssignModalValidationError,
                isSubmitting: false,
                totalPermissionsCount: totalPermissionsCount,

                initAssignForm() {
                    window.addEventListener('pageshow', () => {
                        this.isSubmitting = false;
                    });

                    if (isAssignModalValidationError) {
                        this.$nextTick(() => {
                            if (this.selectedNip) {
                                this.$refs.assignRoleSelect?.focus();
                            } else if (this.employees.length > 0) {
                                this.$refs.employeeSearchInput?.focus();
                            } else {
                                this.$refs.assignCloseButton?.focus();
                            }
                        });
                    }
                },

                handleModalOpen() {
                    this.highlightIndex = 0;
                    if (this.employees.length > 0) {
                        this.isOpen = !this.selectedNip;
                        this.$nextTick(() => {
                            if (this.selectedNip) {
                                this.$refs.assignRoleSelect?.focus();
                            } else {
                                this.$refs.employeeSearchInput?.focus();
                            }
                        });
                        return;
                    }

                    this.isOpen = false;
                    this.$nextTick(() => {
                        this.$refs.assignCloseButton?.focus();
                    });
                },

                get selectedEmployee() {
                    if (!this.selectedNip) {
                        return null;
                    }
                    return this.employees.find(e => e.nip === this.selectedNip) || null;
                },

                get selectedRoleMeta() {
                    const raw = String(this.assignRoleId ?? '').trim();
                    if (raw === '') {
                        return { name: '', user_count: 0 };
                    }
                    const id = Number(raw);
                    if (Number.isNaN(id) || !assignableRoleMeta || !assignableRoleMeta[id]) {
                        return { name: '', user_count: 0 };
                    }
                    return assignableRoleMeta[id];
                },

                get selectedRolePermissionKeys() {
                    const raw = String(this.assignRoleId ?? '').trim();
                    if (raw === '') {
                        return [];
                    }
                    const id = Number(raw);
                    if (Number.isNaN(id) || !rolePermissionsMap) {
                        return [];
                    }
                    const keys = rolePermissionsMap[id];
                    return Array.isArray(keys) ? keys : [];
                },

                get activeOptionId() {
                    if (!this.isOpen || this.filteredEmployees.length === 0) {
                        return null;
                    }
                    const visibleLength = Math.min(this.filteredEmployees.length, 80);
                    if (this.highlightIndex >= 0 && this.highlightIndex < visibleLength) {
                        return 'assign-emp-opt-' + this.highlightIndex;
                    }
                    return null;
                },

                get readinessSummary() {
                    if (!this.selectedEmployee) {
                        return 'Pilih satu pegawai perawat aktif untuk melanjutkan.';
                    }
                    const roleLabel = this.selectedRoleMeta.name
                        ? `${this.assignRoleId} - ${this.selectedRoleMeta.name}`
                        : `Role ${this.assignRoleId}`;
                    return `Siap mendaftarkan: ${this.selectedEmployee.name} ke ${roleLabel}`;
                },

                hasPermissionKey(key) {
                    return this.selectedRolePermissionKeys.includes(key);
                },

                isDuplicateName(name) {
                    const key = normalizeName(name);
                    return key !== '' && duplicateNameSet.has(key);
                },

                getInitials(name) {
                    const baseName = String(name || '').split(',')[0].trim();
                    const words = baseName.split(/\s+/).filter(Boolean);
                    let initials = '';
                    for (const word of words) {
                        const clean = word.replace(/[^A-Za-z]/g, '');
                        if (clean.length > 0) {
                            initials += clean[0].toUpperCase();
                        }
                        if (initials.length >= 2) {
                            break;
                        }
                    }
                    return initials || '?';
                },

                formatEmployeeLabel(emp) {
                    return `${emp.name} (NIP: ${emp.nip})`;
                },

                get filteredEmployees() {
                    const rawSearch = (this.employeeSearch || '').trim();
                    if (!rawSearch) {
                        return this.employees;
                    }

                    const tokens = rawSearch.toLowerCase().split(/\s+/).filter(Boolean);
                    return this.employees.filter((e) => {
                        const haystack = `${e.name || ''} ${e.nip || ''}`.toLowerCase();
                        return tokens.every(token => haystack.includes(token));
                    });
                },

                openDropdown() {
                    if (this.employees.length === 0) {
                        this.isOpen = false;
                        return;
                    }
                    this.isOpen = true;
                    if (this.selectedNip) {
                        const selectedIdx = this.filteredEmployees.findIndex(e => e.nip === this.selectedNip);
                        this.highlightIndex = selectedIdx >= 0 && selectedIdx < 80 ? selectedIdx : 0;
                    } else {
                        this.highlightIndex = 0;
                    }
                },

                resetState() {
                    this.employeeSearch = '';
                    this.selectedNip = '';
                    this.assignRoleId = defaultAssignRoleId;
                    this.isOpen = false;
                    this.highlightIndex = 0;
                    this.employeeError = '';
                    this.showServerError = false;
                    this.isSubmitting = false;
                },

                clearSearchInput() {
                    this.employeeSearch = '';
                    this.employeeError = '';
                    this.showServerError = false;
                    this.highlightIndex = 0;
                    this.isOpen = this.employees.length > 0;
                    this.$nextTick(() => this.$refs.employeeSearchInput?.focus());
                },

                clearSelection() {
                    this.selectedNip = '';
                    this.employeeSearch = '';
                    this.highlightIndex = 0;
                    this.employeeError = '';
                    this.showServerError = false;
                    this.isOpen = this.employees.length > 0;
                    this.$nextTick(() => this.$refs.employeeSearchInput?.focus());
                },

                onSearchInput() {
                    this.selectedNip = '';
                    this.employeeError = '';
                    this.showServerError = false;
                    this.isOpen = this.employees.length > 0;
                    this.highlightIndex = 0;
                },

                selectEmployee(emp, moveFocus = true) {
                    this.selectedNip = emp.nip;
                    this.employeeSearch = '';
                    this.employeeError = '';
                    this.showServerError = false;
                    this.isOpen = false;
                    if (moveFocus) {
                        this.$nextTick(() => this.$refs.assignRoleSelect?.focus());
                    }
                },

                selectHighlighted() {
                    const visible = this.filteredEmployees.slice(0, 80);
                    if (visible.length === 0) {
                        return;
                    }
                    if (this.highlightIndex >= 0 && this.highlightIndex < visible.length) {
                        this.selectEmployee(visible[this.highlightIndex]);
                    } else {
                        this.selectEmployee(visible[0]);
                    }
                },

                highlightNext() {
                    const visibleLength = Math.min(this.filteredEmployees.length, 80);
                    if (!this.isOpen) {
                        this.openDropdown();
                        return;
                    }
                    if (visibleLength === 0) {
                        return;
                    }
                    if (this.highlightIndex < visibleLength - 1) {
                        this.highlightIndex++;
                        this.$nextTick(() => {
                            document.getElementById('assign-emp-opt-' + this.highlightIndex)?.scrollIntoView({
                                block: 'nearest'
                            });
                        });
                    }
                },

                highlightPrev() {
                    if (!this.isOpen) {
                        return;
                    }
                    if (this.highlightIndex > 0) {
                        this.highlightIndex--;
                        this.$nextTick(() => {
                            document.getElementById('assign-emp-opt-' + this.highlightIndex)?.scrollIntoView({
                                block: 'nearest'
                            });
                        });
                    }
                },

                handleSearchEscape(event) {
                    if (this.selectedNip && this.isOpen) {
                        event.stopPropagation();
                        this.isOpen = false;
                        this.employeeSearch = '';
                        return;
                    }
                    if (!this.selectedNip && (this.employeeSearch || '').trim() !== '') {
                        event.stopPropagation();
                        this.employeeSearch = '';
                        this.highlightIndex = 0;
                        this.isOpen = this.employees.length > 0;
                    }
                },

                resolveExactMatch() {
                    const raw = (this.employeeSearch || '').trim();
                    if (!raw) {
                        return { status: 'empty', employee: null };
                    }

                    const q = raw.toLowerCase();
                    const nipMatch = this.employees.find(e => (e.nip || '').trim().toLowerCase() === q);
                    if (nipMatch) {
                        return { status: 'matched', employee: nipMatch };
                    }

                    const labelMatch = this.employees.find(e => this.formatEmployeeLabel(e).toLowerCase() === q);
                    if (labelMatch) {
                        return { status: 'matched', employee: labelMatch };
                    }

                    const nameMatches = this.employees.filter(e => (e.name || '').trim().toLowerCase() === q);
                    if (nameMatches.length === 1) {
                        return { status: 'matched', employee: nameMatches[0] };
                    }

                    if (nameMatches.length > 1) {
                        return { status: 'ambiguous', employee: null };
                    }

                    return { status: 'none', employee: null };
                },

                handleSearchClickOutside() {
                    if (this.$el.getClientRects().length === 0) {
                        return;
                    }

                    if (this.selectedNip) {
                        this.isOpen = false;
                        this.employeeSearch = '';
                        return;
                    }

                    const raw = (this.employeeSearch || '').trim();
                    if (raw === '') {
                        return;
                    }

                    const match = this.resolveExactMatch();
                    if (match.status === 'matched') {
                        this.selectEmployee(match.employee, false);
                    } else if (match.status === 'ambiguous') {
                        this.employeeError = 'Terdapat lebih dari satu pegawai dengan nama tersebut. Silakan pilih langsung berdasarkan NIP.';
                    }
                },

                handleSubmit(event) {
                    if (this.isSubmitting) {
                        event.preventDefault();
                        return;
                    }

                    if (!this.selectedNip) {
                        const match = this.resolveExactMatch();
                        if (match.status === 'matched') {
                            this.selectEmployee(match.employee, false);
                        } else if (match.status === 'ambiguous') {
                            event.preventDefault();
                            this.employeeError = 'Terdapat lebih dari satu pegawai dengan nama tersebut. Silakan pilih langsung berdasarkan NIP.';
                            this.openDropdown();
                            this.$nextTick(() => this.$refs.employeeSearchInput?.focus());
                            return;
                        } else if (this.filteredEmployees.length === 1 && (this.employeeSearch || '').trim() !== '') {
                            this.selectEmployee(this.filteredEmployees[0], false);
                        }
                    }

                    if (!this.selectedNip || this.selectedNip.trim() === '') {
                        event.preventDefault();
                        if (!this.employeeError) {
                            this.employeeError = 'Silakan pilih salah satu pegawai dari daftar pencarian.';
                        }
                        this.openDropdown();
                        this.$nextTick(() => this.$refs.employeeSearchInput?.focus());
                        return;
                    }

                    if (String(this.assignRoleId ?? '').trim() === '') {
                        event.preventDefault();
                        this.$nextTick(() => this.$refs.assignRoleSelect?.focus());
                        return;
                    }

                    this.isSubmitting = true;
                }
            };
        }
    </script>
</x-layouts.app>
