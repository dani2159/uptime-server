<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorLog;
use App\Models\StatusPage;
use App\Services\ApiHealthRegistry;
use Illuminate\Http\Request;

class StatusPageController extends Controller
{
    public function __construct(private ApiHealthRegistry $registry) {}

    public function index()
    {
        $pages = StatusPage::latest()->paginate(20);
        return view('status-pages.index', compact('pages'));
    }

    public function create()
    {
        $monitors = Monitor::orderBy('name')->get(['id', 'name', 'last_status']);
        $services = $this->registry->all();
        return view('status-pages.create', compact('monitors', 'services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug'           => 'required|string|max:50|unique:status_pages,slug|regex:/^[a-z0-9-]+$/',
            'title'          => 'required|string|max:100',
            'description'    => 'nullable|string|max:500',
            'is_public'      => 'boolean',
            'sections_json'  => 'required|string',
            'service_keys'   => 'array',
            'service_keys.*' => 'string',
            'custom_domain'  => 'nullable|string|max:255|unique:status_pages,custom_domain',
        ]);

        $sections = json_decode($request->sections_json, true) ?? [];
        $allIds   = collect($sections)
            ->flatMap(fn($s) => array_map('intval', $s['monitor_ids'] ?? []))
            ->unique()->values()->toArray();

        StatusPage::create([
            'slug'          => $request->slug,
            'title'         => $request->title,
            'description'   => $request->description,
            'is_public'     => $request->boolean('is_public', true),
            'sections'      => $sections,
            'monitor_ids'   => $allIds,
            'service_keys'  => $request->input('service_keys', []),
            'custom_domain' => $request->input('custom_domain') ?: null,
        ]);

        return redirect()->route('status-pages.index')->with('success', 'Status page berhasil dibuat.');
    }

    public function edit(StatusPage $statusPage)
    {
        $monitors = Monitor::orderBy('name')->get(['id', 'name', 'last_status']);
        $services = $this->registry->all();
        return view('status-pages.edit', compact('statusPage', 'monitors', 'services'));
    }

    public function update(Request $request, StatusPage $statusPage)
    {
        $request->validate([
            'slug'           => 'required|string|max:50|unique:status_pages,slug,' . $statusPage->id . '|regex:/^[a-z0-9-]+$/',
            'title'          => 'required|string|max:100',
            'description'    => 'nullable|string|max:500',
            'is_public'      => 'boolean',
            'sections_json'  => 'required|string',
            'service_keys'   => 'array',
            'service_keys.*' => 'string',
            'custom_domain'  => 'nullable|string|max:255|unique:status_pages,custom_domain,' . $statusPage->id,
        ]);

        $sections = json_decode($request->sections_json, true) ?? [];
        $allIds   = collect($sections)
            ->flatMap(fn($s) => array_map('intval', $s['monitor_ids'] ?? []))
            ->unique()->values()->toArray();

        $statusPage->update([
            'slug'          => $request->slug,
            'title'         => $request->title,
            'description'   => $request->description,
            'is_public'     => $request->boolean('is_public', true),
            'sections'      => $sections,
            'monitor_ids'   => $allIds,
            'service_keys'  => $request->input('service_keys', []),
            'custom_domain' => $request->input('custom_domain') ?: null,
        ]);

        return redirect()->route('status-pages.index')->with('success', 'Status page berhasil diperbarui.');
    }

    public function destroy(StatusPage $statusPage)
    {
        $statusPage->delete();
        return redirect()->route('status-pages.index')->with('success', 'Status page dihapus.');
    }

    public function show(string $slug)
    {
        $page = StatusPage::where('slug', $slug)->where('is_public', true)->firstOrFail();

        $sectionData = [];
        $sections    = $page->sections ?? [];

        if (!empty($sections)) {
            foreach ($sections as $section) {
                $ids = array_map('intval', $section['monitor_ids'] ?? []);
                if (empty($ids)) {
                    continue;
                }
                $monitors = Monitor::whereIn('id', $ids)
                    ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                    ->get();
                $heartbeats = [];
                foreach ($monitors as $m) {
                    $heartbeats[$m->id] = MonitorLog::where('monitor_id', $m->id)
                        ->latest('checked_at')->limit(90)
                        ->get(['status', 'checked_at', 'response_time'])
                        ->reverse()->values();
                }
                $sectionData[] = [
                    'name'       => $section['name'] ?? 'Layanan',
                    'monitors'   => $monitors,
                    'heartbeats' => $heartbeats,
                ];
            }
        } else {
            // Backward compat: flat monitor_ids
            $monitorIds = array_map('intval', $page->monitor_ids ?? []);
            if (!empty($monitorIds)) {
                $monitors = Monitor::whereIn('id', $monitorIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $monitorIds) . ')')
                    ->get();
                $heartbeats = [];
                foreach ($monitors as $m) {
                    $heartbeats[$m->id] = MonitorLog::where('monitor_id', $m->id)
                        ->latest('checked_at')->limit(90)
                        ->get(['status', 'checked_at', 'response_time'])
                        ->reverse()->values();
                }
                $sectionData[] = ['name' => null, 'monitors' => $monitors, 'heartbeats' => $heartbeats];
            }
        }

