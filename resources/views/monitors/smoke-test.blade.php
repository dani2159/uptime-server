@extends('layouts.app')
@section('title', 'Smoke Test')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('monitors.import-page') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-vial text-sky-500"></i>
                Hasil Smoke Test
            </h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>

    {{-- Summary banner --}}
    @php $allPass = $passed === $total; @endphp
    <div class="rounded-2xl p-5 mb-6 flex items-center gap-4
        {{ $allPass
            ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800'
            : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
            {{ $allPass ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-red-100 dark:bg-red-900/40' }}">
            <i class="fa-solid {{ $allPass ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-red-500' }} text-2xl"></i>
        </div>
        <div>
            <div class="text-lg font-black {{ $allPass ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                {{ $passed }}/{{ $total }} Monitor UP
            </div>
            <div class="text-sm {{ $allPass ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                {{ $allPass ? 'Semua monitor merespons dengan baik.' : ($total - $passed).' monitor DOWN atau tidak merespons.' }}
            </div>
        </div>
        {{-- Progress bar --}}
        <div class="ml-auto text-right hidden sm:block">
            <div class="text-2xl font-black {{ $allPass ? 'text-emerald-500' : 'text-red-500' }}">
                {{ $total > 0 ? round($passed / $total * 100) : 0 }}%
            </div>
            <div class="text-xs {{ $allPass ? 'text-emerald-500' : 'text-red-400' }}">pass rate</div>
        </div>
    </div>

    {{-- Results table --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 dark:text-white text-sm">Detail Hasil</h2>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1 text-emerald-500"><i class="fa-solid fa-circle text-[8px]"></i> {{ $passed }} UP</span>
                <span class="flex items-center gap-1 text-red-500"><i class="fa-solid fa-circle text-[8px]"></i> {{ $total - $passed }} DOWN</span>
            </div>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-800">
            @foreach($results as $r)
            @php $up = $r['status'] === 'up'; @endphp
            <div class="flex items-center gap-3 px-5 py-3.5">
                {{-- Status icon --}}
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0
                    {{ $up ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                    <i class="fa-solid {{ $up ? 'fa-check text-emerald-500' : 'fa-xmark text-red-500' }} text-xs"></i>
                </div>
                {{-- Name + URL --}}
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ $r['name'] }}</div>
                    <div class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ $r['url'] }}</div>
                </div>
                {{-- Status badge --}}
                <span class="text-xs font-bold px-2 py-0.5 rounded-full border flex-shrink-0
                    {{ $up
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
                        : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800' }}">
                    {{ strtoupper($r['status']) }}
                </span>
                {{-- Response time --}}
                <div class="text-right flex-shrink-0 w-16">
                    <div class="text-sm font-mono font-bold
                        {{ $r['rt'] < 500 ? 'text-emerald-500' : ($r['rt'] < 1500 ? 'text-amber-500' : 'text-red-500') }}">
                        {{ $r['rt'] }}ms
                    </div>
                    <div class="text-[10px] text-gray-300 dark:text-slate-600">response</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-5 flex items-center gap-3">
        <a href="{{ route('monitors.smoke-test') }}"
           class="flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2 rounded-xl text-sm transition">
            <i class="fa-solid fa-rotate-right"></i> Jalankan Ulang
        </a>
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800 font-semibold px-5 py-2 rounded-xl text-sm transition">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
    </div>
</div>
@endsection
