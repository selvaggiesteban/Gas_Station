<?php

use App\Models\CustomTest;
?>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $customTest->name }}
            </h2>
            <a href="{{ route('examenes-personalizados.lista') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if(session('success'))
                        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }} </span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }} </span>
                        </div>
                    @endif

                    <!-- Test Details -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-corporate-primary">{{ $customTest->question_count }} </div>
                            <div class="text-sm text-gray-500">{{ __('Preguntas') }} </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-corporate-primary">{{ $customTest->time_limit_minutes }} min</div>
                            <div class="text-sm text-gray-500">{{ __('Tiempo límite') }} </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-corporate-primary">{{ $customTest->difficulty_label }} </div>
                            <div class="text-sm text-gray-500">{{ __('Dificultad') }} </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-corporate-primary">{{ count($customTest->question_types ?? []) }} </div>
                            <div class="text-sm text-gray-500">{{ __('Tipos de pregunta') }} </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-8">
                        @if($customTest->isDraft())<div class="bg-gray-100 border-l-4 border-gray-500 p-4">
                                <p class="text-gray-700">{{ __('Este test está en estado borrador.') }} </p>
                            </div>
                        @elseif($customTest->isGenerated())<div class="bg-orange-100 border-l-4 border-orange-500 p-4">
                                <p class="text-orange-700 mb-4">{{ __('¡Tu test está listo para comenzar!') }} </p>
                                <a href="{{ route('examenes-personalizados.iniciar', $customTest) }}" class="inline-flex items-center px-6 py-3 bg-corporate-primary border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-orange-700">{{ __('Empezar Test') }} </a>
                            </div>
                        @elseif($customTest->isCompleted())<div class="bg-green-100 border-l-4 border-green-500 p-4">
                                <p class="text-green-700 mb-2">{{ __('Test completado') }} </p>
                                <p class="text-3xl font-bold text-green-600">{{ $customTest->score }}%</p>
                                <a href="{{ route('examenes-personalizados.resultado', $customTest) }}" class="inline-flex items-center mt-4 px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">{{ __('Ver Resultados') }} </a>
                            </div>
                        @endif
                    </div>

                    <!-- Questions Preview (if generated) -->
                    @if($customTest->isGenerated() || $customTest->isCompleted())<div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Preguntas del Test') }} </h3>

                            @if($questions->isEmpty())<p class="text-gray-500">{{ __('No se encontraron preguntas para este test.') }} </p>
                            @else
                                <div class="space-y-4">
                                    @foreach($questions as $index => $question)<div class="p-4 border rounded-lg">
                                            <div class="flex items-start">
                                                <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-medium text-gray-700">
                                                    {{ $index + 1 }}
                                                </span>
                                                <div class="ml-4 flex-1">
                                                    <p class="text-gray-800 font-medium">{{ Str::limit($question->text, 150) }} </p>
                                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                                        <span class="px-2 py-1 bg-gray-100 rounded">
                                                            {{ match($question->type) {
                                                                'multiple_choice' => 'Opción múltiple (4)',
                                                                'five_options' => 'Opción múltiple (5)',
                                                                'fill_blank' => 'Completar',
                                                                'open_ended' => 'Abierta',
                                                                default => $question->type
                                                            } }}
                                                        </span>
                                                        @if($question->topic)<span>{{ $question->topic->title }} </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Delete Action -->
                    @if(!$customTest->isCompleted())<div class="border-t mt-8 pt-6">
                            <form action="{{ route('examenes-personalizados.eliminar', $customTest) }}" method="POST" onsubmit="return confirm('{{ __('¿Estás seguro de que quieres eliminar este test? Esta acción no se puede deshacer.') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">{{ __('Eliminar este test') }} </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
