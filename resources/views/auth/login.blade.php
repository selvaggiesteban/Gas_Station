<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Header -->
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('Iniciar Sesión') }} </h2>
        <p class="text-sm text-gray-500 mt-1">{{ __('Accede a tu cuenta de Academicus') }} </p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" class="text-gray-700 font-medium" />
            <x-text-input id="email" class="block mt-2 w-full rounded-lg border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary transition-colors"
                          type="email"
                          name="email"
                          :value="old('email')"
                          placeholder="tu@email.com"
                          required
                          autofocus
                          autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" class="text-gray-700 font-medium" />
            <x-text-input id="password" class="block mt-2 w-full rounded-lg border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary transition-colors"
                          type="password"
                          name="password"
                          placeholder="••••••••"
                          required
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-corporate-primary shadow-sm focus:ring-corporate-primary" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme') }} </span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-corporate-primary hover:text-[#D66A0F] font-medium transition-colors" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center py-3 bg-corporate-primary hover:bg-[#D66A0F] focus:bg-[#D66A0F] active:bg-[#C25D0B]">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                {{ __('Iniciar Sesión') }}
            </x-primary-button>
        </div>

        <!-- Social Login -->
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-[#f1f1f1] text-gray-500">{{ __('O continúa con') }} </span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('auth.google') }}" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.617,3.979-5.448,3.979c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.308,2.026,14.956,1,12.545,1C7.021,1,2.543,5.477,2.543,11s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                    </svg>{{ __('Google') }} </a>
            </div>
        </div>

        <!-- Register Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">{{ __('¿No tienes una cuenta?') }} <a href="{{ route('register') }}" class="font-semibold text-corporate-primary hover:text-[#D66A0F] transition-colors">{{ __('Regístrate aquí') }} </a>
            </p>
        </div>
    </form>
</x-guest-layout>
