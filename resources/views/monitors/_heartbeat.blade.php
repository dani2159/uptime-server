{{--
    Heartbeat bar: 90 kotak kecil status UP/DOWN/PENDING
    @param $logs  -- collection MonitorLog (latest first, akan direverse)
    @param $limit -- jumlah kotak (default 90)
--}}
@php
    $limit  = $limit ?? 90;
    $bars   = $logs->take($limit)->reverse()->values();
    $filled = $bars->count();
    $empty  = max(0, $limit - $filled);
@endphp

<div class="flex items-center gap-0.5 flex-wrap">
    {{-- kotak kosong di kiri (belum ada data) --}}
    @for($i = 0; $i < $empty; $i++)
        <div class="w-2 h-6 rounded-sm bg-gray-200" title="Belum ada data"></div>
    @endfor

    {{-- kotak heartbeat --}}
    @foreach($bars as $log)
        @php
            $color = match($log->status) {
                'up'   => 'bg-green-500',
                'down' => 'bg-red-500',
                default => 'bg-gray-300',
            };
            $time  = $log->checked_at?->format('d M H:i') ?? '-';
            $ms    = $log->response_time ? $log->response_time . 'ms' : '-';
            $label = strtoupper($log->status) . " — {$time} ({$ms})";
        @endphp
        <div class="w-2 h-6 rounded-sm {{ $color }} hover:opacity-75 cursor-default"
             title="{{ $label }}"></div>
    @endforeach
</div>
