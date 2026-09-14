<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the user's detailed progress.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get base dashboard data
        $progressData = [
            'stats' => $this->dashboardService->getUserStats($user),
            'percentile' => $this->dashboardService->getUserPercentile($user),
            'block_progress' => $this->dashboardService->getBlockProgress($user),
            'study_progress' => $this->dashboardService->getStudyProgress($user),
            'streak' => $this->dashboardService->getStudyStreak($user),
        ];

        // Add detailed test statistics
        $attempts = TestAttempt::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get();

        $progressData['test_stats'] = [
            'total_attempts' => $attempts->count(),
            'passed_attempts' => $attempts->where('passed', true)->count(),
            'average_score' => $attempts->avg('score') ?? 0,
            'recent_attempts' => $attempts->take(5),
        ];

        return view('progress.index', compact('progressData'));
    }

    /**
     * Download progress report in PDF.
     */
    public function exportPdf(Request $request, \App\Services\MetricsExportService $exportService)
    {
        $user = Auth::user();
        $pdf = $exportService->generatePdf($user);
        
        return $pdf->download("Academicus_Progreso_{$user->id}.pdf");
    }
}
