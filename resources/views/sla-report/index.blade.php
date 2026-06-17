@extends('layouts.app')
@section('title', 'SLA Report')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">SLA Report</h1>
        <p class="text-xs text-gray-400 mt-0.5">Availability monitor & ringkasan insiden operasional</p>
    </div>
    <a href="{{ route('incidents.index') }}"
       class="inline-flex items-center gap-1.5 text-sky-600 hover:text-sky-800 border border-sky-200 hover:border-sky-400 text-sm px-3 py-2 rounded-xl transition-colors">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i>Lihat Insiden
    </a>
</div>

{{-- Period selector --}}
<div class="flex flex-wrap gap-2 mb-6">
    @foreach([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $val => $label)
    <a href="{{ route('sla-report.index', ['days' => $val]) }}"
       class="text-xs px-3 py-1.5 rounded-lg border transition-colors
              {{ $days == $val ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-gray-600 border-sky-200 hover:border-sky-400' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- ================================================================ --}}
{{-- PANEL 1: SLA per Monitor --}}
{{-- ================================================================ --}}
<h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">
    <i class="fa-solid fa-chart-bar text-sky-500 mr-1.5"></i>SLA per Monitor
</h2>

<div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 border-b border-sky-100 text-gray-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">Monitor</th>
                <th class="px-5 py-3 text-center">Availability</th>
                <th class="px-5 py-3 text-center">Insiden</th>
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
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-md
                        {{ $availOk ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $row['availability'] }}%
                    </span>
                </td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $row['incident_count'] }}</td>
                <td class="px-5 py-3 text-center text-gray-600 text-xs">
                    @if($row['downtime_seconds'] > 0)
                        {{ $downH }}j {{ $downM }}m
                    @else —
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

{{-- ================================================================ --}}
{{-- PANEL 2: Ringkasan Insiden Operasional --}}
{{-- ================================================================ --}}
<h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">
    <i class="fa-solid fa-clipboard-list text-amber-500 mr-1.5"></i>Ringkasan Insiden Operasional
    <span class="text-gray-400 font-normal normal-case tracking-normal ml-1">(Insiden Umum IT + Laporan Client)</span>
</h2>

{{-- Summary cards --}}
@php
    $opMttrM = intdiv($opSummary['mttr_seconds'], 60);
    $opMttrS = $opSummary['mttr_seconds'] % 60;
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-sky-100 shadow-sm px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">Total Insiden Operasional</p>
        <p class="text-2xl font-bold text-gray-800">{{ $opSummary['total'] }}</p>
        <p class="text-xs text-gray-400 mt-1">
            <span class="text-amber-600 font-medium">{{ $opSummary['general'] }}</span> Umum &middot;
            <span class="text-rose-600 font-medium">{{ $opSummary['client_report'] }}</span> Error &middot;
            <span class="text-violet-600 font-medium">{{ $opSummary['work_order'] }}</span> WO
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-sky-100 shadow-sm px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">Masih Berlangsung</p>
        <p class="text-2xl font-bold {{ $opSummary['open'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
            {{ $opSummary['open'] }}
        </p>
        <p class="text-xs text-gray-400 mt-1">{{ $opSummary['closed'] }} selesai</p>
    </div>
    <div class="bg-white rounded-2xl border border-sky-100 shadow-sm px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">Rata-rata Penanganan (MTTR)</p>
        <p class="text-2xl font-bold text-gray-800">
            {{ $opSummary['mttr_seconds'] > 0 ? $opMttrM . 'm' : '—' }}
        </p>
        <p class="text-xs text-gray-400 mt-1">dari insiden yang selesai</p>
    </div>
    <div class="bg-white rounded-2xl border border-sky-100 shadow-sm px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">Severity Tertinggi (aktif)</p>
        @php
            $criticalOpen = \App\Models\Incident::where('status','open')
                ->whereIn('category',['general','client_report','work_order'])
                ->where('severity','critical')->count();
            $highOpen = \App\Models\Incident::where('status','open')
                ->whereIn('category',['general','client_report','work_order'])
                ->where('severity','high')->count();
        @endphp
        @if($criticalOpen > 0)
        <p class="text-2xl font-bold text-red-600">{{ $criticalOpen }} Critical</p>
        @elseif($highOpen > 0)
        <p class="text-2xl font-bold text-orange-500">{{ $highOpen }} High</p>
        @else
        <p class="text-2xl font-bold text-emerald-600">Aman</p>
        @endif
        <p class="text-xs text-gray-400 mt-1">insiden operasional aktif</p>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

    {{-- Bar chart: insiden per waktu --}}
    <div class="md:col-span-2 bg-white rounded-2xl border border-sky-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-700 mb-4">
            Tren Insiden per {{ $days === 7 ? 'Hari' : 'Minggu' }}
        </p>
        <canvas id="barChart" height="120"></canvas>
    </div>

    {{-- Donut chart: severity breakdown --}}
    <div class="bg-white rounded-2xl border border-sky-100 shadow-sm p-5 flex flex-col">
        <p class="text-sm font-semibold text-gray-700 mb-4">Breakdown Severity</p>
        <div class="flex-1 flex items-center justify-center">
            <canvas id="donutChart" style="max-height:180px"></canvas>
        </div>
        <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-4 text-xs">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span> Critical: <b>{{ $severityCounts['critical'] }}</b></span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-orange-400 inline-block"></span> High: <b>{{ $severityCounts['high'] }}</b></span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span> Medium: <b>{{ $severityCounts['medium'] }}</b></span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span> Low: <b>{{ $severityCounts['low'] }}</b></span>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark = document.documentElement.classList.contains('dark');
const gridColor  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
const labelColor = isDark ? '#94a3b8' : '#6b7280';

// Bar chart
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: @json($chartData['labels']),
        datasets: [
            {
                label: 'Monitor Down',
                data: @json($chartData['monitor']),
                backgroundColor: 'rgba(14,165,233,0.7)',
                borderRadius: 4,
            },
            {
                label: 'Insiden Umum IT',
                data: @json($chartData['general']),
                backgroundColor: 'rgba(245,158,11,0.7)',
                borderRadius: 4,
            },
            {
                label: 'Laporan Error Client',
                data: @json($chartData['client']),
                backgroundColor: 'rgba(244,63,94,0.65)',
                borderRadius: 4,
            },
            {
                label: 'Work Order',
                data: @json($chartData['work_order']),
                backgroundColor: 'rgba(139,92,246,0.7)',
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: labelColor, boxWidth: 12, font: { size: 11 } } },
        },
        scales: {
            x: {
                stacked: true,
                ticks: { color: labelColor, font: { size: 10 } },
                grid: { color: gridColor },
            },
            y: {
                stacked: true,
                beginAtZero: true,
                ticks: { color: labelColor, font: { size: 10 }, precision: 0 },
                grid: { color: gridColor },
            }
        }
    }
});

// Donut chart
const sevTotal = {{ array_sum($severityCounts) }};
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Critical', 'High', 'Medium', 'Low'],
        datasets: [{
            data: [
                {{ $severityCounts['critical'] }},
                {{ $severityCounts['high'] }},
                {{ $severityCounts['medium'] }},
                {{ $severityCounts['low'] }},
            ],
            backgroundColor: [
                'rgba(239,68,68,0.85)',
                'rgba(249,115,22,0.85)',
                'rgba(234,179,8,0.85)',
                'rgba(52,211,153,0.85)',
            ],
            borderWidth: 2,
            borderColor: isDark ? '#1e293b' : '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.raw} (${sevTotal > 0 ? Math.round(ctx.raw/sevTotal*100) : 0}%)`
                }
            }
        }
    }
});
</script>
@endpush
