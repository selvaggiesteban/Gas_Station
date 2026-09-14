<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mi Temario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-12">
            
            <!-- Mis Cursos Activos -->
            <section>
                <h3 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">{{ __('Mis Cursos Activos') }} </h3>
                @if($myCourses->isEmpty())
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                        <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="text-gray-500 text-lg">{{ __('Aún no tienes cursos activos. Explora nuestro catálogo para comenzar a estudiar.') }} </p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($myCourses as $course)
                            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg border-t-4 border-corporate-primary hover:shadow-lg transition">
                                <div class="p-6 text-gray-900">
                                    <h3 class="text-lg font-bold mb-2">{{ $course->title }} </h3>
                                    <p class="text-sm text-gray-600 mb-4">{{ Str::limit($course->description, 100) }} </p>
                                    <div class="flex justify-end">
                                        <a href="{{ route('cursos.ver', $course) }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 transition">
                                            {{ __('Continuar Estudiando') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Cursos Disponibles para Adquirir -->
            @if($availableCourses->isNotEmpty())
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mb-6 border-b pb-2">{{ __('Te Puede Interesar') }} </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($availableCourses as $course)
                            <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                                <div class="p-6 text-gray-900">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-lg font-bold">{{ $course->title }} </h3>
                                        <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded font-bold">{{ number_format($course->price, 2) }}€</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-4">{{ Str::limit($course->description, 100) }} </p>
                                    <div class="flex justify-end">
                                        <a href="{{ route('cursos.catalogo') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                                            {{ __('Ir a la Tienda') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
