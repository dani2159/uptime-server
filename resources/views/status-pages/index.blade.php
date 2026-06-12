@extends('layouts.app')
@section('title', 'Status Pages')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-circle-check text-sky-500 mr-2"></i>Status Pages
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Halaman status publik yang bisa dibagikan</p>
    </div>
    <a href="{{ route('status-pages.create') }}"
       class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
              text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus"></i>Buat Status Page
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 dark:bg-slate-700/50 border-b border-sky-100 dark:border-slate-700 text-gray-500 dark:text-slate-400 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3 text-left">Judul</th>
                <th class="px-5 py-3 text-left">URL Publik</th>
                <th class="px-5 py-3 text-center">Monitor</th>
                <th class="px-5 py-3 text-center">Publik</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50 dark:divide-slate-700">
            @forelse($pages as $page)
            @php $totalMonitors = count($page->allMonitorIds()); @endphp
            <tr class="hover:bg-sky-50/40 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-5 py-3">
                    <p class="font-semibold text-gray-800 dark:text-slate-100">{{ $page->title }}</p>
                    @if($page->description)
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5 truncate max-w-xs">{{ $page->description }}</p>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <a href="{{ route('status.public', $page->slug) }}" target="_blank"
                       class="text-sky-600 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-200 hover:underline font-mono text-xs inline-flex items-center gap-1">
                        /status/{{ $page->slug }}
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </td>
                <td class="px-5 py-3 text-center">
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        <i class="fa-solid fa-chart-bar text-sky-400 mr-1"></i>{{ $totalMonitors }}
                    </span>
                </td>
                <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center gap-1 text-xs font-medium
                        {{ $page->is_public ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                        <span class="w-2 h-2 rounded-full {{ $page->is_public ? 'bg-green-400' : 'bg-gray-300' }}"></span>
                        {{ $page->is_public ? 'Ya' : 'Tidak' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3 text-xs">
                        <a href="{{ route('status.public', $page->slug) }}" target="_blank"
                           class="text-sky-500 dark:text-sky-400 hover:underline font-medium"
                           title="Lihat publik">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="{{ route('status-pages.edit', $page) }}"
                           class="text-sky-600 dark:text-sky-400 hover:underline font-medium"
                           title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form id="del-sp-{{ $page->id }}" method="POST"
                              action="{{ route('status-pages.destroy', $page) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button"
                                    onclick="swalDelete('del-sp-{{ $page->id }}', '{{ addslashes($page->title) }}')"
                                    class="text-red-400 hover:text-red-500" title="Hapus">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-5 py-14 text-center text-gray-400 dark:text-slate-500">
                    <i class="fa-solid fa-circle-check text-4xl mb-3 opacity-30 block"></i>
                    <p class="mb-2">Belum ada status page.</p>
                    <a href="{{ route('status-pages.create') }}" class="text-sky-500 hover:underline text-sm font-medium">
                        <i class="fa-solid fa-plus mr-1"></i>Buat sekarang
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($pages->hasPages())
    <div class="px-5 py-3 border-t border-sky-50 dark:border-slate-700">{{ $pages->links() }}</div>
    @endif
</div>
@endsection
