<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>{{ $page->title }}</title>
    <link rel="icon" href="/images/logo-uptime.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

        /* Heartbeat bars */
        .hb-row { display: flex; gap: 2px; overflow: hidden; }
        .hb-bar { flex: 1; height: 36px; border-radius: 3px; min-width: 0;
                  cursor: default; transition: opacity .15s; }
        .hb-bar:hover { opacity: .65; }
        @media (max-width: 640px) { .hb-bar { height: 28px; } }

        /* 3D lift on hover — bottom shadow */
        .monitor-row {
            position: relative;
            background: #fff;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .monitor-row:hover {
            transform: translateY(-2px);
            background: #f8fbff;
            box-shadow: 0 6px 0 rgba(0,0,0,.05), 0 8px 20px rgba(14,165,233,.09);
            z-index: 1;
        }

        /* Dot blink for DOWN */
        @keyframes dot-blink { 0%,100%{opacity:1} 50%{opacity:.2} }
        .dot-blink { animation: dot-blink 1.6s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

@php
    $allMons = collect($sectionData)->flatMap(fn($s) => $s['monitors']);
    $totalUp = $allMons->where('last_status', 'up')->count();
    $totalAll = $allMons->count();
    $isOk    = $overallStatus === 'operational';
@endphp

{{-- ══════════ HEADER ══════════ --}}
<header class="bg-white border-b border-sky-100 shadow-sm">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center gap-4">
        <img src="/images/logo-uptime.png" alt="" class="w-10 h-10 object-contain flex-shrink-0">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 leading-tight">{{ $page->title }}</h1>
            @if($page->description)
            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $page->description }}</p>
            @endif
        </div>
        <div class="ml-auto flex-shrink-0 text-right hidden sm:block">
            <p class="text-xs text-gray-400">{{ now()->format('d M Y, H:i') }} WIB</p>
            <p class="text-xs text-sky-400 mt-0.5">
                <i class="fa-solid fa-rotate mr-0.5"></i>Auto-refresh 60s
            </p>
        </div>
    </div>
    {{-- Sky blue accent line at bottom of header --}}
    <div class="h-0.5 bg-gradient-to-r from-sky-400 via-blue-400 to-sky-300"></div>
</header>

{{-- ══════════ STATUS BANNER ══════════ --}}
<div class="max-w-5xl mx-auto w-full px-6 pt-6 pb-1">
    <div class="flex items-center gap-3 px-5 py-3.5 rounded-xl border
        {{ $isOk
            ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
            : 'bg-amber-50 border-amber-200 text-amber-700' }}">
        @if($isOk)
            <i class="fa-solid fa-circle-check text-lg flex-shrink-0"></i>
            <span class="font-bold text-base">Semua Sistem Beroperasi Normal</span>
        @else
            <i class="fa-solid fa-triangle-exclamation text-lg flex-shrink-0 dot-blink"></i>
            <span class="font-bold text-base">Ada Gangguan Pada Layanan</span>
        @endif
        <span class="ml-auto text-sm opacity-70 flex-shrink-0">
            {{ $totalUp }}/{{ $totalAll }} beroperasi
        </span>
    </div>
</div>

