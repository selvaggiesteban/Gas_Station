{{-- resources/views/challenges/create.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">{{ __('Proponer Nueva Pregunta') }} </h1>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="#" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Pregunta *') }} </label>
                        <textarea name="question" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50" required></textarea>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tipo de pregunta *') }} </label>
                        <select name="type" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                            <option value="multiple_choice">{{ __('Opción múltiple') }} </option>
                            <option value="true_false">{{ __('Verdadero/Falso') }} </option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">{{ __('Opciones (marca la correcta):') }} </p>
                        @for($i = 1; $i <= 4; $i++)
                            <div class="flex items-center mb-2">
                                <input type="radio" name="correct_option" value="{{ $i }}" class="mr-2">
                                <input type="text" name="option_{{ $i }}" placeholder="Opción {{ $i }}" class="flex-1 border-gray-300 rounded-lg shadow-sm">
                            </div>
                        @endfor
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('retos.actual') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">{{ __('Cancelar') }} </a>
                        <button type="submit" class="px-6 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('Enviar Propuesta') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
