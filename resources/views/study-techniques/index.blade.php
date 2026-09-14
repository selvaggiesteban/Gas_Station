<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Técnicas de Estudio para IIPP') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Hero Section -->
            <div class="bg-gradient-to-r from-corporate-primary to-[#000000] rounded-2xl shadow-xl overflow-hidden mb-12">
                <div class="px-8 py-12 md:px-16 md:py-16 text-white">
                    <h1 class="text-4xl font-extrabold mb-4">{{ __('Domina tu Oposición') }} </h1>
                    <p class="text-xl text-orange-100 max-w-2xl">{{ __('Estudiar para Instituciones Penitenciarias no es una carrera de velocidad, es de resistencia. Aplica estas Técnicas probadas para maximizar tu retención y eficiencia.') }} </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Pomodoro Technique -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-red-100 rounded-lg mr-4 text-red-600">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ __('Método Pomodoro') }} </h3>
                        </div>
                        <p class="text-gray-600 mb-6 leading-relaxed">{{ __('Divide tu tiempo de estudio en bloques de') }} <strong>{{ __('25 minutos de concentración total') }} </strong>{{ __(', seguidos de 5 minutos de descanso. Tras 4 bloques, toma un descanso largo de 20-30 minutos.') }} </p>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-700">{{ __('Evita la fatiga mental en jornadas largas.') }} </span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-700">{{ __('Mantiene tu cerebro fresco para el siguiente bloque.') }} </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Spaced Repetition -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-orange-100 rounded-lg mr-4 text-orange-600">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ __('Repaso Espaciado') }} </h3>
                        </div>
                        <p class="text-gray-600 mb-6 leading-relaxed">{{ __('Lucha contra la') }} <strong>{{ __('Curva del Olvido') }} </strong>{{ __('. Repasa el contenido en intervalos crecientes: 1 día, 3 días, 1 semana, 1 mes. En AcademicUS te recordamos qué temas necesitan un repaso.') }} </p>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-700">{{ __('Consolida la información en la memoria a largo plazo.') }} </span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-700">{{ __('Ideal para leyes y artículos complejos de IIPP.') }} </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Pareto Principle -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-green-100 rounded-lg mr-4 text-green-600">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ __('Ley de Pareto (80/20)') }} </h3>
                        </div>
                        <p class="text-gray-600 mb-6 leading-relaxed">{{ __('El') }} <strong>{{ __('80% de las preguntas') }} </strong>{{ __('del examen suelen salir del') }} <strong>{{ __('20% del contenido') }} </strong>{{ __('clave. Identifica los artículos más preguntados históricamente y domínalos a la perfección.') }} </p>
                        <div class="bg-gray-50 p-4 rounded-lg italic text-sm text-gray-500">{{ __('"Prioriza lo que es vital, no solo lo que es urgente."') }} </div>
                    </div>
                </div>

                <!-- Active Recall -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-purple-100 rounded-lg mr-4 text-purple-600">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ __('Recuerdo Activo') }} </h3>
                        </div>
                        <p class="text-gray-600 mb-6 leading-relaxed">{{ __('No te limites a leer. Cierra el libro y') }} <strong>{{ __('explícate el tema en voz alta') }} </strong>{{ __('o hazte preguntas críticas. Si puedes explicarlo, es que lo has entendido y memorizado.') }} </p>
                        <div class="flex space-x-2">
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">{{ __('SIMULACROS') }} </span>
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">{{ __('FLASHCARDS') }} </span>
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">{{ __('AUTOPREGUNTAS') }} </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conclusion Card -->
            <div class="mt-12 bg-gray-900 rounded-2xl p-8 text-center">
                <h2 class="text-2xl font-bold text-white mb-4">{{ __('¿Listo para ponerlo en práctica?') }} </h2>
                <p class="text-gray-400 mb-8 max-w-2xl mx-auto">{{ __('Combina estas Técnicas con nuestro generador de tests personalizados para medir tu progreso real día a día.') }} </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('examenes-personalizados.crear') }}" class="px-8 py-3 bg-corporate-primary text-white font-bold rounded-lg hover:bg-orange-600 transition">{{ __('Crear Test Personalizado') }} </a>
                    <a href="{{ route('schedule.personal') }}" class="px-8 py-3 bg-white text-gray-900 font-bold rounded-lg hover:bg-gray-100 transition">{{ __('Organizar mi Calendario') }} </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
