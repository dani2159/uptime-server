<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WatchTower')</title>
    <link rel="icon" href="{{ asset('images/logo-uptime.png') }}" type="image/png">
    <script>
        // Apply saved theme before styles render (prevents FOUC)
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
        .panel-h { height: calc(100vh - 56px); }
        .sidebar-list::-webkit-scrollbar { width: 3px; }
        .sidebar-list::-webkit-scrollbar-thumb { background: #bae6fd; border-radius: 9999px; }
        .dark .sidebar-list::-webkit-scrollbar-thumb { background: #334155; }
        .main-panel::-webkit-scrollbar { width: 6px; }
        .main-panel::-webkit-scrollbar-thumb { background: #bae6fd; border-radius: 9999px; }
        .dark .main-panel::-webkit-scrollbar-thumb { background: #334155; }
        .modal-scroll::-webkit-scrollbar { width: 4px; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #bae6fd; border-radius: 9999px; }
        .dark .modal-scroll::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>
<body class="bg-sky-50 dark:bg-slate-900 text-gray-900 dark:text-slate-100 overflow-hidden"
      x-data="{ mobileNav: false, mobileSidebar: false }">

{{-- Topbar --}}
<header class="bg-white dark:bg-slate-800 border-b border-sky-100 dark:border-slate-700 flex-shrink-0 shadow-sm">

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
            $kNav = [
                ['route' => 'dashboard',           'pattern' => 'dashboard',       'icon' => 'fa-house',                'label' => 'Dashboard'],
                ['route' => 'monitors.index',      'pattern' => 'monitors.*',      'icon' => 'fa-chart-bar',            'label' => 'Monitors'],
                ['route' => 'api-health.dashboard','pattern' => 'api-health.*',    'icon' => 'fa-bolt',                 'label' => 'API Health'],
                ['route' => 'channels.index',      'pattern' => 'channels.*',      'icon' => 'fa-bell',                 'label' => 'Notifikasi'],
                ['route' => 'maintenance.index',   'pattern' => 'maintenance.*',   'icon' => 'fa-clock',                'label' => 'Maintenance'],
                ['route' => 'incidents.index',     'pattern' => 'incidents.*',     'icon' => 'fa-triangle-exclamation', 'label' => 'Insiden'],
                ['route' => 'sla-report.index',    'pattern' => 'sla-report.*',    'icon' => 'fa-chart-line',           'label' => 'SLA Report'],
                ['route' => 'status-pages.index',  'pattern' => 'status-pages.*', 'icon' => 'fa-circle-check',         'label' => 'Status Pages'],
                ['route' => 'settings.index',      'pattern' => 'settings.*',      'icon' => 'fa-sliders',              'label' => 'Settings'],
            ];
        @endphp
        <nav class="hidden lg:flex items-center gap-0.5">
            @foreach($kNav as $n)
            <a href="{{ route($n['route']) }}"
               class="px-2.5 py-1.5 text-xs rounded-lg font-medium transition-colors whitespace-nowrap
                   {{ request()->routeIs($n['pattern'])
                       ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400'
                       : 'text-gray-500 dark:text-slate-400 hover:text-sky-700 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
                <i class="fa-solid {{ $n['icon'] }} mr-1"></i>{{ $n['label'] }}
            </a>
            @endforeach
        </nav>

        {{-- Right (flex-1, justify-end) --}}
        <div class="flex-1 flex items-center justify-end gap-2">

            @if(!empty($serverIpInfo['ip']))
            <div class="hidden xl:flex items-center gap-1.5 bg-sky-50 dark:bg-slate-700/60 border border-sky-100 dark:border-slate-600 rounded-lg px-2.5 py-1"
                 title="{{ $serverIpInfo['isp'] ?? '' }}">
                <i class="fa-solid fa-tower-broadcast text-sky-400 text-[10px]"></i>
                <span class="font-mono text-[11px] text-sky-700 dark:text-sky-400">{{ $serverIpInfo['ip'] }}</span>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 hidden 2xl:inline">· {{ Str::limit($serverIpInfo['isp'] ?? '', 20) }}</span>
            </div>
            @endif

            <button onclick="toggleDark()" id="theme-btn"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-slate-400 text-sm"></i>
            </button>
            <div class="hidden md:block text-right">
                <p id="live-clock" class="text-xs font-mono text-gray-500 dark:text-slate-400 font-medium"></p>
                <p id="live-date"  class="text-[10px] text-gray-400 dark:text-slate-500"></p>
            </div>
            @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Keluar"
                        class="w-8 h-8 hidden sm:flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
                </button>
            </form>
            @endauth

            {{-- Mobile: sidebar toggle --}}
            <button @click="mobileSidebar = !mobileSidebar"
                    class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400">
                <i class="fa-solid fa-list text-sm"></i>
            </button>

            {{-- Mobile: nav hamburger --}}
            <button @click="mobileNav = !mobileNav"
                    class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400">
                <i class="fa-solid" :class="mobileNav ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>
    </div>

    {{-- Mobile nav drawer --}}
    <div x-show="mobileNav" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="lg:hidden border-t border-sky-100 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 grid grid-cols-2 gap-1">
        @foreach($kNav as $n)
        <a href="{{ route($n['route']) }}" @click="mobileNav = false"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-xl font-medium transition-colors
               {{ request()->routeIs($n['pattern'])
                   ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400'
                   : 'text-gray-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
            <i class="fa-solid {{ $n['icon'] }} w-4 text-center text-sky-400"></i>{{ $n['label'] }}
        </a>
        @endforeach
    </div>

</header>

{{-- Body: sidebar + main --}}
<div class="flex" style="height:calc(100vh - 56px)">

    {{-- Sidebar: desktop always visible, mobile overlay --}}
    <aside class="hidden lg:flex lg:w-[280px] flex-col flex-shrink-0 bg-white dark:bg-slate-800 border-r border-sky-100 dark:border-slate-700 overflow-y-auto sidebar-list">
        @yield('sidebar')
    </aside>

    {{-- Mobile sidebar overlay --}}
    <div x-show="mobileSidebar" x-cloak class="lg:hidden fixed inset-0 z-40 flex" @click.self="mobileSidebar = false">
        <div class="w-72 max-w-[85vw] bg-white dark:bg-slate-800 border-r border-sky-100 dark:border-slate-700 overflow-y-auto sidebar-list flex flex-col shadow-xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            @yield('sidebar')
        </div>
        <div class="flex-1 bg-black/30"></div>
    </div>

    <main class="flex-1 overflow-y-auto main-panel bg-sky-50 dark:bg-slate-900">
        @yield('main')
    </main>
</div>

@stack('modals')
@stack('scripts')

<script>
// Live clock
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

// Dark mode
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

// Global swalDelete
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
