@if ($paginator->hasPages())
    @php
        $totalPages = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
        $hasManyPages = $totalPages > 5;
        $oneThird = (int) ceil($totalPages / 3);
        $twoThirds = (int) ceil(($totalPages * 2) / 3);

        $showFirst = $hasManyPages && $currentPage > $oneThird;
        $showLast = $hasManyPages && $currentPage <= $twoThirds;

        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
    @endphp

    <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
        {{-- Keterangan Jumlah Data --}}
        <div class="text-slate-600 font-medium text-center sm:text-left">
            Menampilkan <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span> sampai <span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span> dari <span class="font-bold text-slate-800">{{ $paginator->total() }}</span> {{ $itemLabel ?? 'data' }}
        </div>

        {{-- Kontrol Navigasi Halaman --}}
        <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2">
            {{-- Tombol « Awal (Muncul jika halaman > 5 dan posisi > 1/3 total halaman) --}}
            @if ($showFirst)
                <a href="{{ $paginator->url(1) }}"
                    class="inline-flex items-center px-3 sm:px-3.5 py-2 rounded-lg font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    &laquo; Awal
                </a>
            @endif

            {{-- Tombol Sebelumnya (‹) --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-3 sm:px-3.5 py-2 rounded-lg font-medium text-slate-400 bg-slate-50 border border-slate-200 cursor-not-allowed">
                    <svg class="w-4 h-4 sm:mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="inline-flex items-center px-3 sm:px-3.5 py-2 rounded-lg font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    <svg class="w-4 h-4 sm:mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </a>
            @endif

            {{-- Nomor Halaman Aktif (2 sebelum dan 2 sesudah) --}}
            @for ($page = $startPage; $page <= $endPage; $page++)
                @if ($page == $currentPage)
                    <span aria-current="page"
                        class="inline-flex items-center justify-center min-w-[36px] sm:min-w-[40px] h-9 sm:h-10 px-2.5 sm:px-3.5 rounded-lg font-bold text-white bg-blue-600 shadow-2xs">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $paginator->url($page) }}"
                        class="inline-flex items-center justify-center min-w-[36px] sm:min-w-[40px] h-9 sm:h-10 px-2.5 sm:px-3.5 rounded-lg font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                        {{ $page }}
                    </a>
                @endif
            @endfor

            {{-- Tombol Berikutnya (›) --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="inline-flex items-center px-3 sm:px-3.5 py-2 rounded-lg font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <svg class="w-4 h-4 sm:ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center px-3 sm:px-3.5 py-2 rounded-lg font-medium text-slate-400 bg-slate-50 border border-slate-200 cursor-not-allowed">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <svg class="w-4 h-4 sm:ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif

            {{-- Tombol Akhir » (Muncul jika halaman > 5 dan posisi <= 2/3 total halaman) --}}
            @if ($showLast)
                <a href="{{ $paginator->url($totalPages) }}"
                    class="inline-flex items-center px-3 sm:px-3.5 py-2 rounded-lg font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    Akhir &raquo;
                </a>
            @endif
        </div>
    </nav>
@endif

