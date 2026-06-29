@extends('layouts.app')
@section('title', 'Tambah Pagespeed Monitor')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('pagespeed.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Tambah Pagespeed Monitor</h1>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl p-6">
        <form action="{{ route('pagespeed.store') }}" method="POST">
            @csrf
            @php $pagespeed = null; @endphp
            @include('pagespeed._form')

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-xl text-sm transition">
                    <i class="fa-solid fa-plus mr-1"></i> Buat Monitor
                </button>
                <a href="{{ route('pagespeed.index') }}" class="border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold px-6 py-2 rounded-xl text-sm transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
