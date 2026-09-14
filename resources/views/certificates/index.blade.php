<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mis Certificados') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Cursos Completados') }} </h3>

                    @if($courses->isEmpty())<p class="text-gray-500">{{ __('Aún no has completado ningún curso al 100%.') }} </p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($courses as $course)
                                <div class="border rounded-lg p-4 hover:shadow-lg transition">
                                    <h4 class="font-semibold text-lg mb-2">{{ $course->title }} </h4>
                                    <p class="text-sm text-gray-600 mb-4">Progreso: {{ auth()->user()->getCourseProgress($course) }}%</p>
                                    @if(auth()->user()->getCourseProgress($course) >= 100)
                                        <a href="{{ route('certificates.download', $course) }}"
                                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>{{ __('Descargar Certificado') }} </a>
                                    @else
                                        <span class="text-orange-600 text-sm">{{ __('Completa el curso para obtener tu certificado') }} </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
