<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorTemplate;
use App\Models\NotificationChannel;
use Illuminate\Http\Request;

class MonitorTemplateController extends Controller
{
    public function index()
    {
        $templates = MonitorTemplate::orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'icon'     => 'nullable|string|max:50',
            'config'   => 'required|string',
        ]);

        $config = json_decode($data['config'], true);
        if (!is_array($config)) {
            return back()->withErrors(['config' => 'Config harus JSON valid'])->withInput();
        }

        MonitorTemplate::create([
            'name'       => $data['name'],
            'category'   => $data['category'],
            'icon'       => $data['icon'],
            'config'     => $config,
            'is_builtin' => false,
        ]);
        return redirect()->route('templates.index')->with('success', 'Template dibuat.');
    }

    public function destroy($id)
    {
        $t = MonitorTemplate::findOrFail($id);
        if ($t->is_builtin) {
            return back()->with('error', 'Template bawaan tidak dapat dihapus.');
        }
        $t->delete();
        return redirect()->route('templates.index')->with('success', 'Template dihapus.');
    }

    public function apply(Request $request, MonitorTemplate $template)
    {
        $config = $template->config;
        $monitor = Monitor::create(array_merge([
            'name'           => $request->input('name', $template->name),
            'url'            => $request->input('url', ''),
            'is_active'      => false,
            'last_status'    => 'pending',
            'check_interval' => 5,
            'timeout'        => 10,
            'retry_count'    => 3,
        ], $config));

        return redirect()->route('monitors.edit', $monitor)->with('success', "Monitor dari template \"{$template->name}\" dibuat. Lengkapi URL dan konfigurasi.");
    }
}
