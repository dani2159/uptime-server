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
        $host = $request->getHost();
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
}
