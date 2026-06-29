<?php

namespace App\Http\Controllers;

use App\Models\PagespeedMonitor;
use App\Services\PagespeedService;
use Illuminate\Http\Request;

class PagespeedController extends Controller
{
    public function index()
    {
        $monitors = PagespeedMonitor::withCount('checks')
            ->with(['checks' => fn($q) => $q->latest('checked_at')->limit(1)])
            ->orderBy('name')
            ->get();

        return view('pagespeed.index', compact('monitors'));
    }

    public function create()
    {
        return view('pagespeed.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'url'              => 'required|url|max:500',
            'strategy'         => 'required|in:mobile,desktop',
            'interval_minutes' => 'required|integer|min:30|max:1440',
            'api_key'          => 'nullable|string|max:255',
            'is_active'        => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        PagespeedMonitor::create($data);

        return redirect()->route('pagespeed.index')->with('success', 'Monitor Pagespeed berhasil dibuat.');
    }

    public function show(PagespeedMonitor $pagespeed)
    {
        $checks = $pagespeed->checks()
            ->latest('checked_at')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $latest = $checks->last();

        $chartLabels = $checks->map(fn($c) => $c->checked_at->format('d/m H:i'))->toJson();
        $chartPerf   = $checks->map(fn($c) => $c->performance_score)->toJson();
        $chartA11y   = $checks->map(fn($c) => $c->accessibility_score)->toJson();
        $chartBp     = $checks->map(fn($c) => $c->best_practices_score)->toJson();
        $chartSeo    = $checks->map(fn($c) => $c->seo_score)->toJson();

        $totalChecks = $pagespeed->checks()->count();
        $lastCheck   = $pagespeed->checks()->latest('checked_at')->first();

        return view('pagespeed.show', compact(
            'pagespeed', 'checks', 'latest', 'totalChecks', 'lastCheck',
            'chartLabels', 'chartPerf', 'chartA11y', 'chartBp', 'chartSeo'
        ));
    }

    public function edit(PagespeedMonitor $pagespeed)
    {
        return view('pagespeed.edit', compact('pagespeed'));
    }

    public function update(Request $request, PagespeedMonitor $pagespeed)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'url'              => 'required|url|max:500',
            'strategy'         => 'required|in:mobile,desktop',
            'interval_minutes' => 'required|integer|min:30|max:1440',
            'api_key'          => 'nullable|string|max:255',
            'is_active'        => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $pagespeed->update($data);

        return redirect()->route('pagespeed.show', $pagespeed)->with('success', 'Monitor berhasil diperbarui.');
    }

    public function destroy(PagespeedMonitor $pagespeed)
    {
        $pagespeed->delete();
        return redirect()->route('pagespeed.index')->with('success', 'Monitor dihapus.');
    }

    public function checkNow(PagespeedMonitor $pagespeed, PagespeedService $service)
    {
        $check = $service->check($pagespeed);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => ! $check->error_message,
                'check'   => $check,
            ]);
        }

        return redirect()->route('pagespeed.show', $pagespeed)
            ->with($check->error_message ? 'error' : 'success',
                $check->error_message ?? 'Cek berhasil dijalankan.');
    }

    public function toggle(PagespeedMonitor $pagespeed)
    {
        $pagespeed->update(['is_active' => ! $pagespeed->is_active]);
        return back()->with('success', 'Status monitor diperbarui.');
    }
}
