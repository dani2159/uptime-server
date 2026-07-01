@extends('layouts.app')
@section('title', 'Monitors')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-chart-bar text-sky-500 mr-2"></i>Monitors
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Semua monitor yang dipantau</p>
    </div>
    <a href="{{ route('monitors.create') }}"
       class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
              text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i>
        Tambah Monitor
    </a>
</div>

<div class="space-y-3">
    @forelse($monitors as $monitor)
    @php $logs = $monitor->heartbeatLogs; @endphp

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-4 hover:border-sky-200 dark:hover:border-slate-600 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="relative flex-shrink-0">
                    @if($monitor->last_status === 'down')
                    <span class="absolute inset-0 rounded-full bg-red-400 animate-ping opacity-75"></span>
                    @endif
                    <span class="relative block w-3 h-3 rounded-full {{ match($monitor->last_status) {
                        'up' => 'bg-green-400', 'down' => 'bg-red-500', default => 'bg-gray-300 dark:bg-slate-500'
                    } }}"></span>
                </div>
                <div class="min-w-0">
                    <a href="{{ route('monitors.show', $monitor) }}"
                       class="font-semibold text-gray-800 dark:text-slate-100 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">
                        {{ $monitor->name }}
                    </a>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5 truncate">
                        {{ $monitor->url }}
                        <span class="ml-2 uppercase text-[10px] bg-sky-50 dark:bg-sky-900/30 border border-sky-100 dark:border-sky-700/40 text-sky-500 dark:text-sky-400 px-1.5 py-0.5 rounded-md">{{ $monitor->type }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-5 text-right flex-shrink-0">
                <div class="hidden md:grid grid-cols-3 gap-5 text-center">
                    @foreach(['24h' => $monitor->uptime_24h, '7d' => $monitor->uptime_7d, '30d' => $monitor->uptime_30d] as $label => $val)
                    <div>
                        <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-sm font-bold {{ ($val ?? 100) >= 99 ? 'text-green-500 dark:text-green-400' : (($val ?? 100) >= 95 ? 'text-yellow-500' : 'text-red-500 dark:text-red-400') }}">
                            {{ $val !== null ? $val . '%' : '—' }}
                        </p>
                    </div>
                    @endforeach
                </div>

                <div class="text-center hidden sm:block">
                    <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-wide">Response</p>
                    <p class="text-sm font-bold text-sky-600 dark:text-sky-400">
                        {{ $monitor->last_response_time ? $monitor->last_response_time . 'ms' : '—' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('monitors.show', $monitor) }}"
                       class="text-xs text-sky-500 dark:text-sky-400 border border-sky-200 dark:border-sky-700/50 bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 dark:hover:bg-sky-900/40 px-2.5 py-1 rounded-lg transition-colors font-medium">
                        <i class="fa-solid fa-eye mr-1 text-[10px]"></i>Detail
                    </a>
                    <a href="{{ route('monitors.edit', $monitor) }}"
                       class="text-xs text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 border border-gray-200 dark:border-slate-600 hover:border-gray-300 dark:hover:border-slate-500 px-2.5 py-1 rounded-lg transition-colors">
                        <i class="fa-solid fa-pen-to-square mr-1 text-[10px]"></i>Edit
                    </a>
                    <form method="POST" action="{{ route('monitors.destroy', $monitor) }}" class="inline" id="del-m-{{ $monitor->id }}">
                        @csrf @method('DELETE')
                        <button type="button"
                                onclick="swalDelete('del-m-{{ $monitor->id }}', '{{ addslashes($monitor->name) }}')"
                                class="text-xs text-red-400 dark:text-red-400 hover:text-red-600 border border-red-100 dark:border-red-800/40 hover:border-red-300 px-2.5 py-1 rounded-lg transition-colors">
                            <i class="fa-solid fa-trash text-[10px]"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 overflow-hidden">
                @include('monitors._heartbeat', ['logs' => $logs, 'limit' => 60])
            </div>
            <span class="text-xs text-gray-400 dark:text-slate-500 flex-shrink-0">
                {{ $monitor->uptime_percentage ? $monitor->uptime_percentage . '%' : '—' }}
            </span>
        </div>

        @if($monitor->ssl_expiry_at)
        <div class="mt-2 flex items-center gap-1.5 text-xs
            {{ $monitor->ssl_days_remaining <= 7 ? 'text-red-500 dark:text-red-400' : ($monitor->ssl_days_remaining <= 30 ? 'text-yellow-500' : 'text-sky-400') }}">
            <i class="fa-solid fa-lock text-[10px]"></i>
            SSL expire {{ $monitor->ssl_expiry_at->format('d M Y') }}
            <span class="opacity-70">({{ $monitor->ssl_days_remaining }} hari lagi)</span>
        </div>
        @endif
        @if($monitor->domain_expiry_at)
        @php $ddays = $monitor->domain_expiry_days_remaining ?? 0; @endphp
        <div class="mt-1 flex items-center gap-1.5 text-xs
            {{ $ddays <= 7 ? 'text-red-500 dark:text-red-400' : ($ddays <= 30 ? 'text-yellow-500' : 'text-violet-500 dark:text-violet-400') }}">
            <i class="fa-solid fa-calendar-xmark text-[10px]"></i>
            Domain expire {{ \Carbon\Carbon::parse($monitor->domain_expiry_at)->format('d M Y') }}
            <span class="opacity-70">({{ $ddays }} hari lagi)</span>
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm px-5 py-16 text-center">
        <i class="fa-solid fa-satellite-dish text-4xl text-gray-300 dark:text-slate-600 mb-4 block"></i>
        <p class="text-gray-400 dark:text-slate-500 mb-3">Belum ada monitor.</p>
        <a href="{{ route('monitors.create') }}"
           class="inline-flex items-center gap-1.5 text-sky-600 dark:text-sky-400 hover:text-sky-700 text-sm font-medium">
            <i class="fa-solid fa-plus text-xs"></i> Tambah sekarang
        </a>
    </div>
    @endforelse
</div>

@if($monitors->hasPages())
<div class="mt-4">{{ $monitors->links() }}</div>
@endif
@endsection
