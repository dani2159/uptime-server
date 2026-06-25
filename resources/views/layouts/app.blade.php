<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WatchTower')</title>
    <link rel="icon" href="{{ asset('images/logo-uptime.png') }}" type="image/png">
    <script>
        (function() {
            const saved = localStorage.getItem('wt_theme');
            if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { scrollbar-width: thin; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #bae6fd; border-radius: 9999px; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>
<body class="bg-sky-50 dark:bg-slate-900 text-gray-900 dark:text-slate-100 min-h-screen">

<header class="bg-white dark:bg-slate-800 border-b border-sky-100 dark:border-slate-700 sticky top-0 z-30 shadow-sm" x-data="{ mobileOpen: false }">

    {{-- Top bar --}}
    <div class="flex items-center px-4 h-14">

        {{-- Brand (left, flex-1) --}}
        <div class="flex-1 flex items-center">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo-uptime.png') }}" alt="WatchTower" class="w-8 h-8 object-contain">
                <div class="hidden sm:block">
                    <p class="font-bold text-gray-800 dark:text-slate-100 leading-none text-sm">WatchTower</p>
                    <p class="text-[10px] text-sky-500 leading-none mt-0.5">Uptime Monitor</p>
                </div>
            </a>
        </div>

        {{-- Desktop nav (center) --}}
        @php
            $navGroups = [
                [
                    'label'   => 'Dashboard',
                    'icon'    => 'fa-house',
                    'route'   => 'dashboard',
                    'pattern' => 'dashboard',
                    'single'  => true,
                ],
                [
                    'label'   => 'Monitoring',
                    'icon'    => 'fa-chart-bar',
                    'pattern' => 'monitors.*|api-health.*|maintenance.*|tags.*',
                    'items'   => [
                        ['route' => 'monitors.index',       'pattern' => 'monitors.*',     'icon' => 'fa-chart-bar',  'label' => 'Monitors'],
                        ['route' => 'api-health.dashboard', 'pattern' => 'api-health.*',   'icon' => 'fa-bolt',       'label' => 'API Health'],
                        ['route' => 'maintenance.index',    'pattern' => 'maintenance.*',  'icon' => 'fa-clock',      'label' => 'Maintenance'],
                        ['route' => 'tags.index',           'pattern' => 'tags.*',         'icon' => 'fa-tags',       'label' => 'Tags'],
                    ],
                ],
                [
                    'label'   => 'Insiden',
                    'icon'    => 'fa-triangle-exclamation',
                    'pattern' => 'incidents.*|sla-report.*',
                    'items'   => [
                        ['route' => 'incidents.index',   'pattern' => 'incidents.*',   'icon' => 'fa-triangle-exclamation', 'label' => 'Daftar Insiden'],
                        ['route' => 'sla-report.index',  'pattern' => 'sla-report.*',  'icon' => 'fa-chart-line',           'label' => 'SLA Report'],
                    ],
                ],
                [
                    'label'   => 'Notifikasi',
                    'icon'    => 'fa-bell',
                    'pattern' => 'channels.*|escalations.*',
                    'items'   => [
                        ['route' => 'channels.index',    'pattern' => 'channels.*',    'icon' => 'fa-bell',              'label' => 'Channels'],
                        ['route' => 'escalations.index', 'pattern' => 'escalations.*', 'icon' => 'fa-bell-slash',        'label' => 'Eskalasi'],
                        ['route' => 'settings.notifications', 'pattern' => 'settings.notifications*', 'icon' => 'fa-pen-to-square', 'label' => 'Template Pesan'],
                    ],
                ],
                [
                    'label'   => 'Status Pages',
                    'icon'    => 'fa-circle-check',
                    'route'   => 'status-pages.index',
                    'pattern' => 'status-pages.*',
                    'single'  => true,
                ],
                [
                    'label'   => 'Audit Log',
                    'icon'    => 'fa-clock-rotate-left',
                    'route'   => 'audit-logs.index',
                    'pattern' => 'audit-logs.*',
                    'single'  => true,
                ],
                [
                    'label'   => 'Settings',
                    'icon'    => 'fa-sliders',
                    'route'   => 'settings.index',
                    'pattern' => 'settings.index',
                    'single'  => true,
                ],
            ];
        @endphp
        <nav class="hidden lg:flex items-center gap-0.5" x-data="{ open: null }">
            @foreach($navGroups as $gi => $group)
            @if(!empty($group['single']))
                {{-- Single link --}}
                <a href="{{ route($group['route']) }}"
                   class="px-2.5 py-1.5 text-xs rounded-lg font-medium transition-colors whitespace-nowrap
                       {{ request()->routeIs($group['pattern'])
                           ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400'
                           : 'text-gray-500 dark:text-slate-400 hover:text-sky-700 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
                    <i class="fa-solid {{ $group['icon'] }} mr-1"></i>{{ $group['label'] }}
                </a>
            @else
                {{-- Dropdown group --}}
                @php
                    $patterns = explode('|', $group['pattern']);
                    $isActive = collect($patterns)->some(fn($p) => request()->routeIs($p));
                @endphp
                <div class="relative" @mouseenter="open = {{ $gi }}" @mouseleave="open = null">
                    <button class="flex items-center gap-1 px-2.5 py-1.5 text-xs rounded-lg font-medium transition-colors whitespace-nowrap
                        {{ $isActive
                            ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400'
                            : 'text-gray-500 dark:text-slate-400 hover:text-sky-700 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
                        <i class="fa-solid {{ $group['icon'] }}"></i>
                        {{ $group['label'] }}
                        <i class="fa-solid fa-chevron-down text-[9px] opacity-60 transition-transform duration-150"
                           :class="open === {{ $gi }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $gi }}" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-xl shadow-lg py-1 z-50">
                        @foreach($group['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-2.5 px-4 py-2 text-xs transition-colors
                               {{ request()->routeIs($item['pattern'])
                                   ? 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-900/20'
                                   : 'text-gray-600 dark:text-slate-300 hover:text-sky-600 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
                            <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-sky-400 text-[11px]"></i>
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @endforeach
        </nav>

        {{-- Right actions (flex-1, justify-end) --}}
        <div class="flex-1 flex items-center justify-end gap-2">

            {{-- IP badge (desktop only) --}}
            @if(!empty($serverIpInfo['ip']))
            <div class="hidden xl:flex items-center gap-1.5 bg-sky-50 dark:bg-slate-700/60 border border-sky-100 dark:border-slate-600 rounded-lg px-2.5 py-1"
                 title="{{ $serverIpInfo['isp'] ?? '' }}{{ isset($serverIpInfo['city']) ? ' · '.$serverIpInfo['city'].', '.$serverIpInfo['country'] : '' }}">
                <i class="fa-solid fa-tower-broadcast text-sky-400 text-[10px]"></i>
                <span class="font-mono text-[11px] text-sky-700 dark:text-sky-400">{{ $serverIpInfo['ip'] }}</span>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 hidden 2xl:inline">· {{ Str::limit($serverIpInfo['isp'] ?? '', 20) }}</span>
            </div>
            @endif

            {{-- Clock --}}
            <div class="hidden md:block text-right">
                <p id="live-clock" class="text-xs font-mono text-gray-500 dark:text-slate-400 font-medium"></p>
                <p id="live-date"  class="text-[10px] text-gray-400 dark:text-slate-500"></p>
            </div>

            {{-- Dark mode --}}
            <button onclick="toggleDark()" id="theme-btn"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-slate-400 text-sm"></i>
            </button>

            {{-- Logout --}}
            @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Keluar"
                        class="w-8 h-8 hidden sm:flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
                </button>
            </form>
            @endauth

            {{-- Hamburger (mobile) --}}
            <button @click="mobileOpen = !mobileOpen"
                    class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400">
                <i class="fa-solid" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>
    </div>

    {{-- Mobile nav drawer --}}
    <div x-show="mobileOpen" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden border-t border-sky-100 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 space-y-1"
         x-data="{ mSub: null }">

        @foreach($navGroups as $gi => $group)
        @if(!empty($group['single']))
        {{-- Single link --}}
        <a href="{{ route($group['route']) }}" @click="mobileOpen = false"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-xl font-medium transition-colors
               {{ request()->routeIs($group['pattern']) ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400' : 'text-gray-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
            <i class="fa-solid {{ $group['icon'] }} w-4 text-center text-sky-400"></i>{{ $group['label'] }}
        </a>
        @else
        {{-- Dropdown group --}}
        @php
            $mPatterns = explode('|', $group['pattern']);
            $mActive = collect($mPatterns)->some(fn($p) => request()->routeIs($p));
        @endphp
        <div>
            <button @click="mSub = mSub === {{ $gi }} ? null : {{ $gi }}"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-xl font-medium transition-colors
                        {{ $mActive ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400' : 'text-gray-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
                <i class="fa-solid {{ $group['icon'] }} w-4 text-center text-sky-400"></i>
                <span class="flex-1 text-left">{{ $group['label'] }}</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-40 transition-transform duration-150"
                   :class="mSub === {{ $gi }} ? 'rotate-90' : ''"></i>
            </button>
            <div x-show="mSub === {{ $gi }}" x-cloak class="ml-6 mt-0.5 space-y-0.5">
                @foreach($group['items'] as $item)
                <a href="{{ route($item['route']) }}" @click="mobileOpen = false"
                   class="flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg transition-colors
                       {{ request()->routeIs($item['pattern']) ? 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-900/20' : 'text-gray-500 dark:text-slate-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
                    <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-sky-400 text-xs"></i>
                    {{ $item['label'] }}
                </a>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach

        @if(!empty($serverIpInfo['ip']))
        <div class="flex items-center gap-2 px-3 py-2 text-xs text-gray-400 dark:text-slate-500 border-t border-sky-50 dark:border-slate-700 mt-1 pt-2">
            <i class="fa-solid fa-tower-broadcast text-sky-400"></i>
            <span class="font-mono text-sky-600 dark:text-sky-400">{{ $serverIpInfo['ip'] }}</span>
            <span>· {{ $serverIpInfo['isp'] ?? '' }}</span>
        </div>
        @endif
    </div>

</header>

<main class="max-w-6xl mx-auto px-5 py-6">
    @yield('content')
</main>

@stack('modals')
@stack('scripts')

<script>
function updateClock() {
    const now = new Date();
    const p = n => String(n).padStart(2, '0');
    const el = document.getElementById('live-clock');
    if (el) el.textContent = p(now.getHours()) + ':' + p(now.getMinutes()) + ':' + p(now.getSeconds());
    const days   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const ed = document.getElementById('live-date');
    if (ed) ed.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
}
updateClock();
setInterval(updateClock, 1000);

function updateThemeIcon() {
    const isDark = document.documentElement.classList.contains('dark');
    const icon = document.getElementById('theme-icon');
    if (icon) icon.className = `fa-solid ${isDark ? 'fa-sun text-yellow-400' : 'fa-moon text-slate-400'} text-sm`;
}
window.toggleDark = function() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('wt_theme', isDark ? 'dark' : 'light');
    updateThemeIcon();
};
document.addEventListener('DOMContentLoaded', updateThemeIcon);

window.swalDelete = function(formId, name) {
    Swal.fire({
        title: 'Hapus "' + name + '"?',
        text: 'Data tidak bisa dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i>Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
        color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#111827',
    }).then(r => { if (r.isConfirmed) document.getElementById(formId).submit(); });
};

@if(session('success'))
Swal.fire({ icon: 'success', title: '{{ addslashes(session('success')) }}', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, timerProgressBar: true });
@endif
@if(session('error'))
Swal.fire({ icon: 'error', title: '{{ addslashes(session('error')) }}', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
@endif
</script>
</body>
</html>
