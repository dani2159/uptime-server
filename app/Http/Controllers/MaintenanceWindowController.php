<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use Illuminate\Http\Request;

class MaintenanceWindowController extends Controller
{
    public function index()
    {
        $windows  = MaintenanceWindow::latest()->paginate(20);
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('maintenance.index', compact('windows', 'monitors'));
    }

    public function create()
    {
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('maintenance.create', compact('monitors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'monitor_ids' => 'nullable|array',
        ]);

        $data['monitor_ids'] = empty($data['monitor_ids']) ? null : $data['monitor_ids'];
        MaintenanceWindow::create($data);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance window berhasil ditambahkan.');
    }

    public function edit(MaintenanceWindow $maintenance)
    {
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('maintenance.edit', compact('maintenance', 'monitors'));
    }

    public function update(Request $request, MaintenanceWindow $maintenance)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'monitor_ids' => 'nullable|array',
        ]);

        $data['monitor_ids'] = empty($data['monitor_ids']) ? null : $data['monitor_ids'];
        $maintenance->update($data);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance window berhasil diperbarui.');
    }

    public function destroy(MaintenanceWindow $maintenance)
    {
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success', 'Maintenance window dihapus.');
    }
}