{{-- ══════════ CONTENT ══════════ --}}
<main class="flex-1 max-w-5xl w-full mx-auto px-6 py-6">

    @if(!empty($apiServices))
    <div class="mb-8">
        <h2 class="text-xl font-light text-gray-600 mb-3">Status API</h2>
        @foreach($apiServices as $svc)
        <div class="mb-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ $svc['label'] }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @forelse($svc['results'] as $result)
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col items-center text-center">
                    <p class="text-sm font-semibold text-gray-800 mb-2 leading-tight">{{ $result['label'] }}</p>
                    @if(isset($result['ms']))
                    <p class="text-xl font-bold text-gray-900">{{ $result['ms'] }}<sup class="text-xs font-normal text-gray-400 ml-0.5">ms</sup></p>
                    @endif
                    <p class="mt-2 text-sm font-semibold {{ ($result['connected'] ?? false) ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ ($result['connected'] ?? false) ? 'Terhubung' : 'Gagal' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">{{ $result['checked_at'] ?? '-' }}</p>
                </div>
                @empty
                <div class="col-span-6 py-6 text-center text-gray-400 text-sm">Belum ada data pengecekan.</div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @forelse($sectionData as $section)
    <div class="mb-8">

        {{-- Section heading --}}
        @if($section['name'])
        @php $secOk = $section['monitors']->every(fn($m) => $m->last_status === 'up'); @endphp
        <h2 class="text-xl font-light text-gray-600 mb-3 flex items-center gap-3">
            {{ $section['name'] }}
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                {{ $secOk ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                {{ $secOk ? 'Operasional' : 'Ada Gangguan' }}
            </span>
        </h2>
        @endif

        {{-- Monitor list — each row separate, no overflow-hidden on container --}}
        <div class="rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-50">
            @foreach($section['monitors'] as $monitor)
            @php
                $hb    = $section['heartbeats'][$monitor->id] ?? collect();
                $isUp  = $monitor->last_status === 'up';
                $bars  = $hb->values();
                $empty = max(0, 90 - $bars->count());
                $pct   = round((float)($monitor->uptime_30d ?? $monitor->uptime_percentage ?? 0), 1);
                $first = $loop->first;
                $last  = $loop->last;
            @endphp
            <div class="monitor-row px-5 py-4
                {{ $first ? 'rounded-t-xl' : '' }}
                {{ $last  ? 'rounded-b-xl' : '' }}">

                <div class="flex items-center gap-4">

                    {{-- Left: uptime badge + name --}}
                    <div class="flex items-center gap-3 w-56 flex-shrink-0 min-w-0">
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-md flex-shrink-0
                            {{ $isUp ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                            {{ $pct }}%
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate leading-tight">{{ $monitor->name }}</p>
                            <p class="text-[11px] text-gray-400 truncate mt-0.5 leading-tight">{{ $monitor->url }}</p>
                        </div>
                    </div>

                    {{-- Right: heartbeat bars --}}
                    <div class="flex-1 min-w-0">
                        <div class="hb-row">
                            @for($i = 0; $i < $empty; $i++)
                                <div class="hb-bar bg-gray-100"></div>
                            @endfor
                            @foreach($bars as $log)
                            @php
                                $bc  = match($log->status) { 'up' => 'bg-emerald-400', 'down' => 'bg-rose-400', default => 'bg-gray-200' };
                                $tip = ($log->checked_at?->format('d M H:i') ?? '-') . ' · ' . strtoupper($log->status ?? '-');
                                if (!empty($log->response_time)) $tip .= ' · ' . $log->response_time . 'ms';
                            @endphp
                                <div class="hb-bar {{ $bc }}" title="{{ $tip }}"></div>
                            @endforeach
                        </div>
                        <div class="flex justify-between text-[10px] text-gray-300 mt-1">
                            <span>90 hari lalu</span><span>sekarang</span>
                        </div>
                    </div>

                    {{-- Status indicator dot --}}
                    <div class="flex-shrink-0 hidden sm:flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full
                            {{ $isUp ? 'bg-emerald-400' : 'bg-rose-400 dot-blink' }}"></span>
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    @if(empty($apiServices))
    <div class="text-center py-20 text-gray-400">
        <i class="fa-solid fa-server text-4xl opacity-20 block mb-3"></i>
        <p class="text-sm">Belum ada monitor yang ditampilkan.</p>
    </div>
    @endif
    @endforelse

</main>

{{-- ══════════ FOOTER ══════════ --}}
<footer class="border-t border-gray-100 bg-white">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <img src="/images/logo-uptime.png" alt="" class="w-4 h-4 object-contain opacity-40">
            <span class="text-xs text-gray-300">WatchTower</span>
        </div>
        <span class="text-xs text-gray-300">Diperbarui {{ now()->format('d M Y, H:i') }} WIB</span>
    </div>
</footer>

</body>
</html>
