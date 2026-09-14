<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Academicus - Tienda de Cursos') }} </title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
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

        <!-- Page Content -->
        <main class="flex-grow py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Success Messages -->
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }} </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Error Messages -->
                @if(session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }} </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-100 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Brand -->
                    <div class="col-span-1 md:col-span-1">
                        <div class="flex items-center mb-4">
                            <img src="{{ asset('images/logo.svg') }}" :alt="__('Academicus')" class="h-8 w-auto">
                        </div>
                        <p class="text-gray-500 text-sm">{{ __('La plataforma líder en preparación para oposiciones de Prisiones.') }} </p>
                    </div>

                    <!-- Links -->
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4">{{ __('Enlaces') }} </h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('tienda.inicio') }}" class="text-gray-500 hover:text-corporate-primary transition">{{ __('Inicio') }} </a></li>
                            <li><a href="{{ route('cursos.catalogo') }}" class="text-gray-500 hover:text-corporate-primary transition">{{ __('Catálogo') }} </a></li>
                            <li><a href="#" class="text-gray-500 hover:text-corporate-primary transition">{{ __('Preguntas Frecuentes') }} </a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4">{{ __('Legal') }} </h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-gray-500 hover:text-corporate-primary transition">{{ __('Términos y Condiciones') }} </a></li>
                            <li><a href="#" class="text-gray-500 hover:text-corporate-primary transition">{{ __('Política de Privacidad') }} </a></li>
                            <li><a href="#" class="text-gray-500 hover:text-corporate-primary transition">{{ __('Cookies') }} </a></li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4">{{ __('Contacto') }} </h4>
                        <ul class="space-y-2 text-sm text-gray-500">
                            <li class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>{{ __('info@academicus.com') }} </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                +34 900 000 000
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-10 pt-8 text-center">
                    <p class="text-gray-400 text-sm">© {{ date('Y') }} Academicus. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Alpine.js for mobile menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
