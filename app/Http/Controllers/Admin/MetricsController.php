<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;

class MetricsController extends Controller
{
    private AdminStatsService $statsService;

    public function __construct(AdminStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Muestra el panel de métricas globales del administrador.
     */
    public function index()
    {
        $metrics = $this->statsService->getDashboardMetrics();

        return view('admin.metrics.index', compact('metrics'));
    }
}
