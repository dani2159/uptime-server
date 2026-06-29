@php
    $val = fn($field) => old($field, $pagespeed->{$field} ?? '');
    $lbl = 'block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5';
    $inp = 'w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 transition';
    $sel = $inp;
@endphp

<div class="grid md:grid-cols-2 gap-5">

    {{-- Nama --}}
    <div class="md:col-span-2">
        <label class="{{ $lbl }}">Nama Monitor</label>
        <input type="text" name="name" value="{{ $val('name') }}" class="{{ $inp }}" placeholder="Contoh: Website Utama" required>
    </div>

    {{-- URL --}}
    <div class="md:col-span-2">
        <label class="{{ $lbl }}">URL</label>
        <input type="url" name="url" value="{{ $val('url') }}" class="{{ $inp }}" placeholder="https://example.com" required>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">URL lengkap termasuk https://</p>
    </div>

    {{-- Strategy --}}
    <div>
        <label class="{{ $lbl }}">Strategi</label>
        <select name="strategy" class="{{ $sel }}">
            <option value="mobile"   {{ $val('strategy') === 'mobile'  ? 'selected' : '' }}>Mobile</option>
            <option value="desktop"  {{ $val('strategy') === 'desktop' ? 'selected' : '' }}>Desktop</option>
        </select>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Mobile biasanya memberi skor lebih rendah</p>
    </div>

    {{-- Interval --}}
    <div>
        <label class="{{ $lbl }}">Interval Cek (menit)</label>
        <select name="interval_minutes" class="{{ $sel }}">
            @foreach ([30 => '30 menit', 60 => '1 jam', 120 => '2 jam', 360 => '6 jam', 720 => '12 jam', 1440 => '24 jam'] as $minutes => $label)
                <option value="{{ $minutes }}" {{ (int)$val('interval_minutes') === $minutes ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- API Key --}}
    <div class="md:col-span-2">
        <label class="{{ $lbl }}">Google API Key <span class="font-normal text-gray-400">(opsional)</span></label>
        <input type="text" name="api_key" value="{{ $val('api_key') }}" class="{{ $inp }}" placeholder="AIza...">
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Tanpa API key: rate limit 25 request/hari. Dengan key: 25.000/hari. Buat di <a href="https://console.cloud.google.com/" target="_blank" class="text-sky-500 underline">Google Cloud Console</a> → enable "PageSpeed Insights API".</p>
    </div>

    {{-- Is Active --}}
    <div class="md:col-span-2 flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1"
            class="w-4 h-4 rounded accent-sky-500"
            {{ $val('is_active') !== '0' && $val('is_active') !== '' ? 'checked' : ($pagespeed === null ? 'checked' : '') }}>
        <label for="is_active" class="text-sm text-gray-700 dark:text-slate-300 cursor-pointer">Monitor aktif</label>
    </div>
</div>
