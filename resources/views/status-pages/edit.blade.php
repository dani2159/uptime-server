@extends('layouts.app')
@section('title', 'Edit Status Page')

@section('content')
@php
    $monitorsJson = json_encode(
        $monitors->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'last_status' => $m->last_status])->values()
    );

    $existingSections = $statusPage->sections ?? [];
    if (empty($existingSections) && !empty($statusPage->monitor_ids)) {
        $existingSections = [['name' => 'Layanan Utama', 'monitor_ids' => $statusPage->monitor_ids]];
    }
    $initialSectionsJson = json_encode($existingSections);
@endphp

<div class="max-w-4xl" x-data="statusBuilder({{ $monitorsJson }}, {{ $initialSectionsJson }})">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('status-pages.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-sky-400 hover:text-sky-600 transition-colors border border-sky-100 dark:border-slate-600">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">Edit Status Page</h1>
            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $statusPage->title }}</p>
        </div>
        <div class="ml-auto">
            <a href="{{ route('status.public', $statusPage->slug) }}" target="_blank"
               class="inline-flex items-center gap-1.5 text-xs text-sky-500 dark:text-sky-400 hover:underline font-medium">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>Lihat Halaman Publik
            </a>
        </div>
    </div>

    <form id="edit-form" method="POST" action="{{ route('status-pages.update', $statusPage) }}" class="space-y-5">
        @csrf @method('PUT')

        {{-- Info Halaman --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-sky-400"></i>Informasi Halaman
            </h2>
            @include('status-pages._form')
        </div>

        {{-- Builder Sections --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-sky-400"></i>Builder Sections
                </h2>
                <span class="text-xs text-gray-400 dark:text-slate-500 italic">Kelompokkan monitor berdasarkan layanan</span>
            </div>

            {{-- Unassigned monitors --}}
            <div class="mb-4 p-3 rounded-xl bg-sky-50/50 dark:bg-slate-700/30 border border-sky-100 dark:border-slate-700 min-h-[52px]">
                <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-2">
                    <i class="fa-solid fa-inbox mr-1 text-sky-400"></i>Belum ditempatkan:
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-if="unassignedMonitors().length === 0">
                        <span class="text-xs text-gray-400 dark:text-slate-500 italic">Semua monitor sudah ditempatkan.</span>
                    </template>
                    <template x-for="m in unassignedMonitors()" :key="m.id">
                        <span class="inline-flex items-center gap-1 text-xs bg-white dark:bg-slate-700 border border-sky-200 dark:border-slate-600 text-sky-700 dark:text-sky-300 px-2.5 py-1 rounded-full font-medium">
                            <span class="w-1.5 h-1.5 rounded-full" :class="m.last_status === 'up' ? 'bg-green-400' : 'bg-gray-300'"></span>
                            <span x-text="m.name"></span>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Sections --}}
            <div class="space-y-3">
                <template x-for="(section, sIdx) in sections" :key="sIdx">
                    <div class="border border-sky-200 dark:border-slate-600 rounded-xl">
                        <div class="flex items-center gap-2 bg-sky-50/70 dark:bg-slate-700/50 px-4 py-2.5 rounded-t-xl">
                            <i class="fa-solid fa-folder text-sky-400 text-xs flex-shrink-0"></i>
                            <input type="text" x-model="section.name"
                                   class="flex-1 text-sm font-semibold bg-transparent focus:outline-none text-gray-800 dark:text-slate-100 placeholder:text-gray-400"
                                   placeholder="Nama Section">
                            <div class="flex items-center gap-1 ml-2">
                                <button type="button" @click="moveSectionUp(sIdx)" :disabled="sIdx === 0"
                                        :class="sIdx === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-sky-100 dark:hover:bg-slate-600'"
                                        class="w-6 h-6 flex items-center justify-center rounded text-gray-400 transition-colors">
                                    <i class="fa-solid fa-chevron-up text-xs"></i>
                                </button>
                                <button type="button" @click="moveSectionDown(sIdx)" :disabled="sIdx === sections.length - 1"
                                        :class="sIdx === sections.length - 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-sky-100 dark:hover:bg-slate-600'"
                                        class="w-6 h-6 flex items-center justify-center rounded text-gray-400 transition-colors">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </button>
                                <button type="button" @click="removeSection(sIdx)" :disabled="sections.length <= 1"
                                        :class="sections.length <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-500'"
                                        class="w-6 h-6 flex items-center justify-center rounded text-red-400 transition-colors ml-1">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="px-4 py-3 space-y-1.5">
                            <template x-if="section.monitor_ids.length === 0">
                                <p class="text-xs text-gray-400 dark:text-slate-500 italic text-center py-2">
                                    Belum ada monitor — tambahkan dari dropdown di bawah.
                                </p>
                            </template>
                            <template x-for="(monId, mIdx) in section.monitor_ids" :key="mIdx">
                                <div class="flex items-center gap-2 bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-sky-100 dark:border-slate-600">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0"
                                          :class="(monitors.find(m => Number(m.id) === Number(monId))?.last_status ?? '') === 'up' ? 'bg-green-400' : 'bg-gray-300'">
                                    </span>
                                    <span class="flex-1 text-sm text-gray-700 dark:text-slate-200"
                                          x-text="monitors.find(m => Number(m.id) === Number(monId))?.name || 'Monitor #' + monId">
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="moveMonitorUp(sIdx, mIdx)" :disabled="mIdx === 0"
                                                :class="mIdx === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-sky-50 dark:hover:bg-slate-700'"
                                                class="w-5 h-5 flex items-center justify-center rounded text-gray-400 transition-colors">
                                            <i class="fa-solid fa-chevron-up" style="font-size:10px"></i>
                                        </button>
                                        <button type="button" @click="moveMonitorDown(sIdx, mIdx)" :disabled="mIdx === section.monitor_ids.length - 1"
                                                :class="mIdx === section.monitor_ids.length - 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-sky-50 dark:hover:bg-slate-700'"
                                                class="w-5 h-5 flex items-center justify-center rounded text-gray-400 transition-colors">
                                            <i class="fa-solid fa-chevron-down" style="font-size:10px"></i>
                                        </button>
                                        <button type="button" @click="unassignMonitor(sIdx, mIdx)"
                                                class="w-5 h-5 flex items-center justify-center rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-red-400 hover:text-red-500 transition-colors ml-0.5">
                                            <i class="fa-solid fa-xmark" style="font-size:10px"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div class="relative mt-1" x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                        class="inline-flex items-center gap-1.5 text-xs text-sky-600 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-200 font-medium py-1.5 px-2 rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 transition-colors">
                                    <i class="fa-solid fa-plus"></i>Tambah Monitor
                                    <i class="fa-solid fa-chevron-down text-[10px]" :class="open ? 'rotate-180' : ''" style="transition:transform .2s"></i>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false"
                                     class="absolute left-0 bottom-full mb-1 z-50 bg-white dark:bg-slate-700 border border-sky-100 dark:border-slate-600 rounded-xl shadow-lg py-1 min-w-[180px] max-h-48 overflow-y-auto">
                                    <template x-if="unassignedMonitors().length === 0">
                                        <p class="px-3 py-2 text-xs text-gray-400 dark:text-slate-500 italic">Semua monitor sudah ditempatkan.</p>
                                    </template>
                                    <template x-for="m in unassignedMonitors()" :key="m.id">
                                        <button type="button"
                                                @click="assignMonitor(sIdx, m.id); open = false"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-sky-50 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 flex items-center gap-2 transition-colors">
                                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                  :class="m.last_status === 'up' ? 'bg-green-400' : 'bg-gray-300'"></span>
                                            <span x-text="m.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addSection()"
                    class="mt-3 inline-flex items-center justify-center gap-2 w-full text-sm text-sky-600 dark:text-sky-400
                           hover:text-sky-800 dark:hover:text-sky-200 font-medium border border-dashed border-sky-300 dark:border-sky-700
                           rounded-xl px-4 py-3 hover:bg-sky-50/50 dark:hover:bg-slate-700/50 transition-colors">
                <i class="fa-solid fa-folder-plus"></i>Tambah Section
            </button>

            <input type="hidden" id="sections_json" name="sections_json">
        </div>

        @error('sections_json')
        <p class="text-red-500 text-xs">{{ $message }}</p>
        @enderror

        <div class="flex items-center gap-3">
            <button type="button" @click="prepareAndSubmit('edit-form')"
                    class="bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                           text-white text-sm px-6 py-2.5 rounded-xl font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i>Simpan Perubahan
            </button>
            <a href="{{ route('status-pages.index') }}"
               class="text-sm text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 px-4 py-2.5">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function statusBuilder(monitors, initial) {
    return {
        monitors: monitors,
        sections: initial && initial.length
            ? initial
            : [{ name: 'Layanan Utama', monitor_ids: [] }],

        usedIds() {
            return this.sections.flatMap(s => (s.monitor_ids || []).map(Number));
        },

        unassignedMonitors() {
            const used = this.usedIds();
            return this.monitors.filter(m => !used.includes(Number(m.id)));
        },

        addSection() {
            this.sections.push({ name: 'Section Baru', monitor_ids: [] });
        },

        removeSection(idx) {
            if (this.sections.length <= 1) return;
            this.sections.splice(idx, 1);
            this.sections = [...this.sections];
        },

        moveSectionUp(idx) {
            if (idx === 0) return;
            [this.sections[idx - 1], this.sections[idx]] = [this.sections[idx], this.sections[idx - 1]];
            this.sections = [...this.sections];
        },

        moveSectionDown(idx) {
            if (idx >= this.sections.length - 1) return;
            [this.sections[idx], this.sections[idx + 1]] = [this.sections[idx + 1], this.sections[idx]];
            this.sections = [...this.sections];
        },

        assignMonitor(sectionIdx, monitorId) {
            const id = Number(monitorId);
            if (!id) return;
            this.sections[sectionIdx].monitor_ids.push(id);
            this.sections = [...this.sections];
        },

        unassignMonitor(sectionIdx, monitorIdx) {
            this.sections[sectionIdx].monitor_ids.splice(monitorIdx, 1);
            this.sections = [...this.sections];
        },

        moveMonitorUp(sectionIdx, monitorIdx) {
            if (monitorIdx === 0) return;
            const ids = [...this.sections[sectionIdx].monitor_ids];
            [ids[monitorIdx - 1], ids[monitorIdx]] = [ids[monitorIdx], ids[monitorIdx - 1]];
            this.sections[sectionIdx].monitor_ids = ids;
            this.sections = [...this.sections];
        },

        moveMonitorDown(sectionIdx, monitorIdx) {
            const ids = [...this.sections[sectionIdx].monitor_ids];
            if (monitorIdx >= ids.length - 1) return;
            [ids[monitorIdx], ids[monitorIdx + 1]] = [ids[monitorIdx + 1], ids[monitorIdx]];
            this.sections[sectionIdx].monitor_ids = ids;
            this.sections = [...this.sections];
        },

        prepareAndSubmit(formId) {
            const total = this.sections.reduce((n, s) => n + (s.monitor_ids || []).length, 0);
            if (total === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tambahkan minimal 1 monitor ke dalam section',
                    toast: true, position: 'top-end', timer: 3000, showConfirmButton: false,
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#111827',
                });
                return;
            }
            document.getElementById('sections_json').value = JSON.stringify(
                this.sections.map(s => ({
                    name: s.name || 'Layanan',
                    monitor_ids: (s.monitor_ids || []).map(Number)
                }))
            );
            document.getElementById(formId).submit();
        }
    };
}
</script>
@endpush
