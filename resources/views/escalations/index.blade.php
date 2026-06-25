@extends('layouts.app')
@section('title', 'Eskalasi')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold dark:text-white">Aturan Eskalasi</h1>
        <a href="{{ route('escalations.create') }}"
           class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm rounded-lg font-medium">
            + Tambah Aturan
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Channel</th>
                    <th class="px-4 py-3 text-left">Delay</th>
                    <th class="px-4 py-3 text-left">Monitor</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($rules as $rule)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-3 font-medium dark:text-slate-200">{{ $rule->name }}</td>
                    <td class="px-4 py-3 dark:text-slate-300">
                        {{ $rule->channel?->name ?? '<i class="text-gray-400">Dihapus</i>' }}
                        <span class="text-xs text-gray-400">({{ $rule->channel?->type }})</span>
                    </td>
                    <td class="px-4 py-3 dark:text-slate-300">{{ $rule->delay_minutes }} menit</td>
                    <td class="px-4 py-3 dark:text-slate-300">
                        {{ $rule->monitor?->name ?? '<span class="text-gray-400 text-xs">Semua</span>' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($rule->is_active)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs">Aktif</span>
                        @else
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400 rounded-full text-xs">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('escalations.edit', $rule) }}"
                           class="inline-block px-3 py-1 text-xs bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400 rounded-lg mr-1">Edit</a>
                        <button onclick="deleteRule({{ $rule->id }}, '{{ addslashes($rule->name) }}')"
                                class="px-3 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-lg">
                            Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-slate-500">
                        Belum ada aturan eskalasi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-xs text-gray-400 dark:text-slate-500">
        Eskalasi dikirim X menit setelah insiden dibuka dan monitor masih DOWN. Aturan global berlaku ke semua monitor.
    </p>

</div>

<script>
async function deleteRule(id, name) {
    const result = await Swal.fire({
        title: 'Hapus aturan?',
        text: name,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal',
    });
    if (!result.isConfirmed) return;
    const r = await fetch(`/escalations/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    });
    if (r.ok) location.reload();
}
</script>
@endsection
