<x-shop-layout>
    <!-- Hero Section -->
    <div class="relative bg-corporate-secondary rounded-3xl overflow-hidden mb-16 shadow-2xl">
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
        <div class="relative max-w-7xl mx-auto py-16 px-8 sm:py-24 sm:px-12 lg:px-16 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl mb-6">{{ __('Tu Futuro en ') }} <span class="text-corporate-primary">{{ __('Prisiones ') }} </span>{{ __('Comienza Aquí') }} </h1>
            <p class="max-w-3xl mx-auto text-xl text-gray-300 mb-10">{{ __('Preparación integral con los mejores materiales, tests actualizados y seguimiento personalizado. Elige el plan que mejor se adapte a tu ritmo.') }} </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="#cursos" class="bg-corporate-primary text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-orange-600 transition transform hover:-translate-y-1 shadow-lg">{{ __('Ver Catálogo') }} </a>
                <div class="flex items-center text-white/90 text-sm font-medium bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm">
                    <svg class="w-5 h-5 text-corporate-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>{{ __('+5.000 Alumnos Aprobados') }} </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="mb-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100">
                <div class="text-3xl font-bold text-corporate-primary mb-1">95%</div>
                <div class="text-sm text-gray-600">{{ __('Tasa de Aprobación') }} </div>
            </div>
            <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100">
                <div class="text-3xl font-bold text-corporate-primary mb-1">+10K</div>
                <div class="text-sm text-gray-600">{{ __('Tests Realizados') }} </div>
            </div>
            <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100">
                <div class="text-3xl font-bold text-corporate-primary mb-1">500+</div>
                <div class="text-sm text-gray-600">{{ __('Temas de Estudio') }} </div>
            </div>
            <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100">
                <div class="text-3xl font-bold text-corporate-primary mb-1">24/7</div>
                <div class="text-sm text-gray-600">{{ __('Acceso Ilimitado') }} </div>
            </div>
        </div>
    </div>

    <!-- Courses Grid -->
    <div id="cursos" class="space-y-16">
        @forelse($courses as $course)
            <section class="bg-white rounded-3xl p-8 shadow-lg border border-gray-100">
                <!-- Course Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-gray-100">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="w-1.5 h-12 bg-corporate-primary rounded-full mr-4"></div>
                        <div>
                            <h2 class="text-3xl font-bold text-corporate-secondary">{{ $course->title }} </h2>
                            @if($course->description)<p class="text-gray-600 mt-1 max-w-2xl">{{ $course->description }} </p>
                            @endif
                        </div>
                    </div>
                    @if($course && $course->slug)<a href="{{ route('curso.detalles', $course->slug) }}" class="inline-flex items-center text-corporate-primary font-semibold hover:text-orange-700 transition">{{ __('Ver detalles del curso') }} <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    @endif
                </div>

                <!-- Products Grid -->
                @if($course->products && $course->products->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($course->products as $product)<x-shop.product-card :product="$product" />
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p>{{ __('No hay planes disponibles para este curso actualmente.') }} </p>
                    </div>
                @endif
            </section>
        @empty
            <div class="text-center py-20 bg-white rounded-3xl shadow-lg border border-gray-100">
                <svg class="w-20 h-20 mx-auto mb-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('No hay cursos disponibles') }} </h3>
                <p class="text-gray-600 mb-6">{{ __('Vuelve pronto para ver nuestras novedades.') }} </p>
            </div>
        @endforelse
    </div>

    <!-- Trust Section -->
    <div class="mt-20 bg-corporate-primary/5 rounded-3xl p-12 border border-corporate-primary/10">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-corporate-secondary mb-4">{{ __('¿Por qué elegir Academicus?') }} </h2>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ __('La plataforma líder en preparación para oposiciones de Prisiones') }} </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-corporate-secondary mb-2">{{ __('Tests Inteligentes') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Generador de tests adaptativos que aprenden de tus errores') }} </p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-corporate-secondary mb-2">{{ __('Temario Actualizado') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Material siempre al día según las últimas convocatorias') }} </p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-corporate-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-corporate-secondary mb-2">{{ __('Comunidad Activa') }} </h3>
                <p class="text-gray-600 text-sm">{{ __('Únete a miles de opositores que estudian junto a ti') }} </p>
            </div>
        </div>
    </div>
</x-shop-layout>
