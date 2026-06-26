@extends('layouts.app')
@section('title', 'Service Topology')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-diagram-project text-sky-500 mr-2"></i>Service Topology
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Visualisasi dependency antar monitor</p>
    </div>
    <a href="{{ route('monitors.index') }}" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">
        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
    </a>
</div>

<div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden mb-5" style="height:520px;">
    @if($monitors->isEmpty())
    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-slate-500">
        <i class="fa-solid fa-diagram-project text-4xl mb-3 text-sky-200 dark:text-slate-600"></i>
        <p class="text-sm">Belum ada monitor aktif</p>
    </div>
    @else
    <canvas id="topology-canvas" style="width:100%;height:100%;display:block;"></canvas>
    @endif
</div>

@if($monitors->isNotEmpty())
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
    @foreach($monitors as $m)
    <div class="bg-white dark:bg-slate-800 border {{ $m->last_status === 'up' ? 'border-emerald-200 dark:border-emerald-700' : ($m->last_status === 'down' ? 'border-red-200 dark:border-red-700' : 'border-sky-100 dark:border-slate-700') }} rounded-xl p-3 shadow-sm">
        <div class="flex items-center gap-2 mb-0.5">
            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $m->last_status === 'up' ? 'bg-emerald-400' : ($m->last_status === 'down' ? 'bg-red-400 animate-pulse' : 'bg-gray-300 dark:bg-slate-500') }}"></span>
            <span class="text-sm font-medium text-gray-800 dark:text-slate-100 truncate">{{ $m->name }}</span>
        </div>
        @if($m->dependencies->isNotEmpty())
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1 pl-4 truncate">
            <i class="fa-solid fa-arrow-right text-[10px] mr-0.5"></i>{{ $m->dependencies->pluck('name')->join(', ') }}
        </p>
        @endif
    </div>
    @endforeach
</div>
@endif

@push('scripts')
<script>
const monitors = @json($topologyData);
const canvas   = document.getElementById('topology-canvas');
if (canvas && monitors.length) {
    const ctx = canvas.getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    function resize() {
        canvas.width  = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        draw();
    }

    const nodes = monitors.map((m, i) => ({
        ...m,
        x: 90 + (i % 5) * 160,
        y: 90 + Math.floor(i / 5) * 130,
    }));
    const nodeMap = Object.fromEntries(nodes.map(n => [n.id, n]));

    function draw() {
        const dark = document.documentElement.classList.contains('dark');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // edges
        monitors.forEach(m => {
            m.deps.forEach(depId => {
                const a = nodeMap[m.id], b = nodeMap[depId];
                if (!a || !b) return;
                ctx.beginPath();
                ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y);
                ctx.strokeStyle = dark ? '#334155' : '#bae6fd';
                ctx.lineWidth = 1.5; ctx.stroke();
                const angle = Math.atan2(b.y - a.y, b.x - a.x);
                const mx = (a.x+b.x)/2, my = (a.y+b.y)/2;
                ctx.save(); ctx.translate(mx, my); ctx.rotate(angle);
                ctx.fillStyle = dark ? '#475569' : '#7dd3fc';
                ctx.beginPath(); ctx.moveTo(0,-4); ctx.lineTo(8,0); ctx.lineTo(0,4); ctx.fill();
                ctx.restore();
            });
        });

        // nodes
        nodes.forEach(n => {
            const color = n.status === 'up' ? '#10b981' : n.status === 'down' ? '#ef4444' : '#94a3b8';
            ctx.beginPath(); ctx.arc(n.x, n.y, 22, 0, 2*Math.PI);
            ctx.fillStyle = color + '22'; ctx.strokeStyle = color; ctx.lineWidth = 2;
            ctx.fill(); ctx.stroke();
            ctx.fillStyle = dark ? '#e2e8f0' : '#1e293b';
            ctx.font = '11px system-ui,sans-serif'; ctx.textAlign = 'center';
            ctx.fillText(n.name.slice(0, 14), n.x, n.y + 36);
        });
    }

    window.addEventListener('resize', resize);
    resize();
}
</script>
@endpush
@endsection
