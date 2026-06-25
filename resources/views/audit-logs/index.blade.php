@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold dark:text-white">Audit Log</h1>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('audit-logs.index') }}"
          class="flex flex-wrap gap-3 mb-6">
        <select name="action"
                class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-slate-600
                       bg-white dark:bg-slate-800 dark:text-slate-200">
            <option value="">Semua Aksi</option>
            @foreach($actions as $a)
            <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}"
               class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-slate-600
                      bg-white dark:bg-slate-800 dark:text-slate-200">
        <input type="date" name="to" value="{{ request('to') }}"
               class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-slate-600
                      bg-white dark:bg-slate-800 dark:text-slate-200">
        <button type="submit"
                class="px-4 py-1.5 text-sm bg-sky-500 hover:bg-sky-600 text-white rounded-lg">
            Filter
        </button>
        @if(request()->hasAny(['action','from','to']))
        <a href="{{ route('audit-logs.index') }}"
           class="px-4 py-1.5 text-sm bg-gray-200 dark:bg-slate-700 dark:text-slate-200 rounded-lg">
            Reset
        </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Waktu</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                    <th class="px-4 py-3 text-left">Deskripsi</th>
                    <th class="px-4 py-3 text-left">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-slate-400">
                        {{ $log->created_at->format('d-m-Y H:i:s') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            @if(str_contains($log->action,'deleted') || str_contains($log->action,'down'))
                                bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                            @elseif(str_contains($log->action,'slow'))
                                bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else
                                bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                            @endif">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3 dark:text-slate-200">{{ $log->description }}</td>
                    <td class="px-4 py-3 text-gray-400 dark:text-slate-500 font-mono text-xs">
                        {{ $log->ip_address ?? '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-slate-500">
                        Tidak ada log ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>

</div>
@endsection
