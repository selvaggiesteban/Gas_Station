<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Editar Curso') }} </h1>
            <a href="{{ route('admin.cursos.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Volver') }} </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('admin.cursos.update', $course) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700">{{ __('Nombre del Curso') }} </label>
                    <input type="text" name="title" id="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" value="{{ old('title', $course->title) }}">
                    @error('title') <span class="text-red-500 text-xs">{{ $message }} </span> @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">{{ __('Descripción') }} </label>
                    <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">{{ old('description', $course->description) }} </textarea>
                    @error('description') <span class="text-red-500 text-xs">{{ $message }} </span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">{{ __('Precio (€)') }} </label>
                        <input type="number" step="0.01" min="0" name="price" id="price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" value="{{ old('price', $course->price) }}">
                        @error('price') <span class="text-red-500 text-xs">{{ $message }} </span> @enderror
                    </div>
                    
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700">{{ __('Estado') }} </label>
                        <select name="is_active" id="is_active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            <option value="1" {{ old('is_active', $course->is_active) == '1' ? 'selected' : '' }}>{{ __('Activo (Visible)') }} </option>
                            <option value="0" {{ old('is_active', $course->is_active) == '0' ? 'selected' : '' }}>{{ __('Inactivo (Borrador)') }} </option>
                        </select>
                        @error('is_active') <span class="text-red-500 text-xs">{{ $message }} </span> @enderror
                    </div>
                </div>

                <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.cursos.show', $course) }}" class="text-corporate-primary hover:underline font-medium">{{ __('Gestionar Bloques y Temas →') }} </a>
                    <button type="submit" class="bg-corporate-primary text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('Actualizar Curso') }} </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>