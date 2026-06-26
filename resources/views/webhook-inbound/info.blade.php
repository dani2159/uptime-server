@extends('layouts.app')
@section('title', 'Webhook Endpoint — '.$receiver->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('webhook-inbound.index') }}" class="text-sky-600 dark:text-sky-400 hover:text-sky-700">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
                <i class="fa-solid fa-satellite-dish text-sky-500 mr-2"></i>{{ $receiver->name }}
            </h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Webhook Inbound Receiver</p>
        </div>
    </div>

    {{-- Status --}}
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Status Terakhir</h2>
            @if($receiver->last_status)
            <span class="text-xs font-medium px-2.5 py-1 rounded-full
                {{ in_array($receiver->last_status, ['firing','down','problem','alerting']) ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' }}">
                {{ strtoupper($receiver->last_status) }}
            </span>
            @else
            <span class="text-xs text-gray-400 dark:text-slate-500">Belum pernah menerima</span>
            @endif
        </div>
        @if($receiver->last_received_at)
        <p class="text-sm text-gray-500 dark:text-slate-400">Terakhir diterima: <span class="font-medium text-gray-700 dark:text-slate-300">{{ $receiver->last_received_at->diffForHumans() }}</span></p>
        @endif
        @if($receiver->source)
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Sumber: <span class="font-medium text-gray-700 dark:text-slate-300">{{ $receiver->source }}</span></p>
        @endif
    </div>

    {{-- Endpoint info --}}
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5 mb-4">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">
            <i class="fa-solid fa-link text-sky-500 mr-1.5"></i>Endpoint URL
        </h2>
        <div class="flex items-center gap-2 mb-3">
            <code class="flex-1 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sky-600 dark:text-sky-400 text-sm break-all font-mono select-all">{{ $url }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $url }}').then(()=>this.innerHTML='<i class=\'fa-solid fa-check\'></i>')"
                class="px-3 py-2.5 bg-sky-50 dark:bg-slate-700 hover:bg-sky-100 dark:hover:bg-slate-600 text-sky-600 dark:text-slate-300 rounded-xl text-sm border border-sky-200 dark:border-slate-600 transition-colors flex-shrink-0">
                <i class="fa-solid fa-copy"></i>
            </button>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-xl px-4 py-3 text-sm text-amber-700 dark:text-amber-400">
            <i class="fa-solid fa-info-circle mr-1"></i>
            Endpoint ini hanya menerima request <strong>POST</strong>. Akses GET (browser) hanya menampilkan halaman ini.
        </div>
    </div>

    {{-- curl example --}}
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5 mb-4">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">
            <i class="fa-solid fa-terminal text-sky-500 mr-1.5"></i>Contoh Penggunaan
        </h2>
        <div class="space-y-3">
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-1.5 font-medium">curl (Custom/Generic)</p>
                <pre class="bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-700 rounded-xl px-4 py-3 text-xs font-mono text-gray-700 dark:text-slate-300 overflow-auto select-all">curl -X POST {{ $url }} \
  -H "Content-Type: application/json" \
  -d '{"status": "firing", "message": "Service down"}'</pre>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-1.5 font-medium">Grafana Webhook URL</p>
                <code class="block bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-700 rounded-xl px-4 py-2.5 text-xs font-mono text-gray-700 dark:text-slate-300 select-all">{{ $url }}</code>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Di Grafana → Alerting → Contact Points → Webhook → URL field</p>
            </div>
        </div>
    </div>

    {{-- Last payload --}}
    @if($receiver->last_payload)
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">
            <i class="fa-solid fa-code text-sky-500 mr-1.5"></i>Payload Terakhir
        </h2>
        <pre class="bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-700 rounded-xl p-4 text-xs font-mono text-gray-700 dark:text-slate-300 overflow-auto max-h-60">{{ json_encode($receiver->last_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif
</div>
@endsection
