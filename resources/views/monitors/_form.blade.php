@php
    $val = fn($field, $default = '') => old($field, $monitor?->$field ?? $default);
    $inp = 'w-full border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-sky-900 focus:border-sky-400 bg-white dark:bg-slate-900';
    $lbl = 'block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1 uppercase tracking-wide';
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
                <optgroup label="Web">
                    <option value="http">HTTP/HTTPS</option>
                    <option value="keyword">Keyword</option>
                </optgroup>
                <optgroup label="Infrastruktur">
                    <option value="ping">Ping (ICMP)</option>
                    <option value="tcp">TCP Port</option>
                    <option value="dns">DNS</option>
                    <option value="docker">Docker Container</option>
                </optgroup>
                <optgroup label="Database">
                    <option value="database">Database (MySQL/PgSQL/Redis)</option>
                </optgroup>
                <optgroup label="Domain">
                    <option value="whois">WHOIS / Domain Expiry</option>
                </optgroup>
                <optgroup label="Heartbeat">
                    <option value="push">Push Heartbeat</option>
                    <option value="cron">Cron Job Monitor</option>
                </optgroup>
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

    {{-- URL dinamis: http, keyword, ping, database, docker, whois --}}
    <div x-show="!['tcp','push','dns','cron'].includes(type)" class="mb-4">
        <label class="{{ $lbl }}">
            <span x-text="{
                http:'URL',keyword:'URL',ping:'Hostname / IP',
                database:'Connection String',docker:'Container / Socket',whois:'Domain'
            }[type] ?? 'URL'"></span>
        </label>
        <input type="text" name="url" value="{{ $val('url') }}" class="{{ $inp }}"
               :placeholder="{
                   http:'https://example.com',
                   keyword:'https://example.com',
                   ping:'8.8.8.8 atau google.com',
                   database:'mysql://user:pass@host:3306/dbname',
                   docker:'container_name atau unix:///var/run/docker.sock',
                   whois:'example.com'
               }[type] ?? 'https://example.com'">
        <p x-show="type === 'ping'" class="text-xs text-gray-400 dark:text-slate-500 mt-1">IP address atau hostname tanpa http://</p>
        <p x-show="type === 'database'" class="text-xs text-gray-400 dark:text-slate-500 mt-1">Format: driver://user:pass@host:port/database · Driver: mysql, pgsql, redis</p>
        <p x-show="type === 'docker'" class="text-xs text-gray-400 dark:text-slate-500 mt-1">Nama container atau path socket Docker. Remote: http://host:2375</p>
        <p x-show="type === 'whois'" class="text-xs text-gray-400 dark:text-slate-500 mt-1">Domain tanpa http://. Alert jika expiry &lt; threshold hari.</p>
        @error('url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Keyword --}}
    <div x-show="type === 'keyword'" class="mb-4">
        <label class="{{ $lbl }}">Keyword</label>
        <input type="text" name="keyword" value="{{ $val('keyword') }}"
               class="{{ $inp }}" placeholder="Teks yang harus ada di body response">
        <p class="text-xs text-sky-500 dark:text-sky-400 mt-1">Monitor UP jika keyword ditemukan</p>
    </div>

    {{-- WHOIS expiry threshold --}}
    <div x-show="type === 'whois'" class="mb-4">
        <label class="{{ $lbl }}">Alert X hari sebelum expired</label>
        <input type="number" name="domain_expiry_alert_days" value="{{ $val('domain_expiry_alert_days', 30) }}"
               min="1" max="365" class="{{ $inp }}" placeholder="30">
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
                       class="{{ $inp }}" placeholder="example.com">
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
        <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl p-4">
            <label class="{{ $lbl }}">Push Token</label>
            <input type="text" name="push_token" value="{{ $val('push_token', \Illuminate\Support\Str::random(32)) }}"
                   class="{{ $inp }} font-mono" placeholder="Token otomatis">
            <p class="text-xs text-sky-600 dark:text-sky-400 mt-2">
                Kirim GET ke:
                <code class="bg-sky-100 dark:bg-sky-900/40 px-1.5 py-0.5 rounded text-sky-700 dark:text-sky-300">{{ url('/push/') }}/[token]</code>
            </p>
        </div>
        <input type="hidden" name="url" value="{{ $val('url', 'push://heartbeat') }}"
               :disabled="type !== 'push'">
    </div>

    {{-- Cron Job Monitor --}}
    <div x-show="type === 'cron'" class="mb-4">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 space-y-3">
            <div>
                <label class="{{ $lbl }}">Push Token (Heartbeat URL)</label>
                <input type="text" name="push_token" value="{{ $val('push_token', \Illuminate\Support\Str::random(32)) }}"
                       class="{{ $inp }} font-mono" placeholder="Token otomatis">
                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                    Panggil URL ini di akhir cron job:
                    <code class="bg-indigo-100 dark:bg-indigo-900/40 px-1.5 py-0.5 rounded">{{ url('/push/') }}/[token]</code>
                </p>
            </div>
            <div>
                <label class="{{ $lbl }}">Heartbeat Interval (menit)</label>
                <input type="number" name="heartbeat_interval" value="{{ $val('heartbeat_interval', 60) }}"
                       min="1" class="{{ $inp }}" placeholder="60">
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">DOWN jika tidak ada ping selama N menit</p>
            </div>
        </div>
        <input type="hidden" name="url" value="{{ $val('url', 'cron://heartbeat') }}"
               :disabled="type !== 'cron'">
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
        <label class="flex items-center gap-2.5 text-sm cursor-pointer hover:bg-sky-50 dark:hover:bg-slate-700 rounded-lg px-2 py-1.5 transition-colors">
            <input type="checkbox" name="notification_channels[]" value="{{ $channel->id }}"
                   {{ in_array($channel->id, $val('notification_channels', []) ?: []) ? 'checked' : '' }}
                   class="rounded border-sky-300 text-sky-500">
            <span class="font-medium text-gray-700 dark:text-slate-200">{{ $channel->name }}</span>
            <span class="text-xs text-gray-400 bg-sky-50 dark:bg-slate-700 border border-sky-100 dark:border-slate-600 px-2 py-0.5 rounded-full">{{ $channel->type }}</span>
        </label>
        @endforeach
    </div>
</div>
@endif

{{-- ====== ADVANCED v2 Fields ====== --}}
<div x-data="{ advOpen: false }">
    <button type="button" @click="advOpen = !advOpen"
        class="flex items-center gap-2 text-sm text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 mt-2">
        <i class="fa fa-chevron-right transition-transform" :class="advOpen ? 'rotate-90' : ''"></i>
        <span x-text="advOpen ? 'Sembunyikan pengaturan lanjutan' : 'Tampilkan pengaturan lanjutan (HTTP Auth, Body Assertion, Flap, dll)'"></span>
    </button>

    <div x-show="advOpen" x-transition class="mt-4 space-y-4">

        {{-- Notes & Runbook --}}
        <div class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 space-y-3">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">Catatan & Runbook</h4>
            <div>
                <label class="{{ $lbl }}">Notes</label>
                <textarea name="notes" rows="2" class="{{ $inp }} font-mono text-xs"
                    placeholder="Catatan internal tentang monitor ini...">{{ $val('notes') }}</textarea>
            </div>
            <div>
                <label class="{{ $lbl }}">Runbook URL</label>
                <input type="url" name="runbook_url" value="{{ $val('runbook_url') }}" class="{{ $inp }}"
                    placeholder="https://wiki.company.com/runbook/service-x">
            </div>
            <div>
                <label class="{{ $lbl }}">Environment</label>
                <select name="environment" class="{{ $inp }}">
                    @foreach(['production','staging','development','testing'] as $env)
                    <option value="{{ $env }}" {{ $val('environment','production') === $env ? 'selected' : '' }}>{{ ucfirst($env) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- HTTP Advanced --}}
        <div x-show="type === 'http' || type === 'keyword'" class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 space-y-3">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">HTTP Lanjutan</h4>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $lbl }}">HTTP Method</label>
                    <select name="http_method" class="{{ $inp }}">
                        @foreach(['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS'] as $m)
                        <option value="{{ $m }}" {{ $val('http_method','GET') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Accepted Status Codes</label>
                    <input type="text" name="accepted_status_codes" value="{{ $val('accepted_status_codes','200') }}" class="{{ $inp }}"
                        placeholder="200,201,301">
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">Request Body (JSON / Form)</label>
                <textarea name="request_body" rows="2" class="{{ $inp }} font-mono text-xs"
                    placeholder='{"key": "value"}'>{{ $val('request_body') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $lbl }}">Auth Type</label>
                    <select name="auth_type" class="{{ $inp }}">
                        <option value="">Tidak ada</option>
                        <option value="basic" {{ $val('auth_type') === 'basic' ? 'selected' : '' }}>Basic Auth</option>
                        <option value="bearer" {{ $val('auth_type') === 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Username / Token</label>
                    <input type="text" name="auth_username" value="{{ $val('auth_username') }}" class="{{ $inp }}"
                        placeholder="username atau bearer token">
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">Password (Basic Auth)</label>
                <input type="password" name="auth_password" value="{{ $val('auth_password') }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Custom Headers (JSON)</label>
                <textarea name="custom_headers" rows="2" class="{{ $inp }} font-mono text-xs"
                    placeholder='{"X-Api-Key": "abc123", "Accept": "application/json"}'>{{ $val('custom_headers') }}</textarea>
            </div>
            <div>
                <label class="{{ $lbl }}">Custom User-Agent</label>
                <input type="text" name="custom_user_agent" value="{{ $val('custom_user_agent') }}" class="{{ $inp }}"
                    placeholder="WatchTower/2.0 (+https://watchtower.app)">
            </div>
            <div>
                <label class="{{ $lbl }}">HTTP Proxy URL</label>
                <input type="text" name="proxy_url" value="{{ $val('proxy_url') }}" class="{{ $inp }}"
                    placeholder="http://proxy:3128 atau socks5://proxy:1080">
            </div>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="ignore_tls_error" value="1" {{ $val('ignore_tls_error') ? 'checked' : '' }} class="rounded">
                    <span class="text-gray-700 dark:text-slate-300">Abaikan error TLS/SSL</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="follow_redirects" value="1" {{ $val('follow_redirects',true) ? 'checked' : '' }} class="rounded">
                    <span class="text-gray-700 dark:text-slate-300">Ikuti redirect</span>
                </label>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="{{ $lbl }}">Max Redirect</label>
                    <input type="number" name="max_redirects" value="{{ $val('max_redirects',5) }}" min="0" max="20" class="{{ $inp }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Min Response Size (byte)</label>
                    <input type="number" name="min_response_size" value="{{ $val('min_response_size') }}" min="0" class="{{ $inp }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Max Response Size (byte)</label>
                    <input type="number" name="max_response_size" value="{{ $val('max_response_size') }}" min="0" class="{{ $inp }}">
                </div>
            </div>
        </div>

        {{-- Body Assertion --}}
        <div x-show="type === 'http' || type === 'keyword'" class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 space-y-3">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">Body Assertion (JSON Path)</h4>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="{{ $lbl }}">JSON Path</label>
                    <input type="text" name="body_assertion_path" value="{{ $val('body_assertion_path') }}" class="{{ $inp }}"
                        placeholder="$.status">
                </div>
                <div>
                    <label class="{{ $lbl }}">Operator</label>
                    <select name="body_assertion_op" class="{{ $inp }}">
                        @foreach(['equals','contains','not_contains'] as $op)
                        <option value="{{ $op }}" {{ $val('body_assertion_op') === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Nilai</label>
                    <input type="text" name="body_assertion_value" value="{{ $val('body_assertion_value') }}" class="{{ $inp }}"
                        placeholder="ok">
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">Suppress Pattern (Regex — abaikan DOWN jika cocok)</label>
                <input type="text" name="suppress_pattern" value="{{ $val('suppress_pattern') }}" class="{{ $inp }}"
                    placeholder="maintenance|scheduled">
            </div>
        </div>

        {{-- Cron/Heartbeat --}}
        <div x-show="type === 'cron' || type === 'push'" class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 space-y-3">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">Heartbeat / Cron</h4>
            <div>
                <label class="{{ $lbl }}">Expected Interval (menit)</label>
                <input type="number" name="heartbeat_interval" value="{{ $val('heartbeat_interval',60) }}" min="1" class="{{ $inp }}">
                <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">Monitor DOWN jika tidak menerima ping dalam N menit ini</p>
            </div>
        </div>

        {{-- Flap Detection --}}
        <div class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 space-y-3">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">Flap Detection</h4>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="flap_detection" value="1" {{ $val('flap_detection') ? 'checked' : '' }} class="rounded">
                <span class="text-gray-700 dark:text-slate-300 text-sm">Aktifkan flap detection (tahan notif jika UP-DOWN bergantian)</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $lbl }}">Window (menit)</label>
                    <input type="number" name="flap_window_minutes" value="{{ $val('flap_window_minutes',5) }}" min="1" max="60" class="{{ $inp }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Threshold (count)</label>
                    <input type="number" name="flap_count_threshold" value="{{ $val('flap_count_threshold',3) }}" min="2" max="20" class="{{ $inp }}">
                </div>
            </div>
        </div>

        {{-- Latency Trend --}}
        <div class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="latency_trend_alert" value="1" {{ $val('latency_trend_alert') ? 'checked' : '' }} class="rounded">
                <div>
                    <span class="text-gray-700 dark:text-slate-300 text-sm font-medium">Latency Trend Alert</span>
                    <p class="text-xs text-gray-500 dark:text-slate-500">Kirim peringatan jika response time naik konsisten 5 cek berturut-turut</p>
                </div>
            </label>
        </div>

    </div>
</div>
