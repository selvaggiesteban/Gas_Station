<x-shop-layout>
    <!-- Hero Section -->
    <div class="relative bg-corporate-secondary rounded-3xl overflow-hidden mb-12 shadow-2xl">
        <div class="absolute inset-0 opacity-10">
            <svg class="h-full w-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="url(#grid)" />
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" />
                    </pattern>
                </defs>
            </svg>
        </div>

        <div class="relative max-w-7xl mx-auto py-12 px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl mb-4">{{ __('Catálogo de') }} <span class="text-corporate-primary">{{ __('Cursos') }} </span>
            </h1>
            <p class="max-w-2xl mx-auto text-lg text-gray-300">{{ __('Explora todos nuestros cursos de preparación para oposiciones de Prisiones') }} </p>
        </div>
    </div>

    <!-- Filters & Grid -->
    <div class="space-y-8">
        <!-- Filter Bar -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-gray-600 font-medium">{{ __('Filtrar por:') }} </span>
                <select class="border-gray-200 rounded-lg text-sm focus:border-corporate-primary focus:ring-corporate-primary">
                    <option>{{ __('Todos los cursos') }} </option>
                    <option>{{ __('Promoción Interna') }} </option>
                    <option>{{ __('Turno Libre') }} </option>
                    <option>{{ __('Escala Básica') }} </option>
                </select>
            </div>

            <div class="text-sm text-gray-500">
                Mostrando {{ $courses->count() }} curso{{ $courses->count() !== 1 ? 's' : '' }}
            </div>
        </div>

        <!-- Courses Grid -->
        @if($courses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($courses as $course)
                    <x-shop.course-card :course="$course" />
                @endforeach
            </div>
        @else
            <div class="text-center py-20 bg-white rounded-3xl shadow-lg border border-gray-100">
                <svg class="w-20 h-20 mx-auto mb-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('No hay cursos disponibles') }} </h3>
                <p class="text-gray-600 mb-6">{{ __('Vuelve pronto para ver nuestras novedades.') }} </p>
                <a href="{{ route('tienda.inicio') }}" class="inline-flex items-center px-6 py-3 bg-corporate-primary text-white rounded-xl font-semibold hover:bg-orange-600 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>{{ __('Volver al inicio') }} </a>
            </div>
        @endif
    </div>

    <!-- Why Choose Us -->
    <div class="mt-16 bg-white rounded-3xl p-12 shadow-lg border border-gray-100">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-corporate-secondary mb-4">{{ __('¿Por qué Academicus?') }} </h2>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ __('La mejor preparación para tus oposiciones de Prisiones') }} </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-corporate-secondary mb-2">{{ __('95% Aprobados') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Tasa de éxito en oposiciones') }} </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-corporate-secondary mb-2">{{ __('+500 Temas') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Material completo y actualizado') }} </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-corporate-secondary mb-2">{{ __('Tests IA') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Inteligencia artificial adaptativa') }} </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-corporate-secondary mb-2">{{ __('+5.000 Alumnos') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Comunidad de opositores') }} </p>
            </div>
        </div>
    </div>
</x-shop-layout>
