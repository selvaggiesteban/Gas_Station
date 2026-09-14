<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ __('Corregir Respuesta') }} </h1>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b">
                <p><strong>{{ __('Alumno:') }} </strong> {{ $answer->testAttempt->user->name }} </p>
                <p><strong>{{ __('Test:') }} </strong> {{ $answer->testAttempt->test->name }} </p>
            </div>
            <div class="p-6 bg-gray-50">
                <h3 class="font-bold text-lg mb-2">{{ __('Pregunta:') }} </h3>
                <p class="text-gray-700">{!! nl2br(e($answer->question->text)) !!}</p>
            </div>
            <div class="p-6">
                <h3 class="font-bold text-lg mb-2 text-corporate-primary">{{ __('Respuesta del Alumno:') }} </h3>
                <div class="prose max-w-none p-4 border rounded bg-gray-50">
                    {!! nl2br(e($answer->answer_text)) !!}
                </div>
            </div>
            <div class="p-6 bg-gray-50 border-t">
                <form action="{{ route('teacher.essays.grade', $answer) }}" method="POST">
                    @csrf
                    <h3 class="font-bold text-lg mb-4">{{ __('Calificación') }} </h3>
                    <div class="mb-4">
                        <label class="block font-medium text-gray-700">{{ __('Resultado:') }} </label>
                        <div class="mt-2 space-x-4">
                            <label><input type="radio" name="is_correct" value="1" required>{{ __('Correcta') }} </label>
                            <label><input type="radio" name="is_correct" value="0">{{ __('Incorrecta') }} </label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="feedback" class="block font-medium text-gray-700">{{ __('Feedback (Opcional):') }} </label>
                        <textarea name="feedback" id="feedback" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-4">
                         <a href="{{ route('teacher.essays.index') }}" class="px-4 py-2 rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300">{{ __('Cancelar') }} </a>
                        <button type="submit" class="px-4 py-2 rounded-md text-white bg-corporate-primary hover:bg-orange-600 font-bold">{{ __('Guardar Calificación') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>