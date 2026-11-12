<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Supervisi Keperawatan' }} - RS St. Elisabeth Semarang</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function () {
            try {
                var isDesktop = window.innerWidth >= 768;
                var isFolded = localStorage.getItem('supervisi_sidebar_folded') === 'true';
                var isHoverNav = sessionStorage.getItem('supervisi_sidebar_hover_nav') === 'true';
                if (isDesktop && isFolded && !isHoverNav) {
                    document.documentElement.classList.add('sidebar-compact-init');
                }
            } catch (e) {}
        })();
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        @media (min-width: 768px) {
            html.sidebar-compact-init #app-sidebar {
                width: 86px !important;
            }

            html.sidebar-compact-init #app-sidebar .sidebar-expanded-brand {
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }

            html.sidebar-compact-init #app-sidebar .sidebar-compact-brand {
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
            }

            html.sidebar-compact-init #app-sidebar .sidebar-text-reveal {
                opacity: 0 !important;
                pointer-events: none !important;
            }

            html.sidebar-compact-init #app-sidebar .sidebar-section-header {
                opacity: 0 !important;
            }

            html.sidebar-compact-init #app-sidebar .sidebar-section-divider {
                opacity: 1 !important;
            }
        }
    </style>
</head>

<body class="h-full text-slate-800 antialiased font-sans" x-data="sidebarLayout()" x-init="initSidebar()">
    <div class="min-h-full flex flex-col md:flex-row">
        <!-- Mobile Header Bar -->
        <header
            class="md:hidden bg-blue-900 text-white px-4 py-3.5 flex items-center justify-between shadow-sm sticky top-0 z-30">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-lg bg-white p-1.5 flex items-center justify-center shadow-xs shrink-0">
                    <img src="{{ Vite::asset('resources/images/logo.png') }}" alt="Logo RS St. Elisabeth"
                        class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-base font-bold tracking-tight">Supervisi Keperawatan</h1>
                    <p class="text-xs text-blue-200">RS St. Elisabeth Semarang</p>
                </div>
            </div>
            <button type="button" @click="mobileMenuOpen = !mobileMenuOpen"
                class="p-2.5 rounded-lg text-blue-100 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white"
                aria-label="Buka menu navigasi">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </header>

        <!-- Sidebar Navigation -->
        <aside id="app-sidebar" x-ref="sidebar" @mouseenter="onSidebarMouseEnter()" @mouseleave="onSidebarMouseLeave()"
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 w-72 bg-slate-900 text-slate-200 flex flex-col transition-transform duration-200 ease-in-out md:sticky md:top-0 md:h-screen md:max-h-screen md:translate-x-0 border-r border-slate-800 flex-shrink-0 overflow-x-hidden select-none">
            <!-- Hospital Branding & Fold Toggle (Fixed-Height Stage: Zero Vertical Jump) -->
            <div class="relative h-[144px] border-b border-slate-800 bg-slate-950/60 flex-shrink-0 overflow-hidden">
                <!-- Layer 1: Expanded Logo Card (logo.png) -->
                <div x-ref="expandedBrand"
                    class="sidebar-expanded-brand absolute inset-y-0 left-0 w-[287px] p-4 flex flex-col justify-between">
                    <div
                        class="bg-white rounded-xl p-3 shadow-xs border border-slate-800/60 flex items-center justify-center h-[68px]">
                        <img src="{{ Vite::asset('resources/images/logo.png') }}" alt="Logo RS St. Elisabeth Semarang"
                            class="h-12 w-auto max-w-full object-contain">
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            class="flex-1 flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-bold uppercase tracking-wider bg-blue-950 text-blue-300 border border-blue-800/60 text-center truncate">
                            Supervisi Keperawatan
                        </span>

                        <button type="button" @click="toggleFold()"
                            class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-800/90 hover:bg-blue-600 text-slate-300 hover:text-white border border-slate-700 transition-colors cursor-pointer shrink-0"
                            :title="sidebarFolded ? 'Sematkan Sidebar Terbuka' : 'Lipat Sidebar (30%)'"
                            aria-label="Toggle Lipat Sidebar">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Layer 2: Compact / Folded Logo (web-icon.webp) -->
                <div x-ref="compactBrand"
                    class="sidebar-compact-brand absolute inset-y-0 left-0 w-[85px] py-4 px-2.5 flex flex-col items-center justify-between opacity-0 invisible pointer-events-none">
                    <div
                        class="w-14 h-14 bg-white rounded-xl p-1.5 shadow-xs border border-slate-800/60 flex items-center justify-center">
                        <img src="{{ Vite::asset('resources/images/web-icon.webp') }}"
                            alt="Ikon RS St. Elisabeth Semarang" class="w-full h-full object-contain rounded-lg">
                    </div>

                    <button type="button" @click="toggleFold()"
                        class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-800/90 hover:bg-blue-600 text-blue-300 hover:text-white border border-slate-700 transition-colors cursor-pointer"
                        title="Buka Penuh Sidebar" aria-label="Buka Penuh Sidebar">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation Links (Fixed-Rail Icon Geometry: Zero Horizontal/Vertical Jump) -->
            <nav class="flex-1 py-4 px-3 space-y-1.5 overflow-y-auto overflow-x-hidden">
                <div class="relative h-8 flex items-center px-3 overflow-hidden whitespace-nowrap">
                    <span class="sidebar-section-header text-xs font-bold text-slate-400 uppercase tracking-wider truncate">
                        Menu Utama
                    </span>
                    <span
                        class="sidebar-section-divider absolute inset-x-2 top-1/2 -translate-y-1/2 border-t border-slate-800 opacity-0 pointer-events-none"></span>
                </div>

                @if (auth()->user()?->hasPermission('create_report'))
                    <a href="{{ route('supervisi.create') }}" @click="onSidebarLinkClick()"
                        title="Input Logbook Supervisi"
                        class="group flex items-center h-12 rounded-xl text-base font-semibold transition-colors overflow-hidden whitespace-nowrap {{ request()->routeIs('supervisi.create') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="w-[61px] h-12 shrink-0 flex items-center justify-center">
                            <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                        <span class="sidebar-text-reveal pr-3.5 truncate">Input Logbook Supervisi</span>
                    </a>
                @endif

                <a href="{{ route('supervisi.index') }}" @click="onSidebarLinkClick()" title="Riwayat & Monitoring"
                    class="group flex items-center h-12 rounded-xl text-base font-semibold transition-colors overflow-hidden whitespace-nowrap {{ request()->routeIs('supervisi.index') || request()->routeIs('supervisi.show') || request()->routeIs('supervisi.edit') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span class="w-[61px] h-12 shrink-0 flex items-center justify-center">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </span>
                    <span class="sidebar-text-reveal pr-3.5 truncate">Riwayat & Monitoring</span>
                </a>

                @if (auth()->user()?->hasPermission('view_analytics'))
                    <div class="relative h-9 pt-2 flex items-center px-3 overflow-hidden whitespace-nowrap">
                        <span
                            class="sidebar-section-header text-xs font-bold text-slate-400 uppercase tracking-wider truncate">
                            Analisis & Laporan
                        </span>
                        <span
                            class="sidebar-section-divider absolute inset-x-2 top-1/2 -translate-y-1/2 border-t border-slate-800 opacity-0 pointer-events-none"></span>
                    </div>

                    <a href="{{ route('supervisi.analytics') }}" @click="onSidebarLinkClick()" title="Grafik Insiden"
                        class="group flex items-center h-12 rounded-xl text-base font-semibold transition-colors overflow-hidden whitespace-nowrap {{ request()->routeIs('supervisi.analytics') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="w-[61px] h-12 shrink-0 flex items-center justify-center">
                            <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </span>
                        <span class="sidebar-text-reveal pr-3.5 truncate">Grafik Insiden</span>
                    </a>
                @endif

                @auth
                    @if (auth()->user()->hasPermission('manage_roles') || auth()->user()->hasPermission('manage_incident_categories'))
                        <div class="relative h-9 pt-2 flex items-center px-3 overflow-hidden whitespace-nowrap">
                            <span
                                class="sidebar-section-header text-xs font-bold text-slate-400 uppercase tracking-wider truncate">
                                Administrasi Sistem
                            </span>
                            <span
                                class="sidebar-section-divider absolute inset-x-2 top-1/2 -translate-y-1/2 border-t border-slate-800 opacity-0 pointer-events-none"></span>
                        </div>

                        @if (auth()->user()->hasPermission('manage_roles'))
                            <a href="{{ route('admin.roles.index') }}" @click="onSidebarLinkClick()"
                                title="Manajemen Role & Akses"
                                class="group flex items-center h-12 rounded-xl text-base font-semibold transition-colors overflow-hidden whitespace-nowrap {{ request()->routeIs('admin.roles.*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="w-[61px] h-12 shrink-0 flex items-center justify-center">
                                    <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </span>
                                <span class="sidebar-text-reveal pr-3.5 truncate">Manajemen Role & Akses</span>
                            </a>
                        @endif

                        @if (auth()->user()->hasPermission('manage_incident_categories'))
                            <a href="{{ route('admin.incident-categories.index') }}" @click="onSidebarLinkClick()"
                                title="Manajemen Kategori Insiden"
                                class="group flex items-center h-12 rounded-xl text-base font-semibold transition-colors overflow-hidden whitespace-nowrap {{ request()->routeIs('admin.incident-categories.*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="w-[61px] h-12 shrink-0 flex items-center justify-center">
                                    <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </span>
                                <span class="sidebar-text-reveal pr-3.5 truncate">Manajemen Kategori Insiden</span>
                            </a>
                        @endif
                    @endif
                @endauth
            </nav>

            <!-- User Information Card (Fixed-Rail Geometry: Zero Jump) -->
            @auth
                <div class="border-t border-slate-800 bg-slate-950/80 py-3.5 px-3 space-y-2.5 flex-shrink-0 overflow-hidden">
                    <div class="flex items-center h-16 overflow-hidden whitespace-nowrap">
                        <div class="w-[61px] shrink-0 flex items-center justify-center">
                            <div class="w-11 h-11 rounded-full bg-teal-700 border-2 border-teal-400/40 flex items-center justify-center font-bold text-white text-sm shrink-0"
                                title="{{ auth()->user()->name }} (NIP: {{ auth()->user()->nip }})">
                                {{ auth()->user()->initials() }}
                            </div>
                        </div>
                        <div class="sidebar-text-reveal flex-1 min-w-0 pr-2">
                            <p class="text-sm font-semibold text-white truncate" title="{{ auth()->user()->name }}">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-slate-400 font-mono truncate">
                                NIP: {{ auth()->user()->nip }}
                            </p>
                            <span
                                class="mt-0.5 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-800 text-teal-300 border border-slate-700 truncate max-w-full">
                                {{ auth()->user()->roleLabel() }}
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Keluar" title="Keluar"
                            class="w-full h-11 flex items-center rounded-xl text-sm font-semibold text-slate-200 bg-slate-900 hover:bg-rose-950/80 hover:text-rose-200 border border-slate-700 hover:border-rose-800/60 transition-colors cursor-pointer overflow-hidden whitespace-nowrap">
                            <span class="w-[61px] h-11 shrink-0 flex items-center justify-center">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </span>
                            <span class="sidebar-text-reveal pr-4">Keluar</span>
                        </button>
                    </form>
                </div>
            @endauth
        </aside>

        <!-- Backdrop for mobile menu -->
        <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" x-cloak
            class="fixed inset-0 bg-slate-950/50 z-30 md:hidden transition-opacity"></div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Topbar (Desktop) -->
            <header
                class="hidden md:flex bg-white border-b border-slate-200 px-8 py-4 items-center justify-between shadow-2xs">
                <div class="flex items-center space-x-3.5">
                    <button type="button" @click="toggleFold()"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 hover:text-blue-700 bg-slate-100 hover:bg-blue-50 border border-slate-200 transition cursor-pointer"
                        :title="sidebarFolded ? 'Buka Penuh Sidebar' : 'Lipat Sidebar (30%)'"
                        aria-label="Lipat atau Buka Sidebar">
                        <svg class="w-5 h-5 transition-transform duration-300"
                            :class="sidebarFolded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h10M4 18h16" />
                        </svg>
                    </button>

                    <span
                        class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        Supervisi Keperawatan RS St. Elisabeth Semarang
                    </span>
                    <span class="text-sm text-slate-500 font-medium">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                </div>

                <div class="flex items-center space-x-3">
                    @if (auth()->user()?->hasPermission('create_report'))
                        <a href="{{ route('supervisi.create') }}"
                            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Logbook Baru
                        </a>
                    @endif

                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white hover:bg-rose-50 hover:text-rose-700 border border-slate-300 hover:border-rose-200 transition cursor-pointer">
                                <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Keluar
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <!-- Flash Alert Messages -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-6">
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start shadow-xs"
                        role="alert">
                        <svg class="w-6 h-6 text-emerald-600 mr-3 flex-shrink-0 mt-0.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1 text-base font-medium">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-6">
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start shadow-xs"
                        role="alert">
                        <svg class="w-6 h-6 text-rose-600 mr-3 flex-shrink-0 mt-0.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1 text-base font-medium">
                            {{ session('error') }}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Main Page Content Slot -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>

            <!-- Hospital Footer -->
            <footer
                class="mt-auto border-t border-slate-200 bg-white py-4 px-6 sm:px-8 text-sm text-slate-500 flex flex-col md:flex-row justify-between items-center gap-3 shadow-2xs">
                <!-- Left: Hak Cipta & Unit Kerja -->
                <div class="flex items-center space-x-2 text-slate-600">
                    <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p class="font-medium text-center md:text-left">
                        &copy; {{ date('Y') }} <span class="font-bold text-slate-800">RS St. Elisabeth
                            Semarang</span> &middot; Direktorat Keperawatan
                    </p>
                </div>

                <!-- Center: Status Konektivitas Sistem Aktif -->
                <div
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 shadow-2xs">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span>Sistem Supervisi Keperawatan</span>
                </div>

                <!-- Right: Motto Rumah Sakit & Waktu Server -->
                <div class="flex items-center space-x-3 text-slate-500 text-xs">
                    <span class="hidden lg:inline italic text-slate-600">
                        &ldquo;Pancaran Cintanya Menyembuhkan Derita Sesama&rdquo;
                    </span>
                    {{-- <span class="hidden sm:inline text-slate-300">|</span>
                    <span class="font-mono text-slate-600 font-medium">
                        {{ now()->translatedFormat('d F Y') }}
                    </span> --}}
                </div>
            </footer>
        </div>
    </div>

    <script>
        function sidebarLayout() {
            return {
                mobileMenuOpen: false,
                sidebarFolded: false,
                sidebarHovering: false,
                isDesktop: window.innerWidth >= 768,
                fullWidth: 288,
                foldedWidth: 86, // 30% dari 288px
                sidebarTl: null,
                hoverEnterTimer: null,
                hoverLeaveTimer: null,

                get isCompact() {
                    return this.isDesktop && this.sidebarFolded && !this.sidebarHovering;
                },

                initSidebar() {
                    let hoverNav = false;
                    try {
                        this.sidebarFolded = localStorage.getItem('supervisi_sidebar_folded') === 'true';
                        hoverNav = sessionStorage.getItem('supervisi_sidebar_hover_nav') === 'true';
                        sessionStorage.removeItem('supervisi_sidebar_hover_nav');
                    } catch (e) {
                        this.sidebarFolded = false;
                    }

                    this.isDesktop = window.innerWidth >= 768;

                    if (this.isDesktop && this.sidebarFolded && hoverNav) {
                        this.sidebarHovering = true;
                    }

                    this.applySidebarAnimation(true);

                    if (this.sidebarHovering) {
                        setTimeout(() => {
                            if (this.sidebarHovering && this.$refs.sidebar && !this.$refs.sidebar.matches(':hover')) {
                                this.sidebarHovering = false;
                                this.applySidebarAnimation(false);
                            }
                        }, 180);
                    }

                    window.addEventListener('resize', () => {
                        const wasDesktop = this.isDesktop;
                        this.isDesktop = window.innerWidth >= 768;
                        if (wasDesktop !== this.isDesktop) {
                            this.clearHoverTimers();
                            this.sidebarHovering = false;
                            this.applySidebarAnimation(true);
                        }
                    });
                },

                clearHoverTimers() {
                    if (this.hoverEnterTimer) {
                        clearTimeout(this.hoverEnterTimer);
                        this.hoverEnterTimer = null;
                    }
                    if (this.hoverLeaveTimer) {
                        clearTimeout(this.hoverLeaveTimer);
                        this.hoverLeaveTimer = null;
                    }
                },

                onSidebarLinkClick() {
                    this.clearHoverTimers();
                    try {
                        if (this.isDesktop && this.sidebarFolded && this.sidebarHovering) {
                            sessionStorage.setItem('supervisi_sidebar_hover_nav', 'true');
                        } else {
                            sessionStorage.removeItem('supervisi_sidebar_hover_nav');
                        }
                    } catch (e) {}
                },

                toggleFold() {
                    this.clearHoverTimers();
                    this.sidebarFolded = !this.sidebarFolded;
                    this.sidebarHovering = false;
                    try {
                        localStorage.setItem('supervisi_sidebar_folded', this.sidebarFolded ? 'true' : 'false');
                        sessionStorage.removeItem('supervisi_sidebar_hover_nav');
                    } catch (e) {}
                    this.applySidebarAnimation(false);
                },

                onSidebarMouseEnter() {
                    if (!this.isDesktop || !this.sidebarFolded) {
                        return;
                    }
                    if (this.hoverLeaveTimer) {
                        clearTimeout(this.hoverLeaveTimer);
                        this.hoverLeaveTimer = null;
                    }
                    if (this.sidebarHovering) {
                        return;
                    }
                    this.hoverEnterTimer = setTimeout(() => {
                        this.hoverEnterTimer = null;
                        if (this.isDesktop && this.sidebarFolded && !this.sidebarHovering) {
                            this.sidebarHovering = true;
                            this.applySidebarAnimation(false);
                        }
                    }, 45);
                },

                onSidebarMouseLeave() {
                    if (!this.isDesktop || !this.sidebarFolded) {
                        return;
                    }
                    if (this.hoverEnterTimer) {
                        clearTimeout(this.hoverEnterTimer);
                        this.hoverEnterTimer = null;
                    }
                    if (!this.sidebarHovering) {
                        return;
                    }
                    this.hoverLeaveTimer = setTimeout(() => {
                        this.hoverLeaveTimer = null;
                        if (this.isDesktop && this.sidebarFolded && this.sidebarHovering) {
                            this.sidebarHovering = false;
                            this.applySidebarAnimation(false);
                        }
                    }, 75);
                },

                applySidebarAnimation(immediate = false) {
                    const sidebarEl = this.$refs.sidebar;
                    if (!sidebarEl) {
                        return;
                    }

                    const expandedBrand = this.$refs.expandedBrand;
                    const compactBrand = this.$refs.compactBrand;
                    const textReveals = sidebarEl.querySelectorAll('.sidebar-text-reveal');
                    const sectionHeaders = sidebarEl.querySelectorAll('.sidebar-section-header');
                    const sectionDividers = sidebarEl.querySelectorAll('.sidebar-section-divider');

                    if (this.sidebarTl) {
                        this.sidebarTl.kill();
                        this.sidebarTl = null;
                    }

                    if (!this.isDesktop) {
                        document.documentElement.classList.remove('sidebar-compact-init');
                        if (window.gsap) {
                            window.gsap.set(sidebarEl, { clearProps: 'width' });
                            if (expandedBrand) {
                                window.gsap.set(expandedBrand, { autoAlpha: 1, scale: 1, pointerEvents: 'auto' });
                            }
                            if (compactBrand) {
                                window.gsap.set(compactBrand, { autoAlpha: 0, scale: 0.86, pointerEvents: 'none' });
                            }
                            window.gsap.set(textReveals, { opacity: 1, x: 0, pointerEvents: 'auto' });
                            window.gsap.set(sectionHeaders, { opacity: 1, x: 0 });
                            window.gsap.set(sectionDividers, { opacity: 0 });
                        } else {
                            sidebarEl.style.width = '';
                        }
                        return;
                    }

                    const compact = this.isCompact;
                    const targetWidth = compact ? this.foldedWidth : this.fullWidth;

                    if (!window.gsap) {
                        sidebarEl.style.width = targetWidth + 'px';
                        document.documentElement.classList.toggle('sidebar-compact-init', compact);
                        return;
                    }

                    if (immediate) {
                        window.gsap.set(sidebarEl, { width: targetWidth });
                        if (expandedBrand) {
                            window.gsap.set(expandedBrand, {
                                autoAlpha: compact ? 0 : 1,
                                scale: compact ? 0.94 : 1,
                                pointerEvents: compact ? 'none' : 'auto',
                            });
                        }
                        if (compactBrand) {
                            window.gsap.set(compactBrand, {
                                autoAlpha: compact ? 1 : 0,
                                scale: compact ? 1 : 0.86,
                                pointerEvents: compact ? 'auto' : 'none',
                            });
                        }
                        window.gsap.set(textReveals, {
                            opacity: compact ? 0 : 1,
                            x: compact ? -6 : 0,
                            pointerEvents: compact ? 'none' : 'auto',
                        });
                        window.gsap.set(sectionHeaders, {
                            opacity: compact ? 0 : 1,
                            x: compact ? -4 : 0,
                        });
                        window.gsap.set(sectionDividers, {
                            opacity: compact ? 1 : 0,
                        });
                        document.documentElement.classList.remove('sidebar-compact-init');
                        return;
                    }

                    document.documentElement.classList.remove('sidebar-compact-init');

                    const tl = window.gsap.timeline({
                        defaults: { overwrite: 'auto' },
                    });
                    this.sidebarTl = tl;

                    if (compact) {
                        tl.to(textReveals, {
                            opacity: 0,
                            x: -6,
                            pointerEvents: 'none',
                            duration: 0.16,
                            ease: 'power2.out',
                        }, 0)
                        .to(sectionHeaders, {
                            opacity: 0,
                            x: -4,
                            duration: 0.15,
                            ease: 'power2.out',
                        }, 0)
                        .to(sectionDividers, {
                            opacity: 1,
                            duration: 0.2,
                            ease: 'power2.out',
                        }, 0.08)
                        .to(expandedBrand, {
                            autoAlpha: 0,
                            scale: 0.94,
                            pointerEvents: 'none',
                            duration: 0.18,
                            ease: 'power2.out',
                        }, 0)
                        .to(sidebarEl, {
                            width: this.foldedWidth,
                            duration: 0.32,
                            ease: 'power3.inOut',
                        }, 0.02)
                        .to(compactBrand, {
                            autoAlpha: 1,
                            scale: 1,
                            pointerEvents: 'auto',
                            duration: 0.24,
                            ease: 'back.out(1.4)',
                        }, 0.12);
                    } else {
                        tl.to(compactBrand, {
                            autoAlpha: 0,
                            scale: 0.86,
                            pointerEvents: 'none',
                            duration: 0.16,
                            ease: 'power2.out',
                        }, 0)
                        .to(sectionDividers, {
                            opacity: 0,
                            duration: 0.15,
                            ease: 'power2.out',
                        }, 0)
                        .to(sidebarEl, {
                            width: this.fullWidth,
                            duration: 0.32,
                            ease: 'power3.out',
                        }, 0)
                        .to(expandedBrand, {
                            autoAlpha: 1,
                            scale: 1,
                            pointerEvents: 'auto',
                            duration: 0.24,
                            ease: 'power2.out',
                        }, 0.08)
                        .to(sectionHeaders, {
                            opacity: 1,
                            x: 0,
                            duration: 0.22,
                            ease: 'power2.out',
                        }, 0.1)
                        .to(textReveals, {
                            opacity: 1,
                            x: 0,
                            pointerEvents: 'auto',
                            duration: 0.22,
                            stagger: 0.012,
                            ease: 'power2.out',
                        }, 0.08);
                    }
                }
            };
        }
    </script>
</body>

</html>
