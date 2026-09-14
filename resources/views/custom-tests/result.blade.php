<?php

use App\Models\CustomTest;
?>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Resultado del Test') }} </h2>
            <a href="{{ route('examenes-personalizados.lista') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Volver a Mis Tests') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Score Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-8 text-center">
                    <h3 class="text-xl font-medium text-gray-900 mb-4">{{ $customTest->name }} </h3>

                    <div class="text-6xl font-bold mb-2 {{ $customTest->score >= 60 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $customTest->score }}%
                    </div>
                    <p class="text-gray-500 mb-6">{{ $customTest->score >= 60 ? '¡Aprobado!' : 'No superado' }} </p>

                    @if(session('score'))
                        <div class="mb-6 p-4 {{ $customTest->score >= 60 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded-lg">
                            {{ $customTest->score >= 60
                                ? '¡Excelente trabajo! Has superado el test con éxito.'
                                : 'Sigue practicando. Revisa tus errores para mejorar.' }}
                        </div>
                    @endif

                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">{{ $customTest->question_count }} </div>
                            <div class="text-sm text-gray-500">{{ __('Preguntas') }} </div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">{{ $customTest->time_limit_minutes }} min</div>
                            <div class="text-sm text-gray-500">{{ __('Tiempo límite') }} </div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">{{ $customTest->completed_at?->format('d/m/Y') : '-' }} </div>
                            <div class="text-sm text-gray-500">{{ __('Completado') }} </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-center space-x-4 mb-8">
                <a href="{{ route('examenes-personalizados.crear') }}" class="inline-flex items-center px-6 py-3 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>{{ __('Crear Nuevo Test') }} </a>

                <a href="{{ route('examenes-personalizados.lista') }}" class="inline-flex items-center px-6 py-3 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('Ver Todos Mis Tests') }} </a>
            </div>

            <!-- Tips -->
            <div class="bg-blue-50 border-l-4 border-orange-500 p-4 rounded-lg">
                <h4 class="font-medium text-orange-900 mb-2">{{ __('Consejos para mejorar') }} </h4>
                <ul class="list-disc list-inside text-orange-800 space-y-1">
                    <li>{{ __('Revisa los temas donde hayas tenido dudas') }} </li>
                    <li>{{ __('Crea tests personalizados enfocados en tus áreas débiles') }} </li>
                    <li>{{ __('Utiliza el sistema de tutorías para resolver dudas específicas') }} </li>
                    <li>{{ __('Participa en batallas PvP para practicar bajo presión') }} </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
