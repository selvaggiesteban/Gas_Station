<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\AcademicStatsService;
use App\Models\MotivationalQuote;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;
    private AcademicStatsService $statsService;

    public function __construct(DashboardService $dashboardService, AcademicStatsService $statsService)
    {
        $this->dashboardService = $dashboardService;
        $this->statsService = $statsService;
    }

    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('Admin')) {
            return app(\App\Http\Controllers\Admin\DashboardController::class)->index();
        } elseif ($user->hasRole('Teacher')) {
            return view('teacher.dashboard');
        } elseif ($user->hasRole('Content Manager')) {
            return view('content-manager.dashboard');
        }

        $dashboardData = $this->dashboardService->getDashboardData($user);
        $academicStats = [
            'percentile' => $this->statsService->calculatePercentile($user),
            'average_speed' => $this->statsService->getAverageSpeed($user),
            'doubt_stats' => $this->statsService->getDoubtStats($user),
        ];

        return view('dashboard', compact('dashboardData', 'academicStats'));
    }

    /**
     * Get dashboard data via AJAX (for dynamic updates)
     */
    public function getData()
    {
        $user = Auth::user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        return response()->json([
            'success' => true,
            'data' => $dashboardData,
        ]);
    }

    /**
     * Get specific widget data
     */
    public function getWidget(string $widget)
    {
        $user = Auth::user();

        $data = match($widget) {
            'stats' => $this->dashboardService->getUserStats($user),
            'percentile' => $this->dashboardService->getUserPercentile($user),
            'ranking' => $this->dashboardService->getMonthlyRanking($user),
            'streak' => $this->dashboardService->getStudyStreak($user),
            'progress' => $this->dashboardService->getBlockProgress($user),
            'schedule' => $this->dashboardService->getWeeklySchedule($user),
            default => null,
        };

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'Widget no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}