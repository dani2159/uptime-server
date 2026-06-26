<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\SlaContract;
use Illuminate\Http\Request;

class SlaContractController extends Controller
{
    public function index()
    {
        $contracts = SlaContract::with('monitor')->orderBy('created_at', 'desc')->paginate(20);
        return view('sla.index', compact('contracts'));
    }

    public function create()
    {
        $monitors = Monitor::where('is_active', true)->orderBy('name')->get();
        return view('sla.create', compact('monitors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'monitor_id'          => 'required|exists:monitors,id',
            'name'                => 'required|string|max:100',
            'target_uptime'       => 'required|numeric|min:0|max:100',
            'period_start'        => 'required|date',
            'period_end'          => 'required|date|after:period_start',
            'downtime_budget_min' => 'nullable|integer|min:0',
            'notes'               => 'nullable|string|max:500',
        ]);
        SlaContract::create($data);
        return redirect()->route('sla.index')->with('success', 'SLA Contract dibuat.');
    }

    public function edit($id)
    {
        $contract = SlaContract::findOrFail($id);
        $monitors = Monitor::where('is_active', true)->orderBy('name')->get();
        return view('sla.edit', compact('contract', 'monitors'));
    }

    public function update(Request $request, $id)
    {
        $contract = SlaContract::findOrFail($id);
        $data = $request->validate([
            'monitor_id'          => 'required|exists:monitors,id',
            'name'                => 'required|string|max:100',
            'target_uptime'       => 'required|numeric|min:0|max:100',
            'period_start'        => 'required|date',
            'period_end'          => 'required|date|after:period_start',
            'downtime_budget_min' => 'nullable|integer|min:0',
            'notes'               => 'nullable|string|max:500',
        ]);
        $contract->update($data);
        return redirect()->route('sla.index')->with('success', 'SLA Contract diperbarui.');
    }

    public function destroy($id)
    {
        SlaContract::findOrFail($id)->delete();
        return redirect()->route('sla.index')->with('success', 'SLA Contract dihapus.');
    }
}
