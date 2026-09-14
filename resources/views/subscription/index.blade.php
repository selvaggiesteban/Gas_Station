<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Suscripciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($currentSubscription && $currentSubscription->isActive())<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p class="font-bold">{{ __('Suscripción Activa') }} </p>
                            <p>{{ __('Estás suscrito al plan') }} <strong>{{ optional($currentSubscription->plan)->name ?? 'Plan no encontrado' }} </strong>. Expira el: {{ $currentSubscription->ends_at->format('d/m/Y') }} </p>
                        </div>
                    @else
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                            <p>{{ __('No tienes ninguna suscripción activa.') }} </p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                        @foreach($plans as $plan)
                            <div class="border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                                <h3 class="text-xl font-bold text-gray-900 text-center">{{ $plan->name }} </h3>
                                <p class="text-3xl font-extrabold text-center my-4 text-corporate-primary">{{ number_format($plan->price, 2, ',', '.') }} €</p>
                                <p class="text-center text-gray-500 mb-6">Acceso durante {{ $plan->duration_in_days }} días</p>
                                <form action="{{ route('suscripcion.suscribirse', $plan) }}" method="POST">
                                    @csrf
                                    <button class="w-full inline-flex justify-center items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-corporate-primary focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Suscribirse Ahora') }} </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    @if(isset($invoices) && $invoices->isNotEmpty())<div class="mt-12">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">{{ __('Mi Historial de Facturas') }} </h3>
                            <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Nº Factura') }} </th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Fecha') }} </th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Importe') }} </th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Acción') }} </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    #{{ $invoice->invoice_number ?? str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $invoice->created_at->format('d/m/Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                                    {{ number_format($invoice->amount, 2, ',', '.') }} €
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="{{ route('facturas.descargar', $invoice) }}" target="_blank" class="text-corporate-primary hover:underline">{{ __('Descargar PDF') }} </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
