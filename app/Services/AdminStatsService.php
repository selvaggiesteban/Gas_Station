<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Subscription;
use App\Models\TestAttempt;
use App\Models\Battle;
use App\Models\StudySession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStatsService
{
    /**
     * Obtiene todas las métricas estructuradas para el dashboard.
     */
    public function getDashboardMetrics(): array
    {
        return [
            'kpis' => $this->getKPIs(),
            'revenue_chart' => $this->getMonthlyRevenue(),
            'users_chart' => $this->getMonthlyRegistrations(),
            'plans_chart' => $this->getSubscriptionDistribution(),
        ];
    }

    /**
     * Indicadores clave de rendimiento (KPIs)
     */
    private function getKPIs(): array
    {
        $totalRevenue = Invoice::where('status', Invoice::STATUS_PAID)->sum('total_amount');
        
        $activeStudents = User::role('Student')->whereHas('subscription', function($q) {
            $q->where('status', Subscription::STATUS_ACTIVE)
              ->where('ends_at', '>', now());
        })->count();
        
        $totalStudents = User::role('Student')->count();
        
        $totalTestsCompleted = TestAttempt::whereNotNull('completed_at')->count();
        $totalBattlesCompleted = Battle::where('status', 'completed')->count();

        return [
            'total_revenue' => $totalRevenue,
            'active_students' => $activeStudents,
            'total_students' => $totalStudents,
            'total_tests_completed' => $totalTestsCompleted,
            'total_battles_completed' => $totalBattlesCompleted,
            'conversion_rate' => $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0,
        ];
    }

    /**
     * Ingresos agrupados por mes (Últimos 12 meses)
     */
    private function getMonthlyRevenue(): array
    {
        $driver = DB::connection()->getDriverName();
        $dateSelect = $driver === 'sqlite' 
            ? "strftime('%Y-%m', issue_date) as month" 
            : "DATE_FORMAT(issue_date, '%Y-%m') as month";

        $revenues = Invoice::select(
                DB::raw('SUM(total_amount) as total'),
                DB::raw($dateSelect)
            )
            ->where('status', Invoice::STATUS_PAID)
            ->where('issue_date', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        return $this->fillMissingMonths($revenues);
    }

    /**
     * Registros de usuarios agrupados por mes (Últimos 12 meses)
     */
    private function getMonthlyRegistrations(): array
    {
        $driver = DB::connection()->getDriverName();
        $dateSelect = $driver === 'sqlite' 
            ? "strftime('%Y-%m', created_at) as month" 
            : "DATE_FORMAT(created_at, '%Y-%m') as month";

        $registrations = User::role('Student')
            ->select(
                DB::raw('COUNT(id) as total'),
                DB::raw($dateSelect)
            )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        return $this->fillMissingMonths($registrations);
    }

    /**
     * Distribución de suscripciones activas por plan
     */
    private function getSubscriptionDistribution(): array
    {
        return Subscription::select('plans.name', DB::raw('COUNT(subscriptions.id) as total'))
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.status', Subscription::STATUS_ACTIVE)
            ->where('subscriptions.ends_at', '>', now())
            ->groupBy('plans.name')
            ->get()
            ->pluck('total', 'name')
            ->toArray();
    }

    /**
     * Rellena con 0 los meses sin datos para que el gráfico sea consistente
     */
    private function fillMissingMonths(array $data): array
    {
        $filled = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $filled[$month] = $data[$month] ?? 0;
        }
        return $filled;
    }
}