        $allMonitors   = collect($sectionData)->flatMap(fn($s) => $s['monitors']);
        $overallStatus = ($allMonitors->isEmpty() || $allMonitors->every(fn($m) => $m->last_status === 'up'))
            ? 'operational'
            : 'degraded';

        $apiServices = [];
        if (!empty($page->service_keys)) {
            $allServices = $this->registry->getCachedAll();
            foreach ($page->service_keys as $key) {
                if (isset($allServices[$key])) {
                    $apiServices[$key] = $allServices[$key];
                }
            }
        }

        return view('status-pages.show', compact('page', 'sectionData', 'overallStatus', 'apiServices'));
    }

    public function showByDomain(\Illuminate\Http\Request $request)
    {
        // Skip DB lookup for static asset paths — avoids unnecessary query on 404 requests
        $path = $request->path();
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?|ttf|eot|map)$/i', $path)) {
            abort(404);
        }

        $host = $request->getHost();
        // Only attempt custom-domain lookup for non-localhost hosts
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            abort(404);
        }

        $page = StatusPage::where('custom_domain', $host)->where('is_public', true)->first();
        if (!$page) {
            abort(404);
        }
        return $this->show($page->slug);
    }

    public function widget(string $slug)
    {
        $page     = StatusPage::where('slug', $slug)->where('is_public', true)->firstOrFail();
        $monitors = Monitor::whereIn('id', $page->allMonitorIds())->get(['id', 'name', 'last_status', 'url']);
        $overall  = ($monitors->isEmpty() || $monitors->every(fn($m) => $m->last_status === 'up'))
            ? 'operational'
            : 'degraded';
        return response()
            ->view('status-pages.widget', compact('page', 'monitors', 'overall'))
            ->header('X-Frame-Options', 'ALLOWALL')
            ->header('Content-Security-Policy', 'frame-ancestors *');
    }

    public function badge(string $slug)
    {
        $page     = StatusPage::where('slug', $slug)->where('is_public', true)->firstOrFail();
        $monitors = Monitor::whereIn('id', $page->allMonitorIds())->get();
        $isOk     = $monitors->isEmpty() || $monitors->every(fn($m) => $m->last_status === 'up');
        $label    = urlencode($page->title);
        $status   = $isOk ? 'operational' : 'degraded';
        $color    = $isOk ? '3fb950' : 'f85149';
        $upCount  = $monitors->where('last_status', 'up')->count();
        $total    = $monitors->count();
        $message  = urlencode("{$upCount}/{$total}");

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="220" height="20">
          <linearGradient id="s" x2="0" y2="100%">
            <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
            <stop offset="1" stop-opacity=".1"/>
          </linearGradient>
          <rect rx="3" width="220" height="20" fill="#555"/>
          <rect rx="3" x="130" width="90" height="20" fill="#{$color}"/>
          <rect rx="3" width="220" height="20" fill="url(#s)"/>
          <g fill="#fff" text-anchor="middle" font-family="sans-serif" font-size="11">
            <text x="65" y="15">{$page->title}</text>
            <text x="175" y="15">{$status} {$upCount}/{$total}</text>
          </g>
        </svg>
        SVG;

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function stream(\Illuminate\Http\Request $request)
    {
        // SSE: max 6 iterations × 15s = 90s max hold per connection.
        // Browser auto-reconnects. Keeps PHP-FPM workers free on shared/sync server.
        return response()->stream(function () {
            $lastSent = [];
            $maxIterations = 6;
            for ($i = 0; $i < $maxIterations; $i++) {
                if (connection_aborted()) break;
                $monitors = Monitor::where('is_active', true)
                    ->get(['id', 'last_status', 'last_response_time', 'health_score'])
                    ->mapWithKeys(fn($m) => [$m->id => [
                        'status' => $m->last_status,
                        'rt'     => $m->last_response_time,
                        'score'  => $m->health_score,
                    ]]);

                $arr = $monitors->toArray();
                if ($arr !== $lastSent) {
                    echo "data: " . json_encode($arr) . "\n\n";
                    ob_flush();
                    flush();
                    $lastSent = $arr;
                }
                sleep(15);
            }
            // Send close signal so browser knows to reconnect cleanly
            echo "event: done\ndata: {}\n\n";
            ob_flush(); flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
