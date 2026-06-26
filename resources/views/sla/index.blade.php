@extends('layouts.app')
@section('title', 'SLA Contracts')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">SLA Contracts</h1>
        <p class="text-slate-400 text-sm mt-1">Kelola target uptime & downtime budget per layanan</p>
    </div>
    <a href="{{ route('sla.create') }}" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
        <i class="fa fa-plus mr-1"></i> Buat SLA
    </a>
</div>

@if(session('success'))
<div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid gap-4">
    @forelse($contracts as $c)
    @php
        $monitor   = $c->monitor;
        $uptime    = $monitor ? round(($monitor->logs()->where('status','up')->count() / max(1,$monitor->logs()->count())) * 100, 2) : null;
        $remaining = $c->downtime_budget_min ? max(0, $c->downtime_budget_min - ($monitor?->totalDowntimeMinutes() ?? 0)) : null;
        $pctUsed   = ($c->downtime_budget_min && $remaining !== null)
            ? round((($c->downtime_budget_min - $remaining) / $c->downtime_budget_min) * 100) : 0;
        $barColor  = $pctUsed < 50 ? 'bg-emerald-500' : ($pctUsed < 80 ? 'bg-amber-500' : 'bg-red-500');
    @endphp
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-white font-semibold">{{ $c->name }}</h3>
                <p class="text-slate-400 text-sm">{{ $monitor?->name ?? 'Monitor tidak ditemukan' }}</p>
                <p class="text-slate-500 text-xs mt-0.5">
                    {{ \Carbon\Carbon::parse($c->period_start)->format('d M Y') }} —
                    {{ \Carbon\Carbon::parse($c->period_end)->format('d M Y') }}
                </p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold {{ $uptime >= $c->target_uptime ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ $uptime ?? '—' }}%
                </div>
                <div class="text-slate-500 text-xs">Target: {{ $c->target_uptime }}%</div>
            </div>
        </div>

        @if($c->downtime_budget_min)
        <div class="mt-3">
            <div class="flex justify-between text-xs text-slate-400 mb-1">
                <span>Budget downtime terpakai</span>
                <span>{{ $c->downtime_budget_min - ($remaining ?? 0) }} / {{ $c->downtime_budget_min }} menit</span>
            </div>
            <div class="w-full bg-slate-700 rounded-full h-2">
                <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width:{{ min(100,$pctUsed) }}%"></div>
            </div>
        </div>
        @endif

        <div class="flex gap-2 mt-3">
            <a href="{{ route('sla.edit', $c->id) }}" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white rounded text-xs"><i class="fa fa-edit mr-1"></i>Edit</a>
            <form method="POST" action="{{ route('sla.destroy', $c->id) }}" onsubmit="return confirm('Hapus SLA ini?')">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 bg-red-900/50 hover:bg-red-800 text-red-300 rounded text-xs"><i class="fa fa-trash mr-1"></i>Hapus</button>
            </form>
        </div>
    </div>
    @empty
    <div class="text-center text-slate-500 py-12">
        <i class="fa fa-file-contract text-3xl mb-3 block"></i>
        Belum ada SLA Contract. <a href="{{ route('sla.create') }}" class="text-sky-400">Buat sekarang</a>
    </div>
    @endforelse
</div>
{{ $contracts->links() }}
@endsection
