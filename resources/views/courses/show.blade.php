<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $course->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-2xl font-bold mb-4">{{ __('Sobre este curso') }} </h3>
                    <p class="mb-4">{{ $course->description }} </p>
                    <div class="flex items-center text-sm font-medium text-corporate-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>{{ __('Acceso ilimitado 24/7') }} </div>
                </div>
            </div>

            <h3 class="text-xl font-bold mb-4 text-gray-800">{{ __('Contenido del Curso') }} </h3>
            
            <div class="space-y-4">
                @foreach($course->blocks as $block)<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4 border-b border-gray-100 bg-gray-50">
                            <h4 class="font-bold text-lg text-corporate-primary">
                                Bloque {{ $loop->iteration }}: {{ $block->title }}
                            </h4>
                        </div>
                        <div class="p-0">
                            <ul class="divide-y divide-gray-100">
                                @foreach($block->topics as $topic)<li class="hover:bg-gray-50 transition">
                                        <a href="{{ route('temas.ver', $topic) }}" class="p-4 flex items-center justify-between w-full">
                                            <div class="flex items-center">
                                                @if(Auth::user()->completedTopics->contains($topic->id))<svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @else
                                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                @endif
                                                <span class="text-gray-700 {{ Auth::user()->completedTopics->contains($topic->id) ? 'line-through text-gray-400' : '' }}">{{ $topic->title }} </span>
                                                @if($topic->class_type)
                                                    @php
                                                        $badgeColor = match($topic->class_type) {
                                                            'Explicativa' => 'bg-blue-100 text-blue-800',
                                                            'FlashClass' => 'bg-purple-100 text-purple-800',
                                                            default => 'bg-gray-100 text-gray-800'
                                                        };
                                                    @endphp
                                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full {{ $badgeColor }}">
                                                        {{ $topic->class_type }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($topic->is_free_preview)<span class="px-2 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-full">{{ __('Gratis') }} </span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            <h3 class="text-xl font-bold mb-4 mt-8 text-gray-800">{{ __('Evaluaciones y Simulacros') }} </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($course->tests as $test)<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-corporate-primary">
                        <div class="p-4 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $test->title }} </h4>
                                <p class="text-xs text-gray-500 uppercase tracking-tighter">{{ str_replace('_', ' ', $test->type) }} </p>
                            </div>
                            <a href="{{ route('examenes.presentar', $test) }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-orange-600 transition">
                                {{ __('Comenzar') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
