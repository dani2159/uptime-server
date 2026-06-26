@extends('layouts.app')
@section('title', 'Webhook Inbound')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-satellite-dish text-sky-500 mr-2"></i>Webhook Inbound
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Terima alert dari Grafana / Zabbix / Prometheus</p>
    </div>
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i> Buat Receiver
    </button>
</div>

<div class="space-y-3">
    @forelse($receivers as $r)
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $r->name }}</span>
                    @if($r->source)
                    <span class="text-xs bg-sky-100 dark:bg-slate-700 text-sky-700 dark:text-slate-300 px-2 py-0.5 rounded-full">{{ $r->source }}</span>
                    @endif
                    @if($r->last_status)
                    <span class="text-xs font-medium {{ in_array($r->last_status, ['firing','down','problem','alerting']) ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' }} px-2 py-0.5 rounded-full">
                        {{ $r->last_status }}
                    </span>
                    @endif
                </div>
                <code class="text-xs text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-slate-900 border border-sky-100 dark:border-slate-700 rounded-lg px-3 py-1.5 block select-all mb-1">{{ url("/webhook-in/{$r->token}") }}</code>
                @if($r->last_received_at)
                <p class="text-xs text-gray-400 dark:text-slate-500">Terakhir diterima: {{ $r->last_received_at->diffForHumans() }}</p>
                @endif
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <button onclick="navigator.clipboard.writeText('{{ url("/webhook-in/{$r->token}") }}').then(()=>Swal.fire({icon:'success',title:'Tersalin!',toast:true,position:'top-end',timer:1500,showConfirmButton:false}))"
                    class="px-3 py-1.5 bg-sky-50 dark:bg-slate-700 hover:bg-sky-100 dark:hover:bg-slate-600 text-sky-600 dark:text-slate-300 rounded-lg text-xs border border-sky-200 dark:border-slate-600 transition-colors">
                    <i class="fa-solid fa-copy"></i>
                </button>
                <form method="POST" action="{{ route('webhook-inbound.destroy', $r->id) }}" onsubmit="return confirm('Hapus receiver ini?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg text-xs border border-red-200 dark:border-red-800/40 transition-colors">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @if($r->last_payload)
        <details class="mt-3">
            <summary class="text-xs text-gray-400 dark:text-slate-500 cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 select-none">
                <i class="fa-solid fa-code mr-1"></i>Payload terakhir
            </summary>
            <pre class="mt-2 bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-700 rounded-xl p-3 text-xs text-gray-700 dark:text-slate-300 overflow-auto max-h-40 font-mono">{{ json_encode($r->last_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
        @endif
    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm text-center py-14">
        <i class="fa-solid fa-satellite-dish text-4xl mb-3 block text-sky-200 dark:text-slate-600"></i>
        <p class="text-gray-500 dark:text-slate-400 text-sm mb-2">Belum ada receiver</p>
        <button onclick="document.getElementById('modal-create').classList.remove('hidden')" class="text-sky-600 dark:text-sky-400 text-sm hover:underline">Buat sekarang</button>
    </div>
    @endforelse
</div>

@if($receivers->hasPages())
<div class="mt-4">{{ $receivers->links() }}</div>
@endif

{{-- Modal --}}
<div id="modal-create" class="hidden fixed inset-0 bg-black/40 dark:bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-gray-800 dark:text-slate-100 font-semibold mb-4">Buat Webhook Receiver</h2>
        <form method="POST" action="{{ route('webhook-inbound.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="name" required placeholder="Grafana Production, Zabbix Infra, dll"
                    class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">Sumber</label>
                <select name="source" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none">
                    <option value="">Custom</option>
                    <option value="grafana">Grafana</option>
                    <option value="zabbix">Zabbix</option>
                    <option value="prometheus">Prometheus / Alertmanager</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white rounded-xl text-sm font-semibold transition-all">Buat</button>
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-xl text-sm transition-all">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
