<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Revisar Impugnación') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Challenge Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4">
                            <a href="{{ route('content-manager.challenges.index') }}" class="text-orange-600 hover:text-orange-800">{{ __('← Volver a la cola') }} </a>
                        </div>

                        <div class="border-b pb-4 mb-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-medium">Impugnación #{{ $challenge->id }} </h3>
                                <span class="px-2 py-1 text-xs rounded-full {{ $challenge->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($challenge->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $challenge->status === 'pending' ? 'Pendiente' : ($challenge->status === 'approved' ? 'Aprobada' : 'Rechazada') }}
                                </span>
                            </div>

                            <div class="text-sm text-gray-500 mt-2">
                                Estudiante: {{ $challenge->user->name }} |
                                Fecha: {{ $challenge->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="font-semibold mb-2">{{ __('Razón de la Impugnación') }} </h4>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $challenge->reason }} </p>
                            </div>
                        </div>

                        @if($challenge->user_explanation)<div class="mb-6">
                                <h4 class="font-semibold mb-2">{{ __('Explicación del Estudiante') }} </h4>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-gray-800 whitespace-pre-wrap">{{ $challenge->user_explanation }} </p>
                                </div>
                            </div>
                        @endif

                        @if($challenge->status !== 'pending')<div class="mb-6">
                                <h4 class="font-semibold mb-2">{{ __('Resolución') }} </h4>
                                <div class="bg-gray-50 p-4 rounded-lg {{ $challenge->status === 'approved' ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
                                    <p class="font-semibold mb-1">Decisión: {{ $challenge->status === 'approved' ? 'Impugnación Aprobada' : 'Impugnación Rechazada' }} </p>
                                    <p class="text-gray-700 whitespace-pre-wrap">{{ $challenge->reviewer_notes }} </p>
                                    <p class="text-sm text-gray-500 mt-2">
                                        Revisado por {{ $challenge->reviewer->name ?? 'Sistema' }} el {{ $challenge->reviewed_at ? $challenge->reviewed_at->format('d/m/Y H:i') : '' }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Question Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">{{ __('Pregunta Original') }} </h3>

                        @if($challenge->question)<div class="mb-4">
                                <p class="font-semibold mb-2">{{ $challenge->question->title }} </p>
                                <p class="text-gray-700 mb-4">{{ $challenge->question->content }} </p>
                            </div>

                            <div class="mb-4">
                                <h4 class="font-medium mb-2">{{ __('Opciones:') }} </h4>
                                <div class="space-y-2">
                                    @foreach($challenge->question->options as $option)<div class="p-3 rounded-lg {{ $option->is_correct ? 'bg-green-100 border border-green-300' : 'bg-gray-50' }}">
                                            <div class="flex items-center">
                                                @if($option->is_correct)<span class="text-green-600 mr-2">✔</span>
                                                @endif
                                                <span>{{ $option->content }} </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-4">
                                <h4 class="font-medium mb-2">{{ __('Explicación:') }} </h4>
                                <p class="text-gray-700 bg-blue-50 p-3 rounded-lg">{{ $challenge->question->explanation ?? 'Sin explicación' }} </p>
                            </div>
                        @else
                            <p class="text-gray-500">{{ __('La pregunta ya no está disponible.') }} </p>
                        @endif

                        @if($challenge->status === 'pending')<div class="border-t pt-6 mt-6">
                                <h4 class="font-medium mb-4">{{ __('Tomar Decisión') }} </h4>

                                <form action="{{ route('content-manager.challenges.approve', $challenge) }}" method="POST" class="mb-4">
                                    @csrf

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Notas de resolución') }} </label>
                                        <textarea name="reviewer_notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="{{ __('Explica tu decisión...') }}"></textarea>
                                    </div>

                                    <x-primary-button type="submit" class="w-full justify-center">{{ __('Aprobar Impugnación') }} </x-primary-button>
                                </form>

                                <form action="{{ route('content-manager.challenges.reject', $challenge) }}" method="POST">
                                    @csrf

                                    <input type="hidden" name="reviewer_notes" id="reject_notes">

                                    <x-danger-button type="submit" class="w-full justify-center" onclick="document.getElementById('reject_notes').value = document.querySelector('textarea[name=reviewer_notes]').value || 'Impugnación rechazada'">{{ __('Rechazar Impugnación') }} </x-danger-button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
