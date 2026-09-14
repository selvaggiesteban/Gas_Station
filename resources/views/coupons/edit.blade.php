{{-- resources/views/coupons/edit.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">{{ __('Editar Cupón') }} </h1>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('gestor-contenido.cupones.actualizar', $coupon) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Código') }} </label>
                        <input type="text" value="{{ $coupon->code }}" disabled
                               class="w-full bg-gray-100 border-gray-300 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500 mt-1">{{ __('El código no se puede modificar.') }} </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tipo de descuento *') }} </label>
                            <select name="discount_type" id="discount_type" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                                <option value="percentage" {{ old('discount_type', $coupon->discount_type) == 'percentage' ? 'selected' : '' }}>{{ __('Porcentaje (%)') }} </option>
                                <option value="fixed" {{ old('discount_type', $coupon->discount_type) == 'fixed' ? 'selected' : '' }}>{{ __('Cantidad fija (€)') }} </option>
                            </select>
                        </div>

                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Cantidad *') }} </label>
                            <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', $coupon->discount_amount) }}" required min="1"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Usos máximos (dejar vacío para ilimitado)') }} </label>
                        <input type="number" name="max_uses" id="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}" min="1"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        <p class="text-sm text-gray-500 mt-1">Usos actuales: {{ $coupon->uses_count ?? 0 }} </p>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-corporate-primary focus:ring-corporate-primary">
                            <span class="ml-2 text-sm text-gray-700">{{ __('Cupón activo') }} </span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('gestor-contenido.cupones.admin-lista') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">{{ __('Cancelar') }} </a>
                        <button type="submit" class="px-6 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('Guardar Cambios') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
