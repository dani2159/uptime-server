@extends('layouts.app')
@section('title', $monitor->name)

@section('content')
<div class="flex items-center justify-between mb-5 gap-4">
    <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('dashboard', ['selected' => $monitor->id]) }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-sky-400 hover:text-sky-600 transition-colors border border-sky-100 dark:border-slate-700 flex-shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="text-[10px] uppercase font-bold tracking-widest px-2 py-0.5 rounded-lg bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400">{{ $monitor->type }}</span>
                @if(!$monitor->is_active)
                <span class="text-[10px] uppercase font-bold tracking-widest px-2 py-0.5 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 dark:text-orange-400">Paused</span>
                @endif
            </div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100 leading-tight truncate">{{ $monitor->name }}</h1>
            <a href="{{ $monitor->url }}" target="_blank"
               class="text-xs text-sky-500 hover:underline dark:hover:text-sky-300">{{ $monitor->url }}</a>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0"
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
                     const r = await fetch('{{ route('monitors.check-now', $monitor) }}?notify=' + notify, {
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                     });
                     const d = await r.json();
                     this.checking = false;
                     Swal.fire({ icon: d.status === 'up' ? 'success' : 'error', title: d.message, toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, timerProgressBar: true, background: isDark ? '#1e293b' : '#fff', color: isDark ? '#e2e8f0' : '#111827' });
                     setTimeout(() => location.reload(), 1600);
                 } catch { this.checking = false; }
             }
         }">
        <form method="POST" action="{{ route('monitors.toggle', $monitor) }}" class="contents">
            @csrf @method('PATCH')
            <button type="submit"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border font-semibold transition-colors
                        {{ $monitor->is_active
                            ? 'border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-500 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-600'
                            : 'border-sky-200 dark:border-sky-700 bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 hover:bg-sky-100' }}">
                <i class="fa-solid {{ $monitor->is_active ? 'fa-pause' : 'fa-play' }} text-[10px]"></i>
                {{ $monitor->is_active ? 'Pause' : 'Resume' }}
            </button>
        </form>

        <button @click="cekNow()" :disabled="checking"
                class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-sky-200 dark:border-sky-700
                       bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 hover:bg-sky-100 font-semibold transition-all
                       disabled:opacity-60 disabled:cursor-wait">
            <i class="fa-solid text-[10px]" :class="checking ? 'fa-spinner fa-spin' : 'fa-rotate-right'"></i>
            <span x-text="checking ? 'Checking...' : 'Cek Sekarang'"></span>
        </button>

        <a href="{{ route('monitors.edit', $monitor) }}"
           class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-600 font-semibold transition-colors">
            <i class="fa-solid fa-pen-to-square text-[10px]"></i> Edit
        </a>

        {{-- Clone --}}
        <form method="POST" action="{{ route('monitors.clone', $monitor) }}" onsubmit="return confirm('Duplikat monitor ini?')">
            @csrf
            <button type="submit"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-600 font-semibold transition-colors">
                <i class="fa-solid fa-copy text-[10px]"></i> Clone
            </button>
        </form>

        {{-- Silence --}}
        <button onclick="silenceMonitor({{ $monitor->id }}, '{{ addslashes($monitor->name) }}')"
                class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/40 font-semibold transition-colors">
            <i class="fa-solid fa-bell-slash text-[10px]"></i> Silence
        </button>
    </div>
</div>

