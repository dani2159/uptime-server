@extends('layouts.app')
@section('title', 'API Tokens')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
            <i class="fa-solid fa-key text-sky-500 mr-2"></i>API Tokens
        </h1>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Bearer token untuk akses REST API <code class="text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-900/20 px-1 py-0.5 rounded">/api/v1/monitors</code></p>
    </div>
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
        <i class="fa-solid fa-plus text-xs"></i> Buat Token
    </button>
</div>

@if(session('new_token'))
<div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-2xl p-4 mb-5">
    <div class="flex items-start gap-3">
        <i class="fa-solid fa-key text-emerald-500 mt-0.5"></i>
        <div class="flex-1 min-w-0">
            <p class="text-emerald-700 dark:text-emerald-300 font-semibold text-sm mb-2">Token baru dibuat — salin sekarang, tidak akan ditampilkan lagi!</p>
            <code class="block bg-white dark:bg-slate-900 border border-emerald-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-emerald-700 dark:text-emerald-400 text-sm break-all select-all font-mono">{{ session('new_token') }}</code>
            <button onclick="navigator.clipboard.writeText('{{ session('new_token') }}').then(()=>this.innerHTML='<i class=\'fa-solid fa-check mr-1\'></i>Tersalin!')" class="mt-2 text-xs text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                <i class="fa-solid fa-copy"></i> Salin ke Clipboard
            </button>
        </div>
    </div>
</div>
@endif

<div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden mb-4">
    <table class="w-full text-sm">
        <thead class="bg-sky-50/60 dark:bg-slate-700/40 text-xs uppercase text-gray-500 dark:text-slate-400 tracking-wider border-b border-sky-100 dark:border-slate-700">
            <tr>
                <th class="px-5 py-3 text-left">Nama</th>
                <th class="px-5 py-3 text-left">Kemampuan</th>
                <th class="px-5 py-3 text-left">Terakhir Digunakan</th>
                <th class="px-5 py-3 text-left">Kadaluarsa</th>
                <th class="px-5 py-3 text-left">Dibuat</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sky-50 dark:divide-slate-700/50">
            @forelse($tokens as $token)
            <tr class="hover:bg-sky-50/30 dark:hover:bg-slate-700/20">
                <td class="px-5 py-3 font-medium text-gray-800 dark:text-slate-100">{{ $token->name }}</td>
                <td class="px-5 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $token->abilities === 'admin' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : ($token->abilities === 'write' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : 'bg-sky-100 dark:bg-slate-700 text-sky-700 dark:text-slate-300') }}">
                        {{ $token->abilities }}
                    </span>
                </td>
                <td class="px-5 py-3 text-gray-500 dark:text-slate-400">{{ $token->last_used_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                <td class="px-5 py-3">
                    @if($token->expires_at)
                        @if($token->isExpired())
                            <span class="text-red-500 dark:text-red-400 text-xs"><i class="fa-solid fa-ban mr-1"></i>Kadaluarsa</span>
                        @else
                            <span class="text-gray-600 dark:text-slate-400">{{ $token->expires_at->format('d M Y') }}</span>
                        @endif
                    @else
                        <span class="text-gray-400 dark:text-slate-500 text-xs">Tidak kadaluarsa</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-400 dark:text-slate-500 text-xs">{{ $token->created_at->format('d M Y H:i') }}</td>
                <td class="px-5 py-3 text-right">
                    <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}"
                        onsubmit="return confirm('Hapus token ini? Integrasi yang menggunakannya akan gagal.')">
                        @csrf @method('DELETE')
                        <button class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-xs"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400 dark:text-slate-500">
                <i class="fa-solid fa-key text-3xl mb-2 block text-gray-200 dark:text-slate-600"></i>
                Belum ada API token
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-sky-50 dark:bg-slate-800/50 border border-sky-100 dark:border-slate-700 rounded-xl p-4 text-sm">
    <p class="font-semibold text-gray-700 dark:text-slate-300 mb-2"><i class="fa-solid fa-code mr-1 text-sky-500"></i> Cara Penggunaan</p>
    <code class="block bg-white dark:bg-slate-900 border border-sky-100 dark:border-slate-700 rounded-lg px-3 py-2 text-xs font-mono text-gray-700 dark:text-slate-300 select-all">
        curl -H "Authorization: Bearer &lt;token&gt;" {{ url('/api/v1/monitors') }}
    </code>
</div>

{{-- Modal --}}
<div id="modal-create" class="hidden fixed inset-0 bg-black/40 dark:bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-gray-800 dark:text-slate-100 font-semibold mb-4">Buat API Token Baru</h2>
        <form method="POST" action="{{ route('api-tokens.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">Nama Token</label>
                <input type="text" name="name" required placeholder="Monitoring Dashboard, CI/CD, dll"
                    class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">Kemampuan</label>
                <select name="abilities" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none">
                    <option value="read">Read — hanya baca data</option>
                    <option value="write">Write — termasuk kirim heartbeat</option>
                    <option value="admin">Admin — akses penuh</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">Kadaluarsa (opsional)</label>
                <input type="date" name="expires_at"
                    class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white rounded-xl text-sm font-semibold transition-all">Buat Token</button>
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-xl text-sm transition-all">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
