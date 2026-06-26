<div class="space-y-4">
    <div>
        <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Monitor <span class="text-red-400">*</span></label>
        <select name="monitor_id" required class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
            <option value="">-- Pilih monitor --</option>
            @foreach($monitors as $m)
            <option value="{{ $m->id }}" {{ old('monitor_id', $contract->monitor_id ?? '') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Nama SLA <span class="text-red-400">*</span></label>
        <input type="text" name="name" value="{{ old('name', $contract->name ?? '') }}" required
            class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
            placeholder="SLA Layanan RIS Q1 2026">
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Target Uptime % <span class="text-red-400">*</span></label>
            <input type="number" name="target_uptime" value="{{ old('target_uptime', $contract->target_uptime ?? '99.9') }}" min="0" max="100" step="0.01" required
                class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
        </div>
        <div>
            <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Mulai <span class="text-red-400">*</span></label>
            <input type="date" name="period_start" value="{{ old('period_start', isset($contract) ? \Carbon\Carbon::parse($contract->period_start)->format('Y-m-d') : '') }}" required
                class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
        </div>
        <div>
            <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Selesai <span class="text-red-400">*</span></label>
            <input type="date" name="period_end" value="{{ old('period_end', isset($contract) ? \Carbon\Carbon::parse($contract->period_end)->format('Y-m-d') : '') }}" required
                class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
        </div>
    </div>
    <div>
        <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Budget Downtime (menit)</label>
        <input type="number" name="downtime_budget_min" value="{{ old('downtime_budget_min', $contract->downtime_budget_min ?? '') }}" min="0"
            class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
            placeholder="Contoh: 43 untuk 99.9% dalam 30 hari">
    </div>
    <div>
        <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Catatan</label>
        <textarea name="notes" rows="2"
            class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">{{ old('notes', $contract->notes ?? '') }}</textarea>
    </div>
</div>
