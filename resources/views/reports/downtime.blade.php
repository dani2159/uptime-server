@extends('layouts.app')
@section('title', 'Downtime Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                Downtime Report
            </h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Riwayat insiden DOWN berdasarkan periode</p>
        </div>
        <a href="{{ route('downtime-report.export', request()->only('from','to','monitor_id')) }}"
           class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2 rounded-xl text-sm transition">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('downtime-report.index') }}"
          class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl px-5 py-4 mb-6 flex flex-wrap gap-4 items-end shadow-sm">
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Dari</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                   class="border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Sampai</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                   class="border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Monitor</label>
            <select name="monitor_id"
                    class="border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                <option value="">Semua Monitor</option>
                @foreach($monitors as $m)
                    <option value="{{ $m->id }}" {{ $monitorId == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2 rounded-xl text-sm transition">
            <i class="fa-solid fa-filter mr-1"></i> Filter
        </button>
    </form>

    @php
        function fmtDur(int $s): string {
            if ($s < 60)   return "{$s}d";
            if ($s < 3600) return round($s / 60) . 'm';
            $h = floor($s / 3600); $m = floor(($s % 3600) / 60);
            return $m > 0 ? "{$h}j {$m}m" : "{$h}j";
        }
    @endphp

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl px-5 py-4 shadow-sm text-center">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total Insiden</p>
            <p class="text-3xl font-bold text-red-500 dark:text-red-400">{{ $incidents->count() }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl px-5 py-4 shadow-sm text-center">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total Downtime</p>
            <p class="text-3xl font-bold text-orange-500 dark:text-orange-400">{{ fmtDur($totalDownSeconds) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl px-5 py-4 shadow-sm text-center">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Monitor Terdampak</p>
            <p class="text-3xl font-bold text-yellow-500 dark:text-yellow-400">{{ $summary->count() }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl px-5 py-4 shadow-sm text-center">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Masih Open</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $incidents->where('status','open')->count() }}</p>
        </div>
    </div>

    @if($summary->isNotEmpty())
    {{-- Per-monitor summary --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-sm mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-800">
            <h2 class="text-sm font-bold text-gray-700 dark:text-slate-200">
                <i class="fa-solid fa-chart-bar text-sky-400 mr-1.5"></i>Ringkasan per Monitor
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left">Monitor</th>
                        <th class="px-5 py-3 text-center">Jumlah Insiden</th>
                        <th class="px-5 py-3 text-center">Total Downtime</th>
                        <th class="px-5 py-3 text-center">Terlama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @foreach($summary as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                        <td class="px-5 py-3 font-medium text-gray-800 dark:text-slate-200">
                            {{ $row['monitor']->name ?? '-' }}
                            <span class="ml-1 text-xs font-normal text-gray-400 uppercase">{{ $row['monitor']->type ?? '' }}</span>
                        </td>
                        <td class="px-5 py-3 text-center text-red-500 dark:text-red-400 font-semibold">{{ $row['count'] }}</td>
                        <td class="px-5 py-3 text-center text-orange-500 dark:text-orange-400 font-semibold">{{ fmtDur($row['total_seconds']) }}</td>
                        <td class="px-5 py-3 text-center text-yellow-600 dark:text-yellow-400">{{ fmtDur($row['longest_seconds']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Detail insiden --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-700 dark:text-slate-200">
                <i class="fa-solid fa-list text-red-400 mr-1.5"></i>Detail Insiden
            </h2>
            <span class="text-xs text-gray-400 dark:text-slate-500">{{ $incidents->count() }} insiden</span>
        </div>
        @if($incidents->isEmpty())
        <div class="px-5 py-16 text-center">
            <i class="fa-solid fa-circle-check text-4xl text-emerald-300 dark:text-emerald-700 mb-3 block"></i>
            <p class="text-gray-400 dark:text-slate-500 text-sm">Tidak ada downtime pada periode ini.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left">Monitor</th>
                        <th class="px-5 py-3 text-left">Mulai DOWN</th>
                        <th class="px-5 py-3 text-left">Pulih</th>
                        <th class="px-5 py-3 text-center">Durasi</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @foreach($incidents as $incident)
                    @php
                        $dur = $incident->duration_seconds
                            ?? $incident->started_at->diffInSeconds($incident->resolved_at ?? now());
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                        <td class="px-5 py-3 font-medium text-gray-800 dark:text-slate-200">
                            @if($incident->monitor_id && $incident->monitor)
                                <a href="{{ route('monitors.show', $incident->monitor_id) }}"
                                   class="hover:text-sky-600 dark:hover:text-sky-400 transition">
                                    {{ $incident->monitor->name }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">
                            {{ $incident->started_at->format('d M Y H:i:s') }}
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">
                            @if($incident->resolved_at)
                                {{ $incident->resolved_at->format('d M Y H:i:s') }}
                            @else
                                <span class="text-red-500 dark:text-red-400 font-medium">Belum pulih</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center font-semibold
                            {{ $dur >= 3600 ? 'text-red-600 dark:text-red-400' : ($dur >= 300 ? 'text-orange-500' : 'text-yellow-500') }}">
                            {{ fmtDur($dur) }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($incident->status === 'resolved')
                                <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-semibold px-2 py-0.5 rounded-full">
                                    <i class="fa-solid fa-check text-[10px]"></i> Resolved
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold px-2 py-0.5 rounded-full">
                                    <i class="fa-solid fa-circle text-[8px] animate-pulse"></i> Open
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-slate-500 text-xs max-w-[200px] truncate">
                            {{ $incident->note ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
