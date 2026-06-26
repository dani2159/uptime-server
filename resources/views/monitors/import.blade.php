@extends('layouts.app')
@section('title', 'Import / Export Monitor')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-file-import text-sky-500 mr-2"></i>Import / Export Monitor
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Backup, restore, dan bulk import konfigurasi monitor</p>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 dark:bg-emerald-900/20 border border-green-200 dark:border-emerald-700 text-green-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-check text-green-500"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-400 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-xmark text-red-500"></i>{{ session('error') }}
    </div>
    @endif

    <div class="space-y-4">

        {{-- Export JSON --}}
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center">
                    <i class="fa-solid fa-download text-sky-500 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-slate-100">Export JSON</h2>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Download semua monitor sebagai file JSON</p>
                </div>
            </div>
            <a href="{{ route('monitors.export') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-download text-xs"></i> Download monitors.json
            </a>
        </div>

        {{-- Import JSON --}}
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-upload text-emerald-500 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-slate-100">Import JSON</h2>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Upload file JSON yang diekspor sebelumnya. Monitor dibuat dalam status <span class="text-amber-600 dark:text-amber-400">inactive</span>.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('monitors.import') }}" enctype="multipart/form-data" class="flex items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".json" required
                    class="block text-sm text-gray-500 dark:text-slate-400
                           file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0
                           file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700
                           dark:file:bg-slate-700 dark:file:text-slate-200 hover:file:bg-sky-100 dark:hover:file:bg-slate-600">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm px-4 py-2 rounded-xl font-semibold transition-all">
                    Import
                </button>
            </form>
        </div>

        {{-- Import CSV --}}
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-table text-amber-500 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-slate-100">Bulk Import CSV</h2>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mb-1">Upload file CSV dengan kolom header:</p>
                    <code class="text-xs bg-gray-100 dark:bg-slate-900 rounded px-2 py-0.5 text-gray-700 dark:text-slate-300 font-mono">name,url,type,check_interval,timeout</code>
                </div>
            </div>
            <form method="POST" action="{{ route('monitors.import-csv') }}" enctype="multipart/form-data" class="flex items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".csv" required
                    class="block text-sm text-gray-500 dark:text-slate-400
                           file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0
                           file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700
                           dark:file:bg-slate-700 dark:file:text-slate-200 hover:file:bg-amber-100 dark:hover:file:bg-slate-600">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm px-4 py-2 rounded-xl font-semibold transition-all">
                    Import CSV
                </button>
            </form>
        </div>

        {{-- Smoke Test --}}
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-flask text-violet-500 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-slate-100">Smoke Test Post-Deploy</h2>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Jalankan cek semua monitor aktif sekarang — berguna setelah deploy.</p>
                </div>
            </div>
            <a href="{{ route('monitors.smoke-test') }}"
               class="inline-flex items-center gap-2 bg-violet-500 hover:bg-violet-600 text-white text-sm px-4 py-2 rounded-xl font-semibold transition-all"
               onclick="this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin text-xs\'></i> Menjalankan...'; this.classList.add('opacity-70','pointer-events-none')">
                <i class="fa-solid fa-flask text-xs"></i> Jalankan Smoke Test
            </a>
        </div>
    </div>
</div>
@endsection
