@php
$lbl = 'block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1';
$inp = 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400 text-sm';
$val = fn($k, $d = '') => old($k, $escalation->$k ?? $d);
@endphp

<div class="grid grid-cols-1 gap-5">
    <div>
        <label class="{{ $lbl }}">Nama Aturan</label>
        <input type="text" name="name" value="{{ $val('name') }}" required maxlength="100" class="{{ $inp }}">
    </div>
    <div>
        <label class="{{ $lbl }}">Channel Notifikasi</label>
        <select name="channel_id" required class="{{ $inp }}">
            <option value="">-- Pilih Channel --</option>
            @foreach($channels as $ch)
            <option value="{{ $ch->id }}" @selected((int)$val('channel_id') === $ch->id)>
                {{ $ch->name }} ({{ $ch->type }})
            </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $lbl }}">Eskalasi Setelah (menit)</label>
        <input type="number" name="delay_minutes" value="{{ $val('delay_minutes', 5) }}"
               min="1" max="1440" required class="{{ $inp }}">
        <p class="text-xs text-gray-400 mt-1">Notifikasi terkirim X menit setelah monitor DOWN dan insiden masih open</p>
    </div>
    <div>
        <label class="{{ $lbl }}">Monitor Spesifik (opsional)</label>
        <select name="monitor_id" class="{{ $inp }}">
            <option value="">-- Semua Monitor (Global) --</option>
            @foreach($monitors as $m)
            <option value="{{ $m->id }}" @selected((int)$val('monitor_id') === $m->id)>
                {{ $m->name }}
            </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">Kosong = berlaku untuk semua monitor</p>
    </div>
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="is_active"
               @checked($val('is_active', true))
               class="w-4 h-4 text-sky-500 rounded">
        <label for="is_active" class="text-sm dark:text-slate-300">Aktif</label>
    </div>
</div>
