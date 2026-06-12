@extends('layouts.app')
@section('title', 'API Health Dashboard')

@section('content')
<div x-data="apiHealth()" x-init="init()">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">API Health Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">BPJS, Satu Sehat, dan layanan lainnya</p>
        </div>
        <div class="flex items-center gap-3">
            <span x-show="lastChecked" x-cloak class="text-xs text-gray-400">
                Cek terakhir: <span x-text="lastChecked"></span>
            </span>

            {{-- CDN Switch — hanya untuk layanan BPJS --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button @click="switchMode('non_cdn')"
                        :disabled="switching"
                        :class="cdnMode === 'non_cdn'
                            ? 'bg-white shadow text-gray-900 font-medium'
                            : 'text-gray-500 hover:text-gray-700'"
                        class="text-xs px-3 py-1.5 rounded-md transition-all disabled:opacity-50">
                    Non-CDN
                </button>
                <button @click="switchMode('cdn')"
                        :disabled="switching"
                        :class="cdnMode === 'cdn'
                            ? 'bg-white shadow text-gray-900 font-medium'
                            : 'text-gray-500 hover:text-gray-700'"
                        class="text-xs px-3 py-1.5 rounded-md transition-all disabled:opacity-50">
                    CDN
                </button>
            </div>

            {{-- Host indicator --}}
            <span class="text-xs text-gray-400 hidden md:block" x-text="activeHost"></span>

            <button @click="checkAll()"
                    :disabled="loading"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                <svg x-show="loading" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span x-text="loading ? 'Mengecek...' : 'Start Requests'"></span>
            </button>
        </div>
    </div>

    {{-- Service tabs --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        <button @click="activeService = null"
                :class="activeService === null ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-300'"
                class="text-sm px-4 py-1.5 rounded-full transition-colors">
            Semua
        </button>
        <template x-for="(svc, key) in services" :key="key">
            <button @click="activeService = key"
                    :class="activeService === key ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-300'"
                    class="text-sm px-4 py-1.5 rounded-full transition-colors">
                <span x-text="svc.label"></span>
                <span class="ml-1 text-xs opacity-70"
                      x-text="'(' + Object.values(svc.results).filter(r => r.connected).length + '/' + Object.values(svc.results).length + ')'">
                </span>
            </button>
        </template>
    </div>

    {{-- Cards per service --}}
    <template x-for="(svc, serviceKey) in filteredServices" :key="serviceKey">
        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-700" x-text="svc.label"></h2>
                <button @click="checkService(serviceKey)"
                        :disabled="loading"
                        class="text-xs text-blue-600 hover:text-blue-800 border border-blue-200 hover:border-blue-400 px-3 py-1 rounded-full transition-colors">
                    Cek service ini
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="result in svc.results" :key="result.key">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col items-center text-center">
                        <p class="text-sm font-semibold text-gray-800 mb-2 leading-tight" x-text="result.label"></p>

                        {{-- Speedometer canvas --}}
                        <canvas :id="'gauge-' + result.key" width="100" height="60"
                                x-init="$nextTick(() => drawGauge(result))"></canvas>

                        {{-- Response time --}}
                        <div class="mt-2">
                            <template x-if="result.ms !== undefined">
                                <p class="text-2xl font-bold text-gray-900">
                                    <span x-text="result.ms"></span><sup class="text-xs font-normal text-gray-400 ml-0.5">ms</sup>
                                </p>
                            </template>
                        </div>

                        {{-- Status --}}
                        <p class="mt-2 text-sm font-semibold"
                           :class="result.connected ? 'text-green-600' : 'text-red-600'"
                           x-text="result.connected ? 'Terhubung' : 'Gagal'">
                        </p>

                        {{-- Last check --}}
                        <p class="mt-1 text-xs text-gray-400" x-text="result.checked_at ? 'Pengecekan Terakhir: ' + result.checked_at : '-'"></p>
                    </div>
                </template>

                {{-- Empty state --}}
                <template x-if="Object.keys(svc.results).length === 0">
                    <div class="col-span-6 py-8 text-center text-gray-400 text-sm">
                        Belum ada data. Klik "Start Requests" untuk memulai pengecekan.
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Empty state global --}}
    <template x-if="Object.keys(services).length === 0">
        <div class="text-center py-16 text-gray-400">
            <p class="text-lg">Belum ada data</p>
            <p class="text-sm mt-1">Klik "Start Requests" untuk memulai.</p>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function apiHealth() {
    const hosts = @json($cdnHosts);

    return {
        loading: false,
        switching: false,
        lastChecked: null,
        activeService: null,
        cdnMode: @json($cdnMode),
        services: @json($services),

        get activeHost() {
            return hosts[this.cdnMode] ?? '';
        },

        get filteredServices() {
            if (this.activeService === null) return this.services;
            const filtered = {};
            if (this.services[this.activeService]) {
                filtered[this.activeService] = this.services[this.activeService];
            }
            return filtered;
        },

        init() {
            if (Object.keys(this.services).length > 0) {
                this.$nextTick(() => this.redrawAllGauges());
            }
        },

        async switchMode(mode) {
            if (mode === this.cdnMode || this.switching) return;
            this.switching = true;
            try {
                const res = await fetch(`{{ url('api-health/mode') }}/${mode}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                this.cdnMode = data.mode;
            } catch (e) {
                console.error('Switch mode failed:', e);
            } finally {
                this.switching = false;
            }
        },

        async checkAll() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('api-health.check-all') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                this.services = data.services;
                this.lastChecked = new Date().toLocaleString('id-ID');
                this.$nextTick(() => this.redrawAllGauges());
            } catch (e) {
                console.error('Check failed:', e);
            } finally {
                this.loading = false;
            }
        },

        async checkService(serviceKey) {
            this.loading = true;
            try {
                const res = await fetch(`{{ url('api-health/check') }}/${serviceKey}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (this.services[serviceKey]) {
                    this.services[serviceKey].results = {};
                    data.results.forEach(r => { this.services[serviceKey].results[r.key] = r; });
                    this.lastChecked = new Date().toLocaleString('id-ID');
                    this.$nextTick(() => this.redrawAllGauges());
                }
            } catch (e) {
                console.error('Service check failed:', e);
            } finally {
                this.loading = false;
            }
        },

        redrawAllGauges() {
            Object.values(this.services).forEach(svc => {
                Object.values(svc.results).forEach(result => this.drawGauge(result));
            });
        },

        drawGauge(result) {
            const canvas = document.getElementById('gauge-' + result.key);
            if (!canvas) return;

            const ctx    = canvas.getContext('2d');
            const w      = canvas.width;
            const h      = canvas.height;
            const cx     = w / 2;
            const cy     = h - 5;
            const radius = Math.min(cx, cy) - 4;

            ctx.clearRect(0, 0, w, h);

            const startAngle = Math.PI;
            const endAngle   = 2 * Math.PI;

            // Background arc
            ctx.beginPath();
            ctx.arc(cx, cy, radius, startAngle, endAngle);
            ctx.lineWidth   = 8;
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineCap     = 'round';
            ctx.stroke();

            // Value arc
            const ms       = result.ms || 0;
            const maxMs    = 2000;
            const ratio    = Math.min(ms / maxMs, 1);
            const fillEnd  = startAngle + ratio * Math.PI;
            const color    = this.gaugeColor(ms, result.connected);

            if (result.connected !== undefined) {
                ctx.beginPath();
                ctx.arc(cx, cy, radius, startAngle, fillEnd);
                ctx.lineWidth   = 8;
                ctx.strokeStyle = color;
                ctx.lineCap     = 'round';
                ctx.stroke();
            }
        },

        gaugeColor(ms, connected) {
            if (!connected) return '#ef4444';
            if (ms < 300)   return '#22c55e';
            if (ms < 800)   return '#f59e0b';
            return '#ef4444';
        },
    };
}
</script>
@endpush
