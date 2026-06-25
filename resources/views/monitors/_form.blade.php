@php
    $val = fn($field, $default = '') => old($field, $monitor?->$field ?? $default);
    $inp = 'w-full border border-sky-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white';
    $lbl = 'block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide';
@endphp

<div>
    <label class="{{ $lbl }}">Nama Monitor</label>
    <input type="text" name="name" value="{{ $val('name') }}"
           class="{{ $inp }}" placeholder="Contoh: Website Utama" required>
    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div x-data="{ type: '{{ $val('type', 'http') }}' }">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div>
            <label class="{{ $lbl }}">Tipe Monitor</label>
            <select name="type" x-model="type" class="{{ $inp }}">
                <option value="http">HTTP/HTTPS</option>
                <option value="keyword">Keyword</option>
                <option value="ping">Ping (ICMP)</option>
                <option value="tcp">TCP Port</option>
                <option value="dns">DNS</option>
                <option value="push">Push Heartbeat</option>
            </select>
        </div>
        <div>
            <label class="{{ $lbl }}">Interval (menit)</label>
            <input type="number" name="check_interval" value="{{ $val('check_interval', 5) }}"
                   min="1" max="1440" class="{{ $inp }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Batas Lambat (ms)</label>
            <input type="number" name="response_time_warning" value="{{ $val('response_time_warning') }}"
                   min="100" max="60000" placeholder="Kosong = nonaktif" class="{{ $inp }}">
            <p class="text-xs text-gray-400 mt-1">Alert jika response &gt; nilai ini</p>
        </div>
        <div>
            <label class="{{ $lbl }}">Timeout (detik)</label>
            <input type="number" name="timeout" value="{{ $val('timeout', 10) }}"
                   min="1" max="60" class="{{ $inp }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Gagal → DOWN & Insiden</label>
            <input type="number" name="retry_count" value="{{ $val('retry_count', 1) }}"
                   min="1" max="10" class="{{ $inp }}">
            <p class="text-xs text-gray-400 mt-1">Gagal N kali berturut = DOWN + buat insiden + notif</p>
        </div>
    </div>

    {{-- URL: untuk http, keyword, ping --}}
    <div x-show="type !== 'tcp' && type !== 'push' && type !== 'dns'" class="mb-4">
        <label class="{{ $lbl }}">URL</label>
        <input type="text" name="url" value="{{ $val('url') }}"
               :disabled="type === 'tcp' || type === 'push' || type === 'dns'"
               class="{{ $inp }}" placeholder="https://example.com">
        @error('url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Keyword --}}
    <div x-show="type === 'keyword'" class="mb-4">
        <label class="{{ $lbl }}">Keyword</label>
        <input type="text" name="keyword" value="{{ $val('keyword') }}"
               class="{{ $inp }}" placeholder="Teks yang harus ada di body response">
        <p class="text-xs text-sky-500 mt-1">Monitor UP jika keyword ditemukan</p>
    </div>

    {{-- TCP --}}
    <div x-show="type === 'tcp'" class="mb-4">
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="{{ $lbl }}">Host</label>
                <input type="text" name="tcp_host" value="{{ $val('tcp_host') }}"
                       class="{{ $inp }}" placeholder="192.168.1.1 atau db.example.com">
            </div>
            <div>
                <label class="{{ $lbl }}">Port</label>
                <input type="number" name="tcp_port" value="{{ $val('tcp_port') }}"
                       min="1" max="65535" class="{{ $inp }}" placeholder="3306">
            </div>
        </div>
        <input type="hidden" name="url" value="{{ $val('url', 'tcp://placeholder') }}"
               :disabled="type !== 'tcp'">
    </div>

    {{-- DNS --}}
    <div x-show="type === 'dns'" class="mb-4">
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="{{ $lbl }}">Domain</label>
                <input type="text" name="url" value="{{ $val('url') }}"
                       :disabled="type !== 'dns'"
                       class="{{ $inp }}" placeholder="https://example.com">
            </div>
            <div>
                <label class="{{ $lbl }}">Record Type</label>
                <select name="dns_resolve_type" class="{{ $inp }}">
                    @foreach(['A','AAAA','CNAME','MX'] as $rt)
                    <option value="{{ $rt }}" {{ $val('dns_resolve_type','A') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Expected Value (opsional)</label>
                <input type="text" name="dns_expected_value" value="{{ $val('dns_expected_value') }}"
                       class="{{ $inp }}" placeholder="1.2.3.4">
            </div>
        </div>
    </div>

    {{-- Push Heartbeat --}}
    <div x-show="type === 'push'" class="mb-4">
        <div class="bg-sky-50 border border-sky-200 rounded-xl p-4">
            <label class="{{ $lbl }}">Push Token</label>
            <input type="text" name="push_token" value="{{ $val('push_token', \Illuminate\Support\Str::random(32)) }}"
                   class="{{ $inp }} font-mono" placeholder="Token otomatis">
            <p class="text-xs text-sky-600 mt-2">
                Kirim GET ke:
                <code class="bg-sky-100 px-1.5 py-0.5 rounded text-sky-700">{{ url('/push/') }}/[token]</code>
                dari cron eksternal
            </p>
        </div>
        <input type="hidden" name="url" value="{{ $val('url', 'push://heartbeat') }}"
               :disabled="type !== 'push'">
    </div>
</div>

{{-- Tags --}}
@if(isset($tags) && $tags->isNotEmpty())
<div>
    <label class="{{ $lbl }}">Tags</label>
    @php $selectedTags = old('tags', $monitor?->tags->pluck('id')->toArray() ?? []); @endphp
    <div class="flex flex-wrap gap-2 mt-1">
        @foreach($tags as $tag)
        <label class="flex items-center gap-1.5 text-sm cursor-pointer px-3 py-1.5 rounded-full border-2 transition-all"
               :class="" style="border-color: {{ $tag->color }}20">
            <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                   {{ in_array($tag->id, $selectedTags) ? 'checked' : '' }}
                   class="rounded" style="accent-color: {{ $tag->color }}">
            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background: {{ $tag->color }}"></span>
            <span class="font-medium text-gray-700 dark:text-slate-200 text-xs">{{ $tag->name }}</span>
        </label>
        @endforeach
    </div>
</div>
@endif

{{-- Notification channels --}}
@if($channels->isNotEmpty())
<div>
    <label class="{{ $lbl }}">Channel Notifikasi</label>
    <div class="space-y-2 mt-1">
        @foreach($channels as $channel)
        <label class="flex items-center gap-2.5 text-sm cursor-pointer hover:bg-sky-50 rounded-lg px-2 py-1.5 transition-colors">
            <input type="checkbox" name="notification_channels[]" value="{{ $channel->id }}"
                   {{ in_array($channel->id, $val('notification_channels', []) ?: []) ? 'checked' : '' }}
                   class="rounded border-sky-300 text-sky-500">
            <span class="font-medium text-gray-700">{{ $channel->name }}</span>
            <span class="text-xs text-gray-400 bg-sky-50 border border-sky-100 px-2 py-0.5 rounded-full">{{ $channel->type }}</span>
        </label>
        @endforeach
    </div>
</div>
@endif
