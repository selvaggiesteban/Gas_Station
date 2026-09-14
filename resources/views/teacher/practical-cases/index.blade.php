<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Revisión de Supuestos Prácticos') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Envíos Pendientes de Revisión') }} </h3>

                    @if($submissions->isEmpty())<p class="text-gray-500 text-center py-8">{{ __('No hay supuestos prácticos pendientes de revisión.') }} </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estudiante') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Curso/Tema') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fecha de Envío') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($submissions as $submission)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="text-sm font-medium text-gray-900">{{ $submission->user->name }} </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $submission->topic->title ?? 'General' }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $submission->created_at->format('d/m/Y H:i') }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $submission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($submission->status === 'reviewed' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800') }}">
                                                    {{ $submission->status === 'pending' ? 'Pendiente' : ($submission->status === 'reviewed' ? 'Revisado' : 'Aprobado') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('teacher.practical-cases.show', $submission) }}" class="text-corporate-primary hover:text-orange-600">{{ __('Revisar') }} </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $submissions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
