<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Nama Jadwal <span class="text-red-400">*</span></label>
            <input type="text" name="name" value="{{ old('name', $schedule->name ?? '') }}" required
                class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"
                placeholder="Tim NOC, Tim Developer, dll">
        </div>
        <div>
            <label class="text-slate-400 text-xs uppercase tracking-wide block mb-1">Deskripsi</label>
            <input type="text" name="description" value="{{ old('description', $schedule->description ?? '') }}"
                class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
        </div>
    </div>

    <div x-data="{ shifts: {{ json_encode(old('shifts', isset($schedule) ? $schedule->shifts->map(fn($s) => ['name'=>$s->name,'day_of_week'=>$s->day_of_week,'start_time'=>substr($s->start_time,0,5),'end_time'=>substr($s->end_time,0,5),'channel_id'=>$s->channel_id,'contact_info'=>$s->contact_info])->toArray() : [])) }} }">
        <div class="flex items-center justify-between mb-2">
            <label class="text-slate-400 text-xs uppercase tracking-wide">Shift</label>
            <button type="button" @click="shifts.push({name:'',day_of_week:null,start_time:'08:00',end_time:'17:00',channel_id:'',contact_info:''})"
                class="text-xs text-sky-400 hover:text-sky-300"><i class="fa fa-plus mr-1"></i>Tambah Shift</button>
        </div>
        <template x-for="(shift, i) in shifts" :key="i">
            <div class="bg-slate-900/50 border border-slate-700 rounded-lg p-3 mb-2 space-y-2">
                <div class="grid grid-cols-5 gap-2">
                    <div class="col-span-2">
                        <input type="text" :name="`shifts[${i}][name]`" x-model="shift.name" required
                            class="w-full bg-slate-900 border border-slate-600 rounded px-2 py-1.5 text-white text-sm focus:border-sky-500 focus:outline-none"
                            placeholder="Nama shift / PIC">
                    </div>
                    <div>
                        <select :name="`shifts[${i}][day_of_week]`" x-model="shift.day_of_week"
                            class="w-full bg-slate-900 border border-slate-600 rounded px-2 py-1.5 text-white text-sm focus:border-sky-500 focus:outline-none">
                            <option value="">Setiap hari</option>
                            @foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $di => $dn)
                            <option value="{{ $di }}">{{ $dn }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="time" :name="`shifts[${i}][start_time]`" x-model="shift.start_time"
                            class="w-full bg-slate-900 border border-slate-600 rounded px-2 py-1.5 text-white text-sm focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <input type="time" :name="`shifts[${i}][end_time]`" x-model="shift.end_time"
                            class="w-full bg-slate-900 border border-slate-600 rounded px-2 py-1.5 text-white text-sm focus:border-sky-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <select :name="`shifts[${i}][channel_id]`" x-model="shift.channel_id"
                            class="w-full bg-slate-900 border border-slate-600 rounded px-2 py-1.5 text-white text-sm focus:border-sky-500 focus:outline-none">
                            <option value="">-- Channel notif opsional --</option>
                            @foreach($channels as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->name }} ({{ $ch->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" :name="`shifts[${i}][contact_info]`" x-model="shift.contact_info"
                            class="flex-1 bg-slate-900 border border-slate-600 rounded px-2 py-1.5 text-white text-sm focus:border-sky-500 focus:outline-none"
                            placeholder="No HP / email">
                        <button type="button" @click="shifts.splice(i,1)" class="text-red-400 hover:text-red-300 px-2">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        <p x-show="shifts.length === 0" class="text-slate-500 text-sm py-2">Belum ada shift</p>
    </div>
</div>
