@extends('layouts.app')
@section('title', 'On-Call Schedule')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">On-Call Schedule</h1>
        <p class="text-slate-400 text-sm mt-1">Notifikasi dikirim ke shift yang sedang aktif</p>
    </div>
    <a href="{{ route('on-call.create') }}" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
        <i class="fa fa-plus mr-1"></i> Buat Jadwal
    </a>
</div>

@if(session('success'))
<div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid gap-4">
    @forelse($schedules as $schedule)
    @php $activeShift = $schedule->currentShift(); @endphp
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-white font-semibold">{{ $schedule->name }}</h3>
                @if($schedule->description)
                <p class="text-slate-400 text-sm">{{ $schedule->description }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                @if($activeShift)
                <span class="px-2 py-1 bg-emerald-900/50 text-emerald-300 text-xs rounded-full">
                    <i class="fa fa-circle text-emerald-400 text-xs mr-1"></i> {{ $activeShift->name }} aktif
                </span>
                @endif
                <a href="{{ route('on-call.edit', $schedule->id) }}" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white rounded text-xs"><i class="fa fa-edit mr-1"></i>Edit</a>
                <form method="POST" action="{{ route('on-call.destroy', $schedule->id) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 bg-red-900/50 hover:bg-red-800 text-red-300 rounded text-xs"><i class="fa fa-trash"></i></button>
                </form>
            </div>
        </div>

        @if($schedule->shifts->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($schedule->shifts as $shift)
            @php
                $days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
                $isActive = $activeShift?->id === $shift->id;
            @endphp
            <div class="bg-slate-900/50 rounded-lg p-3 border {{ $isActive ? 'border-emerald-600' : 'border-slate-700' }}">
                <div class="flex items-center gap-2 mb-1">
                    @if($isActive)<span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>@endif
                    <span class="text-sm font-medium text-white">{{ $shift->name }}</span>
                </div>
                <p class="text-xs text-slate-400">
                    {{ $shift->day_of_week !== null ? $days[$shift->day_of_week] : 'Setiap hari' }}
                    · {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                </p>
                @if($shift->channel)
                <p class="text-xs text-sky-400 mt-0.5"><i class="fa fa-bell mr-1"></i>{{ $shift->channel->name }}</p>
                @endif
                @if($shift->contact_info)
                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $shift->contact_info }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <p class="text-slate-500 text-sm">Belum ada shift</p>
        @endif
    </div>
    @empty
    <div class="text-center text-slate-500 py-12">
        <i class="fa fa-calendar-alt text-3xl mb-3 block"></i>
        Belum ada jadwal on-call. <a href="{{ route('on-call.create') }}" class="text-sky-400">Buat sekarang</a>
    </div>
    @endforelse
</div>
@endsection
