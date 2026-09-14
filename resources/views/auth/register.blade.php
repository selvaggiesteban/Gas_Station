<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('Crear Cuenta') }} </h2>
        <p class="text-sm text-gray-500 mt-1">{{ __('Únete a Academicus y comienza tu preparación') }} </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nombre completo')" class="text-gray-700 font-medium" />
            <x-text-input id="name" class="block mt-2 w-full rounded-lg border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary transition-colors"
                          type="text"
                          name="name"
                          :value="old('name')"
                          :placeholder="__('Tu nombre completo')"
                          required
                          autofocus
                          autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo electrónico')" class="text-gray-700 font-medium" />
            <x-text-input id="email" class="block mt-2 w-full rounded-lg border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary transition-colors"
                          type="email"
                          name="email"
                          :value="old('email')"
                          placeholder="tu@email.com"
                          required
                          autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" class="text-gray-700 font-medium" />
            <x-text-input id="password" class="block mt-2 w-full rounded-lg border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary transition-colors"
                          type="password"
                          name="password"
                          :placeholder="__('Mínimo 8 caracteres')"
                          required
                          autocomplete="new-password" />
            <p class="text-xs text-gray-500 mt-1">{{ __('Debe tener al menos 8 caracteres') }} </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" class="text-gray-700 font-medium" />
            <x-text-input id="password_confirmation" class="block mt-2 w-full rounded-lg border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary transition-colors"
                          type="password"
                          name="password_confirmation"
                          :placeholder="__('Repite tu contraseña')"
                          required
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Terms -->
        <div class="mt-4">
            <label for="terms" class="flex items-start">
                <input id="terms" type="checkbox" class="rounded border-gray-300 text-corporate-primary shadow-sm focus:ring-corporate-primary mt-1" name="terms" required>
                <span class="ms-2 text-sm text-gray-600">{{ __('Acepto los') }} <a href="#" class="text-corporate-primary hover:text-[#D66A0F] font-medium">{{ __('términos y condiciones') }} </a>{{ __('y la') }} <a href="#" class="text-corporate-primary hover:text-[#D66A0F] font-medium">{{ __('política de privacidad') }} </a>
                </span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center py-3">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                {{ __('Crear Cuenta') }}
            </x-primary-button>
        </div>

        <!-- Social Register -->
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">{{ __('O regístrate con') }} </span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('auth.google') }}" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.617,3.979-5.448,3.979c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.308,2.026,14.956,1,12.545,1C7.021,1,2.543,5.477,2.543,11s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                    </svg>{{ __('Google') }} </a>
            </div>
        </div>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">{{ __('¿Ya tienes una cuenta?') }} <a href="{{ route('login') }}" class="font-semibold text-corporate-primary hover:text-[#D66A0F] transition-colors">{{ __('Inicia sesión aquí') }} </a>
            </p>
        </div>
    </form>
</x-guest-layout>
