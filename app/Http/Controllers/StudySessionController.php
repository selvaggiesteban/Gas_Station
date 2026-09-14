<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use App\Models\Schedule;
use App\Models\Topic;
use App\Http\Requests\StartStudySessionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudySessionController extends Controller
{
    /**
     * Start a new study session (Pomodoro)
     */
    public function start(StartStudySessionRequest $request)
    {
        $type = $request->type;
        $duration = $request->duration_minutes ?? StudySession::DURATIONS[$type];

        // End any active sessions first
        $this->endActiveSessions();

        $session = StudySession::create([
            'user_id' => Auth::id(),
            'schedule_id' => $request->schedule_id,
            'topic_id' => $request->topic_id,
            'type' => $type,
            'duration_minutes' => $duration,
            'started_at' => now(),
            'completed' => false,
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
            'remaining_seconds' => $session->getRemainingSeconds(),
        ]);
    }

    /**
     * Get current active session
     */
    public function current()
    {
        $session = StudySession::where('user_id', Auth::id())
            ->whereNull('ended_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'active' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'active' => true,
            'session' => $session,
            'elapsed_seconds' => $session->getElapsedSeconds(),
            'remaining_seconds' => $session->getRemainingSeconds(),
            'is_completed' => $session->isTimeCompleted(),
        ]);
    }

    /**
     * Complete a study session
     */
    public function complete(Request $request, StudySession $session)
    {
        $user = Auth::user();
        if ($session->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $session->markAsCompleted($validated['notes'] ?? null);

        // Award study points if it was a completed pomodoro
        if ($session->type === StudySession::TYPE_POMODORO && $session->completed) {
            $user->increment('total_study_minutes', $session->duration_minutes);
            
            $totalHours = $user->total_study_minutes / 60;
            app(\App\Services\PointService::class)->awardForStudyTime($user, $totalHours);
        }

        return response()->json([
            'success' => true,
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Cancel/stop active session without completing
     */
    public function cancel()
    {
        $session = StudySession::where('user_id', Auth::id())
            ->whereNull('ended_at')
            ->first();

        if ($session) {
            $session->update(['ended_at' => now()]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get study statistics
     */
    public function stats()
    {
        $userId = Auth::id();

        $today = StudySession::getTotalStudyMinutesForUser($userId, 'today');
        $week = StudySession::getTotalStudyMinutesForUser($userId, 'week');
        $month = StudySession::getTotalStudyMinutesForUser($userId, 'month');

        $pomodorosToday = StudySession::getCompletedPomodorosCount($userId, 'today');
        $pomodorosWeek = StudySession::getCompletedPomodorosCount($userId, 'week');
        $pomodorosMonth = StudySession::getCompletedPomodorosCount($userId, 'month');

        // Recent sessions
        $recentSessions = StudySession::where('user_id', $userId)
            ->with(['schedule', 'topic'])
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'today_minutes' => $today,
                'week_minutes' => $week,
                'month_minutes' => $month,
                'today_pomodoros' => $pomodorosToday,
                'week_pomodoros' => $pomodorosWeek,
                'month_pomodoros' => $pomodorosMonth,
            ],
            'recent_sessions' => $recentSessions,
        ]);
    }

    /**
     * Get daily study data for calendar view
     */
    public function calendar(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        $month = $validated['month'] ?? now()->month;
        $year = $validated['year'] ?? now()->year;

        $sessions = StudySession::where('user_id', Auth::id())
            ->where('type', StudySession::TYPE_POMODORO)
            ->where('completed', true)
            ->whereMonth('started_at', $month)
            ->whereYear('started_at', $year)
            ->selectRaw('DATE(started_at) as date, SUM(duration_minutes) as total_minutes, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * End any active sessions for current user
     */
    private function endActiveSessions(): void
    {
        StudySession::where('user_id', Auth::id())
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);
    }
}
