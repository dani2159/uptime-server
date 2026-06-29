@extends('layouts.app')
@section('title', 'Pagespeed — ' . $pagespeed->name)

@section('content')

{{-- Chart data passed via window variable to avoid double-quote conflicts in x-data attribute --}}
<script>
window._psChart = {
    labels : @json(json_decode($chartLabels)),
    perf   : @json(json_decode($chartPerf)),
    a11y   : @json(json_decode($chartA11y)),
    bp     : @json(json_decode($chartBp)),
    seo    : @json(json_decode($chartSeo)),
};
</script>

<div class="max-w-6xl mx-auto px-4 py-8" x-data="{
    showMetrics: { perf: true, a11y: true, bp: true, seo: true },
    chart: null,
    initChart() {
        if (typeof Chart === 'undefined') { setTimeout(() => this.initChart(), 100); return; }
        const ctx = document.getElementById('scoreChart').getContext('2d');
        const labels  = window._psChart.labels;
        const datasets = [
            { label: 'Performance',    data: window._psChart.perf,  borderColor:'#0ea5e9', backgroundColor:'#0ea5e920', key:'perf' },
            { label: 'Accessibility',  data: window._psChart.a11y,  borderColor:'#8b5cf6', backgroundColor:'#8b5cf620', key:'a11y' },
            { label: 'Best Practices', data: window._psChart.bp,    borderColor:'#f59e0b', backgroundColor:'#f59e0b20', key:'bp'   },
            { label: 'SEO',            data: window._psChart.seo,   borderColor:'#22c55e', backgroundColor:'#22c55e20', key:'seo'  },
        ];
        this.chart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: datasets.map(d => ({
                ...d,
                tension: 0.4,
                fill: true,
                pointRadius: labels.length > 20 ? 0 : 3,
                pointHoverRadius: 5,
                borderWidth: 2,
            }))},
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y ?? '—'}`
                        }
                    }
                },
                scales: {
                    y: { min: 0, max: 100, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color:'#94a3b8', font:{size:11} } },
                    x: { grid: { display: false }, ticks: { color:'#94a3b8', font:{size:10}, maxTicksLimit: 10 } },
                }
            }
        });
    },
    toggleMetric(key) {
        this.showMetrics[key] = !this.showMetrics[key];
        if (!this.chart) return;
        const map = {perf:0, a11y:1, bp:2, seo:3};
        const meta = this.chart.getDatasetMeta(map[key]);
        meta.hidden = !this.showMetrics[key];
        this.chart.update();
    }
}" x-init="initChart()">

    {{-- Breadcrumb + actions --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('pagespeed.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-gauge-high text-sky-500"></i>
                    {{ $pagespeed->name }}
                </h1>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400 dark:text-slate-500">
                    <span>{{ $pagespeed->url }}</span>
                    <span>·</span>
                    <span class="capitalize">{{ $pagespeed->strategy }}</span>
                    <span>·</span>
                    <span>Cek tiap {{ $pagespeed->interval_minutes < 60 ? $pagespeed->interval_minutes.' mnt' : ($pagespeed->interval_minutes/60).' jam' }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('pagespeed.check-now', $pagespeed) }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-xl text-xs transition">
                    <i class="fa-solid fa-rotate-right"></i> Cek Sekarang
                </button>
            </form>
            <form action="{{ route('pagespeed.toggle', $pagespeed) }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold px-4 py-2 rounded-xl text-xs transition">
                    <i class="fa-solid {{ $pagespeed->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                    {{ $pagespeed->is_active ? 'Pause' : 'Resume' }}
                </button>
            </form>
            <a href="{{ route('pagespeed.edit', $pagespeed) }}" class="flex items-center gap-1.5 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold px-4 py-2 rounded-xl text-xs transition">
                <i class="fa-solid fa-pen"></i> Edit
            </a>
            <form action="{{ route('pagespeed.destroy', $pagespeed) }}" method="POST" onsubmit="return confirm('Hapus monitor ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="flex items-center gap-1.5 border border-red-200 dark:border-red-900 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 font-semibold px-4 py-2 rounded-xl text-xs transition">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <div class="text-xs text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Checks Since</div>
            <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalChecks) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <div class="text-xs text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Last Check</div>
            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $lastCheck ? $lastCheck->checked_at->diffForHumans() : '—' }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <div class="text-xs text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Strategi</div>
            <div class="text-lg font-bold text-gray-900 dark:text-white capitalize">{{ $pagespeed->strategy }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <div class="text-xs text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Status</div>
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ $pagespeed->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }}"></span>
                <span class="text-lg font-bold {{ $pagespeed->is_active ? 'text-emerald-500' : 'text-gray-400' }}">
                    {{ $pagespeed->is_active ? 'Active' : 'Paused' }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5 items-stretch">

        {{-- Score history chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-sky-500 text-sm"></i>
                    Score History
                </h2>
                {{-- Metric toggles --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button @click="toggleMetric('perf')"
                        :class="showMetrics.perf ? 'opacity-100' : 'opacity-40'"
                        class="flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-lg border border-sky-200 dark:border-sky-800 text-sky-600 dark:text-sky-400 transition">
                        <span class="w-2 h-2 rounded-full bg-sky-500 inline-block"></span>Performance
                    </button>
                    <button @click="toggleMetric('a11y')"
                        :class="showMetrics.a11y ? 'opacity-100' : 'opacity-40'"
                        class="flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-lg border border-violet-200 dark:border-violet-800 text-violet-600 dark:text-violet-400 transition">
                        <span class="w-2 h-2 rounded-full bg-violet-500 inline-block"></span>Accessibility
                    </button>
                    <button @click="toggleMetric('bp')"
                        :class="showMetrics.bp ? 'opacity-100' : 'opacity-40'"
                        class="flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-lg border border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 transition">
                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>Best Practices
                    </button>
                    <button @click="toggleMetric('seo')"
                        :class="showMetrics.seo ? 'opacity-100' : 'opacity-40'"
                        class="flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 transition">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>SEO
                    </button>
                </div>
            </div>
            <div class="relative flex-1 min-h-[200px]">
                @if($checks->isEmpty())
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400 dark:text-slate-600 text-sm">
                        Belum ada data cek
                    </div>
                @endif
                <canvas id="scoreChart" style="width:100%;height:100%;"></canvas>
            </div>

            @if($checks->isNotEmpty())
            @php
                $validChecks = $checks->filter(fn($c) => !$c->error_message);
                $prev   = $validChecks->count() >= 2 ? $validChecks->nth(2)->first() : null;
                $avgPerf = $validChecks->avg('performance_score');
                $avgA11y = $validChecks->avg('accessibility_score');
                $avgBp   = $validChecks->avg('best_practices_score');
                $avgSeo  = $validChecks->avg('seo_score');
                $trend = fn($now, $old) => $old === null || $now === null ? null : $now - $old;
                $trendPerf = $trend($latest?->performance_score, $prev?->performance_score);
                $trendA11y = $trend($latest?->accessibility_score, $prev?->accessibility_score);
                $trendBp   = $trend($latest?->best_practices_score, $prev?->best_practices_score);
                $trendSeo  = $trend($latest?->seo_score, $prev?->seo_score);
                // last 30 perf scores for mini bars
                $miniBars = $validChecks->take(-30)->values();
            @endphp

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-800">
                {{-- Average + trend row --}}
                <div class="grid grid-cols-4 gap-3 mb-4">
                    @foreach([
                        ['Avg Perf',  $avgPerf,  $trendPerf,  '#0ea5e9'],
                        ['Avg A11y',  $avgA11y,  $trendA11y,  '#8b5cf6'],
                        ['Avg BP',    $avgBp,    $trendBp,    '#f59e0b'],
                        ['Avg SEO',   $avgSeo,   $trendSeo,   '#22c55e'],
                    ] as [$lbl, $avg, $delta, $clr])
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl px-3 py-2.5">
                        <div class="text-[10px] text-gray-400 dark:text-slate-500 font-medium mb-0.5">{{ $lbl }}</div>
                        <div class="flex items-end justify-between gap-1">
                            <span class="text-base font-black" style="color:{{ $clr }}">
                                {{ $avg !== null ? round($avg) : '—' }}
                            </span>
                            @if($delta !== null)
                                <span class="text-[10px] font-bold mb-0.5 {{ $delta > 0 ? 'text-emerald-500' : ($delta < 0 ? 'text-red-400' : 'text-gray-400') }}">
                                    {{ $delta > 0 ? '▲'.$delta : ($delta < 0 ? '▼'.abs($delta) : '—') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Mini performance history bars --}}
                <div>
                    <div class="text-[10px] text-gray-400 dark:text-slate-500 mb-1.5 font-medium uppercase tracking-wide">
                        Riwayat Performance ({{ $miniBars->count() }} cek terakhir)
                    </div>
                    <div class="flex items-end gap-0.5 h-8">
                        @foreach($miniBars as $bar)
                            @php
                                $s = $bar->performance_score ?? 0;
                                $h = max(10, $s);
                                $c = $s >= 90 ? '#22c55e' : ($s >= 50 ? '#f59e0b' : '#ef4444');
                            @endphp
                            <div class="flex-1 rounded-sm transition-all"
                                 style="height:{{ $h }}%;background:{{ $c }};opacity:0.85"
                                 title="{{ $bar->checked_at->format('d/m H:i') }}: {{ $s }}"></div>
                        @endforeach
                        {{-- Pad with empty slots if < 30 checks --}}
                        @for($i = $miniBars->count(); $i < 30; $i++)
                            <div class="flex-1 rounded-sm bg-gray-100 dark:bg-slate-800" style="height:10%"></div>
                        @endfor
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Performance Report --}}
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col">
            <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fa-solid fa-map text-sky-500 text-sm"></i>
                Performance Report
            </h2>

            @if($latest && !$latest->error_message)
                @php
                    $perf  = $latest->performance_score ?? 0;
                    $color = $perf >= 90 ? '#22c55e' : ($perf >= 50 ? '#f59e0b' : '#ef4444');
                    $dash  = (int)($perf * 2.51);
                @endphp

                {{-- Gauge --}}
                <div class="flex justify-center mb-5">
                    <div class="relative">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#e5e7eb" stroke-width="10" class="dark:stroke-slate-700"/>
                            <circle cx="60" cy="60" r="50" fill="none"
                                stroke="{{ $color }}" stroke-width="10"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $dash * 3.14 }} 314"
                                stroke-dashoffset="78.5"
                                transform="rotate(-90 60 60)"/>
                            <text x="60" y="67" text-anchor="middle" font-size="28" font-weight="800"
                                fill="{{ $color }}" font-family="Inter,sans-serif">{{ $perf }}</text>
                        </svg>
                        <div class="text-center text-xs font-semibold mt-1"
                            style="color:{{ $color }}">
                            {{ $perf >= 90 ? 'Good' : ($perf >= 50 ? 'Needs Improvement' : 'Poor') }}
                        </div>
                    </div>
                </div>

                {{-- 4 category scores --}}
                <div class="grid grid-cols-2 gap-2 mb-5">
                    @foreach([
                        ['Performance',   $latest->performance_score,    '#0ea5e9'],
                        ['Accessibility', $latest->accessibility_score,  '#8b5cf6'],
                        ['Best Practices',$latest->best_practices_score, '#f59e0b'],
                        ['SEO',           $latest->seo_score,            '#22c55e'],
                    ] as [$label, $score, $c])
                    <div class="bg-gray-50 dark:bg-slate-800 rounded-xl p-3 text-center">
                        <div class="text-lg font-black" style="color:{{ $c }}">{{ $score ?? '—' }}</div>
                        <div class="text-[10px] text-gray-400 dark:text-slate-500 leading-tight">{{ $label }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Performance metrics --}}
                <div class="space-y-0">
                    <div class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">Performance Metrics</div>
                    @php
                        $metrics = [
                            ['CUMULATIVE LAYOUT SHIFT', $latest->cls,         $latest->cls !== null ? number_format($latest->cls, 3) : null, null],
                            ['SPEED INDEX',             $latest->speed_index, $latest->speed_index !== null ? $latest->speed_index.' s' : null,
                                $latest->speed_index !== null ? ($latest->speed_index <= 3.4 ? 'good' : ($latest->speed_index <= 5.8 ? 'warn' : 'poor')) : null],
                            ['FIRST CONTENTFUL PAINT',  $latest->fcp,         $latest->fcp !== null ? $latest->fcp.' s' : null,
                                $latest->fcp !== null ? ($latest->fcp <= 1.8 ? 'good' : ($latest->fcp <= 3.0 ? 'warn' : 'poor')) : null],
                            ['LARGEST CONTENTFUL PAINT',$latest->lcp,         $latest->lcp !== null ? $latest->lcp.' s' : null,
                                $latest->lcp !== null ? ($latest->lcp <= 2.5 ? 'good' : ($latest->lcp <= 4.0 ? 'warn' : 'poor')) : null],
                            ['TOTAL BLOCKING TIME',     $latest->tbt,         $latest->tbt !== null ? $latest->tbt.' ms' : null,
                                $latest->tbt !== null ? ($latest->tbt <= 200 ? 'good' : ($latest->tbt <= 600 ? 'warn' : 'poor')) : null],
                        ];
                        $barColors = ['good' => '#22c55e', 'warn' => '#f59e0b', 'poor' => '#ef4444', null => '#94a3b8'];
                    @endphp
                    @foreach($metrics as [$mLabel, $mVal, $mDisplay, $mRating])
                    <div class="flex items-center gap-2 py-2 border-b border-gray-100 dark:border-slate-800 last:border-0">
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide truncate">{{ $mLabel }}</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $mDisplay ?? '—' }}</div>
                        </div>
                        <div class="w-2 h-8 rounded-full flex-shrink-0"
                            style="background:{{ $barColors[$mRating] ?? '#94a3b8' }}"></div>
                    </div>
                    @endforeach
                </div>

                <div class="text-[10px] text-gray-300 dark:text-slate-700 mt-3 text-center">
                    Data dari {{ $latest->checked_at->format('d/m/Y H:i') }}
                </div>

            @elseif($latest?->error_message)
                <div class="text-sm text-red-500 dark:text-red-400 text-center py-8">
                    <i class="fa-solid fa-circle-exclamation text-2xl mb-2 block"></i>
                    {{ $latest->error_message }}
                </div>
            @else
                <div class="text-sm text-gray-400 dark:text-slate-600 text-center py-12">
                    <i class="fa-solid fa-gauge-high text-3xl mb-2 block opacity-30"></i>
                    Belum ada data.<br>Klik "Cek Sekarang" untuk menjalankan pengecekan pertama.
                </div>
            @endif
        </div>
    </div>

    {{-- Check history table --}}
    @if($checks->isNotEmpty())
    <div class="mt-5 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-800">
            <h2 class="font-bold text-gray-900 dark:text-white text-sm">Riwayat Cek ({{ $checks->count() }} terakhir)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-slate-800">
                        <th class="text-left px-5 py-2.5 text-gray-400 dark:text-slate-500 font-semibold">Waktu</th>
                        <th class="text-center px-3 py-2.5 text-sky-500 font-semibold">Perf</th>
                        <th class="text-center px-3 py-2.5 text-violet-500 font-semibold">A11y</th>
                        <th class="text-center px-3 py-2.5 text-amber-500 font-semibold">BP</th>
                        <th class="text-center px-3 py-2.5 text-emerald-500 font-semibold">SEO</th>
                        <th class="text-center px-3 py-2.5 text-gray-400 font-semibold">CLS</th>
                        <th class="text-center px-3 py-2.5 text-gray-400 font-semibold">FCP</th>
                        <th class="text-center px-3 py-2.5 text-gray-400 font-semibold">LCP</th>
                        <th class="text-center px-3 py-2.5 text-gray-400 font-semibold">TBT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checks->reverse() as $check)
                    <tr class="border-b border-gray-50 dark:border-slate-800/50 hover:bg-gray-50 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-2.5 text-gray-500 dark:text-slate-400">
                            {{ $check->checked_at->format('d/m H:i') }}
                        </td>
                        @if($check->error_message)
                            <td colspan="8" class="px-3 py-2.5 text-red-500 dark:text-red-400">{{ $check->error_message }}</td>
                        @else
                            @foreach([$check->performance_score, $check->accessibility_score, $check->best_practices_score, $check->seo_score] as $s)
                            <td class="text-center px-3 py-2.5 font-mono font-bold"
                                style="color:{{ $s === null ? '#94a3b8' : ($s >= 90 ? '#22c55e' : ($s >= 50 ? '#f59e0b' : '#ef4444')) }}">
                                {{ $s ?? '—' }}
                            </td>
                            @endforeach
                            <td class="text-center px-3 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $check->cls ?? '—' }}</td>
                            <td class="text-center px-3 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $check->fcp ? $check->fcp.'s' : '—' }}</td>
                            <td class="text-center px-3 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $check->lcp ? $check->lcp.'s' : '—' }}</td>
                            <td class="text-center px-3 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $check->tbt ? $check->tbt.'ms' : '—' }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush
@endsection
