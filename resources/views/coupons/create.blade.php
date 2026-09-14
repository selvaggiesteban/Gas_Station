{{-- resources/views/coupons/create.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">{{ __('Crear Nuevo Cupón') }} </h1>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('gestor-contenido.cupones.guardar') }}" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Código del cupón *') }} </label>
                        <div class="flex">
                            <input type="text" name="code" id="code" value="{{ old('code') }}" required
                                   class="flex-1 border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50 uppercase"
                                   :placeholder="__('EJ: PROMO2024')">
                            <button type="button" onclick="generateCode()" class="ml-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">{{ __('Generar') }} </button>
                        </div>
                        @error('code')
                            <p class="text-red-500 text-sm mt-1">{{ $message }} </p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tipo de descuento *') }} </label>
                            <select name="discount_type" id="discount_type" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                                <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>{{ __('Porcentaje (%)') }} </option>
                                <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>{{ __('Cantidad fija (€)') }} </option>
                            </select>
                        </div>

                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Cantidad *') }} </label>
                            <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount') }}" required min="1"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Usos máximos (dejar vacío para ilimitado)') }} </label>
                        <input type="number" name="max_uses" id="max_uses" value="{{ old('max_uses') }}" min="1"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Válido desde') }} </label>
                            <input type="datetime-local" name="valid_from" id="valid_from" value="{{ old('valid_from') }}"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        </div>

                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Válido hasta') }} </label>
                            <input type="datetime-local" name="valid_until" id="valid_until" value="{{ old('valid_until') }}"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('gestor-contenido.cupones.admin-lista') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">{{ __('Cancelar') }} </a>
                        <button type="submit" class="px-6 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('Crear Cupón') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function generateCode() {
            const code = 'ACAD' + Math.random().toString(36).substring(2, 8).toUpperCase();
            document.getElementById('code').value = code;
        }
    </script>
</x-app-layout>
