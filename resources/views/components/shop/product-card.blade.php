@props(['product'])

@php
$savings = $product->getSavingsPercent();
$isBestValue = $product->isBestValue();
@endphp<div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 flex flex-col group overflow-hidden transform hover:-translate-y-1">
    {{-- Badge Section --}}
    @if($isBestValue)
        <div class="bg-gradient-to-r from-corporate-primary to-orange-500 text-white text-center py-2.5 text-sm font-bold tracking-wider uppercase flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>{{ __('Mejor Valor') }} </div>
    @elseif($savings)
        <div class="bg-corporate-secondary text-white text-center py-2.5 text-sm font-bold tracking-wider uppercase">
            Ahorra {{ $savings }}%
        </div>
    @endif

    <div class="p-8 flex-grow">
        {{-- Header --}}
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-bold text-corporate-secondary group-hover:text-corporate-primary transition-colors">{{ $product->name }} </h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-corporate-primary/10 text-corporate-primary mt-2">
                    {{ $product->duration_label }}
                </span>
            </div>
        </div>

        {{-- Price Section --}}
        <div class="mb-6">
            @if($savings)
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="text-lg text-gray-400 line-through">{{ number_format($product->price * 100 / (100 - $savings), 2, ',', '.') }}€</span>
                </div>
            @endif
            <div class="flex items-baseline">
                <span class="text-4xl font-extrabold text-corporate-secondary">{{ number_format($product->price, 2, ',', '.') }}€</span>
                <span class="text-gray-500 text-sm ml-2">{{ __('/ total') }} </span>
            </div>
            @if($product->duration_days > 30)
                <p class="text-sm text-corporate-primary font-medium mt-1">
                    {{ number_format($product->monthly_price, 2, ',', '.') }}€/mes
                </p>
            @endif
        </div>

        {{-- Features List --}}
        <ul class="space-y-3 mb-8">
            @if($product->features)
                @foreach($product->features as $feature)<li class="flex items-start text-sm text-gray-600">
                        <svg class="h-5 w-5 text-corporate-primary mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ $feature }} </span>
                    </li>
                @endforeach
            @else
                <li class="flex items-start text-sm text-gray-600">
                    <svg class="h-5 w-5 text-corporate-primary mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ __('Acceso ilimitado al temario completo') }} </span>
                </li>
                <li class="flex items-start text-sm text-gray-600">
                    <svg class="h-5 w-5 text-corporate-primary mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ __('Generador de tests inteligentes') }} </span>
                </li>
                <li class="flex items-start text-sm text-gray-600">
                    <svg class="h-5 w-5 text-corporate-primary mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ __('Soporte técnico prioritario') }} </span>
                </li>
            @endif
        </ul>
    </div>

    {{-- CTA Button --}}
    <div class="px-8 pb-8">
        <form action="{{ route('carrito.añadir', $product) }}" method="POST" class="w-full">
            @csrf
            <button type="submit" class="w-full {{ $isBestValue ? 'bg-corporate-primary hover:bg-orange-600' : 'bg-corporate-secondary hover:bg-gray-800' }} text-white py-4 rounded-xl font-bold transition-all duration-300 shadow-lg flex items-center justify-center group-hover:shadow-xl transform group-hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                {{ $isBestValue ? '¡Mejor Opción! - Añadir' : 'Añadir al Carrito' }}
            </button>
        </form>
        <p class="text-center text-xs text-gray-400 mt-3">{{ __('Acceso inmediato tras la compra') }} </p>
    </div>
</div>
