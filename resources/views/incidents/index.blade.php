@extends('layouts.app')
@section('title', 'Insiden')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Riwayat Insiden</h1>
        <p class="text-xs text-gray-400 mt-0.5">Insiden monitor otomatis, insiden umum IT, dan laporan error client</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('sla-report.index') }}"
           class="inline-flex items-center gap-1.5 text-sky-600 hover:text-sky-800 border border-sky-200 hover:border-sky-400 text-sm px-3 py-2 rounded-xl transition-colors">
            <i class="fa-solid fa-chart-line mr-1"></i>SLA Report
        </a>
        <a href="{{ route('incidents.create') }}"
           class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                  text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
            + Tambah Insiden
        </a>
    </div>
</div>

<form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
    <select name="category" class="text-sm border border-sky-200 rounded-xl px-3 py-2" onchange="this.form.submit()">
        <option value="">Semua Kategori</option>
        <option value="monitor_downtime" {{ request('category') === 'monitor_downtime' ? 'selected' : '' }}>Insiden Monitor</option>
        <option value="general"          {{ request('category') === 'general'          ? 'selected' : '' }}>Insiden Umum IT</option>
        <option value="client_report"    {{ request('category') === 'client_report'    ? 'selected' : '' }}>Laporan Client</option>
    </select>
    <select name="monitor_id" class="text-sm border border-sky-200 rounded-xl px-3 py-2" onchange="this.form.submit()">
        <option value="">Semua Monitor</option>
        @foreach($monitors as $m)
        <option value="{{ $m->id }}" {{ request('monitor_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
    </select>
    <select name="status" class="text-sm border border-sky-200 rounded-xl px-3 py-2" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="open"   {{ request('status') === 'open'   ? 'selected' : '' }}>Berlangsung</option>
        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Selesai</option>
    </select>
    @if(request('monitor_id') || request('status') || request('category'))
    <a href="{{ route('incidents.index') }}" class="text-xs text-gray-400 hover:text-gray-600">Reset filter</a>
    @endif
</form>

<div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 border-b border-sky-100 text-gray-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">Judul / Insiden</th>
                <th class="px-5 py-3 text-left">Mulai</th>
                <th class="px-5 py-3 text-left">Selesai</th>
                <th class="px-5 py-3 text-left">Durasi</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-center">Kategori</th>
                <th class="px-5 py-3 text-center">Severity</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50">
            @forelse($incidents as $incident)
            @php
                $catColor = match($incident->category) {
                    'monitor_downtime' => 'bg-sky-100 text-sky-700',
                    'general'          => 'bg-amber-100 text-amber-700',
                    'client_report'    => 'bg-rose-100 text-rose-700',
                    default            => 'bg-gray-100 text-gray-600',
                };
                $catLabel = match($incident->category) {
                    'monitor_downtime' => 'Monitor',
                    'general'          => 'Umum IT',
                    'client_report'    => 'Laporan Client',
                    default            => $incident->category,
                };
                $sevColor = match($incident->severity ?? 'medium') {
                    'low'      => 'bg-green-100 text-green-700',
                    'medium'   => 'bg-yellow-100 text-yellow-700',
                    'high'     => 'bg-orange-100 text-orange-700',
                    'critical' => 'bg-red-100 text-red-700',
                    default    => 'bg-gray-100 text-gray-500',
                };
                $sevLabel = match($incident->severity ?? 'medium') {
                    'low'      => 'Low',
                    'medium'   => 'Medium',
                    'high'     => 'High',
                    'critical' => 'Critical',
                    default    => '-',
                };
            @endphp
            <tr class="hover:bg-sky-50/40 transition-colors">
                <td class="px-5 py-3 font-semibold text-gray-800">
                    {{ $incident->display_title }}
                    @if($incident->note)
                    <p class="text-xs text-gray-400 mt-0.5 font-normal truncate max-w-xs">{{ $incident->note }}</p>
                    @endif
                    @if($incident->category === 'client_report' && $incident->reporter_name)
                    <p class="text-xs text-rose-400 mt-0.5 font-normal">
                        <i class="fa-solid fa-user fa-xs mr-0.5"></i>{{ $incident->reporter_name }}
                        @if($incident->reporter_contact)
                         · {{ $incident->reporter_contact }}
                        @endif
                    </p>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-600 text-xs">{{ $incident->started_at->format('d M Y H:i') }}</td>
                <td class="px-5 py-3 text-gray-600 text-xs">{{ $incident->resolved_at?->format('d M Y H:i') ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-600 text-xs">{{ $incident->duration_label }}</td>
                <td class="px-5 py-3 text-center">
                    @if($incident->status === 'open')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span> Berlangsung
                    </span>
                    @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Selesai</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $catColor }}">{{ $catLabel }}</span>
                    @if($incident->category === 'monitor_downtime')
                    <span class="text-[10px] ml-0.5 px-1.5 py-0.5 rounded-full {{ $incident->is_manual ? 'bg-gray-100 text-gray-400' : 'bg-sky-50 text-sky-400' }}">
                        {{ $incident->is_manual ? 'Manual' : 'Otomatis' }}
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $sevColor }}">{{ $sevLabel }}</span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3 text-xs">
                        <a href="{{ route('incidents.edit', $incident) }}" class="text-sky-600 hover:underline font-medium">Edit</a>
                        <form method="POST" action="{{ route('incidents.destroy', $incident) }}" id="del-inc-{{ $incident->id }}" class="inline">
                            @csrf @method('DELETE')
                        </form>
                        <button type="button" onclick="swalDelete('del-inc-{{ $incident->id }}', '{{ addslashes($incident->display_title) }}')"
                                class="text-red-400 hover:text-red-600 hover:underline">Hapus</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-5 py-14 text-center text-gray-400">
                    <p class="mb-2">Belum ada insiden tercatat.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($incidents->hasPages())
    <div class="px-5 py-3 border-t border-sky-50">{{ $incidents->links() }}</div>
    @endif
</div>
@endsection
