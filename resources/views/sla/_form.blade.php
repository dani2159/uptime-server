@php
$fi = 'w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900';
$fl = 'block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1';
@endphp
<div class="space-y-4">
    <div>
        <label class="{{ $fl }}">Monitor <span class="text-red-500">*</span></label>
        <select name="monitor_id" required class="{{ $fi }}">
            <option value="">-- Pilih monitor --</option>
            @foreach($monitors as $m)
            <option value="{{ $m->id }}" {{ old('monitor_id', $contract->monitor_id ?? '') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $fl }}">Nama SLA <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $contract->name ?? '') }}" required
            class="{{ $fi }}" placeholder="SLA Layanan RIS Q1 2026">
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="{{ $fl }}">Target Uptime % <span class="text-red-500">*</span></label>
            <input type="number" name="target_uptime" value="{{ old('target_uptime', $contract->target_uptime ?? '99.9') }}" min="0" max="100" step="0.01" required
                class="{{ $fi }}">
        </div>
        <div>
            <label class="{{ $fl }}">Mulai <span class="text-red-500">*</span></label>
            <input type="date" name="period_start" value="{{ old('period_start', isset($contract) ? \Carbon\Carbon::parse($contract->period_start)->format('Y-m-d') : '') }}" required
                class="{{ $fi }}">
        </div>
        <div>
            <label class="{{ $fl }}">Selesai <span class="text-red-500">*</span></label>
            <input type="date" name="period_end" value="{{ old('period_end', isset($contract) ? \Carbon\Carbon::parse($contract->period_end)->format('Y-m-d') : '') }}" required
                class="{{ $fi }}">
        </div>
    </div>
    <div>
        <label class="{{ $fl }}">Budget Downtime (menit)</label>
        <input type="number" name="downtime_budget_min" value="{{ old('downtime_budget_min', $contract->downtime_budget_min ?? '') }}" min="0"
            class="{{ $fi }}" placeholder="Contoh: 43 untuk 99.9% dalam 30 hari">
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">99.9% uptime = 43 menit/bulan. 99.5% = 3.6 jam/bulan.</p>
    </div>
    <div>
        <label class="{{ $fl }}">Catatan</label>
        <textarea name="notes" rows="2" class="{{ $fi }}">{{ old('notes', $contract->notes ?? '') }}</textarea>
    </div>
</div>
