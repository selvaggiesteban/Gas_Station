<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Crear Anuncio') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('content-manager.announcements.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="title" value="Título del Anuncio" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="content" value="Contenido" />
                            <textarea id="content" name="content" rows="10" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required></textarea>
                            <x-input-error :messages="$errors->get('content')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="type" value="Tipo de Anuncio" />
                                <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="info">{{ __('Informativo') }} </option>
                                    <option value="warning">{{ __('Aviso') }} </option>
                                    <option value="urgent">{{ __('Urgente') }} </option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="published_at" value="Fecha de publicación" />
                                <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="expires_at" value="Fecha de expiración (opcional)" />
                                <x-text-input id="expires_at" name="expires_at" type="datetime-local" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="attachment" value="Archivo adjunto (opcional)" />
                            <input type="file" name="attachment" id="attachment" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-corporate-primary shadow-sm focus:ring-corporate-primary">
                                <span class="ml-2 text-sm text-gray-600">{{ __('Publicar activo') }} </span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="target_cuerpos" value="Alcance" />
                            <p class="text-xs text-gray-500 mb-2">{{ __('Si no seleccionas ningún cuerpo, el anuncio se envía a TODOS los usuarios.') }} </p>
                            <select name="target_cuerpos[]" id="target_cuerpos" multiple size="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                @foreach(\App\Models\Cuerpo::where('is_active', true)->orderBy('name')->get() as $cuerpo)
                                    <option value="{{ $cuerpo->id }}">{{ $cuerpo->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Mantén Ctrl/Cmd presionado para selección múltiple. Vacío = Todos.') }} </p>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('content-manager.announcements.index') }}" class="text-gray-600 hover:text-gray-800">{{ __('Cancelar') }} </a>
                            <x-primary-button>{{ __('Publicar Anuncio') }} </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
