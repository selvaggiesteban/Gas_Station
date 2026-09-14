<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-black rounded-lg shadow-xl overflow-hidden aspect-video relative">
                @if(str_contains($topic->video_url, 'youtube.com') || str_contains($topic->video_url, 'youtu.be'))
                    @php
                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $topic->video_url, $match);
                        $videoId = $match[1] ?? null;
                    @endphp
                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $videoId }} ?rel=0&modestbranding=1&controls=1" frameborder="0" allowfullscreen oncontextmenu="return false;"></iframe>
                @elseif(str_contains($topic->video_url, 'vimeo.com'))
                    @php
                        preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $topic->video_url, $match);
                        $videoId = $match[1] ?? null;
                    @endphp
                    <iframe src="https://player.vimeo.com/video/{{ $videoId }} ?rel=0&modestbranding=1&controls=1" class="w-full h-full" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen oncontextmenu="return false;"></iframe>
                @else
                    <video class="w-full h-full" controls controlsList="nodownload" oncontextmenu="return false;">
                        <source src="{{ Storage::url($topic->video_url) }}" type="video/mp4">{{ __('Tu navegador no soporta el elemento de video.') }} </video>
                @endif
                
                <div class="absolute top-4 left-4">
                    <a href="{{ url()->previous() }}" class="bg-white/20 hover:bg-white/40 text-white px-4 py-2 rounded-lg backdrop-blur-sm transition">{{ __('← Volver al Tema') }} </a>
                </div>
            </div>
            
            <div class="mt-6 bg-white p-6 rounded-lg shadow">
                <h1 class="text-2xl font-bold text-gray-900">{{ $topic->title }} </h1>
                <p class="text-gray-500 mt-2">{{ __('Estás viendo la clase en video de este tema.') }} </p>
            </div>
        </div>
    </div>
</x-app-layout>
