@extends('layouts.app')
@section('title', 'Edit — ' . $pagespeed->name)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('pagespeed.show', $pagespeed) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Edit: {{ $pagespeed->name }}</h1>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl p-6">
        <form action="{{ route('pagespeed.update', $pagespeed) }}" method="POST">
            @csrf
            @method('PUT')
            @include('pagespeed._form')

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-xl text-sm transition">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
                </button>
                <a href="{{ route('pagespeed.show', $pagespeed) }}" class="border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold px-6 py-2 rounded-xl text-sm transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
