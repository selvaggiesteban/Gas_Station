<x-shop-layout>
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-corporate-secondary mb-10 text-center">{{ __('Finalizar Pedido') }} </h1>

        <form action="{{ route('pago.procesar') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            @csrf
            
            <!-- Left: Billing Info -->
            <div class="lg:col-span-8 space-y-8">
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="bg-corporate-primary text-white w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">1</span>{{ __('Datos de Facturación') }} </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Nombre Completo *') }} </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Email *') }} </label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="nif" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('NIF / CIF *') }} </label>
                            <input type="text" name="nif" id="nif" value="{{ old('nif', $user->nif ?? '') }}" required :placeholder="__('12345678X')" class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Teléfono') }} </label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone ?? '') }}" class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Empresa (Opcional)') }} </label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $user->company_name ?? '') }}" class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Dirección *') }} </label>
                            <input type="text" name="address" id="address" value="{{ old('address', $user->address ?? '') }}" required class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Ciudad *') }} </label>
                            <input type="text" name="city" id="city" value="{{ old('city', $user->city ?? '') }}" required class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="province" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Provincia *') }} </label>
                            <input type="text" name="province" id="province" value="{{ old('province', $user->province ?? '') }}" required class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Código Postal *') }} </label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code ?? '') }}" required class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-corporate-primary focus:ring-corporate-primary transition">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="bg-corporate-primary text-white w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">2</span>{{ __('Método de Pago') }} </h2>
                    
                    <div class="space-y-4">
                        <label class="relative flex items-center p-4 border-2 border-gray-100 rounded-xl cursor-pointer hover:bg-gray-50 transition peer-checked:border-corporate-primary has-[:checked]:border-corporate-primary has-[:checked]:bg-blue-50">
                            <input type="radio" name="payment_method" value="stripe" checked class="h-5 w-5 text-corporate-primary border-gray-300 focus:ring-corporate-primary">
                            <div class="ml-4 flex items-center justify-between w-full">
                                <div>
                                    <span class="block text-sm font-bold text-gray-900">{{ __('Tarjeta de Crédito / Débito (Stripe)') }} </span>
                                    <span class="block text-xs text-gray-500">{{ __('Pago seguro e instantáneo') }} </span>
                                </div>
                                <div class="flex space-x-2">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/d6/Visa_2021.svg" class="h-6" :alt="__('Visa')">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-6" :alt="__('Mastercard')">
                                </div>
                            </div>
                        </label>

                        <label class="relative flex items-center p-4 border-2 border-gray-100 rounded-xl cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-corporate-primary has-[:checked]:bg-blue-50">
                            <input type="radio" name="payment_method" value="transfer" class="h-5 w-5 text-corporate-primary border-gray-300 focus:ring-corporate-primary">
                            <div class="ml-4">
                                <span class="block text-sm font-bold text-gray-900">{{ __('Transferencia Bancaria') }} </span>
                                <span class="block text-xs text-gray-500">{{ __('Activación manual tras verificar el ingreso') }} </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 sticky top-32 overflow-hidden">
                    <div class="p-8 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">{{ __('Resumen del Pedido') }} </h2>
                    </div>
                    
                    <div class="p-8 space-y-6">
                        @foreach($items as $item)
                            <div class="flex justify-between text-sm">
                                <div class="flex-grow pr-4">
                                    <p class="font-bold text-gray-900">{{ $item['course_name'] }} </p>
                                    <p class="text-gray-500">{{ $item['name'] }} </p>
                                </div>
                                <span class="font-bold text-gray-900">{{ number_format($item['price'], 2, ',', '.') }}€</span>
                            </div>
                        @endforeach
                        
                        <div class="border-t border-gray-100 pt-6 space-y-4">
                            <div class="flex justify-between text-gray-600">
                                <span>{{ __('Subtotal') }} </span>
                                <span>{{ number_format($total, 2, ',', '.') }}€</span>
                            </div>
                            <div class="flex justify-between text-2xl font-black text-corporate-secondary pt-2 border-t border-gray-100">
                                <span>{{ __('Total') }} </span>
                                <span class="text-corporate-primary">{{ number_format($total, 2, ',', '.') }}€</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-corporate-primary text-white py-5 rounded-xl font-black text-xl hover:bg-opacity-90 transition transform hover:-translate-y-1 shadow-2xl mt-8 flex items-center justify-center">{{ __('Realizar Pago') }} <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </button>
                        
                        <p class="text-center text-xs text-gray-400 mt-6">{{ __('Tus datos personales se utilizarán para procesar tu pedido, mejorar tu experiencia en esta web y para otros propósitos descritos en nuestra política de privacidad.') }} </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-shop-layout>
