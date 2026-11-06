<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Supervisi Keperawatan RS. St. Elisabeth Semarang</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-full text-slate-900 antialiased font-sans">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-12">
        <!-- Left Panel: Hospital Identity -->
        <aside
            class="lg:col-span-5 bg-slate-900 text-slate-100 flex flex-col justify-between p-8 sm:p-12 border-b lg:border-b-0 lg:border-r border-slate-800">
            <div>
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-12 h-12 rounded-xl bg-teal-600 flex items-center justify-center text-white font-bold text-lg tracking-wider shadow-xs">
                        SE
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-teal-400">
                            RS. St. Elisabeth Semarang
                        </p>
                        <h1 class="text-lg font-bold text-white tracking-tight">
                            E-Supervisi Keperawatan
                        </h1>
                    </div>
                </div>

                <div class="mt-10 lg:mt-16 space-y-4">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-teal-950 text-teal-300 border border-teal-800">
                        Supervisi Keperawatan RS. St. Elisabeth Semarang
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight leading-snug">
                        Pencatatan Logbook & Monitoring Mutu Asuhan Keperawatan
                    </h2>
                    <p class="text-sm text-slate-300 leading-relaxed max-w-md">
                        Gunakan Nomor Induk Pegawai (NIP) dan kata sandi yang telah terdaftar.
                    </p>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-800/90 space-y-2">
                <p class="text-xs font-medium italic text-slate-300">
                    "Pancaran Cintanya Menyembuhkan Derita Sesama"
                </p>
                <p class="text-xs text-slate-400">
                    &copy; {{ date('Y') }} RS. St. Elisabeth Semarang &middot; Bidang Keperawatan
                </p>
            </div>
        </aside>

        {{-- Right Panel: Login Form --}}
        <main class="lg:col-span-7 flex items-center justify-center p-6 sm:p-12 bg-slate-50">
            <div class="w-full max-w-md" x-data="{ showPassword: false, submitting: false }">
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                            Masuk ke Sistem
                        </h2>
                        <p class="text-sm text-slate-600 mt-1">
                            Silakan masukkan kredensial Anda.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3"
                            role="alert">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm font-medium leading-relaxed">
                                {{ $errors->first() }}
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" @submit="submitting = true"
                        class="space-y-5">
                        @csrf

                        <div>
                            <label for="nip" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Nomor Induk Pegawai (NIP)
                            </label>
                            <input type="text" id="nip" name="nip" value="{{ old('nip') }}" required
                                autofocus autocomplete="username" inputmode="numeric" placeholder="Contoh: 1927181027"
                                class="w-full min-h-11 px-3.5 py-2.5 rounded-lg border {{ $errors->has('nip') ? 'border-rose-500 bg-rose-50/30' : 'border-slate-300 bg-white' }} text-slate-900 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password"
                                    required autocomplete="current-password" placeholder="Masukkan password"
                                    class="w-full min-h-11 pl-3.5 pr-12 py-2.5 rounded-lg border {{ $errors->has('password') ? 'border-rose-500 bg-rose-50/30' : 'border-slate-300 bg-white' }} text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition">
                                <button type="button" @click="showPassword = !showPassword"
                                    :aria-pressed="showPassword.toString()"
                                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    class="absolute inset-y-0 right-0 min-w-11 min-h-11 px-3 flex items-center justify-center text-slate-500 hover:text-slate-700 focus:outline-none focus:text-teal-600">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" :disabled="submitting"
                                class="w-full min-h-11 inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white text-sm font-semibold shadow-xs transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 cursor-pointer">
                                <span x-show="!submitting">Masuk ke Sistem</span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"
                                        aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Memverifikasi Kredensial...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- <p class="mt-6 text-center text-xs text-slate-500 leading-relaxed">
                    Kendala akses akun atau belum terdaftar pada modul Supervisi Keperawatan? Hubungi bagian Sistem Informasi rumah sakit.
                </p> --}}
            </div>
        </main>
    </div>
</body>

</html>
