<x-app-layout>


<div class="container mx-auto px-4 py-8">
    <!-- Header with welcome message -->
    <div class="mb-8 flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Mi Escritorio') }} </h1>
            <p class="text-gray-600 mt-2">Bienvenido de vuelta, {{ auth()->user()->name }}. Estás en la <strong>vuelta nº {{ auth()->user()->current_study_round ?? 1 }} </strong>.</p>
        </div>
        <form action="{{ route('rondas-estudio.siguiente') }}" method="POST" onsubmit="return confirm('{{ __('¿Estás seguro de que quieres empezar una nueva vuelta? Tu progreso visual se reiniciará, pero tu historial se mantendrá guardado.') }}')">
            @csrf
            <button type="submit" class="bg-white border border-corporate-primary text-corporate-primary px-4 py-2 rounded-lg font-bold hover:bg-orange-50 transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>{{ __('Nueva Vuelta') }} </button>
        </form>
    </div>

    <!-- Motivational Quote -->
    <x-motivational-quote />

    @php
        $stats = $dashboardData['user_stats'];
        $percentile = $dashboardData['percentile'];
        $streak = $dashboardData['study_streak'];
        $ranking = $dashboardData['monthly_ranking'];
    @endphp

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Average Speed Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Velocidad media') }} </p>
                    <p class="text-2xl font-bold text-gray-900">{{ $academicStats['average_speed'] }} s <span class="text-sm font-normal text-gray-500">{{ __('/ preg') }} </span></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Doubts Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Preguntas en duda') }} </p>
                    <p class="text-2xl font-bold text-gray-900">{{ $academicStats['doubt_stats']['total_doubts'] }} </p>
                    <p class="text-xs text-purple-600 font-medium">{{ __('Marcadas para repaso') }} </p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Ranking Card (Original) -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-corporate-primary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Puntos totales') }} </p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['points']) }} </p>
                </div>
                <div class="p-3 bg-corporate-primary/10 rounded-full">
                    <svg class="h-6 w-6 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Level Progress Bar -->
    <div class="bg-gradient-to-r from-corporate-primary to-[#FF9933] rounded-lg shadow-lg p-6 mb-8 text-white">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-full mr-4">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Nivel {{ $stats['level'] }} - {{ $stats['rank_name'] }} </h3>
                    <div class="flex items-center gap-4 mt-1">
                        <p class="text-orange-100">{{ number_format($stats['points']) }} puntos</p>
                        <div class="flex items-center bg-white/20 px-2 py-0.5 rounded-full text-xs font-bold">
                            <svg class="w-3 h-3 mr-1 text-yellow-300" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"></path></svg>
                            Racha: {{ auth()->user()->login_streak_days ?? 0 }} días
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-right">
                @php
                    $rankRanges = [
                        'Cobre' => ['min' => 0, 'max' => 999],
                        'Bronce' => ['min' => 1000, 'max' => 2999],
                        'Plata' => ['min' => 3000, 'max' => 5999],
                        'Oro' => ['min' => 6000, 'max' => 8999],
                        'Diamante' => ['min' => 9000, 'max' => PHP_INT_MAX],
                    ];
                    $currentRank = $stats['rank_name'];
                    $nextRank = match($currentRank) {
                        'Cobre' => 'Bronce',
                        'Bronce' => 'Plata',
                        'Plata' => 'Oro',
                        'Oro' => 'Diamante',
                        default => null
                    };
                    $currentMin = $rankRanges[$currentRank]['min'] ?? 0;
                    $currentMax = $rankRanges[$currentRank]['max'] ?? PHP_INT_MAX;
                    $pointsInLevel = $stats['points'] - $currentMin;
                    $pointsNeeded = $nextRank ? ($rankRanges[$nextRank]['min'] - $currentMin) : $pointsInLevel;
                    $progressPercent = $nextRank ? min(100, ($pointsInLevel / $pointsNeeded) * 100) : 100;
                @endphp
                @if($nextRank)
                    <p class="text-2xl font-bold">{{ number_format($rankRanges[$nextRank]['min'] - $stats['points']) }} </p>
                    <p class="text-sm text-orange-200">puntos para {{ $nextRank }} </p>
                @else
                    <p class="text-lg font-bold">{{ __('¡Nivel máximo!') }} </p>
                    <p class="text-sm text-orange-200">{{ __('Eres Diamante') }} </p>
                @endif
            </div>
        </div>
        <div class="w-full bg-white/20 rounded-full h-3">
            <div class="bg-white h-3 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
        </div>

        <!-- Milestones Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-4 border-t border-white/10">
            <div class="text-center">
                <p class="text-xs text-orange-100 uppercase font-bold tracking-wider">{{ __('Clases') }} </p>
                <p class="text-lg font-bold">{{ auth()->user()->total_classes_viewed ?? 0 }} </p>
            </div>
            <div class="text-center">
                <p class="text-xs text-orange-100 uppercase font-bold tracking-wider">{{ __('Tests') }} </p>
                <p class="text-lg font-bold">{{ auth()->user()->total_tests_completed ?? 0 }} </p>
            </div>
            <div class="text-center">
                <p class="text-xs text-orange-100 uppercase font-bold tracking-wider">{{ __('Supuestos') }} </p>
                <p class="text-lg font-bold">{{ auth()->user()->total_supuestos_submitted ?? 0 }} </p>
            </div>
            <div class="text-center">
                <p class="text-xs text-orange-100 uppercase font-bold tracking-wider">{{ __('Media') }} </p>
                <p class="text-lg font-bold">{{ number_format(auth()->user()->testAttempts()->avg('score') ?? 0, 1) }}%</p>
            </div>
        </div>

        <div class="flex justify-between mt-4 text-sm text-orange-200">
            <span>{{ $currentRank }} </span>
            <span>{{ $nextRank ?? 'Máximo' }} </span>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Percentile Card -->
            <div class="bg-gradient-to-r from-corporate-primary to-[#FF9933] rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">{{ $percentile['message'] }} </h3>
                        <p class="text-orange-100">{{ __('Estás en el percentil') }} <strong>{{ $percentile['percentile'] }} </strong>{{ __('de todos los usuarios.') }} <br>{{ __('Posición:') }} <strong>#{{ $percentile['rank_position'] }} </strong> de {{ $percentile['total_users'] }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold">{{ $percentile['percentile'] }}%</div>
                        <div class="text-sm text-orange-200">{{ __('percentil') }} </div>
                    </div>
                </div>
            </div>

            <!-- Block Progress -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Progreso por bloques') }} </h3>
                    <a href="{{ route('cursos.mis-cursos') }}" class="text-sm text-corporate-primary hover:text-[#D66A0F]">{{ __('Ver cursos') }} </a>
                </div>

                <div class="space-y-4">
                    @foreach($dashboardData['block_progress'] as $block)
                        <div class="border-b border-gray-100 last:border-0 pb-4 last:pb-0">
                            <div class="flex justify-between items-center mb-2">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $block['block_name'] }} </p>
                                    <p class="text-sm text-gray-500">{{ $block['course_name'] }} </p>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ $block['completed'] }}/{{ $block['total'] }}
                                </span>
                            </div>

                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-corporate-primary h-2 rounded-full transition-all duration-500"
                                     style="width: {{ $block['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Study Progress by Phase -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('Páginas estudiadas por fase') }} </h3>

                @foreach($dashboardData['study_progress'] as $courseProgress)
                    <div class="mb-6 last:mb-0">
                        <h4 class="font-medium text-gray-700 mb-3">{{ $courseProgress['course_name'] }} </h4>

                        <div class="grid grid-cols-3 gap-4">
                            @foreach($courseProgress['phases'] as $phaseName => $phase)
                                @php
                                    $phaseLabels = ['phase_1' => 'Fase 1', 'phase_2' => 'Fase 2', 'phase_3' => 'Fase 3'];
                                    $phaseColors = ['phase_1' => 'bg-orange-500', 'phase_2' => 'bg-purple-500', 'phase_3' => 'bg-pink-500'];
                                @endphp
                                <div class="bg-gray-50 rounded-lg p-4 text-center">
                                    <p class="text-sm font-medium text-gray-600 mb-1">{{ $phaseLabels[$phaseName] }} </p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $phase['percentage'] }}%</p>
                                    <p class="text-xs text-gray-500">{{ $phase['completed'] }}/{{ $phase['total'] }} </p>

                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                        <div class="{{ $phaseColors[$phaseName] }} h-1.5 rounded-full"
                                             style="width: {{ $phase['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-8">

            <!-- Monthly Ranking -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Ranking Mensual') }} </h3>
                    <a href="{{ route('clasificacion.ver') }}" class="text-sm text-corporate-primary hover:text-[#D66A0F]">{{ __('Ver todo') }} </a>
                </div>

                <p class="text-sm text-gray-500 mb-4">{{ $ranking['month'] }} </p>

                <div class="space-y-3">
                    @foreach($ranking['top_users']->take(5) as $index => $topUser)
                        <div class="flex items-center p-3 {{ $topUser->id === auth()->id() ? 'bg-corporate-primary/10 rounded-lg border border-corporate-primary/30' : 'bg-gray-50 rounded-lg' }}">
                            <div class="flex-shrink-0 w-8 text-center font-bold {{ $index < 3 ? 'text-yellow-600' : 'text-gray-500' }}">
                                {{ $index + 1 }}
                            </div>

                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $topUser->name }} </p>
                                <p class="text-xs text-gray-500">{{ number_format($topUser->monthly_points ?? $topUser->points) }} pts</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($ranking['user_position'] > 5)
                    <div class="mt-4 p-3 bg-corporate-primary/10 rounded-lg border border-corporate-primary/30">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-8 text-center font-bold text-corporate-primary">
                                {{ $ranking['user_position'] }}
                            </div>

                            <div class="ml-3">
                                <p class="text-sm font-medium text-corporate-primary">{{ __('Tu posición') }} </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- What to Study This Week -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Qué estudiar esta semana') }} </h3>

                @if(count($dashboardData['weekly_schedule']['recommended_topics']) > 0)
                    <div class="space-y-3">
                        @foreach($dashboardData['weekly_schedule']['recommended_topics'] as $topic)
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $topic['name'] }} </p>
                                    <p class="text-xs text-gray-500">{{ $topic['block_name'] }} · {{ $topic['estimated_time'] }} min</p>
                                </div>

                                <a href="{{ route('temas.ver', $topic['slug']) }}" class="ml-3 text-corporate-primary hover:text-[#D66A0F]">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 bg-gray-50 rounded-lg">
                        <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-gray-600 font-medium">{{ __('¡Excelente trabajo!') }} </p>
                        <p class="text-gray-500 text-sm mt-1">{{ __('Has completado todos los temas de tu plan de estudio.') }} </p>
                        <a href="{{ route('cursos.catalogo') }}" class="inline-block mt-3 text-sm text-corporate-primary hover:text-[#D66A0F] font-medium">{{ __('Explorar más cursos →') }} </a>
                    </div>
                @endif
            </div>

            <!-- Tutor Asignado -->
            @if(!empty($dashboardData['assigned_teacher']))
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-corporate-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('Tu tutor asignado') }}
                    </h3>
                    <div class="flex items-center p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <img class="h-12 w-12 rounded-full mr-4 border-2 border-corporate-primary" src="{{ $dashboardData['assigned_teacher']['avatar_url'] }}" alt="">
                        <div class="flex-1">
                            <p class="text-sm font-bold text-gray-900">{{ $dashboardData['assigned_teacher']['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $dashboardData['assigned_teacher']['email'] }}</p>
                        </div>
                        <a href="{{ route('tutorias.lista') }}" class="ml-3 inline-flex items-center px-3 py-2 bg-corporate-primary text-white text-xs font-bold uppercase rounded-lg hover:bg-orange-600">
                            {{ __('Enviar pregunta') }}
                        </a>
                    </div>
                </div>
            @endif

            <!-- Upcoming Deadlines -->
            @if(count($dashboardData['upcoming_deadlines']) > 0)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Próximos vencimientos') }} </h3>

                    <div class="space-y-3">
                        @foreach($dashboardData['upcoming_deadlines'] as $deadline)
                            <div class="flex items-center p-3 {{ $deadline['urgent'] ? 'bg-red-50 border border-red-200' : 'bg-orange-50 border border-orange-200' }} rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 {{ $deadline['urgent'] ? 'text-red-500' : 'text-orange-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium {{ $deadline['urgent'] ? 'text-red-900' : 'text-orange-900' }}">{{ $deadline['title'] }} </p>
                                    <p class="text-xs {{ $deadline['urgent'] ? 'text-red-600' : 'text-orange-600' }}">
                                        {{ $deadline['days_remaining'] }} días restantes
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Achievements -->
            @if(count($dashboardData['recent_achievements']) > 0)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Logros recientes') }} </h3>

                    <div class="space-y-3">
                        @foreach($dashboardData['recent_achievements'] as $achievement)
                            <div class="flex items-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>

                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $achievement['title'] }} </p>
                                    <p class="text-xs text-gray-500">{{ $achievement['description'] }} </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
