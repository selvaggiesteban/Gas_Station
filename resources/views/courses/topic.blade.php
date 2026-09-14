<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $topic->block->course->title }} > {{ $topic->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Main Content Area -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Video Section -->
                @if($topic->video_url)
                @php
                    $videoUrl = $topic->video_url;
                    if (str_contains($videoUrl, 'youtube.com') || str_contains($videoUrl, 'youtu.be')) {
                        $separator = str_contains($videoUrl, '?') ? '&' : '?';
                        $videoUrl .= $separator . 'rel=0&modestbranding=1&showinfo=0';
                    } elseif (str_contains($videoUrl, 'vimeo.com')) {
                        $separator = str_contains($videoUrl, '?') ? '&' : '?';
                        $videoUrl .= $separator . 'dnt=1&title=0&byline=0';
                    }
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold mb-4">{{ __('Video Class') }} </h3>
                        <div class="aspect-w-16 aspect-h-9">
                            <iframe src="{{ $videoUrl }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-96 rounded"></iframe>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Content/Description -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold mb-4">{{ __('Study Material') }} </h3>
                        <div class="prose max-w-none">
                            {{ $topic->content }}
                        </div>

                        @if($topic->pdfVersions->count() > 0)
                            <div class="mt-8">
                                <h4 class="text-md font-semibold mb-3 text-gray-700">{{ __('Documentación y PDF') }} </h4>
                                
                                @php
                                    $groupedPdfs = $topic->pdfVersions->groupBy(function($item) {
                                        $title = strtolower($item->title);
                                        if (str_contains($title, 'esquema')) return 'Esquemas';
                                        if (str_contains($title, 'resumen')) return 'Resúmenes';
                                        if (str_contains($title, 'test')) return 'Tests';
                                        return 'Otros Recursos';
                                    });
                                @endphp

                                @foreach($groupedPdfs as $groupName => $versions)
                                    <div class="mb-6">
                                        <h5 class="text-xs font-bold text-corporate-primary uppercase tracking-wider mb-3 border-b border-gray-100 pb-1">{{ $groupName }} </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach($versions as $version)
                                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
                                                    <div>
                                                        <p class="font-medium text-gray-800">{{ $version->title ?: __('Versión') . ' ' . $version->version_number }} </p>
                                                        <p class="text-xs text-gray-500">{{ $version->upload_date->format('d/m/Y') }} @if($version->is_current) <span class="text-green-600 font-bold ml-1">(Actual)</span> @endif</p>
                                                    </div>
                                                    <a href="{{ URL::temporarySignedRoute('seguro.pdf.descargar', now()->addHours(2), ['topic' => $topic, 'version' => $version->id]) }}" target="_blank" class="p-2 text-red-600 hover:bg-red-50 rounded-full transition-colors">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($topic->pdf_path)
                            <div class="mt-6">
                                <a href="{{ URL::temporarySignedRoute('seguro.pdf.descargar', now()->addHours(2), ['topic' => $topic]) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    {{ __('Download PDF Resource') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-col space-y-6">
                        @php
                            $progress = \Illuminate\Support\Facades\DB::table('topic_user')
                                ->where('user_id', Auth::id())
                                ->where('topic_id', $topic->id)
                                ->first();
                        @endphp
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 w-full">
                            @foreach([
                                'phase_reading' => 'Lectura',
                                'phase_underlining' => 'Subrayado',
                                'phase_review' => 'Repaso',
                                'phase_fast_review' => 'Repaso Rápido'
                            ] as $field => $label)
                                <form action="{{ route('temas.completar', $topic) }}" method="POST" class="w-full">
                                    @csrf
                                    <input type="hidden" name="phase" value="{{ $field }}">
                                    <button type="submit" class="w-full flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all {{ $progress && isset($progress->$field) && $progress->$field ? 'bg-green-50 border-green-500 text-green-700' : 'bg-white border-gray-200 text-gray-500 hover:border-corporate-primary hover:text-corporate-primary' }}">
                                        <span class="text-xs font-bold uppercase tracking-wider">{{ __($label) }} </span>
                                        @if($progress && isset($progress->$field) && $progress->$field)
                                            <svg class="w-6 h-6 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        @else
                                            <svg class="w-6 h-6 mt-1 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                            <a href="{{ route('cursos.ver', $topic->block->course) }}" class="text-gray-600 hover:text-gray-900 font-bold text-sm">{{ __('&larr; Volver al Temario') }} </a>
                            
                            @if(Auth::user()->completedTopics->contains($topic->id))
                                <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-bold text-xs uppercase tracking-widest">{{ __('Completado') }} </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pomodoro Timer -->
                @include('components.pomodoro')

                <!-- Navigation -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="font-bold mb-4">{{ __('Navegación del Temario') }} </h4>
                    <ul class="space-y-2">
                        @foreach($topic->block->course->blocks as $block)<li class="font-semibold text-xs text-gray-500 uppercase mt-4">{{ $block->title }} </li>
                            @foreach($block->topics as $t)<li>
                                    <a href="{{ route('temas.ver', $t) }}" class="block p-2 rounded {{ $topic->id === $t->id ? 'bg-orange-50 text-corporate-primary' : 'hover:bg-gray-50' }}">
                                        <div class="flex items-center justify-between">
                                            <span>{{ $t->title }} </span>
                                            @if(Auth::user()->completedTopics->contains($t->id))<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
