<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Métricas Globales') }} </h1>
                <p class="text-gray-600 mt-2">{{ __('Visión general del rendimiento, ingresos y crecimiento de la academia.') }} </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>{{ __('Volver al Panel') }} </a>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-b-4 border-green-500">
                <p class="text-sm font-medium text-gray-500 uppercase">{{ __('Ingresos Totales') }} </p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($metrics['kpis']['total_revenue'], 2) }}€</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-b-4 border-blue-500">
                <p class="text-sm font-medium text-gray-500 uppercase">{{ __('Alumnos Activos') }} </p>
                <p class="text-2xl font-bold text-gray-900">{{ $metrics['kpis']['active_students'] }} </p>
                <p class="text-xs text-gray-400">De un total de {{ $metrics['kpis']['total_students'] }} </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-b-4 border-purple-500">
                <p class="text-sm font-medium text-gray-500 uppercase">{{ __('Tasa de Conversión') }} </p>
                <p class="text-2xl font-bold text-gray-900">{{ $metrics['kpis']['conversion_rate'] }}%</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-b-4 border-red-500">
                <p class="text-sm font-medium text-gray-500 uppercase">{{ __('Actividad Total') }} </p>
                <p class="text-2xl font-bold text-gray-900">{{ $metrics['kpis']['total_tests_completed'] + $metrics['kpis']['total_battles_completed'] }} </p>
                <p class="text-xs text-gray-400">{{ $metrics['kpis']['total_tests_completed'] }} tests | {{ $metrics['kpis']['total_battles_completed'] }} duelos</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">{{ __('Ingresos Mensuales (12 meses)') }} </h3>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Users Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">{{ __('Nuevos Alumnos por Mes') }} </h3>
                <div class="h-64">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Plans Distribution -->
            <div class="bg-white rounded-lg shadow p-6 lg:col-span-1">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">{{ __('Distribución de Planes') }} </h3>
                <div class="h-64">
                    <canvas id="plansChart"></canvas>
                </div>
            </div>

            <!-- Detailed Stats Table (Optional/Future) -->
            <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">{{ __('Resumen de Actividad Reciente') }} </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Métrica') }} </th>
                                <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Valor Actual') }} </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ __('Exámenes realizados') }} </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 font-mono">{{ $metrics['kpis']['total_tests_completed'] }} </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ __('Duelos completados') }} </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 font-mono">{{ $metrics['kpis']['total_battles_completed'] }} </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ __('Total Alumnos Registrados') }} </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 font-mono">{{ $metrics['kpis']['total_students'] }} </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartConfig = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            };

            // Revenue Chart
            const revenueData = @json($metrics['revenue_chart']);
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: Object.keys(revenueData),
                    datasets: [{
                        label: 'Ingresos (€)',
                        data: Object.values(revenueData),
                        borderColor: '#10b981',
                        backgroundColor: '#10b98133',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: chartConfig
            });

            // Users Chart
            const usersData = @json($metrics['users_chart']);
            new Chart(document.getElementById('usersChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(usersData),
                    datasets: [{
                        label: 'Nuevos Registros',
                        data: Object.values(usersData),
                        backgroundColor: '#3b82f6'
                    }]
                },
                options: chartConfig
            });

            // Plans Distribution Chart
            const plansData = @json($metrics['plans_chart']);
            new Chart(document.getElementById('plansChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(plansData),
                    datasets: [{
                        data: Object.values(plansData),
                        backgroundColor: ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6']
                    }]
                },
                options: chartConfig
            });
        });
    </script>
    @endpush
</x-app-layout>
