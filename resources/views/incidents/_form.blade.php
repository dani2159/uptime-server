@php
    $incident = $incident ?? null;
    $val = fn($f, $d = '') => old($f, $incident?->$f ?? $d);
    $inp = 'w-full border border-sky-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400';
    $lbl = 'block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide';
    $initCategory = old('category', $incident?->category ?? 'monitor_downtime');
@endphp

<div x-data="{ category: '{{ $initCategory }}' }">

{{-- Kategori --}}
<div class="mb-4">
    <label class="{{ $lbl }}">Kategori Insiden</label>
    <select name="category" x-model="category" class="{{ $inp }}">
        <option value="monitor_downtime" {{ $initCategory === 'monitor_downtime' ? 'selected' : '' }}>Insiden Monitor (Down/Up)</option>
        <option value="general"          {{ $initCategory === 'general'          ? 'selected' : '' }}>Insiden Umum IT</option>
        <option value="client_report"    {{ $initCategory === 'client_report'    ? 'selected' : '' }}>Laporan Error dari Client</option>
    </select>
    @error('category')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

{{-- Pilih monitor — hanya tampil jika kategori monitor_downtime --}}
<div class="mb-4" x-show="category === 'monitor_downtime'" x-cloak>
    <label class="{{ $lbl }}">Monitor</label>
    <select name="monitor_id" class="{{ $inp }}" :required="category === 'monitor_downtime'">
        <option value="">Pilih monitor...</option>
        @foreach($monitors as $m)
        <option value="{{ $m->id }}" {{ (string) $val('monitor_id') === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
    </select>
    @error('monitor_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

{{-- Judul — selalu tampil, wajib untuk non-monitor --}}
<div class="mb-4">
    <label class="{{ $lbl }}">
        Judul Insiden
        <span x-show="category === 'monitor_downtime'" class="font-normal text-gray-400">(opsional, default ke nama monitor)</span>
    </label>
    <input type="text" name="title" value="{{ $val('title') }}"
           class="{{ $inp }}" placeholder="Ringkasan insiden..."
           :required="category !== 'monitor_downtime'">
    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

{{-- Severity --}}
<div class="mb-4">
    <label class="{{ $lbl }}">Tingkat Keparahan</label>
    <select name="severity" class="{{ $inp }}">
        <option value="low"      {{ $val('severity','medium') === 'low'      ? 'selected' : '' }}>Low — Rendah</option>
        <option value="medium"   {{ $val('severity','medium') === 'medium'   ? 'selected' : '' }}>Medium — Sedang</option>
        <option value="high"     {{ $val('severity','medium') === 'high'     ? 'selected' : '' }}>High — Tinggi</option>
        <option value="critical" {{ $val('severity','medium') === 'critical' ? 'selected' : '' }}>Critical — Kritis</option>
    </select>
    @error('severity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

{{-- Waktu --}}
<div class="grid grid-cols-2 gap-4 mb-4">
    <div>
        <label class="{{ $lbl }}">Mulai</label>
        <input type="datetime-local" name="started_at"
               value="{{ $incident?->started_at?->format('Y-m-d\TH:i') ?? old('started_at') }}"
               class="{{ $inp }}" required>
        @error('started_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="{{ $lbl }}">Selesai (kosongkan jika masih berlangsung)</label>
        <input type="datetime-local" name="resolved_at"
               value="{{ $incident?->resolved_at?->format('Y-m-d\TH:i') ?? old('resolved_at') }}"
               class="{{ $inp }}">
        @error('resolved_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

{{-- Catatan --}}
<div class="mb-4">
    <label class="{{ $lbl }}">Catatan / Root Cause (opsional)</label>
    <textarea name="note" rows="3" class="{{ $inp }}"
              placeholder="Penyebab gangguan, tindakan yang diambil, dll.">{{ $val('note') }}</textarea>
    @error('note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

{{-- Informasi pelapor — hanya tampil untuk laporan client --}}
<div class="grid grid-cols-2 gap-4 mb-4" x-show="category === 'client_report'" x-cloak>
    <div>
        <label class="{{ $lbl }}">Nama Pelapor</label>
        <input type="text" name="reporter_name" value="{{ $val('reporter_name') }}"
               class="{{ $inp }}" placeholder="Nama client / user pelapor">
        @error('reporter_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="{{ $lbl }}">Kontak Pelapor (No. HP / Email)</label>
        <input type="text" name="reporter_contact" value="{{ $val('reporter_contact') }}"
               class="{{ $inp }}" placeholder="Nomor HP atau email">
        @error('reporter_contact')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

</div>{{-- end x-data --}}
