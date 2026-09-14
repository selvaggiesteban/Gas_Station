<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Topic;
use App\Http\Requests\StorePersonalScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $personalSchedules = $user->schedules()
            ->where('is_official', false)
            ->with('topic.block.course')
            ->orderBy('position', 'asc')
            ->get();

        $officialSchedules = $user->schedules()
            ->where('is_official', true)
            ->with('topic.block.course')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('schedule.index', compact('personalSchedules', 'officialSchedules'));
    }

    public function personal()
    {
        $user = Auth::user();
        $personalSchedules = $user->schedules()
            ->where('is_official', false)
            ->with('topic.block.course')
            ->orderBy('position', 'asc')
            ->get();

        $topics = Topic::with('block.course')->whereHas('block')->orderBy('title')->get();

        return view('schedule.personal', compact('personalSchedules', 'topics'));
    }

    public function official()
    {
        $user = Auth::user();
        $officialSchedules = $user->schedules()
            ->where('is_official', true)
            ->with('topic.block.course')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('schedule.official', compact('officialSchedules'));
    }

    public function store(StorePersonalScheduleRequest $request)
    {
        Auth::user()->schedules()->create([
            'title' => $request->title,
            'description' => $request->description,
            'topic_id' => $request->topic_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_official' => false,
        ]);

        return redirect()->back()->with('success', 'Tema añadido a tu cronograma personal.');
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        $schedule->update($request->validated());

        return redirect()->back()->with('success', 'Cronograma actualizado.');
    }

    public function destroy(Schedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        $schedule->delete();

        return redirect()->back()->with('success', 'Tema quitado del cronograma.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'exists:schedules,id'
        ]);

        foreach ($request->items as $index => $id) {
            Schedule::where('id', $id)
                ->where('user_id', Auth::id())
                ->update(['position' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function study(Schedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        return redirect()->route('temas.ver', $schedule->topic);
    }
}
