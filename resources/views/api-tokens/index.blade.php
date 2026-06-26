@extends('layouts.app')
@section('title', 'API Tokens')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">API Tokens</h1>
        <p class="text-slate-400 text-sm mt-1">Token untuk akses REST API <code class="text-sky-400">/api/v1/monitors</code></p>
    </div>
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
        class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
        <i class="fa fa-plus mr-1"></i> Buat Token
    </button>
</div>

@if(session('new_token'))
<div class="bg-emerald-900/40 border border-emerald-500 rounded-xl p-4 mb-5">
    <div class="flex items-start gap-3">
        <i class="fa fa-key text-emerald-400 mt-0.5"></i>
        <div>
            <p class="text-emerald-300 font-medium text-sm mb-1">Token baru dibuat — salin sekarang, tidak akan ditampilkan lagi!</p>
            <code class="block bg-slate-900 rounded px-3 py-2 text-emerald-400 text-sm break-all select-all">{{ session('new_token') }}</code>
            <button onclick="navigator.clipboard.writeText('{{ session('new_token') }}')" class="mt-2 text-xs text-sky-400 hover:text-sky-300">
                <i class="fa fa-copy mr-1"></i> Salin ke Clipboard
            </button>
        </div>
    </div>
</div>
@endif

@if(session('success'))
<div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-900 text-slate-400 text-xs uppercase tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">Nama</th>
                <th class="px-4 py-3 text-left">Kemampuan</th>
                <th class="px-4 py-3 text-left">Terakhir Digunakan</th>
                <th class="px-4 py-3 text-left">Kadaluarsa</th>
                <th class="px-4 py-3 text-left">Dibuat</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
            @forelse($tokens as $token)
            <tr class="hover:bg-slate-700/30">
                <td class="px-4 py-3 text-white font-medium">{{ $token->name }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs
                        {{ $token->abilities === 'admin' ? 'bg-red-900/50 text-red-300' : ($token->abilities === 'write' ? 'bg-amber-900/50 text-amber-300' : 'bg-slate-700 text-slate-300') }}">
                        {{ $token->abilities }}
                    </span>
                </td>
                <td class="px-4 py-3 text-slate-400">{{ $token->last_used_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                <td class="px-4 py-3 text-slate-400">
                    @if($token->expires_at)
                        @if($token->isExpired())
                            <span class="text-red-400"><i class="fa fa-ban mr-1"></i>Kadaluarsa</span>
                        @else
                            {{ $token->expires_at->format('d M Y') }}
                        @endif
                    @else
                        <span class="text-slate-500">Tidak kadaluarsa</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $token->created_at->format('d M Y H:i') }}</td>
                <td class="px-4 py-3 text-right">
                    <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}"
                        onsubmit="return confirm('Hapus token ini? Integrasi yang menggunakannya akan gagal.')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-300 text-xs"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada token</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5 bg-slate-800/50 border border-slate-700 rounded-xl p-4 text-sm text-slate-400">
    <p class="font-medium text-slate-300 mb-2"><i class="fa fa-code mr-1 text-sky-400"></i> Cara Penggunaan</p>
    <code class="block bg-slate-900 rounded px-3 py-2 text-xs font-mono">
        curl -H "Authorization: Bearer &lt;token&gt;" {{ url('/api/v1/monitors') }}
    </code>
</div>

{{-- Modal buat token --}}
<div id="modal-create" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
    <div class="bg-slate-800 border border-slate-700 rounded-xl w-full max-w-md p-6">
        <h2 class="text-white font-semibold mb-4">Buat API Token Baru</h2>
        <form method="POST" action="{{ route('api-tokens.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Nama Token</label>
                <input type="text" name="name" required
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                    placeholder="Monitoring Dashboard, CI/CD, dll">
            </div>
            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Kemampuan</label>
                <select name="abilities" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                    <option value="read">Read — hanya baca data</option>
                    <option value="write">Write — termasuk kirim heartbeat</option>
                    <option value="admin">Admin — akses penuh</option>
                </select>
            </div>
            <div>
                <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Kadaluarsa (opsional)</label>
                <input type="date" name="expires_at"
                    class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">Buat Token</button>
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
