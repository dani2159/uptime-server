@php
$fi = 'w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:focus:ring-sky-900';
$fi2 = 'w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-lg px-2 py-1.5 text-gray-800 dark:text-white text-sm focus:border-sky-400 focus:outline-none';
$fl = 'block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1';
@endphp
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="{{ $fl }}">Nama Jadwal <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $schedule->name ?? '') }}" required
                class="{{ $fi }}" placeholder="Tim NOC, Tim Developer, dll">
        </div>
        <div>
            <label class="{{ $fl }}">Deskripsi</label>
            <input type="text" name="description" value="{{ old('description', $schedule->description ?? '') }}"
                class="{{ $fi }}">
        </div>
    </div>

    <div x-data="{ shifts: {{ json_encode(old('shifts', isset($schedule) ? $schedule->shifts->map(fn($s) => ['name'=>$s->name,'day_of_week'=>$s->day_of_week,'start_time'=>substr($s->start_time,0,5),'end_time'=>substr($s->end_time,0,5),'channel_id'=>$s->channel_id,'contact_info'=>$s->contact_info])->toArray() : [])) }} }">
        <div class="flex items-center justify-between mb-2">
            <label class="{{ $fl }} mb-0">Shift</label>
            <button type="button" @click="shifts.push({name:'',day_of_week:null,start_time:'08:00',end_time:'17:00',channel_id:'',contact_info:''})"
                class="text-xs text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-plus text-[10px]"></i>Tambah Shift
            </button>
        </div>
        <template x-for="(shift, i) in shifts" :key="i">
            <div class="bg-gray-50 dark:bg-slate-900/60 border border-gray-200 dark:border-slate-700 rounded-xl p-3 mb-2 space-y-2">
                <div class="grid grid-cols-5 gap-2">
                    <div class="col-span-2">
                        <input type="text" :name="`shifts[${i}][name]`" x-model="shift.name" required
                            class="{{ $fi2 }}" placeholder="Nama shift / PIC">
                    </div>
                    <div>
                        <select :name="`shifts[${i}][day_of_week]`" x-model="shift.day_of_week"
                            class="{{ $fi2 }}">
                            <option value="">Setiap hari</option>
                            @foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $di => $dn)
                            <option value="{{ $di }}">{{ $dn }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="time" :name="`shifts[${i}][start_time]`" x-model="shift.start_time"
                            class="{{ $fi2 }}">
                    </div>
                    <div>
                        <input type="time" :name="`shifts[${i}][end_time]`" x-model="shift.end_time"
                            class="{{ $fi2 }}">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <select :name="`shifts[${i}][channel_id]`" x-model="shift.channel_id"
                            class="{{ $fi2 }}">
                            <option value="">-- Channel notif opsional --</option>
                            @foreach($channels as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->name }} ({{ $ch->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" :name="`shifts[${i}][contact_info]`" x-model="shift.contact_info"
                            class="flex-1 {{ $fi2 }}" placeholder="No HP / email">
                        <button type="button" @click="shifts.splice(i,1)"
                            class="px-2 text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        <p x-show="shifts.length === 0" class="text-gray-400 dark:text-slate-500 text-sm py-2">Belum ada shift — klik Tambah Shift</p>
    </div>
</div>
