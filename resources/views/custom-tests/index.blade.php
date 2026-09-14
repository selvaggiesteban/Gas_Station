<?php

use App\Models\CustomTest;
?>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tests Personalizados') }}
            </h2>
            <a href="{{ route('examenes-personalizados.crear') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-corporate-primary focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Crear Nuevo Test') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            @if($customTests->isEmpty())<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('No tienes tests personalizados') }} </h3>
                        <p class="mb-4">{{ __('Crea tu primer test personalizado para practicar con preguntas seleccionadas por ti.') }} </p>
                        <a href="{{ route('examenes-personalizados.crear') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                            {{ __('Crear Test') }}
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Nombre') }} </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Configuración') }} </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }} </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Creado') }} </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($customTests as $test)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $test->name }} </div>
                                                @if($test->score !== null)<div class="text-sm text-gray-500">Puntuación: {{ $test->score }}%</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $test->question_count }} preguntas</div>
                                                <div class="text-sm text-gray-500">{{ $test->time_limit_minutes }} minutos</div>
                                                <div class="text-xs text-gray-400">Dificultad: {{ $test->difficulty_label }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($test->isDraft())<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Borrador') }} </span>
                                                @elseif($test->isGenerated())<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">{{ __('Listo para hacer') }} </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Completado') }} </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $test->created_at->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if($test->isGenerated())<a href="{{ route('examenes-personalizados.iniciar', $test) }}" class="text-corporate-primary hover:text-orange-700 mr-3">{{ __('Empezar') }} </a>
                                                @elseif($test->isCompleted())<a href="{{ route('examenes-personalizados.resultado', $test) }}" class="text-corporate-primary hover:text-orange-700 mr-3">{{ __('Ver resultado') }} </a>
                                                @else
                                                    <a href="{{ route('examenes-personalizados.ver', $test) }}" class="text-gray-600 hover:text-gray-900 mr-3">{{ __('Ver') }} </a>
                                                @endif
                                                <form action="{{ route('examenes-personalizados.eliminar', $test) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Estás seguro de que quieres eliminar este test?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }} </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $customTests->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
