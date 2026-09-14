{{-- resources/views/challenges/edit.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">{{ __('Editar Desafío') }} </h1>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="#" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Pregunta *') }} </label>
                        <textarea name="question" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50" required>{{ $challenge->question_text ?? 'Pregunta de ejemplo' }} </textarea>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Explicación') }} </label>
                        <textarea name="explanation" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">{{ $challenge->explanation ?? '' }} </textarea>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('gestor-contenido.desafios.lista') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">{{ __('Cancelar') }} </a>
                        <button type="submit" class="px-6 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('Guardar Cambios') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
