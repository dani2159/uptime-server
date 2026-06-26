@extends('layouts.app')
@section('title', 'Buat Template Kustom')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('templates.index') }}" class="text-sky-400 hover:text-sky-300"><i class="fa fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-white">Buat Template Monitor Kustom</h1>
    </div>
    <form method="POST" action="{{ route('templates.store') }}">
        @csrf
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-4 mb-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Nama Template <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Kategori <span class="text-red-400">*</span></label>
                    <input type="text" name="category" value="{{ old('category') }}" required
                        class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                        placeholder="web, api, infra, database, dll">
                </div>
            </div>
            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Config JSON <span class="text-red-400">*</span></label>
                <textarea name="config" rows="8" required
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm font-mono focus:border-sky-500 focus:outline-none">{{ old('config', '{
  "type": "http",
  "http_method": "GET",
  "check_interval": 5,
  "timeout": 10,
  "retry_count": 3
}') }}</textarea>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">Simpan Template</button>
            <a href="{{ route('templates.index') }}" class="px-5 py-2 bg-slate-700 text-white rounded-lg text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
