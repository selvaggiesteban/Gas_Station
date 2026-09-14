<x-shop-layout>
    <div class="max-w-3xl mx-auto">
        <!-- Success Card -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8">
            <!-- Header with Confetti Effect -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 px-8 py-12 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="success-dots" width="20" height="20" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1" fill="white"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#success-dots)"/>
                    </svg>
                </div>

                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full mb-6 shadow-2xl animate-bounce">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-black text-white mb-3">{{ __('¡Gracias por tu pedido!') }} </h1>
                    <p class="text-green-100 text-lg">{{ __('Tu inscripción se ha procesado correctamente.') }} </p>
                </div>
            </div>

            @if($order)
                <div class="p-8">
                    <!-- Order Number -->
                    <div class="text-center mb-8 pb-8 border-b border-gray-100">
                        <p class="text-sm text-gray-500 uppercase tracking-widest font-bold mb-2">{{ __('Número de Pedido') }} </p>
                        <p class="text-4xl font-black text-corporate-primary tracking-wider">{{ $order->order_number }} </p>
                    </div>

                    <!-- Payment Instructions -->
                    @if($order->payment_method === 'transfer')
                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-lg font-bold text-blue-900 mb-2">{{ __('Instrucciones de Pago') }} </h3>
                                    <p class="text-blue-800 text-sm mb-4">{{ __('Realiza la transferencia del importe total indicando tu número de pedido en el concepto.') }} </p>
                                    <div class="bg-white rounded-xl p-4 border border-blue-200 space-y-3">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                                            <span class="text-xs text-blue-500 font-semibold uppercase">{{ __('Importe') }} </span>
                                            <span class="text-2xl font-bold text-corporate-primary">{{ number_format($order->total_amount, 2, ',', '.') }}€</span>
                                        </div>
                                        <div class="border-t border-blue-100 pt-3">
                                            <span class="text-xs text-blue-500 font-semibold uppercase">{{ __('IBAN') }} </span>
                                            <p class="text-sm font-mono font-bold text-corporate-secondary select-all bg-gray-50 p-2 rounded mt-1">ES37 0049 4451 3226 1003 1820</p>
                                        </div>
                                        <div class="border-t border-blue-100 pt-3">
                                            <span class="text-xs text-blue-500 font-semibold uppercase">{{ __('Concepto (obligatorio)') }} </span>
                                            <p class="text-sm font-mono font-bold text-corporate-secondary select-all bg-corporate-primary/10 p-2 rounded mt-1">
                                                {{ $order->order_number }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2 text-sm text-blue-700">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>{{ __('Te activaremos el acceso en cuanto recibamos el pago (1-2 días hábiles)') }} </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-8">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-green-900 mb-2">{{ __('Pago Confirmado') }} </h3>
                                    <p class="text-green-800 text-sm">{{ __('Hemos recibido tu pago correctamente. En unos minutos recibirás un email con tus credenciales de acceso.') }} </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Order Summary -->
                    <div class="bg-gray-50 rounded-2xl p-6 mb-8">
                        <h4 class="font-bold text-corporate-secondary mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>{{ __('Resumen del Pedido') }} </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ __('Subtotal') }} </span>
                                <span class="font-medium">{{ number_format($order->subtotal, 2, ',', '.') }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ __('IVA (21%)') }} </span>
                                <span class="font-medium">{{ number_format($order->tax_amount, 2, ',', '.') }}€</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 flex justify-between">
                                <span class="font-bold text-corporate-secondary">{{ __('Total') }} </span>
                                <span class="font-bold text-corporate-primary text-lg">{{ number_format($order->total_amount, 2, ',', '.') }}€</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-bold text-corporate-secondary mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>{{ __('Datos de Facturación') }} </h4>
                            <div class="text-sm text-gray-600">
                                <p class="font-medium text-corporate-secondary">{{ $order->name }} </p>
                                <p>NIF: {{ $order->nif }} </p>
                                <p>{{ $order->email }} </p>
                                @if($order->phone)<p>{{ $order->phone }} </p>
                                @endif
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-corporate-secondary mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>{{ __('Dirección') }} </h4>
                            <div class="text-sm text-gray-600">
                                <p>{{ $order->address }} </p>
                                <p>{{ $order->postal_code }} {{ $order->city }} </p>
                                <p>{{ $order->province }} </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="p-8 border-t border-gray-100 flex flex-col sm:flex-row justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center bg-gradient-to-r from-corporate-primary to-orange-500 text-white px-10 py-4 rounded-xl font-bold hover:shadow-xl transition duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>{{ __('Ir a mi Panel') }} </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center bg-gradient-to-r from-corporate-primary to-orange-500 text-white px-10 py-4 rounded-xl font-bold hover:shadow-xl transition duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>{{ __('Iniciar Sesión') }} </a>
                @endauth

                <a href="{{ route('cursos.catalogo') }}" class="inline-flex items-center justify-center border-2 border-corporate-secondary text-corporate-secondary px-10 py-4 rounded-xl font-bold hover:bg-corporate-secondary hover:text-white transition duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>{{ __('Seguir Comprando') }} </a>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-12 bg-gray-50 rounded-2xl p-6 border border-gray-200">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-corporate-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-corporate-secondary mb-1">{{ __('¿Necesitas ayuda?') }} </h4>
                    <p class="text-sm text-gray-600 mb-3">{{ __('Si tienes alguna pregunta sobre tu pedido, contacta con nuestro equipo de soporte.') }} </p>
                    <a href="mailto:soporte@academicus.com" class="inline-flex items-center text-corporate-primary font-medium hover:text-orange-600 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>{{ __('soporte@academicus.com') }} </a>
                </div>
            </div>
        </div>
    </div>
</x-shop-layout>
