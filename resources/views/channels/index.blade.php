@extends('layouts.app')
@section('title', 'Notification Channels')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-bell text-sky-500 mr-2"></i>Notification Channels
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Telegram, WhatsApp & Webhook untuk notifikasi downtime</p>
    </div>
    <a href="{{ route('channels.create') }}"
       class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
              text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i> Tambah Channel
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 dark:bg-slate-700/40 text-xs uppercase text-gray-500 dark:text-slate-400 tracking-wider border-b border-sky-100 dark:border-slate-700">
            <tr>
                <th class="px-5 py-3 text-left">Nama</th>
                <th class="px-5 py-3 text-left">Tipe</th>
                <th class="px-5 py-3 text-left">Target</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50 dark:divide-slate-700/50">
            @forelse($channels as $channel)
            <tr class="hover:bg-sky-50/40 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-5 py-3 font-semibold text-gray-800 dark:text-slate-100">{{ $channel->name }}</td>
                <td class="px-5 py-3">
                    @php
                        $typeStyle = match($channel->type) {
                            'telegram' => 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400',
                            'whatsapp' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                            'webhook'  => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
                            default    => 'bg-gray-100 text-gray-500',
                        };
                        $typeIcon = match($channel->type) {
                            'telegram' => 'fa-brands fa-telegram',
                            'whatsapp' => 'fa-brands fa-whatsapp',
                            'webhook'  => 'fa-solid fa-webhook',
                            default    => 'fa-solid fa-bell',
                        };
                        $typeLabel = match($channel->type) {
                            'telegram' => 'Telegram',
                            'whatsapp' => 'WhatsApp',
                            'webhook'  => 'Webhook',
                            default    => ucfirst($channel->type),
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $typeStyle }}">
                        <i class="{{ $typeIcon }} mr-1"></i>{{ $typeLabel }}
                    </span>
                </td>
                <td class="px-5 py-3 text-gray-500 dark:text-slate-400 font-mono text-xs max-w-xs truncate">
                    {{ $channel->target }}
                    @if($channel->type === 'webhook')
                    <span class="ml-1.5 text-violet-400 non-mono" title="HMAC signing {{ $channel->token ? 'aktif' : 'nonaktif' }}">
                        <i class="fa-solid fa-{{ $channel->token ? 'lock' : 'lock-open' }} text-[10px]"></i>
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center gap-1 text-xs font-medium
                        {{ $channel->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-slate-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $channel->is_active ? 'bg-green-400' : 'bg-gray-300 dark:bg-slate-500' }}"></span>
                        {{ $channel->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('channels.edit', $channel) }}"
                           class="text-xs text-sky-600 dark:text-sky-400 hover:underline font-medium">
                            <i class="fa-solid fa-pen-to-square mr-1 text-[10px]"></i>Edit
                        </a>
                        <form method="POST" action="{{ route('channels.destroy', $channel) }}" class="inline" id="del-ch-{{ $channel->id }}">
                            @csrf @method('DELETE')
                            <button type="button"
                                    onclick="swalDelete('del-ch-{{ $channel->id }}', '{{ addslashes($channel->name) }}')"
                                    class="text-xs text-red-400 dark:text-red-400 hover:text-red-600 hover:underline">
                                <i class="fa-solid fa-trash text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-5 py-14 text-center text-gray-400 dark:text-slate-500">
                    <i class="fa-solid fa-bell-slash text-3xl mb-3 block text-gray-300 dark:text-slate-600"></i>
                    <p class="mb-2">Belum ada channel notifikasi.</p>
                    <a href="{{ route('channels.create') }}" class="text-sky-500 hover:underline text-sm font-medium">
                        <i class="fa-solid fa-plus mr-1 text-xs"></i>Tambah sekarang
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
