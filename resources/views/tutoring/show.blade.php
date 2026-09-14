<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Detalle de Pregunta') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <a href="{{ route('tutorias.lista') }}" class="text-orange-600 hover:text-orange-800">{{ __('← Volver a Tutorías') }} </a>
                    </div>

                    <div class="border-b pb-6 mb-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-2xl font-bold">{{ $question->title }} </h3>
                            <span class="px-3 py-1 text-sm rounded-full {{ $question->status === 'resolved' ? 'bg-green-600 text-white' : 'bg-yellow-600 text-white' }}">
                                {{ $question->status === 'resolved' ? 'Resuelta' : 'Pendiente' }}
                            </span>
                        </div>

                        <div class="text-sm text-gray-500 mb-4">
                            Tema: {{ $question->topic->title ?? 'General' }} |
                            Preguntado el {{ $question->created_at->format('d/m/Y H:i') }}
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-800 whitespace-pre-wrap">{{ $question->content }} </p>
                        </div>
                    </div>

                    @if($question->answer)<div class="bg-blue-50 p-6 rounded-lg mb-6">
                            <h4 class="font-semibold text-lg mb-4 text-orange-900">{{ __('Respuesta del Profesor') }} </h4>

                            <div class="flex items-center mb-4">
                                @if($question->teacher->avatar)<img src="{{ $question->teacher->avatar }}" alt="" class="w-10 h-10 rounded-full mr-3">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-orange-600 text-white flex items-center justify-center mr-3">
                                        {{ substr($question->teacher->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium">{{ $question->teacher->name }} </p>
                                    <p class="text-sm text-gray-500">{{ $question->answered_at->format('d/m/Y H:i') }} </p>
                                </div>
                            </div>

                            <div class="text-gray-800 whitespace-pre-wrap">{{ $question->answer }} </div>
                        </div>

                        @if($question->status !== 'resolved' && $question->user_id === auth()->id())<form action="{{ route('tutorias.resolver', $question) }}" method="POST">
                                @csrf
                                <x-primary-button>{{ __('Marcar como Resuelta') }} </x-primary-button>
                            </form>
                        @endif
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>{{ __('Esperando respuesta de un profesor...') }} </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
