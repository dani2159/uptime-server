<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannel;
use App\Models\OnCallSchedule;
use App\Models\OnCallShift;
use Illuminate\Http\Request;

class OnCallScheduleController extends Controller
{
    public function index()
    {
        $schedules = OnCallSchedule::with('shifts')->orderBy('name')->get();
        return view('on-call.index', compact('schedules'));
    }

    public function create()
    {
        $channels = NotificationChannel::where('is_active', true)->orderBy('name')->get();
        return view('on-call.create', compact('channels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);
        $schedule = OnCallSchedule::create($data);

        $this->syncShifts($schedule, $request->input('shifts', []));
        return redirect()->route('on-call.index')->with('success', 'Jadwal On-Call dibuat.');
    }

    public function edit($id)
    {
        $schedule = OnCallSchedule::with('shifts.channel')->findOrFail($id);
        $channels = NotificationChannel::where('is_active', true)->orderBy('name')->get();
        return view('on-call.edit', compact('schedule', 'channels'));
    }

    public function update(Request $request, $id)
    {
        $schedule = OnCallSchedule::findOrFail($id);
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);
        $schedule->update($data);
        $schedule->shifts()->delete();
        $this->syncShifts($schedule, $request->input('shifts', []));
        return redirect()->route('on-call.index')->with('success', 'Jadwal On-Call diperbarui.');
    }

    public function destroy($id)
    {
        $schedule = OnCallSchedule::findOrFail($id);
        $schedule->shifts()->delete();
        $schedule->delete();
        return redirect()->route('on-call.index')->with('success', 'Jadwal dihapus.');
    }

    private function syncShifts(OnCallSchedule $schedule, array $shifts): void
    {
        foreach ($shifts as $shift) {
            if (empty($shift['name'])) continue;
            OnCallShift::create([
                'schedule_id'  => $schedule->id,
                'name'         => $shift['name'],
                'day_of_week'  => $shift['day_of_week'] ?? null,
                'start_time'   => $shift['start_time'] ?? '08:00',
                'end_time'     => $shift['end_time'] ?? '17:00',
                'channel_id'   => $shift['channel_id'] ?? null,
                'contact_info' => $shift['contact_info'] ?? null,
            ]);
        }
    }
}
