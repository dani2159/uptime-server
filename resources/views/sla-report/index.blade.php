@extends('layouts.app')
@section('title', 'SLA Report')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">SLA Report</h1>
        <p class="text-xs text-gray-400 mt-0.5">Availability, downtime, dan MTTR per monitor</p>
    </div>
    <a href="{{ route('incidents.index') }}"
       class="inline-flex items-center gap-1.5 text-sky-600 hover:text-sky-800 border border-sky-200 hover:border-sky-400 text-sm px-3 py-2 rounded-xl transition-colors">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i>Lihat Insiden
    </a>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $val => $label)
    <a href="{{ route('sla-report.index', ['days' => $val]) }}"
       class="text-xs px-3 py-1.5 rounded-lg border transition-colors
              {{ $days == $val
                  ? 'bg-sky-500 text-white border-sky-500'
                  : 'bg-white text-gray-600 border-sky-200 hover:border-sky-400' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 border-b border-sky-100 text-gray-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">Monitor</th>
                <th class="px-5 py-3 text-center">Availability</th>
                <th class="px-5 py-3 text-center">Jumlah Insiden</th>
                <th class="px-5 py-3 text-center">Total Downtime</th>
                <th class="px-5 py-3 text-center">MTTR</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50">
            @forelse($rows as $row)
            @php
                $availOk = $row['availability'] >= 99.9;
                $downH   = intdiv($row['downtime_seconds'], 3600);
                $downM   = intdiv($row['downtime_seconds'] % 3600, 60);
                $mttrM   = intdiv($row['mttr_seconds'], 60);
            @endphp
            <tr class="hover:bg-sky-50/40 transition-colors">
                <td class="px-5 py-3 font-semibold text-gray-800">{{ $row['monitor']->name }}</td>
                <td class="px-5 py-3 text-center">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-md
                        {{ $availOk ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $row['availability'] }}%
                    </span>
                </td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $row['incident_count'] }}</td>
                <td class="px-5 py-3 text-center text-gray-600 text-xs">
                    @if($row['downtime_seconds'] > 0)
                        {{ $downH }} jam {{ $downM }} menit
                    @else
                        —
                    @endif
                </td>
                <td class="px-5 py-3 text-center text-gray-600 text-xs">
                    {{ $row['mttr_seconds'] > 0 ? $mttrM . ' menit' : '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-5 py-14 text-center text-gray-400">Belum ada monitor.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
