@extends('layouts.app')
@section('title', 'Tags')

@section('content')
<div x-data="tagManager()" class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">
                <i class="fa-solid fa-tags text-sky-500 mr-2"></i>Tags Monitor
            </h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kelompokkan monitor dengan label warna</p>
        </div>
        <button @click="showForm = true; editing = null; form = { name: '', color: '#0ea5e9' }"
                class="inline-flex items-center gap-1.5 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-4 py-2 rounded-xl font-semibold shadow-sm transition-all">
            <i class="fa-solid fa-plus text-xs"></i> Tambah Tag
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Form Tambah/Edit --}}
    <div x-show="showForm" x-cloak
         class="mb-5 bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-4"
            x-text="editing ? 'Edit Tag' : 'Tag Baru'"></h3>
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1 uppercase tracking-wide">Nama</label>
                <input type="text" x-model="form.name" placeholder="Contoh: SIMRS, Network, BPJS"
                       class="w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1 uppercase tracking-wide">Warna</label>
                <input type="color" x-model="form.color"
                       class="h-10 w-16 rounded-xl border border-sky-200 dark:border-slate-600 cursor-pointer p-1 bg-white dark:bg-slate-700">
            </div>
            <div class="flex gap-2">
                <button @click="save()"
                        class="px-4 py-2 bg-sky-500 hover:bg-sky-400 text-white text-sm rounded-xl font-semibold transition-colors">
                    Simpan
                </button>
                <button @click="showForm = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 text-gray-600 dark:text-slate-300 text-sm rounded-xl transition-colors">
                    Batal
                </button>
            </div>
        </div>
        <div x-show="error" class="mt-2 text-red-500 text-xs" x-text="error"></div>
    </div>

    {{-- Daftar Tag --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
        @forelse($tags as $tag)
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-sky-50 dark:border-slate-700/50 last:border-0 hover:bg-sky-50/30 dark:hover:bg-slate-700/30 transition-colors">
            <div class="flex items-center gap-3">
                <span class="w-4 h-4 rounded-full flex-shrink-0" style="background: {{ $tag->color }}"></span>
                <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $tag->name }}</span>
                <span class="text-xs text-gray-400 dark:text-slate-500">{{ $tag->monitors_count }} monitor</span>
            </div>
            <div class="flex items-center gap-3">
                <button @click="edit({{ $tag->id }}, '{{ addslashes($tag->name) }}', '{{ $tag->color }}')"
                        class="text-xs text-sky-600 dark:text-sky-400 hover:underline font-medium">
                    <i class="fa-solid fa-pen-to-square mr-1 text-[10px]"></i>Edit
                </button>
                <button @click="remove({{ $tag->id }}, '{{ addslashes($tag->name) }}')"
                        class="text-xs text-red-400 hover:text-red-600 hover:underline font-medium">
                    <i class="fa-solid fa-trash text-[10px]"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="px-5 py-14 text-center text-gray-400 dark:text-slate-500">
            <i class="fa-solid fa-tags text-3xl mb-3 block text-gray-300 dark:text-slate-600"></i>
            <p class="mb-2">Belum ada tag.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function tagManager() {
    return {
        showForm: false,
        editing: null,
        form: { name: '', color: '#0ea5e9' },
        error: '',

        edit(id, name, color) {
            this.editing = id;
            this.form = { name, color };
            this.showForm = true;
            this.error = '';
        },

        async save() {
            this.error = '';
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const url  = this.editing ? `/tags/${this.editing}` : '/tags';
            const method = this.editing ? 'PUT' : 'POST';
            try {
                const r = await fetch(url, {
                    method,
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const d = await r.json();
                if (d.ok) { location.reload(); }
                else { this.error = JSON.stringify(d.errors ?? d.message ?? 'Error'); }
            } catch(e) { this.error = e.message; }
        },

        async remove(id, name) {
            const isDark = document.documentElement.classList.contains('dark');
            const res = await Swal.fire({
                title: `Hapus tag "${name}"?`,
                text: 'Tag akan dilepas dari semua monitor.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                background: isDark ? '#1e293b' : '#fff',
                color: isDark ? '#e2e8f0' : '#111827',
            });
            if (!res.isConfirmed) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`/tags/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
            location.reload();
        },
    };
}
</script>
@endpush
