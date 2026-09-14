<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.cursos.show', $topic->block->course) }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">{{ __('← Volver a la Estructura') }} </a>
                <h1 class="text-3xl font-bold text-gray-900">Editar Tema: {{ $topic->title }} </h1>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Details Column -->
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">{{ __('Detalles del Tema') }} </h3>
                    <form action="{{ route('admin.temas.update', $topic) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Título') }} </label>
                                <input type="text" name="title" value="{{ $topic->title }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Fecha Disponibilidad') }} </label>
                                <input type="date" name="available_at" value="{{ $topic->available_at ? $topic->available_at->format('Y-m-d') : '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Contenido (HTML)') }} </label>
                                <textarea name="content" id="topic-content" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">{{ $topic->content }} </textarea>
                            </div>
                            <button type="submit" class="w-full bg-corporate-primary text-white py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('Guardar Cambios') }} </button>   
                        </div>
                    </form>
                </div>

                <!-- Video Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">{{ __('Video de la Clase') }} </h3>
                    @if($topic->video_url)
                        <div class="mb-4 p-3 bg-gray-50 border rounded flex justify-between items-center">
                            @if(Str::startsWith($topic->video_url, 'http'))
                                <span class="text-sm truncate mr-2">{{ $topic->video_url }} (Externo)</span>
                                <a href="{{ $topic->video_url }}" target="_blank" class="text-corporate-primary font-bold text-xs uppercase">{{ __('Ver') }} </a>
                            @else
                                <span class="text-sm truncate mr-2">{{ $topic->video_url }} </span>
                                <a href="{{ Storage::url($topic->video_url) }}" target="_blank" class="text-corporate-primary font-bold text-xs uppercase">{{ __('Ver') }} </a>
                            @endif
                        </div>
                    @endif
                    <form action="{{ route('admin.temas.subir', $topic) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="video">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enlace Externo (YouTube/Vimeo)') }} </label>
                            <input type="url" name="external_video_url" placeholder="https://www.youtube.com/watch?v=..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary text-sm">
                        </div>

                        <div class="relative flex py-2 items-center">
                            <div class="flex-grow border-t border-gray-200"></div>
                            <span class="flex-shrink mx-4 text-gray-400 text-xs uppercase">{{ __('O subir archivo') }} </span>
                            <div class="flex-grow border-t border-gray-200"></div>
                        </div>

                        <div class="mt-2">
                            <input type="file" name="file" accept="video/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-corporate-primary hover:file:bg-orange-100">
                        </div>
                        <button type="submit" class="mt-4 w-full bg-gray-800 text-white py-2 rounded-lg text-sm font-bold hover:bg-gray-700">{{ __('Actualizar Video') }} </button>
                    </form>
                </div>
            </div>

            <!-- Material Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- PDF Versions Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4 border-b pb-2">
                        <h3 class="text-xl font-bold">{{ __('Historial de Versiones PDF') }} </h3>
                        <a href="{{ route('admin.temas.versiones', $topic) }}" class="text-corporate-primary hover:underline text-sm font-bold uppercase">{{ __('Gestionar Versiones') }} </a>    
                    </div>
                    <div class="mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">{{ __('Ver.') }} </th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">{{ __('Fecha') }} </th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">{{ __('Notas') }} </th>
                                    <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">{{ __('Acciones') }} </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($topic->pdfVersions()->orderBy('version_number', 'desc')->get() as $version)
                                    <tr class="{{ $version->is_current ? 'bg-orange-50' : '' }}">
                                        <td class="px-4 py-3 text-sm font-bold">{{ $version->version_number }} </td>
                                        <td class="px-4 py-3 text-sm">{{ $version->upload_date ? $version->upload_date->format('d/m/Y') : '-' }} </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 italic">{{ $version->change_notes ?? '-' }} </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <div class="flex justify-end gap-3">
                                                @if(!$version->is_current)
                                                    <form action="{{ route('admin.versiones.establecer-actual', $version) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="text-blue-600 hover:underline font-bold text-xs">{{ __('Marcar Actual') }} </button>
                                                    </form>
                                                    <form action="{{ route('admin.versiones.eliminar', $version) }}" method="POST" onsubmit="return confirm('{{ __('¿Eliminar esta versión?') }}')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-red-500 hover:text-red-700">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="bg-corporate-primary text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">{{ __('Actual') }} </span>
                                                @endif
                                                <a href="{{ Storage::url($version->file_path) }}" target="_blank" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg></a>        
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 italic">{{ __('No hay versiones subidas.') }} </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <form action="{{ route('admin.temas.subir', $topic) }}" method="POST" enctype="multipart/form-data" class="p-4 bg-gray-50 border rounded-lg">
                        @csrf
                        <input type="hidden" name="type" value="main_pdf">
                        <h4 class="font-bold text-sm mb-3 uppercase">{{ __('Subir Nueva Versión PDF') }} </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                            <input type="file" name="file" accept="application/pdf" required class="text-sm">
                            <input type="text" name="change_notes" placeholder="{{ __('Notas de cambios (ej: Ley actualizada)') }}" class="text-sm rounded-md border-gray-300">
                        </div>
                        <button type="submit" class="w-full bg-corporate-primary text-white py-2 rounded font-bold hover:bg-orange-600 transition">{{ __('Publicar Versión') }} </button>
                    </form>
                </div>

                <!-- Additional Resources Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-xl font-bold mb-4 border-b pb-2">{{ __('Recursos Adicionales (Esquemas, Mapas, etc.)') }} </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        @forelse($topic->resources()->orderBy('display_order')->get() as $resource)
                            <div class="p-4 bg-gray-50 border rounded-lg flex justify-between items-start">
                                <div>
                                    <p class="font-bold text-gray-900">{{ $resource->title }} </p>
                                    <p class="text-xs text-gray-500 uppercase">{{ $resource->resource_type }} | {{ number_format($resource->file_size / 1024 / 1024, 2) }} MB</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ Storage::url($resource->file_path) }}" target="_blank" class="text-blue-500 hover:text-blue-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></a>
                                    <form action="{{ route('admin.recursos.eliminar', $resource) }}" method="POST" onsubmit="return confirm('{{ __('¿Eliminar recurso?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="col-span-2 text-center text-gray-400 italic py-4">{{ __('No hay recursos adicionales subidos.') }} </p>
                        @endforelse
                    </div>
                    <form action="{{ route('admin.temas.subir', $topic) }}" method="POST" enctype="multipart/form-data" class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                        @csrf
                        <input type="hidden" name="type" value="resource">
                        <h4 class="font-bold text-sm mb-3 uppercase">{{ __('Añadir Nuevo Recurso') }} </h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" name="title" required placeholder="{{ __('Título del recurso (ej: Esquema Resumen)') }}" class="text-sm rounded-md border-gray-300 shadow-sm"> 
                                <select name="resource_type" required class="text-sm rounded-md border-gray-300 shadow-sm">
                                    <option value="summary">{{ __('Resumen') }} </option>
                                    <option value="diagram">{{ __('Diagrama') }} </option>
                                    <option value="mind_map">{{ __('Mapa Mental') }} </option>
                                    <option value="extra">{{ __('Material Extra') }} </option>
                                </select>
                            </div>
                            <input type="file" name="file" required class="text-sm w-full">
                            <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded font-bold hover:bg-gray-700 transition">{{ __('Subir Recurso') }} </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.tiny.cloud/1/to1at929j9yprv2h7avtpgqgewvbpzqvjrs3pp14atbg22xa/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#topic-content',
                license_key: 'to1at929j9yprv2h7avtpgqgewvbpzqvjrs3pp14atbg22xa',
                plugins: 'advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table directionality emoticons template paste help',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                height: 400,
                language: 'es'
            });
        </script>
    @endpush
</x-app-layout>
