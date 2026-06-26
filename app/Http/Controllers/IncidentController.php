<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Monitor;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::with('monitor')->latest('started_at');

        if ($request->filled('monitor_id')) {
            $query->where('monitor_id', $request->monitor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $incidents = $query->paginate(20)->withQueryString();
        $monitors  = Monitor::orderBy('name')->get(['id', 'name']);

        return view('incidents.index', compact('incidents', 'monitors'));
    }

    public function create()
    {
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('incidents.create', compact('monitors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category'        => 'required|in:monitor_downtime,general,client_report,work_order',
            'monitor_id'      => 'required_if:category,monitor_downtime|nullable|exists:monitors,id',
            'title'           => 'required_unless:category,monitor_downtime|nullable|string|max:150',
            'severity'        => 'required|in:low,medium,high,critical',
            'started_at'      => 'required|date',
            'resolved_at'     => 'nullable|date|after:started_at',
            'note'            => 'nullable|string|max:1000',
            'reporter_name'   => 'nullable|string|max:150',
            'reporter_contact'=> 'nullable|string|max:150',
        ]);

        if ($data['category'] !== 'monitor_downtime') {
            $data['monitor_id'] = null;
        }

        $data['is_manual']  = true;
        $data['status']     = !empty($data['resolved_at']) ? 'closed' : 'open';
        $data['duration_seconds'] = !empty($data['resolved_at'])
            ? Carbon::parse($data['started_at'])->diffInSeconds(Carbon::parse($data['resolved_at']))
            : null;

        $incident = Incident::create($data);
        AuditService::log('incident.created', "Insiden \"{$incident->title}\" dibuat");

        return redirect()->route('incidents.index')->with('success', 'Insiden berhasil ditambahkan.');
    }

    public function edit(Incident $incident)
    {
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('incidents.edit', compact('incident', 'monitors'));
    }

    public function update(Request $request, Incident $incident)
    {
        $data = $request->validate([
            'category'        => 'required|in:monitor_downtime,general,client_report,work_order',
            'monitor_id'      => 'required_if:category,monitor_downtime|nullable|exists:monitors,id',
            'title'           => 'required_unless:category,monitor_downtime|nullable|string|max:150',
            'severity'        => 'required|in:low,medium,high,critical',
            'started_at'      => 'required|date',
            'resolved_at'     => 'nullable|date|after:started_at',
            'note'            => 'nullable|string|max:1000',
            'reporter_name'   => 'nullable|string|max:150',
            'reporter_contact'=> 'nullable|string|max:150',
        ]);

        if ($data['category'] !== 'monitor_downtime') {
            $data['monitor_id'] = null;
        }

        $data['status'] = !empty($data['resolved_at']) ? 'closed' : 'open';
        $data['duration_seconds'] = !empty($data['resolved_at'])
            ? Carbon::parse($data['started_at'])->diffInSeconds(Carbon::parse($data['resolved_at']))
            : null;

        $incident->update($data);
        $statusStr = $data['status'] === 'closed' ? 'ditutup' : 'diperbarui';
        AuditService::log('incident.updated', "Insiden \"{$incident->title}\" {$statusStr}");

        return redirect()->route('incidents.index')->with('success', 'Insiden berhasil diperbarui.');
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();
        return redirect()->route('incidents.index')->with('success', 'Insiden dihapus.');
    }

    public function postMortemForm(Incident $incident)
    {
        $pm = $incident->postMortem ?? new \App\Models\IncidentPostMortem(['incident_id' => $incident->id]);
        return view('incidents.post-mortem', compact('incident', 'pm'));
    }

    public function savePostMortem(\Illuminate\Http\Request $request, Incident $incident)
    {
        $data = $request->validate([
            'title'        => 'nullable|string|max:255',
            'timeline'     => 'nullable|string',
            'root_cause'   => 'nullable|string',
            'impact'       => 'nullable|string',
            'action_items' => 'nullable|string',
            'severity'     => 'in:low,medium,high,critical',
            'author'       => 'nullable|string|max:100',
        ]);
        \App\Models\IncidentPostMortem::updateOrCreate(
            ['incident_id' => $incident->id],
            $data
        );
        \App\Services\AuditService::log('incident.post_mortem', "Post-mortem insiden #{$incident->id} disimpan");
        return redirect()->route('incidents.index')->with('success', 'Post-mortem disimpan.');
    }
}
