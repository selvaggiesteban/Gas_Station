<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ $topic ? route('admin.temas.edit', $topic) : route('admin.temas.index') }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">{{ __('← Volver a Editar Tema') }} </a>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Gestión de Versiones PDF') }} </h1>
                <p class="text-gray-600">{{ __('Tema:') }} {{ $topic->title }} </p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }} </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Column -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">{{ __('Subir Nueva Versión') }} </h3>
                    <form action="{{ route('admin.topics.versions.store', $topic) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Título de la Versión') }} </label>
                                <input type="text" name="title" required placeholder="{{ __('Ej: Versión Marzo 2024') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Número de Versión') }} </label>
                                <input type="number" name="version_number" required value="{{ \App\Models\TopicPdfVersion::getNextVersionNumber($topic->id) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Archivo PDF') }} </label>
                                <input type="file" name="file" required accept=".pdf"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-corporate-primary hover:file:bg-orange-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Notas de Cambios') }} </label>
                                <textarea name="change_notes" rows="3" placeholder="{{ __('¿Qué ha cambiado en esta versión?') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"></textarea>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_current" id="is_current" value="1" checked
                                    class="h-4 w-4 text-corporate-primary focus:ring-corporate-primary border-gray-300 rounded">
                                <label for="is_current" class="ml-2 block text-sm text-gray-900">
                                    {{ __('Establecer como versión actual') }}
                                </label>
                            </div>

                            <button type="submit" class="w-full bg-corporate-primary text-white py-2 rounded-lg font-bold hover:bg-orange-600 transition">
                                {{ __('Subir Versión') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Column -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Ver.') }} </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Título / Notas') }} </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Subido por') }} </th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($versions as $version)
                                <tr class="{{ $version->is_current ? 'bg-orange-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        {{ $version->version_number }}
                                        @if($version->is_current)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-corporate-primary text-white">
                                                {{ __('ACTUAL') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $version->title }} </div>
                                        <div class="text-sm text-gray-500 italic">{{ $version->change_notes ?? __('Sin notas') }} </div>
                                        <div class="text-xs text-gray-400 mt-1">{{ $version->upload_date->format('d/m/Y H:i') }} </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $version->uploader->name ?? __('Sistema') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-3">
                                            <a href="{{ Storage::url($version->file_path) }}" target="_blank" class="text-gray-400 hover:text-gray-600" title="{{ __('Descargar') }}">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>

                                            @if(!$version->is_current)
                                                <form action="{{ route('admin.versiones.establecer-actual', $version) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900 text-xs font-bold uppercase">
                                                        {{ __('Activar') }}
                                                    </button>
                                                </form>

                                                <form action="{{ route('admin.versiones.eliminar', $version) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Seguro que quieres eliminar esta versión?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">
                                        {{ __('No hay versiones registradas para este tema.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
