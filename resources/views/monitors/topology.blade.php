@extends('layouts.app')
@section('title', 'Service Topology')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Service Topology</h1>
        <p class="text-slate-400 text-sm mt-1">Visualisasi dependency antar monitor</p>
    </div>
</div>

<div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden" style="height: 600px; position: relative;">
    <canvas id="topology-canvas" style="width:100%;height:100%"></canvas>
</div>

<div class="mt-4 grid grid-cols-3 gap-3">
    @foreach($monitors as $m)
    <div class="bg-slate-800 border {{ $m->last_status === 'up' ? 'border-emerald-700' : ($m->last_status === 'down' ? 'border-red-700' : 'border-slate-700') }} rounded-lg p-3">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full {{ $m->last_status === 'up' ? 'bg-emerald-400' : ($m->last_status === 'down' ? 'bg-red-400 animate-pulse' : 'bg-slate-400') }}"></span>
            <span class="text-sm font-medium text-white truncate">{{ $m->name }}</span>
        </div>
        @if($m->dependencies->isNotEmpty())
        <p class="text-xs text-slate-500 mt-1 pl-4">Dep: {{ $m->dependencies->pluck('name')->join(', ') }}</p>
        @endif
    </div>
    @endforeach
</div>

@push('scripts')
<script>
const monitors = @json($topologyData);

const canvas = document.getElementById('topology-canvas');
const ctx    = canvas.getContext('2d');

function resize() {
    canvas.width  = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    draw();
}

// Simple force-layout approximation
const nodes = monitors.map((m, i) => ({
    ...m,
    x: 80 + (i % 6) * 140,
    y: 80 + Math.floor(i / 6) * 120,
}));
const nodeMap = Object.fromEntries(nodes.map(n => [n.id, n]));

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw edges
    monitors.forEach(m => {
        m.deps.forEach(depId => {
            const a = nodeMap[m.id], b = nodeMap[depId];
            if (!a || !b) return;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y);
            ctx.strokeStyle = '#334155'; ctx.lineWidth = 1.5;
            ctx.stroke();
            // Arrow
            const angle = Math.atan2(b.y - a.y, b.x - a.x);
            const mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2;
            ctx.save(); ctx.translate(mx, my); ctx.rotate(angle);
            ctx.fillStyle = '#475569';
            ctx.beginPath(); ctx.moveTo(0,-4); ctx.lineTo(8,0); ctx.lineTo(0,4); ctx.fill();
            ctx.restore();
        });
    });

    // Draw nodes
    nodes.forEach(n => {
        const color = n.status === 'up' ? '#10b981' : n.status === 'down' ? '#ef4444' : '#64748b';
        ctx.beginPath();
        ctx.arc(n.x, n.y, 20, 0, 2 * Math.PI);
        ctx.fillStyle = color + '33';
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.fill(); ctx.stroke();

        ctx.fillStyle = '#e2e8f0';
        ctx.font = '11px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(n.name.slice(0, 12), n.x, n.y + 34);
    });
}

window.addEventListener('resize', resize);
resize();
</script>
@endpush
@endsection
