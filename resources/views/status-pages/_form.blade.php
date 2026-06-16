@php
    $statusPage = $statusPage ?? null;
    $val = fn($f, $d = '') => old($f, $statusPage?->$f ?? $d);
    $selectedKeys = old('service_keys', $statusPage?->service_keys ?? []);
    $inp = 'w-full border border-sky-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500';
    $lbl = 'block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1 uppercase tracking-wide';
@endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="{{ $lbl }}">Judul</label>
        <input type="text" name="title" value="{{ $val('title') }}"
               class="{{ $inp }}" placeholder="Contoh: Status Layanan RSUD" required>
        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="{{ $lbl }}">Slug (URL Publik)</label>
        <div class="flex items-center">
            <span class="text-xs text-gray-400 dark:text-slate-500 border border-r-0 border-sky-200 dark:border-slate-600 rounded-l-xl px-3 py-2 bg-sky-50 dark:bg-slate-700/60 whitespace-nowrap">/status/</span>
            <input type="text" name="slug" value="{{ $val('slug') }}"
                   class="flex-1 border border-sky-200 dark:border-slate-600 rounded-r-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-400 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100"
                   placeholder="layanan-rsud" pattern="[a-z0-9-]+" required>
        </div>
        <p class="text-xs text-sky-500 mt-1">Huruf kecil, angka, dan minus saja</p>
        @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="{{ $lbl }}">Deskripsi (opsional)</label>
    <textarea name="description" rows="2" class="{{ $inp }}"
              placeholder="Halaman status layanan sistem informasi...">{{ $val('description') }}</textarea>
</div>

<div>
    <label class="{{ $lbl }} mb-2">Layanan API yang ditampilkan (opsional)</label>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-xl bg-sky-50/50 dark:bg-slate-700/30 border border-sky-100 dark:border-slate-700">
        @forelse($services as $key => $checker)
        <label class="flex items-center gap-2 cursor-pointer text-sm">
            <input type="checkbox" name="service_keys[]" value="{{ $key }}"
                   {{ in_array($key, $selectedKeys) ? 'checked' : '' }}
                   class="rounded border-sky-300 text-sky-500 focus:ring-sky-300">
            <span class="text-gray-700 dark:text-slate-300">{{ $checker->getServiceLabel() }}</span>
        </label>
        @empty
        <p class="text-xs text-gray-400 dark:text-slate-500 italic col-span-3">Belum ada layanan API terdaftar.</p>
        @endforelse
    </div>
    @error('service_keys')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
    <label class="flex items-center gap-2.5 cursor-pointer">
        <input type="checkbox" name="is_public" id="is_public" value="1"
               {{ $val('is_public', '1') == '1' ? 'checked' : '' }}
               class="rounded border-sky-300 text-sky-500 focus:ring-sky-300">
        <span class="text-sm text-gray-700 dark:text-slate-300 font-medium">
            <i class="fa-solid fa-globe text-sky-400 mr-1"></i>Tampilkan publik (tanpa login)
        </span>
    </label>
</div>
