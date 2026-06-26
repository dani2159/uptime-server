@extends('layouts.app')
@section('title', 'Jam Kerja')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center">
            <i class="fa-solid fa-clock text-sky-500 text-sm"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">Jam Kerja</h1>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Notifikasi hanya dikirim pada jam kerja jika diaktifkan di pengaturan</p>
        </div>
    </div>

    <form method="POST" action="{{ route('business-hours.save') }}">
        @csrf
        <div class="bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-3 border-b border-sky-50 dark:border-slate-700 bg-sky-50/40 dark:bg-slate-700/20">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300">Jadwal Hari Kerja</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-sky-50/60 dark:bg-slate-700/40 text-xs uppercase text-gray-500 dark:text-slate-400 tracking-wider border-b border-sky-50 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3 text-left">Hari</th>
                        <th class="px-5 py-3 text-left">Hari Kerja</th>
                        <th class="px-5 py-3 text-left">Jam Buka</th>
                        <th class="px-5 py-3 text-left">Jam Tutup</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sky-50 dark:divide-slate-700/50">
                    @php
                        $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                        $existingMap = $businessHours->keyBy('day_of_week');
                    @endphp
                    @foreach($days as $i => $day)
                    @php $bh = $existingMap->get($i); @endphp
                    <tr class="hover:bg-sky-50/30 dark:hover:bg-slate-700/20">
                        <td class="px-5 py-3 font-medium text-gray-800 dark:text-slate-100">{{ $day }}</td>
                        <td class="px-5 py-3">
                            <input type="hidden" name="days[{{ $i }}][day_of_week]" value="{{ $i }}">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="days[{{ $i }}][is_working_day]" value="1"
                                    {{ $bh?->is_working_day ? 'checked' : '' }}
                                    class="sr-only peer"
                                    onchange="this.closest('tr').querySelectorAll('input[type=time]').forEach(el=>el.disabled=!this.checked)">
                                <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 peer-checked:bg-sky-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                            </label>
                        </td>
                        <td class="px-5 py-3">
                            <input type="time" name="days[{{ $i }}][open_time]"
                                value="{{ $bh?->open_time ?? '08:00' }}"
                                {{ $bh?->is_working_day ? '' : 'disabled' }}
                                class="bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-lg px-3 py-1.5 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900 disabled:opacity-40">
                        </td>
                        <td class="px-5 py-3">
                            <input type="time" name="days[{{ $i }}][close_time]"
                                value="{{ $bh?->close_time ?? '17:00' }}"
                                {{ $bh?->is_working_day ? '' : 'disabled' }}
                                class="bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-lg px-3 py-1.5 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900 disabled:opacity-40">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-sky-50 dark:bg-sky-900/10 border border-sky-200 dark:border-sky-800/40 rounded-xl px-4 py-3 mb-4 flex gap-3 text-sm">
            <i class="fa-solid fa-info-circle text-sky-500 mt-0.5 flex-shrink-0"></i>
            <span class="text-sky-700 dark:text-sky-400">
                Aktifkan <strong>Notifikasi Hanya Jam Kerja</strong> di
                <a href="{{ route('settings.index') }}" class="underline font-medium">Pengaturan → v2 Lanjutan</a>.
            </span>
        </div>

        <button type="submit"
            class="inline-flex items-center gap-2 bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400 text-white text-sm px-5 py-2.5 rounded-xl font-semibold shadow-sm transition-all">
            <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Jam Kerja
        </button>
    </form>
</div>
@endsection
