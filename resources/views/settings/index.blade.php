@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center">
            <i class="fa-solid fa-sliders text-sky-500 text-sm"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">Pengaturan</h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Konfigurasi interval pengecekan dan perilaku sistem</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-check text-green-500"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Server IP / ISP Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden mb-4"
         x-data="{
             loading: false,
             info: @js($serverIpInfo ?? []),
             refresh() {
                 this.loading = true;
                 fetch('{{ route('settings.ip-info') }}', {
                     method: 'POST',
                     headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                 }).then(r => r.json()).then(d => { this.info = d; }).finally(() => { this.loading = false; });
             }
         }">
        <div class="px-5 py-3.5 border-b border-sky-50 dark:border-slate-700 bg-sky-50/40 dark:bg-slate-700/30 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-sky-500 text-sm"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">Koneksi Server</span>
            </div>
            <button type="button" @click="refresh()"
                    :disabled="loading"
                    class="text-xs text-sky-600 dark:text-sky-400 hover:underline flex items-center gap-1 disabled:opacity-50">
                <i class="fa-solid fa-rotate-right text-[10px]" :class="{ 'animate-spin': loading }"></i>
                Refresh
            </button>
        </div>
        <div class="p-5">
            <template x-if="info.error && !info.ip">
                <p class="text-sm text-gray-400 dark:text-slate-500 italic">Gagal mengambil info IP dari server.</p>
            </template>
            <template x-if="info.ip">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mb-0.5">Public IP</p>
                        <p class="font-mono font-bold text-sky-600 dark:text-sky-400 text-lg" x-text="info.ip"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mb-0.5">ISP / Provider</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-slate-100" x-text="info.isp ?? '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mb-0.5">Lokasi</p>
                        <p class="text-sm text-gray-600 dark:text-slate-300" x-text="(info.city ?? '') + (info.country ? ', ' + info.country : '')"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mb-0.5">Org / ASN</p>
                        <p class="text-sm text-gray-600 dark:text-slate-300" x-text="info.org ?? '-'"></p>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        {{-- BPJS Section --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-3.5 border-b border-sky-50 dark:border-slate-700 bg-sky-50/40 dark:bg-slate-700/30 flex items-center gap-2">
                <i class="fa-solid fa-hospital text-sky-500 text-sm"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">BPJS API Health Check</span>
            </div>

            <div class="p-5 space-y-5">

                {{-- Auto check toggle --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-slate-100">Auto Check Aktif</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                            Pengecekan otomatis berjalan sesuai interval. Nonaktifkan jika ingin check manual saja.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                        <input type="checkbox" name="bpjs_auto_check" value="1"
                               {{ $settings['bpjs_auto_check'] ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 dark:bg-slate-600 peer-focus:outline-none rounded-full peer
                                    peer-checked:after:translate-x-5 peer-checked:bg-sky-500
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                    </label>
                </div>

                <hr class="border-sky-50 dark:border-slate-700">

                {{-- Interval --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-slate-100 mb-1">
                        Interval Pengecekan
                    </label>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mb-3">
                        Seberapa sering sistem otomatis mengecek konektivitas endpoint BPJS.
                        Nilai lebih besar mengurangi risiko IP diblokir oleh server BPJS.
                    </p>

                    {{-- Preset buttons --}}
                    <div class="flex flex-wrap gap-2 mb-3" x-data="{ interval: {{ $settings['bpjs_check_interval'] }} }">
                        @foreach([10 => '10 menit', 15 => '15 menit', 30 => '30 menit', 60 => '1 jam', 360 => '6 jam (4x/hari)'] as $val => $label)
                        <button type="button"
                                onclick="document.getElementById('bpjs_interval_input').value = {{ $val }}; this.closest('[x-data]').__x.$data.interval = {{ $val }}"
                                class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                                       {{ $settings['bpjs_check_interval'] == $val
                                           ? 'bg-sky-500 text-white border-sky-500'
                                           : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-sky-200 dark:border-slate-600 hover:border-sky-400' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="number" name="bpjs_check_interval" id="bpjs_interval_input"
                               value="{{ $settings['bpjs_check_interval'] }}"
                               min="5" max="1440"
                               class="w-28 border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400
                                      bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 text-center font-mono">
                        <span class="text-sm text-gray-500 dark:text-slate-400">menit <span class="text-xs">(min 5, max 1440)</span></span>
                    </div>
                    @error('bpjs_check_interval')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Info box --}}
                <div class="rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/40 px-4 py-3 flex gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 text-sm mt-0.5 flex-shrink-0"></i>
                    <div class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                        <strong>Tips agar tidak diblokir BPJS:</strong><br>
                        Gunakan interval minimal <strong>15–30 menit</strong>. BPJS membatasi jumlah request
                        per IP — pengecekan terlalu sering dapat menyebabkan IP Anda diblokir sementara.
                        Gunakan tombol <em>"Start Requests"</em> di dashboard untuk cek manual saat dibutuhkan.
                    </div>
                </div>

            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-sky-500 to-blue-500
                           hover:from-sky-400 hover:to-blue-400 text-white text-sm px-6 py-2.5
                           rounded-xl font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                Simpan Pengaturan
            </button>
        </div>

    </form>
</div>
@endsection
