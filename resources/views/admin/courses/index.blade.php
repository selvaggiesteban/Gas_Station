<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Gestión de Cursos') }} </h1>
                <p class="text-gray-600 mt-2">{{ __('Crea, edita y organiza la estructura académica.') }} </p>
            </div>
            <a href="{{ route('admin.cursos.create') }}" class="btn-corporate px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('+ Nuevo Curso') }} </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Nombre') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Bloques / Temas') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Precio') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }} </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($courses as $course)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ $course->title }} </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ $course->blocks_count }} bloques / {{ $course->topics_count }} temas</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($course->price, 2, ',', '.') }}€
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $course->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"> 
                                    {{ $course->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.cursos.show', $course) }}" class="text-corporate-primary hover:text-orange-900 mr-3 font-bold">{{ __('Ver Estructura') }} </a>
                                <a href="{{ route('admin.cursos.edit', $course) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Editar') }} </a>
                                <form action="{{ route('admin.cursos.duplicar', $course) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900 mr-3">{{ __('Duplicar') }} </button>
                                </form>
                                <form action="{{ route('admin.cursos.destroy', $course) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Eliminar este curso? Se moverá a la papelera.') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Borrar') }} </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($courses->hasPages())
                <div class="px-6 py-4 bg-white border-t border-gray-200">
                    {{ $courses->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
