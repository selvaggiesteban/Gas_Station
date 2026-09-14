<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tutorías - Preguntas y Respuestas') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }} </span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Hacer una Pregunta') }} </h3>
                    <form action="{{ route('tutorias.preguntar') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="topic_id" value="Tema relacionado" />
                            <select name="topic_id" id="topic_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">{{ __('Selecciona un tema') }} </option>
                                @foreach($courses as $course)
                                    <optgroup label="{{ $course->title }}">
                                        @foreach($course->blocks as $block)
                                            @foreach($block->topics as $topic)<option value="{{ $topic->id }}">{{ $topic->title }} </option>
                                            @endforeach
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('topic_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="title" value="Título de la pregunta" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" required :placeholder="__('¿Cuál es tu duda?')" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="content" value="Descripción detallada" />
                            <textarea id="content" name="content" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required :placeholder="__('Describe tu duda con más detalle...')"></textarea>
                            <x-input-error :messages="$errors->get('content')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Enviar Pregunta') }} </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Mis Preguntas') }} </h3>

                    @if($myQuestions->isEmpty())<p class="text-gray-500">{{ __('Aún no has realizado ninguna pregunta.') }} </p>
                    @else
                        <div class="space-y-4">
                            @foreach($myQuestions as $question)
                                <div class="border rounded-lg p-4 {{ $question->status === 'resolved' ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-lg font-bold text-gray-900">
                                            <a href="{{ route('tutorias.ver', $question) }}" class="hover:text-orange-600">
                                                {{ $question->title }}
                                            </a>
                                        </h4>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $question->status === 'resolved' ? 'bg-green-600 text-white' : 'bg-yellow-600 text-white' }}">
                                            {{ $question->status === 'resolved' ? 'Resuelta' : 'Pendiente' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">Tema: {{ $question->topic->title ?? 'General' }} </p>
                                    <p class="text-gray-700 mb-2">{{ Str::limit($question->content, 150) }} </p>
                                    <div class="text-sm text-gray-500">
                                        Preguntada el {{ $question->created_at->format('d/m/Y') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $myQuestions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
