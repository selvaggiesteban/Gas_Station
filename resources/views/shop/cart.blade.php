<x-shop-layout>
    <!-- Header -->
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-corporate-secondary">{{ __('Tu Carrito') }} </h1>
            <span class="text-sm text-gray-500">{{ count($items) }} producto(s)</span>
        </div>

        @if(count($items) > 0)
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 mb-8">
                <!-- Progress Steps -->
                <div class="bg-corporate-secondary px-8 py-6">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-corporate-primary text-white font-bold">1</div>
                            <span class="ml-3 text-white font-medium">{{ __('Carrito') }} </span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-600 mx-4"></div>
                        <div class="flex items-center opacity-50">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-gray-500 text-gray-400 font-bold">2</div>
                            <span class="ml-3 text-gray-400">{{ __('Pago') }} </span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-600 mx-4"></div>
                        <div class="flex items-center opacity-50">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-gray-500 text-gray-400 font-bold">3</div>
                            <span class="ml-3 text-gray-400">{{ __('Confirmación') }} </span>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="divide-y divide-gray-100">
                    @foreach($items as $id => $item)<div class="p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between group hover:bg-gray-50 transition duration-200">
                            <div class="flex items-center mb-4 sm:mb-0 w-full">
                                <div class="bg-gradient-to-br from-corporate-primary/20 to-corporate-primary/5 p-4 rounded-xl mr-6 border border-corporate-primary/10">
                                    <svg class="w-10 h-10 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-lg font-bold text-corporate-secondary">{{ $item['course_name'] }} </h3>
                                    <p class="text-gray-500 text-sm flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-corporate-primary/10 text-corporate-primary">
                                            {{ $item['name'] }}
                                        </span>
                                        @if(isset($item['duration_days']))
                                            <span class="text-gray-400">• {{ $item['duration_days'] }} días de acceso</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6 sm:gap-8 w-full sm:w-auto justify-between sm:justify-end">
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-corporate-secondary">{{ number_format($item['price'], 2, ',', '.') }}€</span>
                                    @if(isset($item['original_price']) && $item['original_price'] > $item['price'])
                                        <p class="text-sm text-gray-400 line-through">{{ number_format($item['original_price'], 2, ',', '.') }}€</p>
                                    @endif
                                </div>

                                <form action="{{ route('carrito.eliminar', $id) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2 text-red-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        <span class="text-sm font-medium sm:hidden">{{ __('Eliminar') }} </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Summary -->
                <div class="bg-gradient-to-br from-gray-50 to-white px-8 py-8 border-t border-gray-100">
                    <div class="max-w-md ml-auto space-y-4">
                        <div class="flex justify-between text-gray-600">
                            <span>{{ __('Subtotal') }} </span>
                            <span class="font-medium">{{ number_format($total, 2, ',', '.') }}€</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>{{ __('IVA (21%)') }} </span>
                            <span class="font-medium">{{ __('Incluido') }} </span>
                        </div>
                        @if(isset($savings) && $savings > 0)
                            <div class="flex justify-between text-green-600">
                                <span>{{ __('Descuento aplicado') }} </span>
                                <span class="font-medium">-{{ number_format($savings, 2, ',', '.') }}€</span>
                            </div>
                        @endif
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-corporate-secondary">{{ __('Total') }} </span>
                                <div class="text-right">
                                    <span class="text-3xl font-black text-corporate-primary">{{ number_format($total, 2, ',', '.') }}€</span>
                                    <p class="text-xs text-gray-500">{{ __('IVA incluido') }} </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <a href="{{ route('tienda.inicio') }}" class="inline-flex items-center text-gray-600 hover:text-corporate-primary font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>{{ __('Seguir comprando') }} </a>

                <a href="{{ route('pago.confirmar') }}" class="w-full sm:w-auto bg-gradient-to-r from-corporate-primary to-orange-500 text-white px-10 py-4 rounded-xl font-bold text-lg hover:shadow-xl transition duration-300 flex items-center justify-center group">{{ __('Proceder al pago') }} <svg class="w-6 h-6 ml-2 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>

            <!-- Trust Badges -->
            <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <svg class="w-8 h-8 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <p class="text-xs text-gray-600 font-medium">{{ __('Pago seguro') }} </p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <svg class="w-8 h-8 mx-auto mb-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-xs text-gray-600 font-medium">{{ __('Factura incluida') }} </p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <svg class="w-8 h-8 mx-auto mb-2 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-xs text-gray-600 font-medium">{{ __('Acceso inmediato') }} </p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <svg class="w-8 h-8 mx-auto mb-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <p class="text-xs text-gray-600 font-medium">{{ __('7 días garantía') }} </p>
                </div>
            </div>
        @else
            <!-- Empty Cart -->
            <div class="bg-white rounded-3xl p-16 text-center shadow-xl border border-gray-100">
                <div class="w-28 h-28 rounded-full bg-gradient-to-br from-corporate-primary/20 to-corporate-secondary/10 flex items-center justify-center mx-auto mb-8">
                    <svg class="w-14 h-14 text-corporate-primary/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-corporate-secondary mb-3">{{ __('Tu carrito está vacío') }} </h2>
                <p class="text-gray-500 mb-10 max-w-md mx-auto">{{ __('Explora nuestros cursos y encuentra el plan perfecto para tu preparación.') }} </p>
                <a href="{{ route('cursos.catalogo') }}" class="inline-flex items-center bg-corporate-primary text-white px-8 py-4 rounded-xl font-bold hover:bg-orange-600 transition duration-300 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>{{ __('Explorar Cursos') }} </a>
            </div>
        @endif
    </div>
</x-shop-layout>
