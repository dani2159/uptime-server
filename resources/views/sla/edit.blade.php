@extends('layouts.app')
@section('title', 'Edit SLA Contract')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('sla.index') }}" class="text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">Edit SLA Contract</h1>
    </div>
    <form method="POST" action="{{ route('sla.update', $contract->id) }}">
        @csrf @method('PUT')
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5 mb-4">
            @include('sla._form')
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white rounded-xl text-sm font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-floppy-disk mr-1 text-xs"></i>Simpan
            </button>
            <a href="{{ route('sla.index') }}" class="px-5 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-xl text-sm transition-all">Batal</a>
        </div>
    </form>
</div>
@endsection
