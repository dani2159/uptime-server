@extends('layouts.app')
@section('title', 'Import / Export Monitor')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-white">Import / Export Monitor</h1>

    @if(session('success'))
    <div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-900/30 border border-red-600 text-red-300 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Export --}}
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-2"><i class="fa fa-download text-sky-400 mr-2"></i>Export JSON</h2>
        <p class="text-slate-400 text-sm mb-4">Download semua monitor sebagai file JSON untuk backup atau migrasi.</p>
        <a href="{{ route('monitors.export') }}" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
            <i class="fa fa-download mr-1"></i> Download monitors.json
        </a>
    </div>

    {{-- Import JSON --}}
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-2"><i class="fa fa-upload text-emerald-400 mr-2"></i>Import JSON</h2>
        <p class="text-slate-400 text-sm mb-4">Upload file JSON yang diekspor sebelumnya. Monitor akan dibuat dalam status <span class="text-amber-400">inactive</span>.</p>
        <form method="POST" action="{{ route('monitors.import') }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="file" accept=".json" required
                class="block text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-700 file:text-white hover:file:bg-slate-600">
            <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium">Import</button>
        </form>
    </div>

    {{-- Import CSV --}}
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-2"><i class="fa fa-table text-amber-400 mr-2"></i>Bulk Import CSV</h2>
        <p class="text-slate-400 text-sm mb-2">Upload file CSV dengan kolom header.</p>
        <code class="block bg-slate-900 rounded px-3 py-2 text-xs text-slate-300 mb-4 font-mono">name,url,type,check_interval,timeout</code>
        <form method="POST" action="{{ route('monitors.import-csv') }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="file" accept=".csv" required
                class="block text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-700 file:text-white hover:file:bg-slate-600">
            <button type="submit" class="px-4 py-2 bg-amber-700 hover:bg-amber-600 text-white rounded-lg text-sm font-medium">Import CSV</button>
        </form>
    </div>

    {{-- Smoke Test --}}
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <h2 class="text-white font-semibold mb-2"><i class="fa fa-flask text-violet-400 mr-2"></i>Smoke Test</h2>
        <p class="text-slate-400 text-sm mb-4">Jalankan cek langsung semua monitor aktif sekarang. Berguna setelah deploy.</p>
        <a href="{{ route('monitors.smoke-test') }}"
            class="px-4 py-2 bg-violet-700 hover:bg-violet-600 text-white rounded-lg text-sm font-medium"
            onclick="this.textContent='Menjalankan...'; this.classList.add('opacity-50','pointer-events-none')">
            <i class="fa fa-flask mr-1"></i> Jalankan Smoke Test
        </a>
    </div>
</div>
@endsection
