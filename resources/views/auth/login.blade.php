<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - WatchTower</title>
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
</head>
<body class="bg-sky-50 dark:bg-slate-900 text-gray-900 dark:text-slate-100 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-sm">
    <div class="text-center mb-6">
        <img src="{{ asset('images/logo-uptime.png') }}" alt="WatchTower" class="w-14 h-14 mx-auto mb-3">
        <p class="font-bold text-gray-800 dark:text-slate-100 text-lg leading-none">WatchTower</p>
        <p class="text-xs text-sky-500 mt-1">Uptime Monitor</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-100 dark:border-slate-700 shadow-sm p-6">
        <h1 class="text-base font-bold text-gray-800 dark:text-slate-100 mb-4">Masuk</h1>

        @if($errors->any())
        <div class="mb-4 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-lg px-3 py-2">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full text-sm rounded-xl border border-sky-100 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full text-sm rounded-xl border border-sky-100 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>
            <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-sky-200">
                Ingat saya
            </label>
            <button type="submit"
                    class="w-full bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                           text-white text-sm font-semibold py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-right-to-bracket mr-1.5"></i>Masuk
            </button>
        </form>
    </div>
</div>
</body>
</html>
