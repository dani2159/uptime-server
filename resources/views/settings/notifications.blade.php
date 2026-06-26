@extends('layouts.app')
@section('title', 'Template Notifikasi')

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center">
            <i class="fa-solid fa-bell text-sky-500 text-sm"></i>
        </div>
        <div>
            <h1 class="text-lg font-bold text-gray-800 dark:text-slate-100">Template Notifikasi</h1>
            <p class="text-xs text-gray-400 dark:text-slate-500">Kustomisasi pesan notifikasi downtime & recovery</p>
        </div>
        <a href="{{ route('settings.index') }}" class="ml-auto text-xs text-sky-500 hover:underline">
            <i class="fa-solid fa-arrow-left mr-1"></i>Kembali
        </a>
    </div>

    @if(session('success'))
    <div class="mb-5 flex items-center gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Variabel tersedia --}}
    <div class="mb-5 bg-sky-50 dark:bg-slate-700/40 border border-sky-100 dark:border-slate-600 rounded-xl p-4">
        <p class="text-xs font-semibold text-gray-600 dark:text-slate-400 mb-2 uppercase tracking-wide">
            <i class="fa-solid fa-code mr-1 text-sky-400"></i>Variabel yang tersedia
        </p>
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach(['{name}' => 'Nama monitor', '{url}' => 'URL monitor', '{status}' => 'UP / DOWN', '{response_time}' => 'Waktu respons', '{timestamp}' => 'Waktu cek', '{duration}' => 'Durasi down (hanya recovery)'] as $var => $desc)
            <span class="inline-flex items-center gap-1.5 bg-white dark:bg-slate-700 border border-sky-200 dark:border-slate-600 rounded-lg px-2.5 py-1 font-mono text-sky-600 dark:text-sky-400">
                {{ $var }}
                <span class="font-sans text-gray-400 dark:text-slate-500 not-italic">{{ $desc }}</span>
            </span>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
            Telegram: gunakan <code class="bg-white dark:bg-slate-700 px-1 rounded">&lt;b&gt;teks&lt;/b&gt;</code> untuk bold.
            WhatsApp: tag HTML otomatis dihapus, kirim plain text.
        </p>
    </div>

    <form method="POST" action="{{ route('settings.notifications.save') }}" class="space-y-6">
        @csrf

        {{-- Template DOWN --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-sky-50 dark:border-slate-700 bg-red-50/50 dark:bg-red-900/10">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">Template Monitor DOWN</span>
                <button type="button" onclick="resetTemplate('down')"
                        class="ml-auto text-xs text-gray-400 dark:text-slate-500 hover:text-red-500 hover:underline">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Reset default
                </button>
            </div>
            <div class="p-5">
                <textarea name="notif_down_body" id="tpl-down" rows="5"
                          class="w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 resize-y"
                          placeholder="Template pesan saat monitor DOWN...">{{ old('notif_down_body', $down) }}</textarea>
                @error('notif_down_body')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1.5">Dikirim saat monitor berubah jadi DOWN atau saat check manual dengan notifikasi.</p>
            </div>
        </div>

        {{-- Template RECOVERY --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-sky-50 dark:border-slate-700 bg-green-50/50 dark:bg-green-900/10">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">Template Monitor RECOVERED</span>
                <button type="button" onclick="resetTemplate('recovered')"
                        class="ml-auto text-xs text-gray-400 dark:text-slate-500 hover:text-green-500 hover:underline">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Reset default
                </button>
            </div>
            <div class="p-5">
                <textarea name="notif_recovered_body" id="tpl-recovered" rows="6"
                          class="w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 resize-y"
                          placeholder="Template pesan saat monitor kembali UP...">{{ old('notif_recovered_body', $recovered) }}</textarea>
                @error('notif_recovered_body')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1.5">Dikirim saat monitor kembali UP setelah DOWN. Gunakan <code class="bg-sky-50 dark:bg-slate-700 px-1 rounded">{duration}</code> untuk tampilkan durasi downtime.</p>
            </div>
        </div>

        {{-- Template Slow --}}
        <div class="rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3.5 bg-gray-50 dark:bg-slate-700/50 border-b border-gray-200 dark:border-slate-700">
                <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">Template Monitor LAMBAT</span>
                <button type="button" onclick="resetTemplate('slow')"
                        class="ml-auto text-xs text-gray-400 dark:text-slate-500 hover:text-yellow-500 hover:underline">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Reset default
                </button>
            </div>
            <div class="p-5">
                <textarea name="notif_slow_body" id="tpl-slow" rows="5"
                          class="w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 resize-y"
                          placeholder="Template pesan saat response time melebihi batas...">{{ old('notif_slow_body', $slow ?? '') }}</textarea>
                @error('notif_slow_body')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1.5">Variabel: <code class="bg-sky-50 dark:bg-slate-700 px-1 rounded">{response_time}</code> <code class="bg-sky-50 dark:bg-slate-700 px-1 rounded">{threshold}</code></p>
            </div>
        </div>

        {{-- Template Eskalasi --}}
        <div class="rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3.5 bg-gray-50 dark:bg-slate-700/50 border-b border-gray-200 dark:border-slate-700">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">Template ESKALASI</span>
                <button type="button" onclick="resetTemplate('escalation')"
                        class="ml-auto text-xs text-gray-400 dark:text-slate-500 hover:text-red-500 hover:underline">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Reset default
                </button>
            </div>
            <div class="p-5">
                <textarea name="notif_escalation_body" id="tpl-escalation" rows="5"
                          class="w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 resize-y"
                          placeholder="Template pesan eskalasi...">{{ old('notif_escalation_body', $escalation_tpl ?? '') }}</textarea>
                @error('notif_escalation_body')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1.5">Variabel: <code class="bg-sky-50 dark:bg-slate-700 px-1 rounded">{duration}</code> <code class="bg-sky-50 dark:bg-slate-700 px-1 rounded">{rule}</code></p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('settings.index') }}"
               class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-5 py-2 rounded-xl font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Template
            </button>
        </div>
    </form>

    {{-- Laporan Otomatis --}}
    <div class="mt-10">
        <h2 class="text-lg font-semibold dark:text-white mb-4">
            <i class="fas fa-chart-line mr-2 text-sky-500"></i>Laporan Otomatis
        </h2>
        <form method="POST" action="{{ route('settings.report.save') }}"
              class="bg-white dark:bg-slate-800 rounded-2xl shadow p-6 space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="flex items-center gap-3">
                    <input type="hidden" name="report_enabled" value="0">
                    <input type="checkbox" name="report_enabled" value="1" id="rep_enabled"
                           @checked(\App\Models\AppSetting::get('report_enabled', '0'))
                           class="w-4 h-4 text-sky-500 rounded">
                    <label for="rep_enabled" class="text-sm dark:text-slate-300 font-medium">Aktifkan Laporan Otomatis</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="hidden" name="report_daily" value="0">
                    <input type="checkbox" name="report_daily" value="1" id="rep_daily"
                           @checked(\App\Models\AppSetting::get('report_daily', '0'))
                           class="w-4 h-4 text-sky-500 rounded">
                    <label for="rep_daily" class="text-sm dark:text-slate-300">Harian</label>
                    <input type="hidden" name="report_weekly" value="0">
                    <input type="checkbox" name="report_weekly" value="1" id="rep_weekly"
                           @checked(\App\Models\AppSetting::get('report_weekly', '0'))
                           class="w-4 h-4 text-sky-500 rounded ml-4">
                    <label for="rep_weekly" class="text-sm dark:text-slate-300">Mingguan (Senin)</label>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Jam Kirim</label>
                    <input type="time" name="report_time"
                           value="{{ \App\Models\AppSetting::get('report_time', '07:00') }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Channel Penerima</label>
                    @php $repChannels = json_decode(\App\Models\AppSetting::get('report_channel_ids', '[]'), true); @endphp
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        @foreach(\App\Models\NotificationChannel::where('is_active', true)->orderBy('name')->get() as $ch)
                        <label class="flex items-center gap-2 text-sm dark:text-slate-300">
                            <input type="checkbox" name="report_channel_ids[]" value="{{ $ch->id }}"
                                   @checked(in_array($ch->id, $repChannels))
                                   class="w-4 h-4 text-sky-500 rounded">
                            {{ $ch->name }} ({{ $ch->type }})
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between pt-2">
                <button type="button" onclick="sendTestReport()"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-slate-700 dark:text-slate-200 hover:bg-gray-200">
                    <i class="fas fa-paper-plane mr-1"></i>Kirim Sekarang (Test)
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm bg-sky-500 hover:bg-sky-600 text-white rounded-xl font-medium">
                    <i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Pengaturan Laporan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const defaults = {
    down:       {{ Js::from($defaults['notif_down_body']) }},
    recovered:  {{ Js::from($defaults['notif_recovered_body']) }},
    slow:       {{ Js::from($defaults['notif_slow_body']) }},
    escalation: {{ Js::from($defaults['notif_escalation_body']) }},
};

async function sendTestReport() {
    const r = await fetch('{{ route("settings.report.test") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
    });
    const data = await r.json();
    Swal.fire({ icon: data.ok ? 'success' : 'error', title: data.ok ? 'Laporan terkirim!' : 'Gagal', text: data.message || '' });
}

function resetTemplate(type) {
    const isDark = document.documentElement.classList.contains('dark');
    Swal.fire({
        title: 'Reset ke default?',
        text: 'Template akan dikembalikan ke teks bawaan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0ea5e9',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, reset',
        cancelButtonText: 'Batal',
        background: isDark ? '#1e293b' : '#fff',
        color: isDark ? '#e2e8f0' : '#111827',
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('tpl-' + type).value = defaults[type];
        }
    });
}
</script>
@endpush
