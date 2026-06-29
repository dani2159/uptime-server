<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportExportController extends Controller
{
    public function page()
    {
        return view('monitors.import');
    }

    public function export()
    {
        $monitors = \App\Models\Monitor::with('tags')->get()->map(fn($m) => [
            'name' => $m->name, 'url' => $m->url, 'type' => $m->type,
            'check_interval' => $m->check_interval, 'timeout' => $m->timeout,
            'retry_count' => $m->retry_count, 'keyword' => $m->keyword,
            'http_method' => $m->http_method, 'request_body' => $m->request_body,
            'auth_type' => $m->auth_type, 'auth_username' => $m->auth_username,
            'custom_headers' => $m->custom_headers,
            'accepted_status_codes' => $m->accepted_status_codes,
            'ignore_tls_error' => $m->ignore_tls_error,
            'follow_redirects' => $m->follow_redirects,
            'custom_user_agent' => $m->custom_user_agent,
            'body_assertion_path' => $m->body_assertion_path,
            'body_assertion_value' => $m->body_assertion_value,
            'body_assertion_op' => $m->body_assertion_op,
            'response_time_warning' => $m->response_time_warning,
            'notes' => $m->notes, 'runbook_url' => $m->runbook_url,
            'environment' => $m->environment,
            'flap_detection' => $m->flap_detection,
            'tags' => $m->tags->pluck('name'),
        ]);

        return response()->json($monitors)
            ->header('Content-Disposition', 'attachment; filename="watchtower-monitors-' . now()->format('Ymd') . '.json"');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:json|max:2048']);
        $data = json_decode($request->file('file')->get(), true);
        if (!is_array($data)) return back()->withErrors(['file' => 'Format JSON tidak valid']);

        $imported = 0;
        foreach ($data as $row) {
            $tags = $row['tags'] ?? [];
            unset($row['tags']);
            $row['is_active'] = false; // import in disabled state
            $monitor = \App\Models\Monitor::create($row + ['last_status' => 'pending']);
            if ($tags) {
                $tagIds = \App\Models\Tag::whereIn('name', $tags)->pluck('id');
                $monitor->tags()->sync($tagIds);
            }
            $imported++;
        }
        \App\Services\AuditService::log('monitor.import', "Import {$imported} monitor dari JSON");
        return redirect()->route('monitors.index')->with('success', "{$imported} monitor berhasil diimport.");
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $csv    = array_map('str_getcsv', file($request->file('file')->getPathname()));
        $header = array_shift($csv);
        $imported = 0;
        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;
            $data = array_combine($header, $row);
            \App\Models\Monitor::create([
                'name'           => $data['name'] ?? 'Monitor ' . ($imported + 1),
                'url'            => $data['url'] ?? '',
                'type'           => $data['type'] ?? 'http',
                'check_interval' => (int)($data['check_interval'] ?? 5),
                'timeout'        => (int)($data['timeout'] ?? 10),
                'retry_count'    => (int)($data['retry_count'] ?? 3),
                'environment'    => $data['environment'] ?? null,
                'is_active'      => false,
                'last_status'    => 'pending',
            ]);
            $imported++;
        }
        \App\Services\AuditService::log('monitor.import_csv', "Import {$imported} monitor dari CSV");
        return redirect()->route('monitors.index')->with('success', "{$imported} monitor berhasil diimport dari CSV.");
    }

    public function smokeTest()
    {
        // Trigger immediate check of all active monitors, return results
        $monitors = \App\Models\Monitor::where('is_active', true)->get();
        $checker  = app(\App\Services\UptimeChecker::class);
        $results  = [];
        foreach ($monitors as $m) {
            $r = $checker->check($m);
            $results[] = ['name' => $m->name, 'url' => $m->url, 'status' => $r['status'], 'rt' => $r['response_time']];
        }
        $passed = collect($results)->where('status', 'up')->count();
        $total  = count($results);
        \App\Services\AuditService::log('monitor.smoke_test', "Smoke test: {$passed}/{$total} passed");

        if (request()->expectsJson()) {
            return response()->json(['passed' => $passed, 'total' => $total, 'results' => $results]);
        }

        return view('monitors.smoke-test', compact('passed', 'total', 'results'));
    }
}
