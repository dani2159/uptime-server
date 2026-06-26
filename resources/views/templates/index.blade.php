@extends('layouts.app')
@section('title', 'Monitor Templates')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-wand-magic-sparkles text-sky-500 mr-2"></i>Monitor Templates
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Preset monitor siap pakai — klik Apply untuk buat monitor baru dari template</p>
    </div>
    <a href="{{ route('templates.create') }}"
        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i> Template Kustom
    </a>
</div>

@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-green-50 dark:bg-emerald-900/20 border border-green-200 dark:border-emerald-700 text-green-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm">
    <i class="fa-solid fa-circle-check text-green-500"></i>{{ session('success') }}
</div>
@endif

@php
$typeIcons = [
    'http'  => 'fa-globe',
    'ping'  => 'fa-wifi',
    'port'  => 'fa-plug',
    'dns'   => 'fa-server',
    'ssl'   => 'fa-lock',
    'cron'  => 'fa-clock',
    'push'  => 'fa-arrow-up',
];
@endphp

@foreach($templates as $category => $tpls)
<div class="mb-6">
    <h2 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
        <span class="flex-shrink-0">{{ ucfirst($category) }}</span>
        <span class="flex-1 border-t border-gray-100 dark:border-slate-700"></span>
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($tpls as $t)
        @php $typeName = $t->config['type'] ?? null; @endphp
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-4 hover:border-sky-300 dark:hover:border-slate-500 transition-colors group">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fa-solid {{ $typeIcons[$typeName] ?? 'fa-globe' }} text-sky-500 dark:text-sky-400 text-xs"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-100 truncate">{{ $t->name }}</h3>
                        @if($typeName)
                        <span class="text-xs text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-900/20 px-1.5 py-0.5 rounded mt-0.5 inline-block">{{ strtoupper($typeName) }}</span>
                        @endif
                    </div>
                </div>
                @if($t->is_builtin)
                <span class="text-xs text-gray-400 dark:text-slate-500 bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded-full flex-shrink-0">Bawaan</span>
                @else
                <form method="POST" action="{{ route('templates.destroy', $t->id) }}" onsubmit="return confirm('Hapus template ini?')">
                    @csrf @method('DELETE')
                    <button class="text-red-400 dark:text-red-500 hover:text-red-600 dark:hover:text-red-400 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
                @endif
            </div>

            @if(count($t->config) > 0)
            <div class="text-xs text-gray-400 dark:text-slate-500 space-y-0.5 mb-3 bg-gray-50 dark:bg-slate-900/60 rounded-lg p-2">
                @foreach(array_slice($t->config, 0, 3, true) as $k => $v)
                @if(!is_array($v) && $k !== 'type')
                <div class="flex gap-1.5 truncate">
                    <span class="text-gray-300 dark:text-slate-600 flex-shrink-0">{{ $k }}:</span>
                    <span class="text-gray-600 dark:text-slate-400 truncate">{{ is_bool($v) ? ($v ? 'true' : 'false') : $v }}</span>
                </div>
                @endif
                @endforeach
            </div>
            @endif

            <button onclick="applyTemplate({{ $t->id }}, '{{ addslashes($t->name) }}')"
                class="w-full py-2 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white rounded-xl text-xs font-semibold transition-all shadow-sm">
                <i class="fa-solid fa-wand-magic-sparkles mr-1 text-[10px]"></i>Apply
            </button>
        </div>
        @endforeach
    </div>
</div>
@endforeach

@if(!$templates || $templates->isEmpty())
<div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm text-center py-14">
    <i class="fa-solid fa-wand-magic-sparkles text-4xl mb-3 block text-sky-200 dark:text-slate-600"></i>
    <p class="text-gray-500 dark:text-slate-400 text-sm mb-2">Belum ada template</p>
    <a href="{{ route('templates.create') }}" class="text-sky-600 dark:text-sky-400 text-sm hover:underline">Buat template kustom</a>
</div>
@endif

{{-- Apply Modal --}}
<div id="apply-modal" class="hidden fixed inset-0 bg-black/40 dark:bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="text-gray-800 dark:text-slate-100 font-semibold mb-4">
            <i class="fa-solid fa-wand-magic-sparkles text-sky-500 mr-2"></i>Buat Monitor dari Template
        </h3>
        <form id="apply-form" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">Nama Monitor <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="apply-name" required
                    class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">URL / Host <span class="text-red-500">*</span></label>
                <input type="text" name="url" required placeholder="https://contoh.com atau 192.168.1.1"
                    class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white rounded-xl text-sm font-semibold transition-all">Buat Monitor</button>
                <button type="button" onclick="document.getElementById('apply-modal').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-xl text-sm transition-all">Batal</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function applyTemplate(id, name) {
    document.getElementById('apply-form').action = '/templates/' + id + '/apply';
    document.getElementById('apply-name').value = name;
    document.getElementById('apply-modal').classList.remove('hidden');
}
</script>
@endpush
@endsection
