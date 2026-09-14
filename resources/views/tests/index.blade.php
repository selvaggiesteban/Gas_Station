<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Exámenes y Simulacros') }} </h1>
            <p class="text-gray-600 mt-2">{{ __('Pon a prueba tus conocimientos con nuestros tests oficiales y simulacros de examen.') }} </p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content: Test Lists -->
            <div class="lg:col-span-2 space-y-12">
                
                <!-- Simulacros Oficiales -->
                <section>
                    <div class="flex items-center mb-6">
                        <div class="p-2 bg-orange-100 rounded-lg mr-4">
                            <svg class="h-6 w-6 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ __('Simulacros de Examen') }} </h2>
                    </div>

                    @if($simulacros->isEmpty())
                        <div class="bg-white rounded-xl border border-gray-100 p-8 text-center shadow-sm">
                            <p class="text-gray-400 italic">{{ __('No hay simulacros disponibles en este momento.') }} </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($simulacros as $test)
                                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition flex flex-col">
                                    <div class="p-6 flex-1">
                                        <div class="flex justify-between items-start mb-4">
                                            <span class="bg-orange-50 text-corporate-primary text-[10px] font-bold px-2 py-1 rounded-full uppercase">{{ __('Oficial') }} </span>
                                            <span class="text-xs text-gray-400 font-medium">{{ $test->time_limit_minutes }} min</span>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $test->title }} </h3>
                                        <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $test->description }} </p>
                                    </div>
                                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-50 flex justify-end">
                                        <a href="{{ route('examenes.presentar', $test) }}" class="bg-corporate-primary text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-orange-600 transition">
                                            {{ __('Comenzar Test') }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <!-- Tests por Temas (Asignados) -->
                <section>
                    <div class="flex items-center mb-6">
                        <div class="p-2 bg-blue-100 rounded-lg mr-4">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ __('Tests de tu Temario') }} </h2>
                    </div>

                    @if($assignedTests->isEmpty())
                        <div class="bg-white rounded-xl border border-gray-100 p-8 text-center shadow-sm">
                            <p class="text-gray-500">{{ __('Aquí aparecerán los tests vinculados a tus cursos activos.') }} </p>
                            <a href="{{ route('cursos.mis-cursos') }}" class="text-corporate-primary font-bold hover:underline mt-2 inline-block">{{ __('Explorar Temario →') }} </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($assignedTests as $test)
                                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition flex flex-col border-l-4 border-blue-500">
                                    <div class="p-6 flex-1">
                                        <div class="flex justify-between items-start mb-4">
                                            <span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-1 rounded-full uppercase">{{ $test->course->title ?? 'Curso' }} </span>
                                            <span class="text-xs text-gray-400 font-medium">{{ $test->time_limit_minutes }} min</span>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $test->title }} </h3>
                                        <p class="text-sm text-gray-600 line-clamp-2">{{ $test->description }} </p>
                                    </div>
                                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-50 flex justify-end">
                                        <a href="{{ route('examenes.presentar', $test) }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-gray-700 transition">
                                            {{ __('Hacer Test') }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <!-- Sidebar: Recent Activity -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="h-5 w-5 text-yellow-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ __('Últimos Intentos') }}
                    </h3>

                    @if($recentAttempts->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-sm text-gray-400 italic">{{ __('Aún no has realizado ningún test. ¡Empieza hoy!') }} </p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($recentAttempts as $attempt)
                                <div class="p-4 rounded-lg bg-gray-50 border border-gray-100">
                                    <p class="text-sm font-bold text-gray-900 line-clamp-1">{{ $attempt->test->title }} </p>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-xs {{ $attempt->passed ? 'text-green-600 font-bold' : 'text-red-500' }}">
                                            {{ $attempt->passed ? __('APTO') : __('NO APTO') }} ({{ number_format($attempt->score, 1) }}%)
                                        </span>
                                        <span class="text-[10px] text-gray-400">{{ $attempt->completed_at->format('d/m/Y') }} </span>
                                    </div>
                                    <a href="{{ route('examenes.resultado', $attempt->test_id) }}" class="text-[10px] font-bold text-corporate-primary hover:underline mt-2 inline-block uppercase">
                                        {{ __('Ver Revisión →') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <a href="{{ route('examenes-personalizados.lista') }}" class="flex items-center justify-between p-4 rounded-lg bg-corporate-primary/10 text-corporate-primary hover:bg-corporate-primary/20 transition group">
                            <span class="font-bold text-sm">{{ __('Generar Test Personalizado') }} </span>
                            <svg class="h-5 w-5 transform group-hover:translate-x-1 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
