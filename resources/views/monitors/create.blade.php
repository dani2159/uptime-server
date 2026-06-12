@extends('layouts.app')
@section('title', 'Tambah Monitor')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('monitors.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-sky-400 hover:text-sky-600 transition-colors border border-sky-100 dark:border-slate-700">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">Tambah Monitor</h1>
            <p class="text-xs text-gray-400 dark:text-slate-500">Konfigurasi monitor baru</p>
        </div>
    </div>

    <form method="POST" action="{{ route('monitors.store') }}"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
        @csrf
        @include('monitors._form', ['monitor' => null])

        <div class="pt-2 flex gap-3">
            <button type="submit"
                    class="bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                           text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-all">
                Simpan Monitor
            </button>
            <a href="{{ route('monitors.index') }}"
               class="text-sm text-gray-400 hover:text-gray-600 px-4 py-2.5">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
