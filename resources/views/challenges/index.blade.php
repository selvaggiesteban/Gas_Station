{{-- resources/views/challenges/index.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">{{ __('Desafíos de Preguntas') }} </h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <p class="text-gray-600 mb-4">{{ __('Los estudiantes pueden proponer preguntas para los tests.
                Revisa las propuestas pendientes y aprueba las que consideres adecuadas.') }} </p>

            <div class="flex gap-4">
                <a href="{{ route('gestor-contenido.desafios.lista') }}" class="inline-flex items-center px-6 py-3 bg-corporate-primary text-white font-semibold rounded-lg hover:bg-orange-600 transition">{{ __('Ir a Gestión de Desafíos') }} </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Pregunta') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Autor') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estado') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Fecha') }} </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">{{ __('Accede a la gestión de desafíos para ver el listado completo.') }} </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
