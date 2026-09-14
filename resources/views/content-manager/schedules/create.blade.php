<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Crear Evento Oficial') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('content-manager.schedules.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="title" value="Título del Evento" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="description" value="Descripción" />
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }} </textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="start_time" value="Fecha y Hora Inicio" />
                                <x-text-input id="start_time" name="start_time" type="datetime-local" class="mt-1 block w-full" :value="old('start_time')" required />
                                <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="end_time" value="Fecha y Hora Fin" />
                                <x-text-input id="end_time" name="end_time" type="datetime-local" class="mt-1 block w-full" :value="old('end_time')" required />
                                <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="sync_to_students" value="1" class="rounded border-gray-300 text-corporate-primary shadow-sm focus:ring-corporate-primary">
                                <span class="ml-2 text-sm text-gray-600">{{ __('Sincronizar automáticamente con todos los alumnos activos') }} </span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1 ml-6">{{ __('Si se marca, se creará este evento en el cronograma personal de todos los alumnos con suscripción activa.') }} </p>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('content-manager.schedules.index') }}" class="text-gray-600 hover:text-gray-800">{{ __('Cancelar') }} </a>
                            <x-primary-button>{{ __('Crear Evento') }} </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>