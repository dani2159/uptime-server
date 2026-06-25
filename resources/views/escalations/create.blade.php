@extends('layouts.app')
@section('title', 'Tambah Aturan Eskalasi')

@section('content')
<div class="max-w-xl mx-auto px-4 py-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('escalations.index') }}" class="text-sky-500 hover:text-sky-600">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold dark:text-white">Tambah Aturan Eskalasi</h1>
    </div>

    <form method="POST" action="{{ route('escalations.store') }}"
          class="bg-white dark:bg-slate-800 rounded-xl shadow p-6 space-y-5">
        @csrf
        @include('escalations._form')
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('escalations.index') }}"
               class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-slate-700 dark:text-slate-200">Batal</a>
            <button type="submit"
                    class="px-4 py-2 text-sm rounded-lg bg-sky-500 hover:bg-sky-600 text-white font-medium">
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection
