<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Revisar Supuesto Práctico') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('teacher.practical-cases.index') }}" class="text-orange-600 hover:text-orange-800">{{ __('�?� Volver a la lista') }} </a>
                    </div>

                    <div class="border-b pb-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Estudiante') }} </p>
                                <p class="font-semibold">{{ $submission->user->name }} </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Tema') }} </p>
                                <p class="font-semibold">{{ $submission->topic->title ?? 'General' }} </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Estado') }} </p>
                                <span class="px-2 py-1 text-xs rounded-full {{ $submission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($submission->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $submission->status === 'pending' ? 'Pendiente' : ($submission->status === 'approved' ? 'Aprobado' : 'Rechazado') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-2">{{ __('Respuesta del Estudiante') }} </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-800 whitespace-pre-wrap">{{ $submission->content }} </p>
                        </div>
                    </div>

                    @if($submission->attachments)<div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">{{ __('Archivos Adjuntos') }} </h3>
                            <div class="space-y-2">
                                @foreach($submission->attachments as $attachment)<a href="{{ Storage::url($attachment) }}" target="_blank" class="text-orange-600 hover:text-orange-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ $attachment }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($submission->status === \'pending\')<div class="border-t pt-6">
                            <h3 class="text-lg font-medium mb-4">{{ __('Calificar') }} </h3>

                            <form action="{{ route('teacher.practical-cases.review', $submission) }}" method="POST">
                                @csrf

                                <div class="mb-4">
                                    <x-input-label for="score" value="Puntuación (0-100)" />
                                    <x-text-input id="score" name="score" type="number" min="0" max="100" class="mt-1 block w-full" required />
                                    <x-input-error :messages="$errors->get('score')" class="mt-2" />
                                </div>

                                <div class="mb-4">
                                    <x-input-label for="feedback" value="Comentarios" />
                                    <textarea id="feedback" name="feedback" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" :placeholder="__('Proporciona retroalimentación constructiva...')"></textarea>
                                    <x-input-error :messages="$errors->get('feedback')" class="mt-2" />
                                </div>

                                <div class="mb-4">
                                    <x-input-label for="status" value="Decisión" />
                                    <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        <option value="approved">{{ __('Aprobar') }} </option>
                                        <option value="rejected">{{ __('Rechazar') }} </option>
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button>{{ __('Enviar Calificación') }} </x-primary-button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium mb-4">{{ __('Calificación') }} </h3>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-lg">
                                    <span class="font-semibold">{{ __('Puntuación:') }} </span> {{ $submission->score }}/100
                                </p>
                                @if($submission->feedback)<div class="mt-2">
                                        <p class="font-semibold">{{ __('Comentarios:') }} </p>
                                        <p class="text-gray-700 whitespace-pre-wrap">{{ $submission->feedback }} </p>
                                    </div>
                                @endif
                                <p class="text-sm text-gray-500 mt-2">
                                    Revisado el {{ $submission->reviewed_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
