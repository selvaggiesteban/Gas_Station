<?php

namespace App\Http\Controllers\ContentManager;

use App\Http\Controllers\Controller;
use App\Models\AcademySchedule;
use App\Models\Topic;
use App\Models\User;
use App\Http\Requests\ContentManager\StoreAcademyScheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademyScheduleController extends Controller
{
    public function index()
    {
        $officialSchedules = AcademySchedule::where('is_from_academy', true)
            ->latest('start_time')
            ->paginate(15);

        return view('content-manager.schedules.index', compact('officialSchedules'));
    }

    public function create()
    {
        $topics = Topic::with('block.course')->get();
        return view('content-manager.schedules.create', compact('topics'));
    }

    public function store(StoreAcademyScheduleRequest $request)
    {
        $data = $request->validated();
        $data['is_from_academy'] = true;
        $data['user_id'] = Auth::id(); // Admin/Content Manager who created it

        $schedule = AcademySchedule::create($data);

        if ($request->sync_to_students) {
            try {
                $this->syncToStudents($schedule);
            } catch (\Exception $e) {
                \Log::error('Schedule sync failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('content-manager.schedules.index')
            ->with('success', 'Evento oficial creado correctamente.');
    }

    public function edit(AcademySchedule $schedule)
    {
        $topics = Topic::with('block.course')->get();
        return view('content-manager.schedules.edit', compact('schedule', 'topics'));
    }

    public function update(StoreAcademyScheduleRequest $request, AcademySchedule $schedule)
    {
        $schedule->update($request->validated());

        return redirect()->route('content-manager.schedules.index')
            ->with('success', 'Evento oficial actualizado.');
    }

    public function destroy(AcademySchedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('content-manager.schedules.index')
            ->with('success', 'Evento eliminado.');
    }

    public function syncToStudents(AcademySchedule $schedule)
    {
        // Get students with active subscriptions
        $students = User::role('Student')->get();

        foreach ($students as $student) {
            // Using DB directly to bypass any model restrictions if necessary, 
            // but first trying proper model assignment
            \App\Models\Schedule::updateOrCreate([
                'title' => $schedule->title,
                'start_time' => $schedule->start_time,
                'user_id' => $student->id
            ], [
                'description' => $schedule->description,
                'end_time' => $schedule->end_time,
                'is_official' => true,
                'topic_id' => $schedule->topic_id,
                'position' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Sincronización completada con ' . $students->count() . ' alumnos.');
    }
}
