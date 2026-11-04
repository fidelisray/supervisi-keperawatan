<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Supervisi Keperawatan' }} - RS St. Elisabeth Semarang</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full text-slate-800 antialiased font-sans" x-data="{ mobileMenuOpen: false }">
    <div class="min-h-full flex flex-col md:flex-row">
        <!-- Mobile Header Bar -->
        <header class="md:hidden bg-blue-900 text-white px-4 py-3 flex items-center justify-between shadow-sm sticky top-0 z-30">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center font-bold text-amber-300 text-sm">
                    RSE
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight">Supervisi Keperawatan</h1>
                    <p class="text-[11px] text-blue-200">RS St. Elisabeth Semarang</p>
                </div>
            </div>
            <button 
                type="button" 
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="p-2 rounded-lg text-blue-100 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white"
                aria-label="Buka menu navigasi"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </header>

        <!-- Sidebar Navigation -->
        <aside 
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 w-72 bg-slate-900 text-slate-200 flex flex-col transition-transform duration-200 ease-in-out md:static md:translate-x-0 md:min-h-screen border-r border-slate-800"
        >
            <!-- Hospital Branding -->
            <div class="p-5 border-b border-slate-800 bg-slate-950/60">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-700 to-indigo-900 flex items-center justify-center shadow-inner border border-blue-400/20 text-white font-extrabold text-lg tracking-wider">
                        SE
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white tracking-tight leading-snug">RS ST. ELISABETH</h2>
                        <p class="text-xs text-blue-300 font-medium tracking-wide">SUPERVISI KEPERAWATAN</p>
                    </div>
                </div>
                <div class="mt-3 px-2.5 py-1.5 rounded-lg bg-blue-950/70 border border-blue-900/50">
                    <p class="text-[11px] text-slate-300 italic font-medium leading-tight">
                        "Pancaran Cintanya Menyembuhkan Derita Sesama"
                    </p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
                <div class="px-3 pb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Menu Utama
                </div>

                <a 
                    href="{{ route('supervisi.create') }}" 
                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('supervisi.create') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Input Logbook Shift</span>
                </a>

                <a 
                    href="{{ route('supervisi.index') }}" 
                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('supervisi.index') || request()->routeIs('supervisi.show') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Riwayat & Monitoring</span>
                </a>

                <div class="pt-5 px-3 pb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Analisis & Laporan
                </div>

                <div class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm text-slate-500 cursor-not-allowed">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Grafik Insiden</span>
                    </div>
                    <span class="text-[10px] uppercase font-bold bg-slate-800 text-slate-400 px-2 py-0.5 rounded">Fase 2</span>
                </div>
            </nav>

            <!-- User Information Card -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/80">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-800 border-2 border-blue-400/40 flex items-center justify-center font-bold text-white text-xs">
                        PA
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-white truncate">Purwanto Ali Sastra, S.Kom</p>
                        <p class="text-[11px] text-slate-400 truncate">NIP: 5508150824</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Backdrop for mobile menu -->
        <div 
            x-show="mobileMenuOpen" 
            @click="mobileMenuOpen = false"
            x-cloak
            class="fixed inset-0 bg-slate-950/50 z-30 md:hidden transition-opacity"
        ></div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Topbar (Desktop) -->
            <header class="hidden md:flex bg-white border-b border-slate-200 px-8 py-3.5 items-center justify-between shadow-2xs">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        Shift Operasional Rumah Sakit
                    </span>
                    <span class="text-xs text-slate-500 font-medium">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <a 
                        href="{{ route('supervisi.create') }}" 
                        class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Logbook Baru
                    </a>
                </div>
            </header>

            <!-- Flash Alert Messages -->
            @if(session('success'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-6">
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start shadow-xs" role="alert">
                        <svg class="w-5 h-5 text-emerald-600 mr-3 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1 text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-6">
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start shadow-xs" role="alert">
                        <svg class="w-5 h-5 text-rose-600 mr-3 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1 text-sm font-medium">
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
            <footer class="mt-auto border-t border-slate-200 bg-white py-4 px-8 text-center md:text-left text-xs text-slate-500 flex flex-col md:flex-row justify-between items-center gap-2">
                <p>&copy; {{ date('Y') }} RS St. Elisabeth Semarang — Tim Supervisi & Mutu Keperawatan.</p>
                <p class="text-slate-400">Versi 2.0 (Modern Clinical Architecture)</p>
            </footer>
        </div>
    </div>
</body>
</html>

