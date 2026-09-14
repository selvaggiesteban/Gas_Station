<?php

use App\Models\CustomTest;
?>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Crear Test Personalizado') }}
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
                    @if(session('error'))
                        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }} </span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('examenes-personalizados.guardar') }}" x-data="{ selectedBlocks: [], selectedTopics: [] }">
                        @csrf

                        <div class="space-y-6">
                            <!-- Test Name -->
                            <div>
                                <x-input-label for="name" :value="__('Nombre del Test')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus :placeholder="__('Ej: Test de Repaso - Bloque I')" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Question Count -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <x-input-label for="question_count" :value="__('Número de Preguntas')" />
                                    <select id="question_count" name="question_count" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                        @foreach([5, 10, 15, 20, 25, 30, 40, 50] as $count)
                                            <option value="{{ $count }}" {{ old('question_count', 20) == $count ? 'selected' : '' }}>{{ $count }} </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('question_count')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="time_limit_minutes" :value="__('Tiempo Límite (minutos)')" />
                                    <select id="time_limit_minutes" name="time_limit_minutes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                        @foreach([10, 15, 20, 30, 45, 60, 90, 120] as $time)
                                            <option value="{{ $time }}" {{ old('time_limit_minutes', 30) == $time ? 'selected' : '' }}>{{ $time }} </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('time_limit_minutes')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="difficulty" :value="__('Dificultad')" />
                                    <select id="difficulty" name="difficulty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                        <option value="mixed" {{ old('difficulty', 'mixed') == 'mixed' ? 'selected' : '' }}>{{ __('Mezclado') }} </option>
                                        <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>{{ __('Fácil') }} </option>
                                        <option value="medium" {{ old('difficulty') == 'medium' ? 'selected' : '' }}>{{ __('Medio') }} </option>
                                        <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>{{ __('Difícil') }} </option>
                                    </select>
                                    <x-input-error :messages="$errors->get('difficulty')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Question Types -->
                            <div>
                                <x-input-label :value="__('Tipos de Preguntas')" />
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($questionTypes as $type => $label)<label class="inline-flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" name="question_types[]" value="{{ $type }}" class="rounded border-gray-300 text-corporate-primary shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"
                                                {{ (is_array(old('question_types')) && in_array($type, old('question_types'))) || (!old('question_types') && in_array($type, ['multiple_choice', 'five_options'])) ? 'checked' : '' }}>
                                            <span class="ml-2 text-sm text-gray-700">{{ $label }} </span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('question_types')" class="mt-2" />
                            </div>

                            <!-- Content Selection -->
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Seleccionar Contenido') }} </h3>
                                <p class="text-sm text-gray-500 mb-4">{{ __('Selecciona bloques y/o temas para filtrar las preguntas. Si no seleccionas nada, se usarán todas las preguntas disponibles.') }} </p>

                                @foreach($courses as $course)
                                    <div class="mb-6 border rounded-lg overflow-hidden" x-data="{ expanded: false }">
                                        <button type="button" @click="expanded = !expanded" class="w-full px-4 py-3 bg-gray-50 flex justify-between items-center hover:bg-gray-100">
                                            <span class="font-medium text-gray-900">{{ $course->title }} </span>
                                            <svg :class="{'rotate-180': expanded}" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div x-show="expanded" x-collapse class="p-4">
                                            @foreach($course->blocks as $block)<div class="mb-4 ml-4">
                                                    <label class="flex items-center mb-2">
                                                        <input type="checkbox" name="blocks[]" value="{{ $block->id }}" class="rounded border-gray-300 text-corporate-primary shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"
                                                            @change="if ($event.target.checked) { $event.target.closest('.mb-4').querySelectorAll('input[name=\'topics[]\']').forEach(cb => cb.checked = true); } else { $event.target.closest('.mb-4').querySelectorAll('input[name=\'topics[]\']').forEach(cb => cb.checked = false); }">
                                                        <span class="ml-2 font-medium text-gray-800">{{ $block->title }} </span>
                                                    </label>

                                                    <div class="ml-6 grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        @foreach($block->topics as $topic)<label class="inline-flex items-center">
                                                                <input type="checkbox" name="topics[]" value="{{ $topic->id }}" class="rounded border-gray-300 text-corporate-primary shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                                                <span class="ml-2 text-sm text-gray-600">{{ $topic->title }} </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end mt-6 pt-6 border-t">
                                <x-secondary-button type="button" onclick="window.location='{{ route('examenes-personalizados.lista') }}'" class="mr-3">
                                    {{ __('Cancelar') }}
                                </x-secondary-button>

                                <x-primary-button class="bg-corporate-primary hover:bg-orange-700 focus:bg-orange-700 focus:ring-orange-500">
                                    {{ __('Generar Test') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
