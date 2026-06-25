@php
    $channel = $channel ?? null;
    $val     = fn($f, $d = '') => old($f, $channel?->$f ?? $d);
    $inp     = 'w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500';
    $lbl     = 'block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1 uppercase tracking-wide';
@endphp

<div x-data="{ type: '{{ $val('type', 'telegram') }}' }" class="space-y-5">

    {{-- Nama --}}
    <div>
        <label class="{{ $lbl }}">Nama Channel</label>
        <input type="text" name="name" value="{{ $val('name') }}"
               class="{{ $inp }}" placeholder="Contoh: Webhook Discord IT" required>
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Tipe --}}
    <div>
        <label class="{{ $lbl }}">Tipe</label>
        <select name="type" x-model="type" class="{{ $inp }}">
            <option value="telegram">Telegram</option>
            <option value="whatsapp">WhatsApp (Fonnte)</option>
            <option value="webhook">Webhook (HTTP POST)</option>
        </select>
    </div>

    {{-- Token / Secret Key --}}
    <div>
        <label class="{{ $lbl }}" x-text="type === 'webhook' ? 'Secret Key (opsional)' : 'Token / Bot Token'"></label>
        <input type="text" name="token" value="{{ old('token') }}"
               class="{{ $inp }}"
               :placeholder="type === 'telegram'
                   ? 'Bot Token dari BotFather (123456:ABC-DEF...)'
                   : type === 'whatsapp'
                   ? 'Token dari Fonnte'
                   : 'Kosongkan jika tidak perlu verifikasi signature'"
               :required="type !== 'webhook' && {{ $channel ? 'false' : 'true' }}">
        <p class="text-xs text-sky-500 mt-1" x-show="type === 'webhook'">
            Jika diisi, setiap request akan menyertakan header
            <code class="bg-sky-50 dark:bg-slate-700 px-1 rounded text-[11px]">X-WatchTower-Signature: sha256=&lt;hmac&gt;</code>
        </p>
        <p class="text-xs text-gray-400 mt-1" x-show="type !== 'webhook' && {{ $channel ? 'true' : 'false' }}">
            Kosongkan jika tidak berubah
        </p>
        @error('token')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Target / URL --}}
    <div>
        <label class="{{ $lbl }}" x-text="type === 'webhook' ? 'Webhook URL' : type === 'telegram' ? 'Chat ID' : 'Nomor HP'"></label>
        <input type="text" name="target" value="{{ $val('target') }}"
               class="{{ $inp }}"
               :placeholder="type === 'telegram'
                   ? 'Chat ID (contoh: -1001234567890)'
                   : type === 'whatsapp'
                   ? 'Nomor tujuan: 628xxxxxxxxxx'
                   : 'https://hooks.example.com/watchtower'"
               :type="type === 'webhook' ? 'url' : 'text'"
               required>
        <p class="text-xs text-sky-500 mt-1" x-show="type === 'telegram'">
            Grup biasa: <code>-1001234567890</code><br>
            Supergroup topic: <code>-1001234567890:456</code> (chat_id:thread_id)
        </p>
        <p class="text-xs text-sky-500 mt-1" x-show="type === 'whatsapp'">Format tanpa <code>+</code>, gunakan kode negara (contoh: 6281234567890)</p>
        @error('target')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Payload preview untuk webhook --}}
    <div x-show="type === 'webhook'" x-cloak
         class="rounded-xl border border-sky-100 dark:border-slate-600 bg-sky-50/40 dark:bg-slate-700/30 p-4">
        <p class="text-xs font-semibold text-gray-600 dark:text-slate-400 mb-2">
            <i class="fa-solid fa-code mr-1 text-sky-400"></i>Contoh payload yang dikirim:
        </p>
        <pre class="text-[11px] text-gray-600 dark:text-slate-300 leading-relaxed overflow-x-auto"><code>{
  "event": "monitor.down",
  "monitor": {
    "id": 1,
    "name": "SIMRS",
    "url": "https://simrs.mitraplumbon.com",
    "type": "http",
    "status": "down",
    "last_checked_at": "2026-06-12T10:30:00+07:00"
  },
  "timestamp": "2026-06-12T10:30:00+07:00",
  "message": "🔴 SIMRS is DOWN\nURL: https://simrs.mitraplumbon.com\nWaktu: 12-06-2026 10:30:00"
}</code></pre>
    </div>

    {{-- Status aktif (hanya saat edit) --}}
    @if($channel)
    <div>
        <label class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" name="is_active" value="1"
                   {{ $val('is_active', '1') ? 'checked' : '' }}
                   class="rounded border-sky-300 text-sky-500 focus:ring-sky-300">
            <span class="text-sm text-gray-700 dark:text-slate-300 font-medium">Channel aktif</span>
        </label>
    </div>
    @endif

</div>
