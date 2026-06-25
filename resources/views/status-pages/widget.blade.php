<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $page->title }} — Status</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{margin:0;padding:0;font-family:system-ui,sans-serif}</style>
</head>
<body class="bg-white text-gray-800">
<div class="border border-gray-200 rounded-xl p-4 max-w-sm mx-auto shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <span class="font-semibold text-sm">{{ $page->title }}</span>
        @if($overall === 'operational')
        <span class="flex items-center gap-1 text-xs text-green-600 font-medium">
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Semua Operasional
        </span>
        @else
        <span class="flex items-center gap-1 text-xs text-red-600 font-medium">
            <span class="w-2 h-2 rounded-full bg-red-500"></span> Gangguan
        </span>
        @endif
    </div>
    <div class="space-y-1.5">
        @foreach($monitors as $m)
        <div class="flex items-center justify-between text-xs">
            <span class="text-gray-600 truncate max-w-[180px]">{{ $m->name }}</span>
            @if($m->last_status === 'up')
            <span class="text-green-500 font-medium">UP</span>
            @elseif($m->last_status === 'down')
            <span class="text-red-500 font-medium">DOWN</span>
            @else
            <span class="text-gray-400">—</span>
            @endif
        </div>
        @endforeach
    </div>
    <div class="mt-3 pt-2 border-t border-gray-100 text-[10px] text-gray-400 text-right">
        <a href="{{ route('status.public', $page->slug) }}" target="_blank" class="hover:text-sky-500">
            Lihat detail →
        </a>
    </div>
</div>
</body>
</html>
