@extends('layouts.app')
@section('title', 'Edit Monitor — ' . $monitor->name)

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('monitors.show', $monitor) }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-sky-400 hover:text-sky-600 transition-colors border border-sky-100 dark:border-slate-700">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">Edit Monitor</h1>
            <p class="text-xs text-sky-500">{{ $monitor->name }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('monitors.update', $monitor) }}"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')
        @include('monitors._form', ['monitor' => $monitor])

        <div class="pt-1">
            <label class="flex items-center gap-2.5 cursor-pointer hover:bg-sky-50 dark:hover:bg-slate-700 rounded-lg px-2 py-2 transition-colors w-fit">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ $monitor->is_active ? 'checked' : '' }}
                       class="rounded border-sky-300 dark:border-slate-500 text-sky-500 bg-sky-50 dark:bg-slate-700">
                <span class="text-sm text-gray-700 dark:text-slate-200 font-medium">Monitor aktif</span>
            </label>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit"
                    class="bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                           text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-all">
                Simpan Perubahan
            </button>
            <a href="{{ route('monitors.show', $monitor) }}"
               class="text-sm text-gray-400 hover:text-gray-600 px-4 py-2.5">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
