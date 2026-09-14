{{-- resources/views/admin/tests/index.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">{{ __('Gestión de Exámenes') }} </h1>
            <a href="{{ route('admin.cursos.index') }}" class="px-4 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('← Volver a Cursos') }} </a>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Título') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Curso') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Preguntas') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Nota mínima') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }} </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tests ?? [] as $test)
                        <tr>
                            <td class="px-6 py-4">{{ $test->title }} </td>
                            <td class="px-6 py-4">{{ $test->course->title ?? 'N/A' }} </td>
                            <td class="px-6 py-4">{{ $test->questions_count ?? 0 }} </td>
                            <td class="px-6 py-4">{{ $test->passing_score ?? 70 }}%</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.examenes.show', $test) }}" class="text-blue-600 hover:text-blue-800">{{ __('Ver') }} </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">{{ __('No hay exámenes registrados.') }} </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
