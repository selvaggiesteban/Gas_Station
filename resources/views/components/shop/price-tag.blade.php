@props([
    'price',
    'size' => 'md',
    'showCurrency' => true,
    'originalPrice' => null,
    'monthlyPrice' => null,
])

@php
$currency = $showCurrency ? '€' : '';
$formattedPrice = number_format($price, 2, ',', '.');
$sizes = [
    'sm' => 'text-2xl',
    'md' => 'text-4xl',
    'lg' => 'text-5xl',
    'xl' => 'text-6xl'
];
$sizeClass = $sizes[$size] ?? $sizes[\'md\'];
@endphp') }} <div class="inline-flex flex-col">
    @if($originalPrice)
        <div class="flex items-baseline gap-2 mb-1">
            <span class="text-lg text-gray-400 line-through">
                {{ number_format($originalPrice, 2, ',', '.') }}{{ $currency }}
            </span>
            @php
                $savings = round((($originalPrice - $price) / $originalPrice) * 100);
            @endphp
            <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
                -{{ $savings }}%
            </span>
        </div>
    @endif

    <div class="flex items-baseline">
        <span class="font-extrabold text-corporate-secondary {{ $sizeClass }}">
            {{ $formattedPrice }}
        </span>
        @if($showCurrency)
            <span class="text-lg text-gray-500 ml-1">€</span>
        @endif
    </div>

    @if($monthlyPrice)
        <p class="text-sm text-corporate-primary font-medium mt-1">
            {{ number_format($monthlyPrice, 2, ',', '.') }}€/mes
        </p>
    @endif
</div>
