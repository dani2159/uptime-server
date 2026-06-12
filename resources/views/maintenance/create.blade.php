@extends('layouts.app')
@section('title', 'Tambah Maintenance Window')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('maintenance.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 text-sky-400 hover:text-sky-600 transition-colors border border-sky-100">
            ←
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Tambah Maintenance Window</h1>
            <p class="text-xs text-gray-400">Notifikasi dinonaktifkan selama periode ini</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-sky-100 shadow-sm p-6">
        <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-5">
            @csrf
            @include('maintenance._form')
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                               text-white text-sm px-6 py-2.5 rounded-xl font-semibold shadow-sm transition-all">
                    Simpan
                </button>
                <a href="{{ route('maintenance.index') }}"
                   class="text-sm text-gray-400 hover:text-gray-600 px-4 py-2.5">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
