<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mi Progreso y Resultados') }}
            </h2>
            <a href="{{ route('progreso.descargar-pdf') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-corporate-primary/90 active:bg-corporate-primary focus:outline-none focus:border-corporate-primary focus:ring ring-corporate-primary/30 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('Descargar Reporte') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Overall Summary Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Points Card -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-corporate-primary">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Puntos totales') }} </p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($progressData['stats']['points']) }} </p>
                        </div>
                        <div class="p-3 bg-corporate-primary/10 rounded-full">
                            <svg class="h-6 w-6 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Rank Card -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Nivel actual') }} </p>
                            <p class="text-2xl font-bold text-gray-900">{{ $progressData['stats']['rank_name'] }} </p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Tests Stats Card -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Nota media tests') }} </p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($progressData['test_stats']['average_score'], 1) }}/10</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-full">
                            <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Study Streak Card -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Racha de estudio') }} </p>
                            <p class="text-2xl font-bold text-gray-900">{{ $progressData['streak']['current_streak'] }} días</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Detailed Progress by Block -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('Detalle de progreso por bloques') }} </h3>
                        
                        <div class="space-y-6">
                            @foreach($progressData['block_progress'] as $block)
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $block['block_name'] }} </p>
                                            <p class="text-sm text-gray-500">{{ $block['course_name'] }} </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-semibold text-gray-700">
                                                {{ $block['completed'] }}/{{ $block['total'] }} temas
                                            </span>
                                            <p class="text-xs font-bold text-corporate-primary">{{ $block['percentage'] }}%</p>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-corporate-primary h-3 rounded-full transition-all duration-500" style="width: {{ $block['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Test Performance List -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Últimos intentos de test') }} </h3>
                            <a href="{{ route('examenes-personalizados.lista') }}" class="text-sm text-corporate-primary hover:underline">{{ __('Ver todos') }} </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3">{{ __('Test') }} </th>
                                        <th class="px-6 py-3">{{ __('Fecha') }} </th>
                                        <th class="px-6 py-3 text-center">{{ __('Puntuación') }} </th>
                                        <th class="px-6 py-3 text-center">{{ __('Resultado') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($progressData['test_stats']['recent_attempts'] as $attempt)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $attempt->test->name ?? 'Test Personalizado' }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $attempt->completed_at->format('d/m/Y H:i') }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="text-sm font-bold {{ $attempt->score >= 5 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ number_format($attempt->score, 2) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if($attempt->passed)<span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-700 rounded-full">{{ __('APTO') }} </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-bold bg-red-100 text-red-700 rounded-full">{{ __('NO APTO') }} </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($progressData['test_stats']['recent_attempts']->isEmpty())<tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no has realizado ningún test.') }} </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Stats -->
                <div class="space-y-8">
                    <!-- Percentile Ranking -->
                    <div class="bg-gradient-to-r from-corporate-primary to-[#FF9933] rounded-lg shadow-lg p-6 text-white">
                        <div class="text-center">
                            <h3 class="text-lg font-bold mb-2">{{ __('Tu Posición Global') }} </h3>
                            <div class="inline-flex items-center justify-center p-4 bg-white/20 rounded-full mb-4">
                                <span class="text-4xl font-extrabold">{{ $progressData['percentile']['percentile'] }}%</span>
                            </div>
                            <p class="text-orange-100 mb-4">
                                {{ $progressData['percentile']['message'] }}
                            </p>
                            <div class="text-sm border-t border-white/20 pt-4">
                                <p><strong>{{ __('Posición:') }} </strong> #{{ $progressData['percentile']['rank_position'] }} de {{ $progressData['percentile']['total_users'] }} </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats breakdown -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Desglose de actividad') }} </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Tests realizados') }} </span>
                                <span class="font-bold text-gray-900">{{ $progressData['test_stats']['total_attempts'] }} </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Tests aptos') }} </span>
                                <span class="font-bold text-green-600">{{ $progressData['test_stats']['passed_attempts'] }} </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Temas completados') }} </span>
                                <span class="font-bold text-gray-900">{{ $progressData['stats']['topics_completed'] }} </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Tiempo de estudio') }} </span>
                                <span class="font-bold text-gray-900">{{ $progressData['stats']['total_study_time'] }} h</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
