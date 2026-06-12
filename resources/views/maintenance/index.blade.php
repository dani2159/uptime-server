@extends('layouts.app')
@section('title', 'Maintenance Windows')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Maintenance Windows</h1>
        <p class="text-xs text-gray-400 mt-0.5">Notifikasi tidak dikirim selama periode maintenance aktif</p>
    </div>
    <a href="{{ route('maintenance.create') }}"
       class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
              text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        + Tambah Maintenance
    </a>
</div>

<div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 border-b border-sky-100 text-gray-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">Judul</th>
                <th class="px-5 py-3 text-left">Mulai</th>
                <th class="px-5 py-3 text-left">Selesai</th>
                <th class="px-5 py-3 text-left">Monitor</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50">
            @forelse($windows as $window)
            @php
                $now    = now();
                $active = $now->between($window->start_at, $window->end_at);
                $past   = $now->gt($window->end_at);
            @endphp
            <tr class="hover:bg-sky-50/40 transition-colors {{ $past ? 'opacity-50' : '' }}">
                <td class="px-5 py-3 font-semibold text-gray-800">
                    {{ $window->title }}
                    @if($window->description)
                    <p class="text-xs text-gray-400 mt-0.5 font-normal">{{ $window->description }}</p>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-600 text-xs">{{ $window->start_at->format('d M Y H:i') }}</td>
                <td class="px-5 py-3 text-gray-600 text-xs">{{ $window->end_at->format('d M Y H:i') }}</td>
                <td class="px-5 py-3">
                    @if($window->monitor_ids === null)
                        <span class="text-xs bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full font-medium">Semua Monitor</span>
                    @else
                        @foreach($monitors->whereIn('id', $window->monitor_ids) as $m)
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full mr-1">{{ $m->name }}</span>
                        @endforeach
                    @endif
                </td>
                <td class="px-5 py-3 text-center">
                    @if($active)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span> Aktif
                    </span>
                    @elseif($past)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Selesai</span>
                    @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">Terjadwal</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3 text-xs">
                        <a href="{{ route('maintenance.edit', $window) }}"
                           class="text-sky-600 hover:underline font-medium">Edit</a>
                        <form method="POST" action="{{ route('maintenance.destroy', $window) }}" class="inline"
                              onsubmit="return confirm('Hapus maintenance window ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-14 text-center text-gray-400">
                    <p class="mb-2">Belum ada maintenance window.</p>
                    <a href="{{ route('maintenance.create') }}" class="text-sky-500 hover:underline text-sm font-medium">
                        + Tambah sekarang
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($windows->hasPages())
    <div class="px-5 py-3 border-t border-sky-50">{{ $windows->links() }}</div>
    @endif
</div>
@endsection
