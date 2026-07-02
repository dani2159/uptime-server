@extends('layouts.app')
@section('title', 'Pagespeed Monitor')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-gauge-high text-sky-500"></i>
                Pagespeed Monitor
            </h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Monitor skor Google PageSpeed Insights secara berkala</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('pagespeed.check-all') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2 rounded-xl text-sm transition">
                    <i class="fa-solid fa-rotate"></i> Cek Semua
                </button>
            </form>
            <a href="{{ route('pagespeed.create') }}"
               class="flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-xl text-sm transition">
                <i class="fa-solid fa-plus"></i> Tambah Monitor
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if($monitors->isEmpty())
        <div class="text-center py-24 text-gray-400 dark:text-slate-600">
            <i class="fa-solid fa-gauge-high text-5xl mb-4 block opacity-30"></i>
            <p class="font-medium">Belum ada Pagespeed Monitor</p>
            <p class="text-xs mt-1">Tambah monitor untuk mulai memantau skor halaman web</p>
            <a href="{{ route('pagespeed.create') }}" class="mt-4 inline-flex items-center gap-2 bg-sky-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-sky-600 transition">
                <i class="fa-solid fa-plus"></i> Tambah Monitor
            </a>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($monitors as $monitor)
                @php
                    $latest = $monitor->checks->first();
                    $perf   = $latest?->performance_score;
                    $color  = $perf === null ? '#94a3b8'
                            : ($perf >= 90 ? '#22c55e' : ($perf >= 50 ? '#f59e0b' : '#ef4444'));
                    $dash   = $perf !== null ? (int)($perf * 2.51) : 0;
                @endphp
                <a href="{{ route('pagespeed.show', $monitor) }}"
                   class="block bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 hover:border-sky-400/50 dark:hover:border-sky-500/40 hover:shadow-md transition group">

                    <div class="flex items-start justify-between mb-4">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white text-sm truncate group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">
                                {{ $monitor->name }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-slate-500 truncate mt-0.5">{{ $monitor->url }}</div>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full border
                                    {{ $monitor->strategy === 'mobile'
                                        ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border-violet-200 dark:border-violet-800'
                                        : 'bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 border-sky-200 dark:border-sky-800' }}">
                                    <i class="fa-solid {{ $monitor->strategy === 'mobile' ? 'fa-mobile-screen' : 'fa-desktop' }} mr-0.5"></i>
                                    {{ ucfirst($monitor->strategy) }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500">
                                    <i class="fa-solid fa-clock mr-0.5"></i>{{ $monitor->interval_minutes < 60 ? $monitor->interval_minutes.'m' : ($monitor->interval_minutes/60).'h' }}
                                </span>
                                @if(!$monitor->is_active)
                                    <span class="text-[10px] font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-1.5 py-0.5 rounded-full">Paused</span>
                                @endif
                            </div>
                        </div>

                        {{-- Score gauge --}}
                        <div class="flex-shrink-0 ml-3">
                            <svg width="56" height="56" viewBox="0 0 56 56">
                                <circle cx="28" cy="28" r="24" fill="none" stroke="#e5e7eb" stroke-width="5" class="dark:stroke-slate-700"/>
                                <circle cx="28" cy="28" r="24" fill="none"
                                    stroke="{{ $color }}" stroke-width="5"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $dash }} 251"
                                    stroke-dashoffset="63"
                                    transform="rotate(-90 28 28)"/>
                                <text x="28" y="33" text-anchor="middle" font-size="13" font-weight="700"
                                    fill="{{ $color }}" font-family="Inter,sans-serif">
                                    {{ $perf ?? '—' }}
                                </text>
                            </svg>
                        </div>
                    </div>

                    {{-- Score bars --}}
                    @if($latest && !$latest->error_message)
                    <div class="space-y-1.5">
                        @foreach([
                            ['Performance',   $latest->performance_score],
                            ['Accessibility', $latest->accessibility_score],
                            ['Best Practices',$latest->best_practices_score],
                            ['SEO',           $latest->seo_score],
                        ] as [$label, $score])
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 w-24 flex-shrink-0">{{ $label }}</span>
                            <div class="flex-1 h-1.5 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    style="width:{{ $score ?? 0 }}%;
                                    background:{{ $score === null ? '#94a3b8' : ($score >= 90 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444')) }}">
                                </div>
                            </div>
                            <span class="text-[10px] font-mono font-semibold w-6 text-right"
                                style="color:{{ $score === null ? '#94a3b8' : ($score >= 90 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444')) }}">
                                {{ $score ?? '—' }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @elseif($latest?->error_message)
                        <div class="text-xs text-red-500 dark:text-red-400 truncate"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $latest->error_message }}</div>
                    @else
                        <div class="text-xs text-gray-400 dark:text-slate-500">Belum ada data — cek pertama akan segera berjalan</div>
                    @endif

                    @if($latest)
                        <div class="text-[10px] text-gray-300 dark:text-slate-600 mt-3 text-right">
                            Cek terakhir {{ $latest->checked_at->diffForHumans() }}
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
