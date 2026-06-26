@extends('layouts.app')
@section('title', 'Webhook Inbound')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Webhook Inbound</h1>
        <p class="text-slate-400 text-sm mt-1">Terima alert dari Grafana / Zabbix / Prometheus</p>
    </div>
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
        class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
        <i class="fa fa-plus mr-1"></i> Buat Receiver
    </button>
</div>

@if(session('success'))
<div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-3">
    @forelse($receivers as $r)
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-white font-medium">{{ $r->name }}</span>
                    @if($r->source)
                    <span class="text-xs bg-slate-700 text-slate-300 px-2 py-0.5 rounded">{{ $r->source }}</span>
                    @endif
                    @if($r->last_status)
                    <span class="text-xs {{ in_array($r->last_status, ['firing','down','problem','alerting']) ? 'bg-red-900/50 text-red-300' : 'bg-emerald-900/50 text-emerald-300' }} px-2 py-0.5 rounded">
                        {{ $r->last_status }}
                    </span>
                    @endif
                </div>
                <code class="text-xs text-sky-400 bg-slate-900 rounded px-2 py-1 block select-all">{{ url("/webhook-in/{$r->token}") }}</code>
                @if($r->last_received_at)
                <p class="text-xs text-slate-500 mt-1">Terakhir diterima: {{ $r->last_received_at->diffForHumans() }}</p>
                @endif
            </div>
            <div class="flex gap-2 ml-4">
                <button onclick="navigator.clipboard.writeText('{{ url("/webhook-in/{$r->token}") }}')"
                    class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white rounded text-xs">
                    <i class="fa fa-copy"></i>
                </button>
                <form method="POST" action="{{ route('webhook-inbound.destroy', $r->id) }}" onsubmit="return confirm('Hapus?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 bg-red-900/50 hover:bg-red-800 text-red-300 rounded text-xs"><i class="fa fa-trash"></i></button>
                </form>
            </div>
        </div>
        @if($r->last_payload)
        <details class="mt-2">
            <summary class="text-xs text-slate-500 cursor-pointer hover:text-slate-300">Payload terakhir</summary>
            <pre class="mt-1 bg-slate-900 rounded p-2 text-xs text-slate-300 overflow-auto max-h-32">{{ json_encode($r->last_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
        @endif
    </div>
    @empty
    <div class="text-center text-slate-500 py-12">
        <i class="fa fa-satellite-dish text-3xl mb-3 block"></i>
        Belum ada receiver. <button onclick="document.getElementById('modal-create').classList.remove('hidden')" class="text-sky-400">Buat sekarang</button>
    </div>
    @endforelse
</div>
{{ $receivers->links() }}

{{-- Modal buat receiver --}}
<div id="modal-create" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
    <div class="bg-slate-800 border border-slate-700 rounded-xl w-full max-w-md p-5">
        <h2 class="text-white font-semibold mb-4">Buat Webhook Receiver</h2>
        <form method="POST" action="{{ route('webhook-inbound.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Nama <span class="text-red-400">*</span></label>
                <input type="text" name="name" required
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="Grafana Production, Zabbix Infra, dll">
            </div>
            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Sumber</label>
                <select name="source" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                    <option value="">Custom</option>
                    <option value="grafana">Grafana</option>
                    <option value="zabbix">Zabbix</option>
                    <option value="prometheus">Prometheus / Alertmanager</option>
                </select>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded text-sm font-medium">Buat</button>
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="flex-1 py-2 bg-slate-700 text-white rounded text-sm">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
