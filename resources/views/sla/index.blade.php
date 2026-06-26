@extends('layouts.app')
@section('title', 'SLA Contracts')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-file-contract text-sky-500 mr-2"></i>SLA Contracts
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kelola target uptime & downtime budget per layanan</p>
    </div>
    <a href="{{ route('sla.create') }}"
        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i> Buat SLA
    </a>
</div>

@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-green-50 dark:bg-emerald-900/20 border border-green-200 dark:border-emerald-700 text-green-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm">
    <i class="fa-solid fa-circle-check text-green-500"></i>{{ session('success') }}
</div>
@endif

<div class="space-y-4">
    @forelse($contracts as $c)
    @php
        $monitor   = $c->monitor;
        $uptime    = $monitor ? round(($monitor->logs()->where('status','up')->count() / max(1,$monitor->logs()->count())) * 100, 2) : null;
        $remaining = $c->downtime_budget_min ? max(0, $c->downtime_budget_min - ($monitor?->totalDowntimeMinutes() ?? 0)) : null;
        $pctUsed   = ($c->downtime_budget_min && $remaining !== null)
            ? round((($c->downtime_budget_min - $remaining) / $c->downtime_budget_min) * 100) : 0;
        $barColor  = $pctUsed < 50 ? 'bg-emerald-500' : ($pctUsed < 80 ? 'bg-amber-500' : 'bg-red-500');
        $onTarget  = $uptime !== null && $uptime >= $c->target_uptime;
    @endphp
    <div class="bg-white dark:bg-slate-800 border {{ $onTarget ? 'border-sky-100 dark:border-slate-700' : 'border-red-100 dark:border-red-900/40' }} rounded-2xl shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 mb-3">
            <div class="min-w-0 flex-1">
                <h3 class="font-semibold text-gray-800 dark:text-slate-100">{{ $c->name }}</h3>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $monitor?->name ?? 'Monitor tidak ditemukan' }}</p>
                    @if($monitor)
                    <span class="w-1 h-1 bg-gray-300 dark:bg-slate-600 rounded-full"></span>
                    <span class="text-xs {{ $monitor->last_status === 'up' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ strtoupper($monitor->last_status ?? 'unknown') }}
                    </span>
                    @endif
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                    <i class="fa-regular fa-calendar mr-1"></i>
                    {{ \Carbon\Carbon::parse($c->period_start)->format('d M Y') }} —
                    {{ \Carbon\Carbon::parse($c->period_end)->format('d M Y') }}
                </p>
            </div>
            <div class="text-right flex-shrink-0">
                <div class="text-3xl font-bold {{ $onTarget ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $uptime !== null ? $uptime.'%' : '—' }}
                </div>
                <div class="text-xs text-gray-400 dark:text-slate-500">Target: {{ $c->target_uptime }}%</div>
            </div>
        </div>

        @if($c->downtime_budget_min)
        <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400 mb-1.5">
                <span>Budget downtime terpakai</span>
                <span class="font-medium">{{ $c->downtime_budget_min - ($remaining ?? 0) }} / {{ $c->downtime_budget_min }} menit</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2.5">
                <div class="{{ $barColor }} h-2.5 rounded-full transition-all" style="width:{{ min(100,$pctUsed) }}%"></div>
            </div>
            @if($remaining !== null)
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                Sisa budget: <span class="font-medium text-gray-600 dark:text-slate-300">{{ $remaining }} menit</span>
            </p>
            @endif
        </div>
        @endif

        <div class="flex gap-2">
            <a href="{{ route('sla.edit', $c->id) }}"
                class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-xs border border-gray-200 dark:border-slate-600 transition-colors">
                <i class="fa-solid fa-pen-to-square mr-1"></i>Edit
            </a>
            <form method="POST" action="{{ route('sla.destroy', $c->id) }}" onsubmit="return confirm('Hapus SLA ini?')">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg text-xs border border-red-200 dark:border-red-800/40 transition-colors">
                    <i class="fa-solid fa-trash mr-1"></i>Hapus
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm text-center py-14">
        <i class="fa-solid fa-file-contract text-4xl mb-3 block text-sky-200 dark:text-slate-600"></i>
        <p class="text-gray-500 dark:text-slate-400 text-sm mb-2">Belum ada SLA Contract</p>
        <a href="{{ route('sla.create') }}" class="text-sky-600 dark:text-sky-400 text-sm hover:underline">Buat sekarang</a>
    </div>
    @endforelse
</div>

@if($contracts->hasPages())
<div class="mt-4">{{ $contracts->links() }}</div>
@endif
@endsection
