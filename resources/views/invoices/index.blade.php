<x-app-layout>


<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Mis Facturas') }} </h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        @if($invoices->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Número') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fecha') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Plan') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Importe') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($invoices as $invoice)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $invoice->invoice_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $invoice->issue_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $invoice->subscription->plan->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                {{ number_format($invoice->total_amount, 2, ',', '.') }} �?�
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'sent' => 'bg-orange-100 text-orange-800',
                                        'paid' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Borrador',
                                        'sent' => 'Enviada',
                                        'paid' => 'Pagada',
                                        'cancelled' => 'Cancelada'
                                    ];
                                @endphp') }} <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$invoice->status] }}">
                                    {{ $statusLabels[$invoice->status] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-corporate-primary hover:text-orange-600 mr-3">{{ __('Ver') }} </a>
                                @if($invoice->hasPdf())<a href="{{ route('invoices.download', $invoice) }}" class="text-green-600 hover:text-green-900">{{ __('Descargar PDF') }} </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4">
                {{ $invoices->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No hay facturas') }} </h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Aún no tienes facturas generadas.') }} </p>
            </div>
        @endif
    </div>
</div>
</x-app-layout>
