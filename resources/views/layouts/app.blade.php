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

<header class="h-14 bg-white dark:bg-slate-800 border-b border-sky-100 dark:border-slate-700 flex items-center px-5 sticky top-0 z-30 shadow-sm relative">

    {{-- Brand (left) --}}
    <div class="flex-shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <div class="w-9 h-9 flex-shrink-0">
                <img src="{{ asset('images/logo-uptime.png') }}" alt="WatchTower" class="w-full h-full object-contain">
            </div>
            <div class="hidden sm:block">
                <p class="font-bold text-gray-800 dark:text-slate-100 leading-none text-sm">WatchTower</p>
                <p class="text-[10px] text-sky-500 leading-none mt-0.5">Uptime Monitor</p>
            </div>
        </a>
    </div>

    {{-- Nav (center, absolute) --}}
    <nav class="absolute left-1/2 -translate-x-1/2 flex items-center gap-0.5">
        @php
            $aNav = [
                ['route' => 'dashboard',           'pattern' => 'dashboard',        'icon' => 'fa-house',        'label' => 'Dashboard'],
                ['route' => 'monitors.index',      'pattern' => 'monitors.*',       'icon' => 'fa-chart-bar',    'label' => 'Monitors'],
                ['route' => 'api-health.dashboard','pattern' => 'api-health.*',     'icon' => 'fa-bolt',         'label' => 'API Health'],
                ['route' => 'channels.index',      'pattern' => 'channels.*',       'icon' => 'fa-bell',         'label' => 'Notifikasi'],
                ['route' => 'maintenance.index',   'pattern' => 'maintenance.*',    'icon' => 'fa-clock',        'label' => 'Maintenance'],
                ['route' => 'incidents.index',     'pattern' => 'incidents.*',     'icon' => 'fa-triangle-exclamation', 'label' => 'Insiden'],
                ['route' => 'sla-report.index',    'pattern' => 'sla-report.*',    'icon' => 'fa-chart-line',   'label' => 'SLA Report'],
                ['route' => 'status-pages.index',  'pattern' => 'status-pages.*',  'icon' => 'fa-circle-check', 'label' => 'Status Pages'],
                ['route' => 'settings.index',      'pattern' => 'settings.*',      'icon' => 'fa-sliders',      'label' => 'Settings'],
            ];
        @endphp
        @foreach($aNav as $n)
        <a href="{{ route($n['route']) }}"
           class="px-3 py-1.5 text-xs rounded-lg font-medium transition-colors whitespace-nowrap
               {{ request()->routeIs($n['pattern'])
                   ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400'
                   : 'text-gray-500 dark:text-slate-400 hover:text-sky-700 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-slate-700' }}">
            <i class="fa-solid {{ $n['icon'] }} mr-1"></i>{{ $n['label'] }}
        </a>
        @endforeach
    </nav>

    {{-- Right: IP badge + dark toggle + time --}}
    <div class="ml-auto flex items-center gap-2">

        @if(!empty($serverIpInfo['ip']))
        <div class="hidden lg:flex items-center gap-1.5 bg-sky-50 dark:bg-slate-700/60 border border-sky-100 dark:border-slate-600 rounded-lg px-2.5 py-1"
             title="{{ $serverIpInfo['isp'] ?? '' }}{{ isset($serverIpInfo['city']) ? ' · '.$serverIpInfo['city'].', '.$serverIpInfo['country'] : '' }}">
            <i class="fa-solid fa-tower-broadcast text-sky-400 text-[10px]"></i>
            <span class="font-mono text-[11px] text-sky-700 dark:text-sky-400">{{ $serverIpInfo['ip'] }}</span>
            @if(!empty($serverIpInfo['isp']))
            <span class="text-[10px] text-gray-400 dark:text-slate-500 hidden xl:inline">· {{ Str::limit($serverIpInfo['isp'], 22) }}</span>
            @endif
        </div>
        @endif

        <button onclick="toggleDark()" id="theme-btn"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 transition-colors"
                title="Toggle tema">
            <i id="theme-icon" class="fa-solid fa-moon text-slate-400 text-sm"></i>
        </button>
        <div class="hidden md:block text-right">
            <p id="live-clock" class="text-xs font-mono text-gray-500 dark:text-slate-400 font-medium"></p>
            <p id="live-date"  class="text-[10px] text-gray-400 dark:text-slate-500"></p>
        </div>
        @auth
        <div class="flex items-center gap-2 pl-2 ml-1 border-l border-sky-100 dark:border-slate-700">
            <span class="text-xs font-medium text-gray-500 dark:text-slate-400 hidden md:block">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Keluar"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
                </button>
            </form>
        </div>
        @endauth
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
