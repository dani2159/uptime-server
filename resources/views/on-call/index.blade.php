@extends('layouts.app')
@section('title', 'On-Call Schedule')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-user-shield text-sky-500 mr-2"></i>On-Call Schedule
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Notifikasi dikirim ke shift yang sedang aktif</p>
    </div>
    <a href="{{ route('on-call.create') }}"
        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i> Buat Jadwal
    </a>
</div>

@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-green-50 dark:bg-emerald-900/20 border border-green-200 dark:border-emerald-700 text-green-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm">
    <i class="fa-solid fa-circle-check text-green-500"></i>{{ session('success') }}
</div>
@endif

<div class="space-y-4">
    @forelse($schedules as $schedule)
    @php $activeShift = $schedule->currentShift(); @endphp
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="font-semibold text-gray-800 dark:text-slate-100">{{ $schedule->name }}</h3>
                    @if($activeShift)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                        {{ $activeShift->name }} aktif
                    </span>
                    @endif
                </div>
                @if($schedule->description)
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">{{ $schedule->description }}</p>
                @endif
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('on-call.edit', $schedule->id) }}"
                    class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-xs border border-gray-200 dark:border-slate-600 transition-colors">
                    <i class="fa-solid fa-pen-to-square mr-1"></i>Edit
                </a>
                <form method="POST" action="{{ route('on-call.destroy', $schedule->id) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg text-xs border border-red-200 dark:border-red-800/40 transition-colors">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        @if($schedule->shifts->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
            @foreach($schedule->shifts as $shift)
            @php
                $days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
                $isActive = $activeShift?->id === $shift->id;
            @endphp
            <div class="rounded-xl p-3 border text-sm
                {{ $isActive
                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700'
                    : 'bg-gray-50 dark:bg-slate-900/60 border-gray-200 dark:border-slate-700' }}">
                <div class="flex items-center gap-1.5 mb-1">
                    @if($isActive)
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse flex-shrink-0"></span>
                    @endif
                    <span class="font-medium text-gray-800 dark:text-slate-100">{{ $shift->name }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-slate-400">
                    {{ $shift->day_of_week !== null ? $days[$shift->day_of_week] : 'Setiap hari' }}
                    · {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                </p>
                @if($shift->channel)
                <p class="text-xs text-sky-600 dark:text-sky-400 mt-1"><i class="fa-solid fa-bell text-[10px] mr-1"></i>{{ $shift->channel->name }}</p>
                @endif
                @if($shift->contact_info)
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5 truncate">{{ $shift->contact_info }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400 dark:text-slate-500 text-center py-4">
            <i class="fa-regular fa-calendar-xmark mr-1"></i>Belum ada shift ditambahkan
        </p>
        @endif
    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm text-center py-14">
        <i class="fa-solid fa-user-shield text-4xl mb-3 block text-sky-200 dark:text-slate-600"></i>
        <p class="text-gray-500 dark:text-slate-400 text-sm mb-2">Belum ada jadwal on-call</p>
        <a href="{{ route('on-call.create') }}" class="text-sky-600 dark:text-sky-400 text-sm hover:underline">Buat sekarang</a>
    </div>
    @endforelse
</div>
@endsection
