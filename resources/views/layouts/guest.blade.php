<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $guestRouteNames = [
                'login' => 'Iniciar Sesión',
                'register' => 'Registro',
                'password.request' => 'Recuperar Contraseña',
                'password.reset' => 'Restablecer Contraseña'
            ];
            $currentRoute = Route::currentRouteName();
            $pageTitle = $title ?? ($guestRouteNames[$currentRoute] ?? 'Plataforma de Preparación para Oposiciones');
        @endphp
        <title>Academia de Prisiones - {{ $pageTitle }} </title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-corporate-secondary antialiased bg-corporate-background">
        <nav x-data="{ mobileMenuOpen: false }" class="bg-white border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ route('tienda.inicio') }}" class="flex items-center gap-2">
                            <img src="{{ asset('images/logo.svg') }}" :alt="__('Academicus')" class="h-9 w-auto">
                        </a>

                        <!-- Desktop Navigation -->
                        <div class="hidden md:ml-10 md:flex md:space-x-8">
                            <a href="{{ route('tienda.inicio') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900 hover:text-corporate-primary transition border-b-2 {{ request()->routeIs('tienda.inicio') ? 'border-corporate-primary text-corporate-primary' : 'border-transparent' }}">{{ __('Inicio') }} </a>
                            <a href="#cursos" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-corporate-primary transition border-b-2 border-transparent">{{ __('Cursos') }} </a>
                            <a href="{{ route('cursos.catalogo') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-corporate-primary transition border-b-2 {{ request()->routeIs('cursos.catalogo') ? 'border-corporate-primary text-corporate-primary' : 'border-transparent' }}">{{ __('Catálogo') }} </a>
                        </div>
                    </div>

                    <!-- Right Side -->
                    <div class="flex items-center space-x-4">
                        <!-- Cart -->
                        <a href="{{ route('carrito.ver') }}" class="relative p-2 text-gray-500 hover:text-corporate-primary transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            @php $cartCount = app(\App\Services\CartService::class)->getCount(); @endphp
                            @if($cartCount > 0)
                                <span class="absolute -top-1 -right-1 bg-corporate-primary text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </a>

                        <!-- Auth Buttons -->
                        @auth
                            <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-corporate-primary text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">{{ __('Mi Panel') }} </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:block text-gray-500 hover:text-gray-700 text-sm font-medium transition">{{ __('Entrar') }} </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">{{ __('Registrarse') }} </a>
                        @endauth

                        <!-- Mobile menu button -->
                        <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" class="md:hidden border-t border-gray-100" style="display: none;">
                <div class="px-2 pt-2 pb-3 space-y-1 bg-white">
                    <a href="{{ route('tienda.inicio') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-corporate-primary">{{ __('Inicio') }} </a>
                    <a href="#cursos" class="block px-3 py-2 rounded-md text-base font-medium text-gray-500 hover:bg-gray-50 hover:text-corporate-primary">{{ __('Cursos') }} </a>
                    <a href="{{ route('cursos.catalogo') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-500 hover:bg-gray-50 hover:text-corporate-primary">{{ __('Catálogo') }} </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-corporate-primary hover:bg-gray-50">{{ __('Mi Panel') }} </a>
                    @else
                        <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-500 hover:bg-gray-50">{{ __('Entrar') }} </a>
                        <a href="{{ route('register') }}" class="block px-3 py-2 rounded-md text-base font-medium text-corporate-primary hover:bg-gray-50">{{ __('Registrarse') }} </a>
                    @endauth
                </div>
            </div>
        </nav>
        <div class="min-h-screen flex flex-col pt-6 sm:pt-0">
            <!-- Logo Section -->
            <div class="mt-12"></div>
            <div class="text-center mb-8">
                <a href="/" class="block">
                    <img src="{{ asset('images/logo.svg') }}" :alt="__('Academicus')" class="w-32 h-32 mx-auto mb-4">
                    <p class="text-sm text-corporate-primary mt-1 font-medium">{{ __('Preparación para Oposiciones') }} </p>
                </a>
            </div>

            <!-- Card Container -->
            <div class="w-full sm:max-w-md mx-auto mt-8 mb-12">
                <div class="bg-corporate-container shadow-2xl overflow-hidden rounded-2xl border border-gray-200">
                    <div class="px-8 py-8">
                        {{ $slot }}
                    </div>
                </div>

                <!-- Footer Links -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        © {{ date('Y') }} Academicus. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