{{-- Heartbeat bar 90 --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 pt-5 pb-4 mb-5">
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm font-semibold text-gray-700 dark:text-slate-200">
            <i class="fa-solid fa-heart-pulse text-sky-400 mr-1.5"></i>90 heartbeat terakhir
        </p>
        <p class="text-xs text-gray-400 dark:text-slate-500">
            {{ $monitor->uptime_percentage ? $monitor->uptime_percentage . '% overall' : '—' }} ·
            24h: <span class="font-medium text-gray-600 dark:text-slate-300">{{ $monitor->uptime_24h ?? '—' }}%</span> ·
            7d: <span class="font-medium text-gray-600 dark:text-slate-300">{{ $monitor->uptime_7d ?? '—' }}%</span> ·
            30d: <span class="font-medium text-gray-600 dark:text-slate-300">{{ $monitor->uptime_30d ?? '—' }}%</span>
        </p>
    </div>
    @include('monitors._heartbeat', ['logs' => $heartbeats, 'limit' => 90])
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Status</p>
        <p class="text-2xl font-bold mt-1 {{ match($monitor->last_status) {
            'up' => 'text-green-500', 'down' => 'text-red-500', default => 'text-gray-300 dark:text-slate-600'
        } }}">{{ strtoupper($monitor->last_status) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">
            <i class="fa-solid fa-bolt mr-1"></i>Response
        </p>
        <p class="text-2xl font-bold mt-1 text-sky-600 dark:text-sky-400">
            {{ $monitor->last_response_time ?? '—' }}<sup class="text-sm font-normal text-gray-400 dark:text-slate-500">ms</sup>
        </p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Uptime 30 hari</p>
        <p class="text-2xl font-bold mt-1 text-gray-800 dark:text-slate-100">{{ $monitor->uptime_30d ?? $monitor->uptime_percentage ?? '—' }}%</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Cek Terakhir</p>
        <p class="text-sm font-semibold mt-1 text-gray-700 dark:text-slate-200">
            {{ $monitor->last_checked_at?->format('d M Y H:i:s') ?? '—' }}
        </p>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Retry</p>
        <p class="text-xl font-bold mt-1 text-gray-800 dark:text-slate-100">{{ $monitor->retry_count }}x</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Sekarang: {{ $monitor->current_retries }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">
            <i class="fa-solid fa-lock mr-1"></i>SSL Cert
        </p>
        @if($monitor->ssl_expiry_at)
            <p class="text-xl font-bold mt-1 {{ $monitor->ssl_days_remaining <= 7 ? 'text-red-600 dark:text-red-400' : ($monitor->ssl_days_remaining <= 30 ? 'text-yellow-500' : 'text-sky-600 dark:text-sky-400') }}">
                {{ $monitor->ssl_days_remaining }} hari
            </p>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Expire: {{ $monitor->ssl_expiry_at->format('d M Y') }}</p>
        @else
            <p class="text-xl font-bold mt-1 text-gray-300 dark:text-slate-600">—</p>
        @endif
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Uptime 24h</p>
        <p class="text-xl font-bold mt-1 {{ ($monitor->uptime_24h ?? 100) >= 99 ? 'text-green-500 dark:text-green-400' : (($monitor->uptime_24h ?? 100) >= 95 ? 'text-yellow-500' : 'text-red-500 dark:text-red-400') }}">
            {{ $monitor->uptime_24h ?? '—' }}%
        </p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Uptime 7 hari</p>
        <p class="text-xl font-bold mt-1 {{ ($monitor->uptime_7d ?? 100) >= 99 ? 'text-green-500 dark:text-green-400' : (($monitor->uptime_7d ?? 100) >= 95 ? 'text-yellow-500' : 'text-red-500 dark:text-red-400') }}">
            {{ $monitor->uptime_7d ?? '—' }}%
        </p>
    </div>
</div>

<div class="grid grid-cols-3 gap-5">
    {{-- IP --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-sky-50 dark:border-slate-700 flex items-center gap-2">
            <i class="fa-solid fa-globe text-sky-400"></i>
            <h2 class="font-semibold text-sm text-gray-700 dark:text-slate-200">{{ $monitor->domain }}</h2>
        </div>
        @forelse($ips as $ip)
        <div class="px-5 py-3 flex items-center justify-between border-b border-sky-50/50 dark:border-slate-700/40 last:border-0">
            <div>
                <span class="font-mono text-xs text-gray-700 dark:text-slate-300">{{ $ip->ip_address }}</span>
                <span class="ml-2 text-[10px] uppercase bg-sky-50 dark:bg-slate-700 text-sky-500 dark:text-sky-400 border border-sky-100 dark:border-slate-600 px-1.5 py-0.5 rounded-md">{{ $ip->type }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                @if($ip->last_ping_ms)
                <span class="text-sky-500 font-mono">{{ $ip->last_ping_ms }}ms</span>
                @endif
                <span class="w-2 h-2 rounded-full {{ $ip->status === 'up' ? 'bg-green-400' : ($ip->status === 'down' ? 'bg-red-400' : 'bg-gray-300 dark:bg-slate-500') }}"></span>
            </div>
        </div>
        @empty
        <p class="px-5 py-6 text-xs text-gray-400 dark:text-slate-500 text-center">Belum ada IP.</p>
        @endforelse
    </div>

    {{-- Chart --}}
    <div class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-sky-50 dark:border-slate-700">
            <h2 class="font-semibold text-sm text-gray-700 dark:text-slate-200">
                <i class="fa-solid fa-chart-line text-sky-400 mr-1.5"></i>Response History (48 cek terakhir)
            </h2>
        </div>
        <div class="p-5">
            <canvas id="responseChart" height="80"></canvas>
        </div>
    </div>
</div>

{{-- Log table --}}
<div class="mt-5 bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-sky-50 dark:border-slate-700">
        <h2 class="font-semibold text-sm text-gray-700 dark:text-slate-200">
            <i class="fa-solid fa-list-check text-sky-400 mr-1.5"></i>Log Pengecekan
        </h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-sky-50/50 dark:bg-slate-700/30 text-xs uppercase text-gray-400 dark:text-slate-500 tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">Waktu</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-right">Response</th>
                <th class="px-5 py-3 text-right">HTTP</th>
                <th class="px-5 py-3 text-left">Pesan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50 dark:divide-slate-700/50">
            @forelse($logs as $log)
            <tr class="hover:bg-sky-50/40 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-5 py-3 text-gray-500 dark:text-slate-400 text-xs">{{ $log->checked_at->format('d M Y H:i:s') }}</td>
                <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $log->status === 'up' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                        {{ strtoupper($log->status) }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right text-sky-600 dark:text-sky-400 font-mono text-xs">{{ $log->response_time ?? '—' }}ms</td>
                <td class="px-5 py-3 text-right text-gray-500 dark:text-slate-400 text-xs">{{ $log->http_status ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-400 dark:text-slate-500 text-xs">{{ $log->message ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400 dark:text-slate-500">Belum ada log.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())
    <div class="px-5 py-3 border-t border-sky-50 dark:border-slate-700">{{ $logs->links() }}</div>
    @endif
</div>
{{-- Heatmap Availability Calendar --}}
@php $calendar = $monitor->availabilityCalendar(90); @endphp
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-sky-100 dark:border-slate-700 p-5 mt-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200">
            <i class="fa fa-calendar-alt text-sky-400 mr-1"></i> Availability Calendar (90 hari)
        </h3>
        @if($monitor->health_score !== null)
        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $monitor->health_score_badge }}">
            Score: {{ $monitor->health_score }}/100
        </span>
        @endif
    </div>
    <div class="flex flex-wrap gap-1">
        @foreach($calendar as $day)
        @php
            $pct = $day['pct'];
            $color = $pct === null ? 'bg-slate-700' : ($pct >= 99 ? 'bg-emerald-500' : ($pct >= 95 ? 'bg-emerald-700' : ($pct >= 80 ? 'bg-amber-600' : 'bg-red-600')));
            $opacity = $pct === null ? 'opacity-20' : 'opacity-80 hover:opacity-100';
        @endphp
        <div class="w-4 h-4 rounded-sm {{ $color }} {{ $opacity }} cursor-default transition-opacity"
             title="{{ $day['date'] }}: {{ $pct !== null ? $pct.'%' : 'no data' }}"></div>
        @endforeach
    </div>
    <div class="flex gap-4 mt-2 text-xs text-slate-500">
        <span><span class="inline-block w-3 h-3 rounded-sm bg-emerald-500 mr-1"></span>≥99%</span>
        <span><span class="inline-block w-3 h-3 rounded-sm bg-amber-600 mr-1"></span>80-95%</span>
        <span><span class="inline-block w-3 h-3 rounded-sm bg-red-600 mr-1"></span>&lt;80%</span>
        <span><span class="inline-block w-3 h-3 rounded-sm bg-slate-700 mr-1"></span>No data</span>
    </div>
</div>

{{-- Notes & Runbook --}}
@if($monitor->notes || $monitor->runbook_url)
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-sky-100 dark:border-slate-700 p-5 mt-4">
    @if($monitor->notes)
    <div class="mb-3">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Catatan</p>
        <p class="text-sm text-slate-300 whitespace-pre-wrap">{{ $monitor->notes }}</p>
    </div>
    @endif
    @if($monitor->runbook_url)
    <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Runbook</p>
        <a href="{{ $monitor->runbook_url }}" target="_blank" class="text-sky-400 hover:text-sky-300 text-sm">
            <i class="fa fa-book-open mr-1"></i>{{ $monitor->runbook_url }}
        </a>
    </div>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const history = @json($responseHistory);
const isDark = document.documentElement.classList.contains('dark');
if (history.length) {
    new Chart(document.getElementById('responseChart'), {
        type: 'line',
        data: {
            labels: history.map(h => h.checked_at
                ? new Date(h.checked_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : ''),
            datasets: [{
                data: history.map(h => h.response_time ?? 0),
                borderColor: '#38bdf8',
                backgroundColor: ctx => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 180);
                    g.addColorStop(0, 'rgba(56,189,248,0.2)');
                    g.addColorStop(1, 'rgba(56,189,248,0.01)');
                    return g;
                },
                borderWidth: 2,
                pointRadius: history.map(h => h.status === 'down' ? 5 : 2),
                pointBackgroundColor: history.map(h => h.status === 'down' ? '#ef4444' : '#38bdf8'),
                fill: true,
                tension: 0.35,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false },
                tooltip: {
                    background: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#e2e8f0' : '#374151',
                    bodyColor: isDark ? '#94a3b8' : '#6b7280',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: isDark ? '#1e293b' : '#f0f9ff' },
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

async function silenceMonitor(id, name) {
    const isDark = document.documentElement.classList.contains('dark');
    const { value: dur } = await Swal.fire({
        title: `Silence "${name}"`,
        input: 'select',
        inputOptions: { '1h': '1 jam', '4h': '4 jam', '24h': '24 jam' },
        inputPlaceholder: 'Pilih durasi',
        showCancelButton: true, cancelButtonText: 'Batal',
        background: isDark ? '#1e293b' : '#fff',
        color: isDark ? '#e2e8f0' : '#111827',
    });
    if (!dur) return;
    const r = await fetch(`/monitors/${id}/silence`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ duration: dur }),
    });
    const d = await r.json();
    Swal.fire({ icon: 'success', title: 'Disilence!', text: `Notifikasi diam hingga ${d.end_at}`, toast: true, position: 'top-end', timer: 4000, showConfirmButton: false, background: isDark ? '#1e293b' : '#fff', color: isDark ? '#e2e8f0' : '#111827' });
}
</script>
@endpush
