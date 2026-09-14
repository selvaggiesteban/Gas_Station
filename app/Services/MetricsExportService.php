<?php

namespace App\Services;

use App\Models\User;
use App\Models\TestAttempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class MetricsExportService
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Generate a PDF report of user metrics.
     */
    public function generatePdf(User $user)
    {
        $data = [
            'user' => $user,
            'stats' => $this->dashboardService->getUserStats($user),
            'percentile' => $this->dashboardService->getUserPercentile($user),
            'block_progress' => $this->dashboardService->getBlockProgress($user),
            'study_progress' => $this->dashboardService->getStudyProgress($user),
            'test_stats' => $this->getTestStats($user),
            'generated_at' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.metrics', $data);
        
        return $pdf;
    }

    private function getTestStats(User $user)
    {
        $attempts = TestAttempt::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get();

        return [
            'total_attempts' => $attempts->count(),
            'passed_attempts' => $attempts->where('passed', true)->count(),
            'average_score' => round($attempts->avg('score') ?? 0, 2),
            'recent_attempts' => $attempts->take(10),
        ];
    }
}
