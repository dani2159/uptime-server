<?php

namespace App\Http\Controllers;

use App\Models\BusinessHour;
use Illuminate\Http\Request;

class BusinessHourController extends Controller
{
    public function index()
    {
        $businessHours = BusinessHour::orderBy('day_of_week')->get();
        return view('business-hours.index', compact('businessHours'));
    }

    public function save(Request $request)
    {
        $days = $request->input('days', []);
        foreach ($days as $i => $day) {
            BusinessHour::updateOrCreate(
                ['day_of_week' => (int) $i],
                [
                    'is_working_day' => isset($day['is_working_day']),
                    'open_time'      => $day['open_time'] ?? '08:00',
                    'close_time'     => $day['close_time'] ?? '17:00',
                ]
            );
        }
        return redirect()->route('business-hours.index')->with('success', 'Jam kerja disimpan.');
    }
}
