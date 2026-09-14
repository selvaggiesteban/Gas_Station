<x-shop-layout>
    <div class="max-w-2xl mx-auto text-center">
        <div class="bg-white rounded-3xl shadow-2xl p-16 border border-red-50">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full mb-8">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-black text-gray-900 mb-4">{{ __('El pago no se pudo completar') }} </h1>
            <p class="text-gray-500 text-lg mb-10 leading-relaxed">{{ __('Lo sentimos, ha ocurrido un error al procesar tu transacción. No se ha realizado ningún cargo en tu cuenta.') }} </p>
            
            <div class="space-y-4">
                <a href="{{ route('pago.confirmar') }}" class="block w-full bg-corporate-primary text-white py-4 rounded-xl font-bold text-lg hover:bg-opacity-90 transition transform hover:-translate-y-1 shadow-lg">{{ __('Intentar de nuevo') }} </a>
                <a href="{{ route('tienda.inicio') }}" class="block w-full text-gray-500 hover:text-corporate-primary py-4 font-bold transition">{{ __('Volver al catálogo') }} </a>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-100">
                <p class="text-sm text-gray-400 italic">{{ __('Si el problema persiste, contacta con nuestro equipo de soporte en') }} <a href="mailto:soporte@academicus.es" class="text-corporate-secondary font-bold underline">{{ __('soporte@academicus.es') }} </a>
                </p>
            </div>
        </div>
    </div>
</x-shop-layout>
