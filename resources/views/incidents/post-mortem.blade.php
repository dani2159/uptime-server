@extends('layouts.app')
@section('title', 'Post-Mortem: ' . $incident->display_title)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('incidents.index') }}" class="text-sky-400 hover:text-sky-300">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-white">Post-Mortem</h1>
        <span class="text-slate-400 text-sm">{{ $incident->display_title }}</span>
    </div>

    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 mb-4 text-sm text-slate-300">
        <div class="grid grid-cols-2 gap-3">
            <div><span class="text-slate-500">Monitor:</span> {{ $incident->monitor->name ?? '-' }}</div>
            <div><span class="text-slate-500">Mulai:</span> {{ $incident->started_at->format('d M Y H:i') }}</div>
            <div><span class="text-slate-500">Durasi:</span> {{ $incident->duration_label }}</div>
            <div><span class="text-slate-500">Status:</span>
                <span class="{{ $incident->status === 'closed' ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ ucfirst($incident->status) }}
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('incidents.post-mortem.save', $incident) }}" class="space-y-5">
        @csrf
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Judul Post-Mortem</label>
                    <input type="text" name="title" value="{{ old('title', $pm->title) }}"
                        class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                        placeholder="Insiden DB down 2 jam">
                </div>
                <div>
                    <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Severitas</label>
                    <select name="severity" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                        @foreach(['low','medium','high','critical'] as $s)
                            <option value="{{ $s }}" {{ old('severity', $pm->severity) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Timeline Kejadian</label>
                <textarea name="timeline" rows="4"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm font-mono focus:border-sky-500 focus:outline-none"
                    placeholder="08:00 - Alert pertama diterima&#10;08:05 - Tim oncall dihubungi&#10;08:30 - Root cause ditemukan&#10;09:00 - Layanan pulih">{{ old('timeline', $pm->timeline) }}</textarea>
            </div>

            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Root Cause</label>
                <textarea name="root_cause" rows="3"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="Jelaskan penyebab utama insiden...">{{ old('root_cause', $pm->root_cause) }}</textarea>
            </div>

            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Dampak</label>
                <textarea name="impact" rows="2"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="Pengguna terdampak, layanan yang terganggu...">{{ old('impact', $pm->impact) }}</textarea>
            </div>

            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Action Items</label>
                <textarea name="action_items" rows="3"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="1. Tambahkan monitoring DB connection pool&#10;2. Setup alert threshold lebih sensitif&#10;3. Buat runbook untuk insiden DB">{{ old('action_items', $pm->action_items) }}</textarea>
            </div>

            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Penulis / Author</label>
                <input type="text" name="author" value="{{ old('author', $pm->author) }}"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="Nama PIC / tim">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
                <i class="fa fa-save mr-1"></i> Simpan Post-Mortem
            </button>
            <a href="{{ route('incidents.index') }}" class="px-5 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
