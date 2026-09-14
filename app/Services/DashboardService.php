<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\TestAttempt;
use App\Models\Battle;
use App\Models\StudySession;
use App\Models\PointLog;
use App\Services\PointService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get all dashboard data for the user
     */
    public function getDashboardData(User $user): array
    {
        return [
            'user_stats' => $this->getUserStats($user),
            'percentile' => $this->getUserPercentile($user),
            'monthly_ranking' => $this->getMonthlyRanking($user),
            'study_progress' => $this->getStudyProgress($user),
            'weekly_schedule' => $this->getWeeklySchedule($user),
            'live_classes' => $this->getLiveClassesThisWeek($user),
            'study_streak' => $this->getStudyStreak($user),
            'block_progress' => $this->getBlockProgress($user),
            'recent_achievements' => $this->getRecentAchievements($user),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($user),
            'assigned_teacher' => $this->getAssignedTeacher($user),
        ];
    }

    /**
     * Get assigned tutor info
     */
    public function getAssignedTeacher(User $user): ?array
    {
        $teacher = $user->assignedTeacher;
        if (!$teacher) {
            return null;
        }
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'avatar_url' => $teacher->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($teacher->name),
        ];
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user): array
    {
        return [
            'points' => $user->points ?? 0,
            'level' => $user->level ?? 1,
            'rank_name' => $user->getRankName(),
            'battles_won' => $user->battles_won ?? 0,
            'tests_completed' => TestAttempt::where('user_id', $user->id)->whereNotNull('completed_at')->count(),
            'topics_completed' => $user->completedTopics()->count(),
            'total_study_time' => $this->getTotalStudyTime($user),
        ];
    }

    /**
     * Get user percentile compared to others
     */
    public function getUserPercentile(User $user): array
    {
        $userPoints = $user->points ?? 0;
        $totalUsers = User::role('Student')->count();

        if ($totalUsers <= 1) {
            return [
                'percentile' => 100,
                'rank_position' => 1,
                'total_users' => $totalUsers,
                'message' => '¡Eres el primero!',
            ];
        }

        $usersBelow = User::role('Student')->where('points', '<', $userPoints)->count();
        $percentile = round(($usersBelow / ($totalUsers - 1)) * 100);
        $rankPosition = User::role('Student')->where('points', '>', $userPoints)->count() + 1;

        return [
            'percentile' => $percentile,
            'rank_position' => $rankPosition,
            'total_users' => $totalUsers,
            'message' => $percentile >= 90 ? '¡Eres un Top Performer!' :
                        ($percentile >= 75 ? '¡Muy buen trabajo!' :
                        ($percentile >= 50 ? 'Por encima de la media' : 'Sigue esforzándote')),
        ];
    }

    /**
     * Get monthly ranking
     */
    public function getMonthlyRanking(User $user): array
    {
        $pointService = app(PointService::class);
        $topUsers = $pointService->getMonthlyRanking(10);

        // Find user's monthly points and position
        $userMonthlyPoints = PointLog::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $userPosition = User::join('point_logs', 'users.id', '=', 'point_logs.user_id')
            ->whereMonth('point_logs.created_at', now()->month)
            ->whereYear('point_logs.created_at', now()->year)
            ->role('Student')
            ->groupBy('users.id')
            ->select('users.id', DB::raw('SUM(point_logs.amount) as total'))
            ->having('total', '>', $userMonthlyPoints)
            ->get()
            ->count() + 1;

        return [
            'top_users' => $topUsers,
            'user_position' => $userPosition,
            'month' => Carbon::now()->translatedFormat('F Y'),
        ];
    }

    /**
     * Get study progress by phase
     */
    public function getStudyProgress(User $user): array
    {
        $courses = Course::with(['blocks.topics'])->get();

        $progress = [];
        foreach ($courses as $course) {
            $courseProgress = [];

            // Group blocks by phase
            $phases = [
                'phase_1' => ['min' => 1, 'max' => 4],
                'phase_2' => ['min' => 5, 'max' => 8],
                'phase_3' => ['min' => 9, 'max' => 12],
            ];

            foreach ($phases as $phaseName => $range) {
                $phaseBlocks = $course->blocks->filter(function($block) use ($range) {
                    return $block->order >= $range['min'] && $block->order <= $range['max'];
                });

                $totalTopics = 0;
                $completedTopics = 0;

                foreach ($phaseBlocks as $block) {
                    $blockTotal = $block->topics->count();
                    $blockCompleted = $user->completedTopics()
                        ->whereIn('topics.id', $block->topics->pluck('id'))
                        ->count();

                    $totalTopics += $blockTotal;
                    $completedTopics += $blockCompleted;
                }

                $percentage = $totalTopics > 0 ? round(($completedTopics / $totalTopics) * 100) : 0;

                $courseProgress[$phaseName] = [
                    'completed' => $completedTopics,
                    'total' => $totalTopics,
                    'percentage' => $percentage,
                ];
            }

            $progress[$course->id] = [
                'course_name' => $course->title,
                'phases' => $courseProgress,
            ];
        }

        return $progress;
    }

    /**
     * Get weekly schedule/study plan
     */
    public function getWeeklySchedule(User $user): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $scheduleItems = [];

        if (method_exists($user, 'schedules')) {
            $officialSchedule = $user->schedules()
                ->where('is_official', true)
                ->whereBetween('start_time', [$weekStart, $weekEnd])
                ->with('topic')
                ->get();

            foreach ($officialSchedule as $item) {
                $scheduleItems[] = [
                    'date' => $item->start_time,
                    'type' => 'official',
                    'title' => $item->topic->title ?? 'Estudio programado',
                    'completed' => $item->is_completed,
                ];
            }
        }

        $recommendedTopics = $this->getRecommendedTopics($user, 5);

        return [
            'schedule_items' => $scheduleItems,
            'recommended_topics' => $recommendedTopics,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
        ];
    }

    /**
     * Get live classes for this week
     */
    public function getLiveClassesThisWeek(User $user): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        return [
            'classes' => [],
            'has_classes' => false,
            'week_start' => $weekStart->format('d/m/Y'),
            'week_end' => $weekEnd->format('d/m/Y'),
        ];
    }

    /**
     * Get study streak
     */
    public function getStudyStreak(User $user): array
    {
        $streak = 0;
        $date = Carbon::today();

        while (true) {
            $hasActivity = StudySession::where('user_id', $user->id)
                ->where('completed', true)
                ->whereDate('ended_at', $date)
                ->exists();

            if (!$hasActivity) {
                $hasActivity = $user->completedTopics()
                    ->whereDate('completed_at', $date)
                    ->exists();
            }

            if (!$hasActivity) {
                break;
            }

            $streak++;
            $date->subDay();
        }

        $studiedToday = StudySession::where('user_id', $user->id)
            ->where('completed', true)
            ->whereDate('ended_at', Carbon::today())
            ->exists();

        return [
            'current_streak' => $streak,
            'studied_today' => $studiedToday,
            'best_streak' => $streak,
        ];
    }

    /**
     * Get progress by blocks
     */
    public function getBlockProgress(User $user): array
    {
        $courses = Course::where('is_active', true)->with(['blocks.topics'])->get();
        $completedTopicIds = $user->completedTopics()->pluck('topics.id')->toArray();

        $progress = [];
        foreach ($courses as $course) {
            foreach ($course->blocks as $block) {
                $totalTopics = $block->topics->count();
                $completedTopics = $block->topics->whereIn('id', $completedTopicIds)->count();

                $percentage = $totalTopics > 0 ? round(($completedTopics / $totalTopics) * 100) : 0;

                $progress[] = [
                    'block_id' => $block->id,
                    'block_name' => $block->title,
                    'course_name' => $course->title,
                    'completed' => $completedTopics,
                    'total' => $totalTopics,
                    'percentage' => $percentage,
                ];
            }
        }

        return $progress;
    }

    /**
     * Get recent achievements
     */
    public function getRecentAchievements(User $user): array
    {
        $achievements = [];

        if (($user->level ?? 1) >= 2) {
            $achievements[] = [
                'type' => 'level',
                'title' => 'Nivel ' . $user->level . ' alcanzado',
                'description' => 'Has subido al nivel ' . $user->getRankName(),
                'date' => $user->updated_at,
            ];
        }

        if (($user->battles_won ?? 0) >= 1) {
            $achievements[] = [
                'type' => 'battle',
                'title' => 'Primera victoria',
                'description' => 'Ganaste tu primera batalla',
                'date' => null,
            ];
        }

        return $achievements;
    }

    /**
     * Get upcoming deadlines
     */
    public function getUpcomingDeadlines(User $user): array
    {
        $deadlines = [];

        if (method_exists($user, 'subscription') || $user->relationLoaded('subscription')) {
            $subscription = $user->subscription;
            if ($subscription && $subscription->ends_at && $subscription->ends_at->isFuture()) {
                $daysRemaining = now()->diffInDays($subscription->ends_at, false);

                if ($daysRemaining <= 7) {
                    $deadlines[] = [
                        'type' => 'subscription',
                        'title' => 'Renovación de suscripción',
                        'date' => $subscription->ends_at,
                        'days_remaining' => $daysRemaining,
                        'urgent' => $daysRemaining <= 3,
                    ];
                }
            }
        }

        return $deadlines;
    }

    /**
     * Get total study time in hours
     */
    private function getTotalStudyTime(User $user): float
    {
        $totalMinutes = StudySession::where('user_id', $user->id)
            ->where('completed', true)
            ->whereNotNull('ended_at')
            ->sum('duration_minutes');

        return round($totalMinutes / 60, 1);
    }

    /**
     * Get recommended topics to study
     */
    private function getRecommendedTopics(User $user, int $limit = 5): array
    {
        $incompleteTopics = Topic::whereDoesntHave('completedByUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('block.course', function ($query) {
                $query->where('is_active', true);
            })
            ->with('block')
            ->orderBy('order')
            ->limit($limit)
            ->get();

        $recommended = [];
        foreach ($incompleteTopics as $topic) {
            $recommended[] = [
                'id' => $topic->id,
                'slug' => $topic->slug, // Fixed: include slug for routing
                'name' => $topic->title,
                'block_name' => $topic->block->title ?? 'General',
                'estimated_time' => 30,
            ];
        }

        return $recommended;
    }
}
