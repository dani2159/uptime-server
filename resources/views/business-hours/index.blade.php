@extends('layouts.app')
@section('title', 'Jam Kerja')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Jam Kerja</h1>
        <p class="text-slate-400 text-sm mt-1">Notifikasi hanya dikirim pada jam kerja jika diaktifkan di pengaturan</p>
    </div>
</div>

@if(session('success'))
<div class="bg-emerald-900/30 border border-emerald-600 text-emerald-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('business-hours.save') }}">
    @csrf
    <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden mb-5">
        <table class="w-full text-sm">
            <thead class="bg-slate-900 text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left w-32">Hari</th>
                    <th class="px-4 py-3 text-left w-28">Hari Kerja</th>
                    <th class="px-4 py-3 text-left">Jam Buka</th>
                    <th class="px-4 py-3 text-left">Jam Tutup</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                @php
                    $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                    $existingMap = $businessHours->keyBy('day_of_week');
                @endphp
                @foreach($days as $i => $day)
                @php $bh = $existingMap->get($i); @endphp
                <tr class="hover:bg-slate-700/30">
                    <td class="px-4 py-3 text-white font-medium">{{ $day }}</td>
                    <td class="px-4 py-3">
                        <input type="hidden" name="days[{{ $i }}][day_of_week]" value="{{ $i }}">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="days[{{ $i }}][is_working_day]" value="1"
                                {{ $bh?->is_working_day ? 'checked' : '' }}
                                class="sr-only peer"
                                onchange="this.closest('tr').querySelectorAll('input[type=time]').forEach(el=>el.disabled=!this.checked)">
                            <div class="w-9 h-5 bg-slate-600 peer-checked:bg-sky-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </label>
                    </td>
                    <td class="px-4 py-3">
                        <input type="time" name="days[{{ $i }}][open_time]"
                            value="{{ $bh?->open_time ?? '08:00' }}"
                            {{ $bh?->is_working_day ? '' : 'disabled' }}
                            class="bg-slate-900 border border-slate-600 rounded px-2 py-1 text-white text-sm focus:border-sky-500 focus:outline-none disabled:opacity-40">
                    </td>
                    <td class="px-4 py-3">
                        <input type="time" name="days[{{ $i }}][close_time]"
                            value="{{ $bh?->close_time ?? '17:00' }}"
                            {{ $bh?->is_working_day ? '' : 'disabled' }}
                            class="bg-slate-900 border border-slate-600 rounded px-2 py-1 text-white text-sm focus:border-sky-500 focus:outline-none disabled:opacity-40">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4 mb-5 text-sm text-slate-400">
        <i class="fa fa-info-circle text-sky-400 mr-1"></i>
        Aktifkan <strong class="text-slate-300">Notifikasi Hanya Jam Kerja</strong> di
        <a href="{{ route('settings.index') }}" class="text-sky-400 hover:text-sky-300">Pengaturan → Notifikasi</a>.
    </div>

    <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">
        <i class="fa fa-save mr-1"></i> Simpan Jam Kerja
    </button>
</form>
@endsection
