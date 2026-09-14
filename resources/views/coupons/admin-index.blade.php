{{-- resources/views/coupons/admin-index.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">{{ __('Gestión de Cupones') }} </h1>
            <a href="{{ route('gestor-contenido.cupones.crear') }}" class="px-6 py-3 bg-corporate-primary text-white font-semibold rounded-lg hover:bg-orange-600 transition">{{ __('+ Nuevo Cupón') }} </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Código') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Descuento') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Usos') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estado') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }} </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($coupons ?? [] as $coupon)
                        <tr>
                            <td class="px-6 py-4 font-mono text-lg">{{ $coupon->code }} </td>
                            <td class="px-6 py-4">{{ $coupon->discount_amount }}{{ $coupon->discount_type === 'percentage' ? '%' : '€' }} </td>
                            <td class="px-6 py-4">{{ $coupon->uses_count ?? 0 }}/{{ $coupon->max_uses ?? '∞' }} </td>
                            <td class="px-6 py-4">
                                @if($coupon->isActive())<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('Activo') }} </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ __('Inactivo') }} </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('gestor-contenido.cupones.editar', $coupon) }}" class="text-blue-600 hover:text-blue-800 mr-3">{{ __('Editar') }} </a>
                                <form action="{{ route('gestor-contenido.cupones.eliminar', $coupon) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('{{ __('¿Eliminar este cupón?') }}')">{{ __('Eliminar') }} </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">{{ __('No hay cupones registrados.') }} </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
