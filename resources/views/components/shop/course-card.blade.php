@props(['course' => null])

@if($course)
<div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 overflow-hidden group">
    {{-- Image/Icon Section --}}
    <div class="h-48 bg-gradient-to-br from-corporate-primary/20 to-corporate-secondary/10 flex items-center justify-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="dots-{{ $course->id }}" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="currentColor" class="text-corporate-primary"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#dots-{{ $course->id }})"/>
            </svg>
        </div>

        {{-- Course Icon --}}
        <div class="w-20 h-20 bg-white rounded-2xl shadow-lg flex items-center justify-center z-10 group-hover:scale-110 transition-transform duration-300">
            <svg class="w-10 h-10 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>

        {{-- Product Count Badge --}}
        @if($course->products && $course->products->count() > 0)
            <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full shadow-sm">
                <span class="text-xs font-semibold text-corporate-secondary">
                    {{ $course->products->count() }} plan{{ $course->products->count() > 1 ? 'es' : '' }}
                </span>
            </div>
        @endif
    </div>

    {{-- Content Section --}}
    <div class="p-6">
        <h3 class="text-xl font-bold text-corporate-secondary mb-2 group-hover:text-corporate-primary transition-colors">
            {{ $course->title }}
        </h3>

        @if($course->description)
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                {{ $course->description }}
            </p>
        @endif

        {{-- Stats --}}
        <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
            @if($course->blocks)
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    {{ $course->blocks->count() }} bloques
                </div>
            @endif

            @if($course->products && $course->products->count() > 0)
                @php
                    $minPrice = $course->products->min('price');
                @endphp
                <div class="flex items-center text-corporate-primary font-medium">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Desde {{ number_format($minPrice, 2, ',', '.') }}€
                </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-3">
            @if($course->slug)
                <a href="{{ route('curso.detalles', $course->slug) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border-2 border-corporate-secondary text-corporate-secondary rounded-xl font-semibold text-sm hover:bg-corporate-secondary hover:text-white transition-all duration-300">{{ __('Ver detalles') }} </a>
            @endif

            @if($course->products && $course->products->count() > 0)
                <form action="{{ route('carrito.añadir', $course->products->first()) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-corporate-primary text-white rounded-xl font-semibold text-sm hover:bg-orange-600 transition-all duration-300 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>{{ __('Añadir') }} </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endif
