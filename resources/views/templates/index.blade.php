@extends('layouts.app')
@section('title', 'Monitor Templates')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Monitor Templates</h1>
        <p class="text-slate-400 text-sm mt-1">Preset monitor siap pakai — klik Apply untuk buat monitor baru dari template</p>
    </div>
    <a href="{{ route('templates.create') }}" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
        <i class="fa fa-plus mr-1"></i> Template Kustom
    </a>
</div>

@if(session('success'))
<div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

@foreach($templates as $category => $tpls)
<div class="mb-6">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-3">
        {{ ucfirst($category) }}
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($tpls as $t)
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-4 hover:border-slate-600 transition-colors">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-white text-sm font-medium">{{ $t->name }}</h3>
                    @if(isset($t->config['type']))
                    <span class="text-xs text-sky-400 bg-sky-900/30 px-2 py-0.5 rounded mt-1 inline-block">{{ $t->config['type'] }}</span>
                    @endif
                </div>
                @if($t->is_builtin)
                <span class="text-xs text-slate-500 bg-slate-700 px-2 py-0.5 rounded">Bawaan</span>
                @else
                <form method="POST" action="{{ route('templates.destroy', $t->id) }}" onsubmit="return confirm('Hapus?')">
                    @csrf @method('DELETE')
                    <button class="text-red-400 hover:text-red-300 text-xs"><i class="fa fa-trash"></i></button>
                </form>
                @endif
            </div>
            @if(count($t->config) > 0)
            <div class="text-xs text-slate-500 space-y-0.5 mb-3">
                @foreach(array_slice($t->config, 0, 3, true) as $k => $v)
                @if(!is_array($v))
                <div><span class="text-slate-600">{{ $k }}:</span> <span class="text-slate-400">{{ is_bool($v) ? ($v?'true':'false') : $v }}</span></div>
                @endif
                @endforeach
            </div>
            @endif
            <button onclick="applyTemplate({{ $t->id }}, '{{ addslashes($t->name) }}')"
                class="w-full py-1.5 bg-sky-700 hover:bg-sky-600 text-white rounded text-xs font-medium">
                <i class="fa fa-magic mr-1"></i> Apply
            </button>
        </div>
        @endforeach
    </div>
</div>
@endforeach

{{-- Apply modal --}}
<div id="apply-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
    <div class="bg-slate-800 border border-slate-700 rounded-xl w-full max-w-sm p-5">
        <h3 class="text-white font-semibold mb-3">Buat Monitor dari Template</h3>
        <form id="apply-form" method="POST" action="">
            @csrf
            <div class="mb-3">
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Nama Monitor</label>
                <input type="text" name="name" id="apply-name"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
            </div>
            <div class="mb-3">
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">URL / Host</label>
                <input type="text" name="url"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="https://contoh.com atau 192.168.1.1">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded text-sm font-medium">Buat Monitor</button>
                <button type="button" onclick="document.getElementById('apply-modal').classList.add('hidden')"
                    class="flex-1 py-2 bg-slate-700 text-white rounded text-sm">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function applyTemplate(id, name) {
    document.getElementById('apply-form').action = '/templates/' + id + '/apply';
    document.getElementById('apply-name').value = name;
    document.getElementById('apply-modal').classList.remove('hidden');
}
</script>
@endsection
