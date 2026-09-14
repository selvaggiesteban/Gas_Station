<x-shop-layout>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li>
                <a href="{{ route('tienda.inicio') }}" class="hover:text-corporate-primary transition">{{ __('Inicio') }} </a>
            </li>
            <li>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </li>
            <li>
                <a href="{{ route('cursos.catalogo') }}" class="hover:text-corporate-primary transition">{{ __('Catálogo') }} </a>
            </li>
            <li>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </li>
            <li class="text-corporate-secondary font-medium truncate max-w-xs">{{ $course->title }} </li>
        </ol>
    </nav>

    <!-- Course Hero -->
    <div class="bg-corporate-secondary rounded-3xl overflow-hidden mb-12 shadow-2xl">
        <div class="max-w-7xl mx-auto px-8 py-16">
            <div class="flex flex-col lg:flex-row gap-12 items-center">
                {{-- Course Info --}}
                <div class="lg:w-1/2">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-3 py-1 bg-corporate-primary text-white text-xs font-bold rounded-full">{{ __('PREPARACIÓN OPOSICIONES') }} </span>
                        @if($course->is_active ?? true)<span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">{{ __('ACTIVO') }} </span>
                        @endif
                    </div>

                    <h1 class="text-4xl lg:text-5xl font-extrabold text-white mb-6">
                        {{ $course->title }}
                    </h1>

                    @if($course->description)<p class="text-lg text-gray-300 mb-8">
                            {{ $course->description }}
                        </p>
                    @endif

                    <div class="flex flex-wrap gap-6 mb-8">
                        @if($course->blocks)<div class="flex items-center text-white">
                                <svg class="w-6 h-6 text-corporate-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <span>{{ $course->blocks->count() }} bloques de estudio</span>
                            </div>
                        @endif

                        @if($course->products)<div class="flex items-center text-white">
                                <svg class="w-6 h-6 text-corporate-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>{{ $course->products->count() }} planes disponibles</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <a href="#planes" class="bg-corporate-primary text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-orange-600 transition transform hover:-translate-y-1 shadow-lg inline-flex items-center">{{ __('Ver planes') }} <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </a>
                        <a href="{{ route('cursos.catalogo') }}" class="text-gray-300 hover:text-white font-medium transition">{{ __('← Volver al catálogo') }} </a>
                    </div>
                </div>

                {{-- Course Visual --}}
                <div class="lg:w-1/2">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                        <div class="flex items-center justify-center h-48">
                            <div class="w-32 h-32 bg-corporate-primary rounded-3xl flex items-center justify-center shadow-2xl">
                                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mt-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-white">95%</div>
                                <div class="text-sm text-gray-400">{{ __('Aprobados') }} </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-white">24/7</div>
                                <div class="text-sm text-gray-400">{{ __('Acceso') }} </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-white">IA</div>
                                <div class="text-sm text-gray-400">{{ __('Tests') }} </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plans Section -->
    <div id="planes" class="mb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-corporate-secondary mb-4">{{ __('Elige tu Plan') }} </h2>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ __('Selecciona el plan que mejor se adapte a tu ritmo de estudio y presupuesto. Todos incluyen acceso completo al temario.') }} </p>
        </div>

        @if($course->products && $course->products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($course->products as $product)<x-shop.product-card :product="$product" />
                @endforeach
            </div>

            {{-- Comparison Table --}}
            <div class="mt-16 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-corporate-secondary">{{ __('Comparativa de Planes') }} </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">{{ __('Característica') }} </th>
                                @foreach($course->products as $product)<th class="px-6 py-4 text-center text-sm font-semibold {{ $product->isBestValue() ? 'text-corporate-primary' : 'text-gray-600' }}">
                                        {{ $product->name }}
                                        @if($product->isBestValue())<span class="block text-xs font-normal text-corporate-primary">{{ __('⭐ Mejor valor') }} </span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ __('Precio') }} </td>
                                @foreach($course->products as $product)<td class="px-6 py-4 text-center text-sm font-bold {{ $product->isBestValue() ? 'text-corporate-primary' : 'text-corporate-secondary' }}">
                                        {{ number_format($product->price, 2, ',', '.') }}€
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ __('Duración') }} </td>
                                @foreach($course->products as $product)<td class="px-6 py-4 text-center text-sm text-gray-900">
                                        {{ $product->duration_label }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ __('Acceso ilimitado') }} </td>
                                @foreach($course->products as $product)<td class="px-6 py-4 text-center">
                                        <svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ __('Tests ilimitados') }} </td>
                                @foreach($course->products as $product)<td class="px-6 py-4 text-center">
                                        <svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ __('Soporte técnico') }} </td>
                                @foreach($course->products as $product)<td class="px-6 py-4 text-center">
                                        <svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 rounded-2xl">
                <p class="text-gray-600">{{ __('No hay planes disponibles para este curso actualmente.') }} </p>
            </div>
        @endif
    </div>

    <!-- FAQ Section -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
        <h3 class="text-2xl font-bold text-corporate-secondary mb-6 text-center">{{ __('Preguntas Frecuentes') }} </h3>

        <div class="space-y-4 max-w-3xl mx-auto">
            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left">
                    <span class="font-semibold text-corporate-secondary">{{ __('¿Qué incluye el acceso al curso?') }} </span>
                    <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <p class="mt-2 text-gray-600">{{ __('El acceso incluye todo el temario actualizado, tests ilimitados con IA, seguimiento de progreso, videoclases, tutorías y soporte técnico.') }} </p>
            </div>

            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left">
                    <span class="font-semibold text-corporate-secondary">{{ __('¿Cuándo puedo empezar?') }} </span>
                    <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <p class="mt-2 text-gray-600">{{ __('El acceso es inmediato una vez realizada la compra. Recibirás un email con tus credenciales de acceso en cuestión de minutos.') }} </p>
            </div>

            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left">
                    <span class="font-semibold text-corporate-secondary">{{ __('¿Puedo cambiar de plan más adelante?') }} </span>
                    <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <p class="mt-2 text-gray-600">{{ __('Sí, puedes ampliar tu plan en cualquier momento. Contacta con nuestro equipo de soporte para gestionar el cambio.') }} </p>
            </div>

            <div class="pb-4">
                <button class="flex justify-between items-center w-full text-left">
                    <span class="font-semibold text-corporate-secondary">{{ __('¿Hay garantía de devolución?') }} </span>
                    <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <p class="mt-2 text-gray-600">{{ __('Ofrecemos 7 días de garantía de satisfacción. Si el curso no cumple tus expectativas, te devolvemos el 100% del importe.') }} </p>
            </div>
        </div>
    </div>
</x-shop-layout>
