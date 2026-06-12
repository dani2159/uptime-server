<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'bpjs_check_interval' => Setting::get('bpjs_check_interval', 10),
            'bpjs_auto_check'     => Setting::get('bpjs_auto_check', 1),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'bpjs_check_interval' => 'required|integer|min:5|max:1440',
            'bpjs_auto_check'     => 'nullable|boolean',
        ]);

        Setting::set('bpjs_check_interval', $data['bpjs_check_interval']);
        Setting::set('bpjs_auto_check',     isset($data['bpjs_auto_check']) ? '1' : '0');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
