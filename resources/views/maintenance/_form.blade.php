@php
    $maintenance = $maintenance ?? null;
    $val = fn($f, $d = '') => old($f, $maintenance?->$f ?? $d);
    $inp = 'w-full border border-sky-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white';
    $lbl = 'block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide';
@endphp

<div>
    <label class="{{ $lbl }}">Judul</label>
    <input type="text" name="title" value="{{ $val('title') }}"
           class="{{ $inp }}" placeholder="Contoh: Maintenance server bulanan" required>
    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
    <label class="{{ $lbl }}">Deskripsi (opsional)</label>
    <textarea name="description" rows="2" class="{{ $inp }}"
              placeholder="Keterangan tambahan...">{{ $val('description') }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Mulai</label>
        <input type="datetime-local" name="start_at"
               value="{{ old('start_at', $maintenance?->start_at?->format('Y-m-d\TH:i') ?? '') }}"
               class="{{ $inp }}" required>
        @error('start_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="{{ $lbl }}">Selesai</label>
        <input type="datetime-local" name="end_at"
               value="{{ old('end_at', $maintenance?->end_at?->format('Y-m-d\TH:i') ?? '') }}"
               class="{{ $inp }}" required>
        @error('end_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="{{ $lbl }}">Monitor yang Terpengaruh</label>
    <p class="text-xs text-sky-500 mb-2">Kosongkan = berlaku untuk semua monitor</p>
    <div class="space-y-1.5 max-h-48 overflow-y-auto border border-sky-100 rounded-xl p-3 bg-sky-50/30">
        @foreach($monitors as $m)
        <label class="flex items-center gap-2.5 text-sm cursor-pointer hover:bg-white rounded-lg px-2 py-1 transition-colors">
            <input type="checkbox" name="monitor_ids[]" value="{{ $m->id }}"
                   {{ in_array($m->id, old('monitor_ids', $maintenance?->monitor_ids ?? [])) ? 'checked' : '' }}
                   class="rounded border-sky-300 text-sky-500">
            <span class="text-gray-700">{{ $m->name }}</span>
        </label>
        @endforeach
    </div>
</div>
