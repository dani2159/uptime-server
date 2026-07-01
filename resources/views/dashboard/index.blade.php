@extends('layouts.kuma')
@section('title', ($selected ? $selected->name . ' — ' : '') . 'Dashboard')

{{-- ═══ SIDEBAR ═══ --}}
@section('sidebar')
<div class="flex flex-col h-full" x-data="sidebar()">

    {{-- Tambah + Refresh --}}
    <div class="p-4 flex gap-2 flex-shrink-0">
        <button onclick="openCreateModal()"
                class="flex-1 flex items-center justify-center gap-1.5
                       bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                       text-white py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-all">
            <i class="fa-solid fa-plus text-sm"></i>
            Tambah Monitor
        </button>
        <button @click="checkAll()" :disabled="checking"
                class="w-10 h-10 flex items-center justify-center rounded-xl border border-sky-200
                       dark:border-slate-600 bg-sky-50 dark:bg-slate-700 hover:bg-sky-100
                       dark:hover:bg-slate-600 text-sky-600 dark:text-sky-400 transition-colors
                       disabled:opacity-40 flex-shrink-0"
                title="Cek semua monitor">
            <i class="fa-solid fa-rotate-right text-sm" :class="checking ? 'animate-spin' : ''"></i>
        </button>
    </div>

    {{-- Stats ringkas --}}
    <div class="px-4 pb-3 flex-shrink-0 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/30 py-2">
            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ $stats['up'] }}</p>
            <p class="text-[10px] text-green-500 dark:text-green-500 font-medium uppercase tracking-wide">Up</p>
        </div>
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 py-2">
            <p class="text-lg font-bold text-red-500 dark:text-red-400">{{ $stats['down'] }}</p>
            <p class="text-[10px] text-red-400 dark:text-red-400 font-medium uppercase tracking-wide">Down</p>
        </div>
        <div class="rounded-xl bg-sky-50 dark:bg-sky-900/20 border border-sky-100 dark:border-sky-800/30 py-2">
            <p class="text-lg font-bold text-sky-600 dark:text-sky-400">{{ $stats['total'] }}</p>
            <p class="text-[10px] text-sky-400 dark:text-sky-500 font-medium uppercase tracking-wide">Total</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="px-4 pb-2 flex-shrink-0">
        <form method="GET" action="{{ route('dashboard') }}">
            @if($selected)<input type="hidden" name="selected" value="{{ $selected->id }}">@endif
            @if(request('tag'))<input type="hidden" name="tag" value="{{ request('tag') }}">@endif
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-sky-400 text-xs"></i>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari monitor..."
                       class="w-full pl-9 pr-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200
                              dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200
                              placeholder-sky-300 dark:placeholder-slate-500
                              focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700
                              focus:border-sky-400 dark:focus:border-sky-500">
            </div>
        </form>
    </div>

    {{-- Tag filter --}}
    @if(isset($allTags) && $allTags->isNotEmpty())
    <div class="px-4 pb-2 flex-shrink-0 flex flex-wrap gap-1">
        <a href="{{ route('dashboard', array_filter(['q' => $search ?: null, 'selected' => $selected?->id])) }}"
           class="text-[10px] px-2 py-0.5 rounded-full border font-medium transition-colors
               {{ !request('tag') ? 'bg-sky-500 text-white border-sky-500' : 'border-gray-200 dark:border-slate-600 text-gray-500 dark:text-slate-400 hover:border-sky-300' }}">
            Semua
        </a>
        @foreach($allTags as $t)
        <a href="{{ route('dashboard', array_filter(['q' => $search ?: null, 'tag' => $t->id, 'selected' => $selected?->id])) }}"
           class="text-[10px] px-2 py-0.5 rounded-full border font-medium transition-colors
               {{ request('tag') == $t->id ? 'text-white' : 'text-gray-500 dark:text-slate-400' }}"
           style="{{ request('tag') == $t->id ? "background:{$t->color};border-color:{$t->color}" : "border-color:{$t->color}40;color:{$t->color}" }}">
            {{ $t->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- Daftar monitor --}}
    <div class="sidebar-list flex-1 overflow-y-auto px-2 pb-2">
        @forelse($monitors as $monitor)
        @php
            $isSelected = $selected && $selected->id === $monitor->id;
            $uptime = $monitor->uptime_24h ?? $monitor->uptime_percentage;
            $miniHb = $monitor->heartbeatLogs->take(15)->reverse()->values();
            $isUp   = $monitor->last_status === 'up';
            $isDown = $monitor->last_status === 'down';
        @endphp

        <a href="{{ route('dashboard', array_filter(['selected' => $monitor->id, 'q' => $search ?: null])) }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl mb-1 border transition-all
               {{ $isSelected
                   ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-200 dark:border-sky-700/50 shadow-sm'
                   : 'border-transparent hover:bg-gray-50 dark:hover:bg-slate-700/50 hover:border-gray-100 dark:hover:border-slate-600' }}">

            {{-- Status dot --}}
            <div class="relative flex-shrink-0 w-3 h-3">
                @if($isDown)
                <span class="absolute inset-0 rounded-full bg-red-400 animate-ping opacity-75"></span>
                @endif
                <span class="relative block w-3 h-3 rounded-full
                    {{ $isUp ? 'bg-green-400' : ($isDown ? 'bg-red-500' : 'bg-gray-300 dark:bg-slate-500') }}">
                </span>
            </div>

            {{-- Nama + info --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate leading-tight
                    {{ $isSelected ? 'text-sky-700 dark:text-sky-400' : 'text-gray-700 dark:text-slate-200' }}">
                    {{ $monitor->name }}
                </p>
                <p class="text-[11px] truncate leading-tight mt-0.5
                    {{ $isDown ? 'text-red-400' : 'text-gray-400 dark:text-slate-500' }}">
                    @if($isDown)
                        <i class="fa-solid fa-arrow-down text-[9px]"></i> Down {{ $monitor->last_down_at?->diffForHumans() ?? '—' }}
                    @else
                        {{ $monitor->domain }}
                    @endif
                </p>
                @if($monitor->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1 mt-1">
                    @foreach($monitor->tags as $tag)
                    <span class="text-[9px] px-1.5 py-0 rounded-full font-medium text-white leading-4"
                          style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Uptime + mini bars --}}
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                @if($monitor->last_is_slow && $isUp)
                <span class="text-[9px] px-1.5 py-0 rounded-full font-bold bg-yellow-400 text-white leading-4">SLOW</span>
                @endif
                <span class="text-[11px] font-bold
                    {{ $isUp ? 'text-green-500' : ($isDown ? 'text-red-500' : 'text-gray-400 dark:text-slate-500') }}">
                    {{ is_null($uptime) ? '—' : number_format((float)$uptime, 0) . '%' }}
                </span>
                <div class="flex items-end gap-px">
                    @for($i = $miniHb->count(); $i < 15; $i++)
                        <div class="w-[3px] h-3 rounded-sm bg-gray-200 dark:bg-slate-600"></div>
                    @endfor
                    @foreach($miniHb as $log)
                        <div class="w-[3px] rounded-sm
                            {{ $log->status === 'up' ? 'bg-sky-400' : ($log->status === 'down' ? 'bg-red-400' : 'bg-gray-300 dark:bg-slate-600') }}"
                             style="height:{{ $log->status === 'up' ? '12px' : '16px' }};"></div>
                    @endforeach
                </div>
            </div>
        </a>
        @empty
        <div class="px-4 py-10 text-center">
            <i class="fa-solid fa-satellite-dish text-3xl text-gray-300 dark:text-slate-600 mb-3 block"></i>
            <p class="text-gray-400 dark:text-slate-500 text-sm">{{ $search ? 'Tidak ditemukan.' : 'Belum ada monitor.' }}</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

{{-- ═══ MAIN PANEL ═══ --}}
@section('main')
@if($selected)
@php
    $hbFilled = $heartbeats->count();
    $hbEmpty  = max(0, 90 - $hbFilled);
    $uptime24 = $selected->uptime_24h;
    $uptime30 = $selected->uptime_30d;
    $sslDays    = $selected->ssl_days_remaining;
    $domainDays = $selected->domain_expiry_days_remaining;
    $isUp     = $selected->last_status === 'up';
    $isDown   = $selected->last_status === 'down';
@endphp

<div class="p-6">

    {{-- Header monitor + aksi --}}
    <div class="flex items-start justify-between mb-5 gap-4"
         x-data="{
             checking: false,
             async cekNow() {
                 const isDark = document.documentElement.classList.contains('dark');
                 const _swal = await Swal.fire({
                     title: 'Check Monitor',
                     text: 'Kirim notifikasi WA/Telegram jika DOWN?',
                     icon: 'question',
                     showDenyButton: true,
                     showCancelButton: true,
                     confirmButtonText: '🔔 Cek + Kirim Notif',
                     denyButtonText: '🔕 Cek Saja',
                     cancelButtonText: 'Batal',
                     confirmButtonColor: '#0ea5e9',
                     denyButtonColor: '#6b7280',
                     background: isDark ? '#1e293b' : '#fff',
                     color: isDark ? '#e2e8f0' : '#111827',
                 });
                 if (_swal.isDismissed) return;
                 const notify = _swal.isConfirmed ? 1 : 0;
                 this.checking = true;
                 try {
                     const r = await fetch('{{ route('monitors.check-now', $selected) }}?notify=' + notify, {
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                     });
                     const d = await r.json();
                     this.checking = false;
                     Swal.fire({
                         icon: d.status === 'up' ? 'success' : (d.status === 'down' ? 'error' : 'info'),
                         title: d.message, toast: true, position: 'top-end',
                         timer: 3000, showConfirmButton: false, timerProgressBar: true,
                         background: isDark ? '#1e293b' : '#fff',
                         color: isDark ? '#e2e8f0' : '#111827',
                     });
                     setTimeout(() => location.reload(), 1600);
                 } catch(e) {
                     this.checking = false;
                 }
             }
         }">
        {{-- Info --}}
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-[10px] uppercase font-bold tracking-widest px-2.5 py-1 rounded-lg
                    bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-700/50">
                    <i class="fa-solid fa-{{ match($selected->type) { 'http','keyword' => 'globe', 'ping' => 'wifi', 'tcp' => 'plug', 'dns' => 'server', 'push' => 'satellite-dish', default => 'globe' } }} mr-1"></i>
                    {{ strtoupper($selected->type) }}
                </span>
                @if(!$selected->is_active)
                <span class="text-[10px] uppercase font-bold tracking-widest px-2.5 py-1 rounded-lg
                    bg-orange-100 dark:bg-orange-900/30 text-orange-500 dark:text-orange-400 border border-orange-200 dark:border-orange-700/40">
                    <i class="fa-solid fa-pause mr-1"></i>PAUSED
                </span>
                @endif
            </div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100 leading-tight">{{ $selected->name }}</h1>
            <a href="{{ $selected->url }}" target="_blank"
               class="text-sm text-sky-500 hover:text-sky-700 dark:hover:text-sky-300 hover:underline mt-0.5 inline-flex items-center gap-1">
                {{ $selected->url }}
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
        </div>

        {{-- Aksi --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            <form method="POST" action="{{ route('monitors.toggle', $selected) }}" class="contents">
                @csrf @method('PATCH')
                <button type="submit"
                        class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border font-semibold transition-colors
                            {{ $selected->is_active
                                ? 'border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-500 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-600'
                                : 'border-sky-200 dark:border-sky-700 bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/50' }}">
                    <i class="fa-solid {{ $selected->is_active ? 'fa-pause' : 'fa-play' }} text-[10px]"></i>
                    {{ $selected->is_active ? 'Pause' : 'Resume' }}
                </button>
            </form>

            {{-- Cek button dengan animasi --}}
            <button @click="cekNow()" :disabled="checking"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-sky-200
                           dark:border-sky-700 bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400
                           hover:bg-sky-100 dark:hover:bg-sky-900/50 font-semibold transition-all
                           disabled:opacity-60 disabled:cursor-wait">
                <i class="fa-solid text-[11px]" :class="checking ? 'fa-spinner fa-spin' : 'fa-rotate-right'"></i>
                <span x-text="checking ? 'Checking...' : 'Cek'"></span>
            </button>

            {{-- Silence button --}}
            <button @click="silenceMonitor({{ $selected->id }}, '{{ addslashes($selected->name) }}')"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-orange-200
                           dark:border-orange-700/50 bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400
                           hover:bg-orange-100 font-semibold transition-colors">
                <i class="fa-solid fa-bell-slash text-[10px]"></i> Silence
            </button>

            <button onclick="openEditModal()"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-gray-200
                           dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300
                           hover:bg-gray-50 dark:hover:bg-slate-600 font-semibold transition-colors">
                <i class="fa-solid fa-pen-to-square text-[10px]"></i> Edit
            </button>

            <button @click="deleteMonitor({{ $selected->id }}, '{{ addslashes($selected->name) }}')"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg bg-red-500 hover:bg-red-600
                           text-white font-semibold transition-colors">
                <i class="fa-solid fa-trash text-[10px]"></i> Hapus
            </button>
        </div>
    </div>

    {{-- Heartbeat bar --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 pt-5 pb-4 mb-5">
        <div class="flex items-end gap-[2px] overflow-hidden mb-3" style="height:64px;">
            @for($i = 0; $i < $hbEmpty; $i++)
                <div class="flex-1 min-w-[3px] rounded-t-sm bg-sky-50 dark:bg-slate-700 border border-sky-100 dark:border-slate-600"
                     style="height:20px;align-self:flex-end;"></div>
            @endfor
            @foreach($heartbeats as $log)
            @php
                $rt   = (int)($log->response_time ?? 400);
                $barH = max(16, min(64, (int)(16 + ($rt / 2500) * 48)));
                $bc   = match($log->status) {
                    'up'    => 'from-sky-400 to-blue-500',
                    'down'  => 'from-red-400 to-red-600',
                    default => 'from-gray-200 to-gray-300',
                };
                $tt = strtoupper($log->status)
                    . ' · ' . ($log->checked_at?->format('d/m H:i') ?? '')
                    . ($log->response_time ? ' · ' . $log->response_time . 'ms' : '');
            @endphp
            <div class="flex-1 min-w-[3px] rounded-t-sm bg-gradient-to-t {{ $bc }}
                         cursor-default transition-opacity hover:opacity-70"
                 style="height:{{ $barH }}px;align-self:flex-end;"
                 title="{{ $tt }}">
            </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-400 dark:text-slate-500">
                <span class="text-gray-600 dark:text-slate-300 font-medium">{{ $hbFilled }}</span> heartbeat ·
                interval <span class="text-gray-600 dark:text-slate-300 font-medium">{{ $selected->check_interval }} mnt</span>
            </p>
            @if($isUp)
            <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 text-white text-sm font-bold shadow-sm">
                <span class="w-2 h-2 rounded-full bg-white/80"></span> UP
            </span>
            @elseif($isDown)
            <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-gradient-to-r from-red-400 to-red-600 text-white text-sm font-bold shadow-sm animate-pulse">
                <span class="w-2 h-2 rounded-full bg-white/80"></span> DOWN
            </span>
            @else
            <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-gray-400 dark:bg-slate-600 text-white text-sm font-bold shadow-sm">
                <span class="w-2 h-2 rounded-full bg-white/80"></span> PENDING
            </span>
            @endif
            <p class="text-xs text-gray-400 dark:text-slate-500">
                Cek terakhir: <span class="text-gray-600 dark:text-slate-300 font-medium">{{ $selected->last_checked_at?->diffForHumans() ?? '—' }}</span>
            </p>
        </div>
    </div>

    {{-- 5 Stats cards (with FA icons) --}}
    <div class="grid grid-cols-5 gap-3 mb-5">
        @php
            $cards = [
                ['icon' => 'fa-bolt',           'label' => 'Response',    'sub' => 'Saat ini',
                 'val' => $selected->last_response_time !== null ? $selected->last_response_time . ' ms' : '—',
                 'color' => 'text-sky-700 dark:text-sky-400', 'bg' => 'bg-sky-50 dark:bg-sky-900/20 border-sky-100 dark:border-sky-800/30'],
                ['icon' => 'fa-chart-simple',   'label' => 'Avg Response','sub' => '24 jam',
                 'val' => $avgResponse24h !== null ? $avgResponse24h . ' ms' : '—',
                 'color' => 'text-sky-700 dark:text-sky-400', 'bg' => 'bg-sky-50 dark:bg-sky-900/20 border-sky-100 dark:border-sky-800/30'],
                ['icon' => 'fa-arrow-trend-up', 'label' => 'Uptime',      'sub' => '24 jam',
                 'val' => $uptime24 !== null ? $uptime24 . '%' : '—',
                 'color' => is_null($uptime24) ? 'text-gray-400 dark:text-slate-500' : ($uptime24 >= 99 ? 'text-green-600 dark:text-green-400' : ($uptime24 >= 95 ? 'text-yellow-500' : 'text-red-500 dark:text-red-400')),
                 'bg' => 'bg-green-50 dark:bg-green-900/20 border-green-100 dark:border-green-800/30'],
                ['icon' => 'fa-calendar-check', 'label' => 'Uptime',      'sub' => '30 hari',
                 'val' => $uptime30 !== null ? $uptime30 . '%' : '—',
                 'color' => is_null($uptime30) ? 'text-gray-400 dark:text-slate-500' : ($uptime30 >= 99 ? 'text-green-600 dark:text-green-400' : ($uptime30 >= 95 ? 'text-yellow-500' : 'text-red-500 dark:text-red-400')),
                 'bg' => 'bg-green-50 dark:bg-green-900/20 border-green-100 dark:border-green-800/30'],
                ['icon' => 'fa-lock',           'label' => 'SSL Cert',    'sub' => $selected->ssl_expiry_at?->format('d M Y') ?? 'N/A',
                 'val' => $sslDays !== null ? $sslDays . ' hari' : '—',
                 'color' => is_null($sslDays) ? 'text-gray-400 dark:text-slate-500' : ($sslDays <= 7 ? 'text-red-600 dark:text-red-400' : ($sslDays <= 30 ? 'text-yellow-500' : 'text-sky-600 dark:text-sky-400')),
                 'bg' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800/30'],
                ['icon' => 'fa-calendar-xmark', 'label' => 'Domain Expiry', 'sub' => $selected->domain_expiry_at ? \Carbon\Carbon::parse($selected->domain_expiry_at)->format('d M Y') : 'N/A',
                 'val' => $domainDays !== null ? $domainDays . ' hari' : '—',
                 'color' => is_null($domainDays) ? 'text-gray-400 dark:text-slate-500' : ($domainDays <= 7 ? 'text-red-600 dark:text-red-400' : ($domainDays <= 30 ? 'text-yellow-500' : 'text-violet-600 dark:text-violet-400')),
                 'bg' => 'bg-violet-50 dark:bg-violet-900/20 border-violet-100 dark:border-violet-800/30'],
            ];
        @endphp
        @foreach($cards as $c)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border {{ $c['bg'] }} px-4 py-4 text-center shadow-sm">
            <i class="fa-solid {{ $c['icon'] }} text-xl {{ $c['color'] }} mb-2 block"></i>
            <p class="text-xs font-semibold text-gray-500 dark:text-slate-400">{{ $c['label'] }}</p>
            <p class="text-[10px] text-gray-400 dark:text-slate-500">{{ $c['sub'] }}</p>
            <p class="text-xl font-bold mt-1.5 {{ $c['color'] }}">{{ $c['val'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Chart --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4 mb-5 w-full">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">
            <i class="fa-solid fa-chart-line text-sky-400 mr-1.5"></i>
            Response Time
            <span class="text-xs font-normal text-gray-400 dark:text-slate-500 ml-1">48 pengecekan terakhir</span>
        </h3>
        <div class="w-full relative" style="height:160px;">
            <canvas id="responseChart"></canvas>
        </div>
    </div>

    {{-- Bottom grid: IP + Log --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-sky-50 dark:border-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-globe text-sky-400 text-sm"></i>
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200">{{ $selected->domain }}</h2>
            </div>
            @php $ips = $selected->ips()->where('is_active', true)->get(); @endphp
            @forelse($ips as $ip)
            <div class="px-4 py-2.5 flex items-center justify-between border-b border-gray-50 dark:border-slate-700/50 last:border-0">
                <div>
                    <span class="font-mono text-xs text-gray-700 dark:text-slate-300">{{ $ip->ip_address }}</span>
                    <span class="ml-1.5 text-[10px] uppercase text-gray-400 dark:text-slate-500 bg-gray-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">{{ $ip->type }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    @if($ip->last_ping_ms)
                        <span class="text-xs text-sky-500 font-mono">{{ $ip->last_ping_ms }}ms</span>
                    @endif
                    <span class="w-2 h-2 rounded-full
                        {{ $ip->status === 'up' ? 'bg-green-400' : ($ip->status === 'down' ? 'bg-red-400' : 'bg-gray-300 dark:bg-slate-500') }}">
                    </span>
                </div>
            </div>
            @empty
            <p class="px-4 py-6 text-xs text-gray-400 dark:text-slate-500 text-center">Belum ada IP.</p>
            @endforelse
        </div>

        <div class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-sky-50 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200">
                    <i class="fa-solid fa-list-check text-sky-400 mr-1.5"></i>Log Terbaru
                </h2>
                <a href="{{ route('monitors.show', $selected) }}"
                   class="text-xs text-sky-500 hover:text-sky-700 dark:hover:text-sky-300 hover:underline">Lihat semua →</a>
            </div>
            <table class="w-full text-xs">
                <thead class="bg-sky-50/50 dark:bg-slate-700/30 text-gray-400 dark:text-slate-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2 text-left">Waktu</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-right">Response</th>
                        <th class="px-4 py-2 text-left hidden sm:table-cell">Pesan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                    @php $logs = $selected->logs()->latest('checked_at')->limit(12)->get(); @endphp
                    @forelse($logs as $log)
                    <tr class="hover:bg-sky-50/40 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-4 py-2 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $log->checked_at->format('d/m H:i:s') }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                {{ $log->status === 'up' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                    : ($log->status === 'down' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                        : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400') }}">
                                {{ strtoupper($log->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono text-sky-600 dark:text-sky-400">{{ $log->response_time ?? '—' }}ms</td>
                        <td class="px-4 py-2 text-gray-400 dark:text-slate-500 truncate max-w-[180px] hidden sm:table-cell">{{ $log->message ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-slate-500">Belum ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@else
{{-- Empty state --}}
<div class="flex items-center justify-center h-full">
    <div class="text-center max-w-sm">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl
                    bg-gradient-to-br from-sky-100 to-blue-100 dark:from-sky-900/30 dark:to-blue-900/30
                    border border-sky-200 dark:border-sky-700/40 mb-5">
            <i class="fa-solid fa-chart-line text-4xl text-sky-400"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-700 dark:text-slate-200 mb-2">Belum ada monitor</h2>
        <p class="text-sm text-gray-400 dark:text-slate-500 mb-6">Tambahkan monitor pertama kamu untuk mulai memantau uptime layanan secara real-time.</p>
        <button onclick="openCreateModal()"
                class="inline-flex items-center gap-2
                       bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                       text-white px-6 py-3 rounded-xl font-semibold shadow-sm transition-all">
            <i class="fa-solid fa-plus"></i>
            Tambah Monitor
        </button>
    </div>
</div>
@endif
@endsection

{{-- ═══ MODAL Add/Edit Monitor ═══ --}}
@push('modals')
<div x-data="monitorModal()"
     x-cloak
     @open-monitor-modal.window="openModal($event.detail)"
     x-show="open"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 dark:bg-black/70 backdrop-blur-sm">

    <div @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg
                border border-sky-100 dark:border-slate-700 flex flex-col max-h-[90vh]">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-sky-100 dark:border-slate-700 flex-shrink-0">
            <h2 class="text-base font-bold text-gray-800 dark:text-slate-100">
                <i class="fa-solid fa-satellite-dish text-sky-500 mr-2"></i>
                <span x-text="mode === 'create' ? 'Tambah Monitor Baru' : 'Edit Monitor'"></span>
            </h2>
            <button @click="open = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-400 dark:text-slate-400 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Modal body (scrollable) --}}
        <div class="modal-scroll flex-1 overflow-y-auto px-6 py-5 space-y-4">

            {{-- Name --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Nama Monitor <span class="text-red-400">*</span></label>
                <input type="text" x-model="form.name" placeholder="Mis: Google, API Produksi..."
                       class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                <p x-show="errors.name" x-text="errors.name?.[0]" class="text-red-500 dark:text-red-400 text-xs mt-1"></p>
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Tipe Monitor <span class="text-red-400">*</span></label>
                <select x-model="form.type"
                        @change="onTypeChange()"
                        class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                    <optgroup label="Web">
                        <option value="http">HTTP(S) — Cek URL</option>
                        <option value="keyword">Keyword — Cari teks dalam respons</option>
                    </optgroup>
                    <optgroup label="Infrastruktur">
                        <option value="ping">Ping — ICMP ke host</option>
                        <option value="tcp">TCP Port — Cek port terbuka</option>
                        <option value="dns">DNS — Resolve domain</option>
                        <option value="docker">Docker Container</option>
                    </optgroup>
                    <optgroup label="Database">
                        <option value="database">Database (MySQL/PgSQL/Redis)</option>
                    </optgroup>
                    <optgroup label="Domain">
                        <option value="whois">WHOIS / Domain Expiry</option>
                    </optgroup>
                    <optgroup label="Heartbeat">
                        <option value="push">Push Heartbeat</option>
                        <option value="cron">Cron Job Monitor</option>
                    </optgroup>
                </select>
            </div>

            {{-- URL field: http, keyword, ping, dns, database, docker, whois --}}
            <div x-show="!['tcp','push','cron'].includes(form.type)">
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">
                    <span x-text="{http:'URL',keyword:'URL',ping:'Hostname / IP',dns:'Domain',database:'Connection String',docker:'Container / Socket',whois:'Domain'}[form.type] ?? 'URL'"></span>
                    <span class="text-red-400">*</span>
                </label>
                <input type="text" x-model="form.url"
                       :placeholder="{
                           http:'https://example.com',keyword:'https://example.com',
                           ping:'8.8.8.8 atau google.com',dns:'example.com',
                           database:'mysql://user:pass@host:3306/db',
                           docker:'container_name',whois:'example.com'
                       }[form.type] ?? 'https://example.com'"
                       class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                <p x-show="errors.url" x-text="errors.url?.[0]" class="text-red-500 dark:text-red-400 text-xs mt-1"></p>
            </div>

            {{-- Keyword (keyword type) --}}
            <div x-show="form.type === 'keyword'">
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Kata kunci yang dicari</label>
                <input type="text" x-model="form.keyword" placeholder="Mis: OK, success, running"
                       class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
            </div>

            {{-- TCP host + port --}}
            <div x-show="form.type === 'tcp'" class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Host <span class="text-red-400">*</span></label>
                    <input type="text" x-model="form.tcp_host" placeholder="Mis: db.example.com"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Port <span class="text-red-400">*</span></label>
                    <input type="number" x-model="form.tcp_port" placeholder="3306" min="1" max="65535"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                </div>
            </div>

            {{-- DNS fields --}}
            <div x-show="form.type === 'dns'" class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Tipe DNS</label>
                    <select x-model="form.dns_resolve_type"
                            class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                        <option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Nilai yang diharapkan</label>
                    <input type="text" x-model="form.dns_expected_value" placeholder="Mis: 8.8.8.8"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                </div>
            </div>

            {{-- Push token --}}
            <div x-show="form.type === 'push'">
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Push Token</label>
                <div class="flex gap-2">
                    <input type="text" x-model="form.push_token" placeholder="Token unik untuk heartbeat"
                           class="flex-1 px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700 font-mono">
                    <button type="button" @click="form.push_token = Math.random().toString(36).substring(2) + Date.now().toString(36)"
                            class="px-3 py-2 text-xs bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 rounded-xl border border-sky-200 dark:border-sky-700 hover:bg-sky-200 dark:hover:bg-sky-900/50 font-semibold whitespace-nowrap transition-colors">
                        <i class="fa-solid fa-dice mr-1"></i>Generate
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1 flex items-center gap-1 flex-wrap">
                    URL heartbeat:
                    <code class="bg-sky-50 dark:bg-slate-700 px-1.5 py-0.5 rounded text-sky-600 dark:text-sky-400 break-all">{{ rtrim(config('app.url'), '/') }}/push/<span x-text="form.push_token || '{token}'"></span></code>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ rtrim(config('app.url'), '/') }}/push/' + form.push_token)"
                            x-show="form.push_token"
                            class="text-sky-500 hover:text-sky-700 text-[10px]" title="Salin URL">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </p>
            </div>

            {{-- Cron Monitor --}}
            <div x-show="form.type === 'cron'" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Push Token (Heartbeat)</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="form.push_token" placeholder="Token otomatis"
                               class="flex-1 px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700 font-mono">
                        <button type="button" @click="form.push_token = Math.random().toString(36).substring(2) + Date.now().toString(36)"
                                class="px-3 py-2 text-xs bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 rounded-xl border border-sky-200 dark:border-sky-700 hover:bg-sky-200 font-semibold whitespace-nowrap transition-colors">
                            <i class="fa-solid fa-dice mr-1"></i>Generate
                        </button>
                    </div>
                    <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">
                        Panggil: <code class="bg-indigo-50 dark:bg-indigo-900/30 px-1 rounded">{{ rtrim(config('app.url'), '/') }}/push/<span x-text="form.push_token || '{token}'"></span></code>
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Heartbeat Interval (menit)</label>
                    <input type="number" x-model="form.heartbeat_interval" min="1" placeholder="60"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">DOWN jika tidak ada ping selama N menit</p>
                </div>
            </div>

            {{-- WHOIS: alert days --}}
            <div x-show="form.type === 'whois'">
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Alert X hari sebelum expired</label>
                <input type="number" x-model="form.domain_expiry_alert_days" min="1" max="365" placeholder="30"
                       class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
            </div>

            {{-- Database: info --}}
            <div x-show="form.type === 'database'" class="text-xs text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-900/40 rounded-xl p-3">
                <i class="fa-solid fa-circle-info text-sky-400 mr-1"></i>
                Format connection string: <code class="text-sky-600 dark:text-sky-400">mysql://user:pass@host:3306/db</code> · <code>pgsql://...</code> · <code>redis://host:6379</code>
            </div>

            {{-- Docker: info --}}
            <div x-show="form.type === 'docker'" class="text-xs text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-900/40 rounded-xl p-3">
                <i class="fa-solid fa-circle-info text-sky-400 mr-1"></i>
                Masukkan nama container atau path socket Docker (<code>unix:///var/run/docker.sock</code>). Untuk remote gunakan <code>http://host:2375</code>.
            </div>

            {{-- Interval + Timeout + Retry --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Interval (mnt)</label>
                    <input type="number" x-model="form.check_interval" min="1" max="1440"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Timeout (dtk)</label>
                    <input type="number" x-model="form.timeout" min="1" max="60"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Gagal → DOWN & Insiden</label>
                    <input type="number" x-model="form.retry_count" min="1" max="10"
                           class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
                </div>
            </div>

            {{-- Notification channels --}}
            @if($channels->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-2">Notifikasi</label>
                <div class="space-y-2">
                    @foreach($channels as $ch)
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <input type="checkbox" value="{{ $ch->id }}" x-model="form.notification_channels"
                               class="w-4 h-4 rounded border-sky-300 dark:border-slate-500 text-sky-500 focus:ring-sky-300 dark:focus:ring-sky-700 bg-sky-50 dark:bg-slate-700">
                        <span class="text-sm text-gray-700 dark:text-slate-200 group-hover:text-sky-700 dark:group-hover:text-sky-400">
                            {{ $ch->name }}
                        </span>
                        <span class="text-[10px] uppercase bg-sky-50 dark:bg-slate-700 text-sky-500 dark:text-sky-400 border border-sky-200 dark:border-slate-600 px-1.5 py-0.5 rounded-md">
                            {{ $ch->type }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tags --}}
            @if($allTags->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-2">Tags</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($allTags as $tag)
                    <label class="flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" value="{{ $tag->id }}" x-model="form.tags"
                               class="w-3.5 h-3.5 rounded border-sky-300 dark:border-slate-500 text-sky-500 focus:ring-sky-300 dark:bg-slate-700">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium text-white"
                              style="background:{{ $tag->color }}">
                            {{ $tag->name }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Batas Lambat --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Batas Lambat (ms)</label>
                <input type="number" x-model="form.response_time_warning" min="100" max="60000" placeholder="Kosong = nonaktif"
                       class="w-full px-3 py-2 text-sm bg-sky-50 dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-xl text-gray-700 dark:text-slate-200 placeholder-sky-300 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-700">
            </div>

            {{-- General error --}}
            <p x-show="errors.general" x-text="errors.general" class="text-red-500 dark:text-red-400 text-sm"></p>
        </div>

        {{-- Modal footer --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-sky-100 dark:border-slate-700 flex-shrink-0">
            <button @click="open = false"
                    class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 px-4 py-2 transition-colors">
                Batal
            </button>
            <button @click="submit()" :disabled="submitting"
                    class="flex items-center gap-2 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                           text-white text-sm px-6 py-2.5 rounded-xl font-semibold shadow-sm transition-all
                           disabled:opacity-60 disabled:cursor-wait">
                <i class="fa-solid text-xs" :class="submitting ? 'fa-spinner fa-spin' : (mode === 'create' ? 'fa-plus' : 'fa-floppy-disk')"></i>
                <span x-text="submitting ? 'Menyimpan...' : (mode === 'create' ? 'Tambah Monitor' : 'Simpan Perubahan')"></span>
            </button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
@if($selected)
@php
    $monitorEditJson = json_encode(array_merge(
        $selected->only([
            'id','name','url','type','check_interval','timeout','retry_count',
            'keyword','tcp_host','tcp_port','push_token',
            'dns_resolve_type','dns_expected_value','notification_channels'
        ]),
        ['tags' => $selected->tags->pluck('id')->map(fn($id) => (string)$id)->toArray()]
    ));
@endphp
<script>
// Chart.js
const historyData = @json($responseHistory);
const canvas = document.getElementById('responseChart');
if (canvas && historyData.length) {
    const isDark = () => document.documentElement.classList.contains('dark');
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: historyData.map(h => {
                if (!h.checked_at) return '';
                const d = new Date(h.checked_at);
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }),
            datasets: [{
                data: historyData.map(h => h.response_time ?? 0),
                borderColor: '#38bdf8',
                backgroundColor: ctx => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    g.addColorStop(0, 'rgba(56,189,248,0.25)');
                    g.addColorStop(1, 'rgba(56,189,248,0.01)');
                    return g;
                },
                borderWidth: 2,
                pointRadius:          historyData.map(h => h.status === 'down' ? 5 : 2),
                pointBackgroundColor: historyData.map(h => h.status === 'down' ? '#ef4444' : '#38bdf8'),
                pointBorderColor:     historyData.map(h => h.status === 'down' ? '#ef4444' : '#38bdf8'),
                fill: true,
                tension: 0.4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark() ? '#1e293b' : '#fff',
                    titleColor: isDark() ? '#e2e8f0' : '#374151',
                    bodyColor: isDark() ? '#94a3b8' : '#6b7280',
                    borderColor: isDark() ? '#334155' : '#e0f2fe',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y + ' ms',
                        afterLabel: ctx => {
                            const s = historyData[ctx.dataIndex]?.status;
                            return s ? 'Status: ' + s.toUpperCase() : '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: isDark() ? '#1e293b' : '#f0f9ff' },
                    ticks: { font: { size: 10 }, color: '#9ca3af', callback: v => v + 'ms' },
                },
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 12, font: { size: 10 }, color: '#9ca3af' },
                },
            },
        },
    });
}

// Open edit modal with current monitor data
function openEditModal() {
    window.dispatchEvent(new CustomEvent('open-monitor-modal', {
        detail: {
            mode: 'edit',
            monitor: {!! $monitorEditJson !!}
        }
    }));
}

// Silence monitor - create quick maintenance window
async function silenceMonitor(id, name) {
    const isDark = document.documentElement.classList.contains('dark');
    const result = await Swal.fire({
        title: 'Silence "' + name + '"',
        text: 'Pilih durasi silence (notifikasi tidak dikirim):',
        icon: 'info',
        input: 'select',
        inputOptions: { '1h': '1 Jam', '4h': '4 Jam', '24h': '24 Jam' },
        inputPlaceholder: 'Pilih durasi',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-bell-slash mr-1"></i>Silence',
        cancelButtonText: 'Batal',
        background: isDark ? '#1e293b' : '#fff',
        color: isDark ? '#e2e8f0' : '#111827',
    });
    if (!result.isConfirmed || !result.value) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const r = await fetch(`/monitors/${id}/silence`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ duration: result.value }),
    });
    const d = await r.json();
    Swal.fire({ icon: 'success', title: 'Disilence!', text: `Notifikasi diam hingga ${d.end_at}`, toast: true, position: 'top-end', timer: 4000, showConfirmButton: false, background: isDark ? '#1e293b' : '#fff', color: isDark ? '#e2e8f0' : '#111827' });
}

// Delete monitor via AJAX + SweetAlert
async function deleteMonitor(id, name) {
    const isDark = document.documentElement.classList.contains('dark');
    const result = await Swal.fire({
        title: 'Hapus "' + name + '"?',
        text: 'Data tidak bisa dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i>Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        background: isDark ? '#1e293b' : '#fff',
        color: isDark ? '#e2e8f0' : '#111827',
    });
    if (!result.isConfirmed) return;
    try {
        await fetch('/monitors/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: '_method=DELETE',
        });
        location.href = '{{ route('dashboard') }}';
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Gagal menghapus monitor', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
    }
}
</script>
@endif

<script>
// Open create modal (global function for sidebar + empty state)
function openCreateModal() {
    window.dispatchEvent(new CustomEvent('open-monitor-modal', { detail: { mode: 'create' } }));
}

// Alpine: sidebar component
function sidebar() {
    return {
        checking: false,
        async checkAll() {
            if (this.checking) return;
            const isDark = document.documentElement.classList.contains('dark');
            const _swal = await Swal.fire({
                title: 'Check All Monitor',
                text: 'Kirim notifikasi WA/Telegram jika ada yang DOWN?',
                icon: 'question',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '🔔 Cek + Kirim Notif',
                denyButtonText: '🔕 Cek Saja',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#0ea5e9',
                denyButtonColor: '#6b7280',
                background: isDark ? '#1e293b' : '#fff',
                color: isDark ? '#e2e8f0' : '#111827',
            });
            if (_swal.isDismissed) return;
            const notify = _swal.isConfirmed ? 1 : 0;
            this.checking = true;
            Swal.fire({
                title: 'Sedang mengecek semua monitor...',
                text: 'Mohon tunggu, jangan tutup halaman ini.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
                background: isDark ? '#1e293b' : '#fff',
                color: isDark ? '#e2e8f0' : '#111827',
            });
            try {
                const r = await fetch('{{ route('dashboard.check-all') }}?notify=' + notify, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                if (!r.ok) throw new Error(`HTTP ${r.status}: ${await r.text()}`);
                const d = await r.json();
                const notifInfo = notify
                    ? (d.notified > 0 ? `${d.notified} notifikasi terkirim.` : 'Semua monitor tidak punya channel notif.')
                    : 'Tanpa notifikasi.';
                Swal.fire({
                    icon: d.down > 0 ? 'warning' : 'success',
                    title: `Selesai! ${d.up} UP, ${d.down} DOWN`,
                    text: notifInfo,
                    toast: true, position: 'top-end', timer: 4000, showConfirmButton: false, timerProgressBar: true,
                    background: isDark ? '#1e293b' : '#fff', color: isDark ? '#e2e8f0' : '#111827',
                });
                setTimeout(() => location.reload(), 800);
            } catch(err) {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Gagal', text: String(err), background: isDark ? '#1e293b' : '#fff', color: isDark ? '#e2e8f0' : '#111827' });
                this.checking = false;
            }
        },
    };
}

// Alpine: monitor modal component
function monitorModal() {
    return {
        open: false,
        mode: 'create',
        submitting: false,
        errors: {},
        form: {
            id: null, name: '', url: '', type: 'http',
            check_interval: 5, timeout: 30, retry_count: 3,
            keyword: '', tcp_host: '', tcp_port: '',
            push_token: '', dns_resolve_type: 'A', dns_expected_value: '',
            notification_channels: [], tags: [], response_time_warning: '',
            heartbeat_interval: 60, domain_expiry_alert_days: 30,
        },

        onTypeChange() {
            const placeholders = ['push://heartbeat', 'tcp://placeholder', 'cron://heartbeat'];
            if (!['tcp','push','cron'].includes(this.form.type) && placeholders.includes(this.form.url)) {
                this.form.url = '';
            }
        },

        openModal(detail) {
            this.errors = {};
            this.submitting = false;
            if (detail.mode === 'edit' && detail.monitor) {
                this.mode = 'edit';
                const m = detail.monitor;
                this.form = {
                    id: m.id,
                    name: m.name,
                    url: m.url ?? '',
                    type: m.type,
                    check_interval: m.check_interval,
                    timeout: m.timeout,
                    retry_count: m.retry_count,
                    keyword: m.keyword ?? '',
                    tcp_host: m.tcp_host ?? '',
                    tcp_port: m.tcp_port ?? '',
                    push_token: m.push_token ?? '',
                    dns_resolve_type: m.dns_resolve_type ?? 'A',
                    dns_expected_value: m.dns_expected_value ?? '',
                    notification_channels: (m.notification_channels ?? []).map(String),
                    tags: (m.tags ?? []).map(String),
                    response_time_warning: m.response_time_warning ?? '',
                    heartbeat_interval: m.heartbeat_interval ?? 60,
                    domain_expiry_alert_days: m.domain_expiry_alert_days ?? 30,
                };
            } else {
                this.mode = 'create';
                this.form = {
                    id: null, name: '', url: '', type: 'http',
                    check_interval: 5, timeout: 30, retry_count: 3,
                    keyword: '', tcp_host: '', tcp_port: '',
                    push_token: '', dns_resolve_type: 'A', dns_expected_value: '',
                    notification_channels: [], tags: [], response_time_warning: '',
                    heartbeat_interval: 60, domain_expiry_alert_days: 30,
                };
            }
            this.open = true;
        },

        get effectiveUrl() {
            if (this.form.type === 'tcp') return 'tcp://placeholder';
            if (this.form.type === 'push') return 'push://heartbeat';
            if (this.form.type === 'cron') return 'cron://heartbeat';
            return this.form.url;
        },

        async submit() {
            this.submitting = true;
            this.errors = {};
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const url = this.mode === 'edit' ? '/monitors/' + this.form.id : '/monitors';
            const body = new URLSearchParams();
            body.set('name', this.form.name);
            body.set('url', this.effectiveUrl);
            body.set('type', this.form.type);
            body.set('check_interval', this.form.check_interval);
            body.set('timeout', this.form.timeout);
            body.set('retry_count', this.form.retry_count);
            if (this.form.type === 'keyword') body.set('keyword', this.form.keyword);
            if (this.form.type === 'tcp') { body.set('tcp_host', this.form.tcp_host); body.set('tcp_port', this.form.tcp_port); }
            if (['push','cron'].includes(this.form.type)) body.set('push_token', this.form.push_token);
            if (this.form.type === 'cron') body.set('heartbeat_interval', this.form.heartbeat_interval);
            if (this.form.type === 'whois') body.set('domain_expiry_alert_days', this.form.domain_expiry_alert_days);
            if (this.form.type === 'dns') { body.set('dns_resolve_type', this.form.dns_resolve_type); body.set('dns_expected_value', this.form.dns_expected_value); }
            this.form.notification_channels.forEach(id => body.append('notification_channels[]', id));
            this.form.tags.forEach(id => body.append('tags[]', id));
            if (this.form.response_time_warning) body.set('response_time_warning', this.form.response_time_warning);
            if (this.mode === 'edit') body.set('_method', 'PUT');
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString(),
                });
                if (res.status === 422) {
                    const d = await res.json();
                    this.errors = d.errors ?? {};
                    this.submitting = false;
                    return;
                }
                if (res.ok) {
                    const d = await res.json();
                    this.open = false;
                    this.submitting = false;
                    Swal.fire({ icon: 'success', title: this.mode === 'create' ? 'Monitor ditambahkan!' : 'Monitor diperbarui!', toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, timerProgressBar: true });
                    setTimeout(() => { location.href = d.redirect || location.pathname; }, 600);
                    return;
                }
                this.submitting = false;
            } catch(e) {
                console.error(e);
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush

@push('scripts')
<script>
// Live status polling — fetch JSON, no persistent connection, no blocked workers
(function() {
    const POLL_URL = '{{ route('monitors.poll') }}';
    const INTERVAL = 20000; // 20s
    let timer = null;
    let prev = {};

    function applyData(data) {
        let anyDown = false;
        Object.entries(data).forEach(([id, mon]) => {
            document.querySelectorAll(`[data-monitor-id="${id}"]`).forEach(el => {
                if (el.dataset.field === 'status') {
                    el.textContent = mon.status.toUpperCase();
                    el.className = el.className.replace(/text-(red|emerald|amber|slate)-\d+/g, '');
                    el.classList.add(mon.status === 'up' ? 'text-emerald-400' : mon.status === 'down' ? 'text-red-400' : 'text-amber-400');
                }
                if (el.dataset.field === 'rt')    el.textContent = mon.rt ? mon.rt + 'ms' : '-';
                if (el.dataset.field === 'score') el.textContent = mon.score ?? '-';
            });
            if (mon.status === 'down') anyDown = true;
            if (prev[id] && prev[id] !== mon.status && mon.status === 'down') window.wtSound?.play();
            prev[id] = mon.status;
        });
        anyDown ? window.wtFavicon?.red() : window.wtFavicon?.normal();
    }

    async function poll() {
        try {
            const res = await fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (res.ok) applyData(await res.json());
        } catch(e) {}
        timer = setTimeout(poll, INTERVAL);
    }

    // Pause polling saat tab tidak aktif, lanjut saat aktif kembali
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { clearTimeout(timer); }
        else { poll(); }
    });

    poll(); // mulai segera
})();
</script>
@endpush
