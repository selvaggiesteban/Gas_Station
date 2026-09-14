<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $routeNames = [
                'dashboard' => 'Panel Principal',
                'perfil.editar' => 'Perfil',
                'cronograma.personal' => 'Zona de Estudio',
                'cursos.mis-cursos' => 'Temario',
                'zoom.clases' => 'Videoclases',
                'examenes.lista' => 'Tests',
                'batallas.lista' => 'Batallas',
                'clasificacion.ver' => 'Ranking',
                'tutorias.lista' => 'Acción Tutorial',
                'suscripcion.gestionar' => 'Suscripciones',
            ];
            $currentRoute = Route::currentRouteName();
            $pageTitle = $title ?? ($routeNames[$currentRoute] ?? 'Academia de Prisiones');
        @endphp

        <title>Academicus - {{ $pageTitle }} </title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/activity-tracker.js'])

        <style>
            /* Force pagination to light mode */
            .pagination, [role="navigation"] {
                background-color: white !important;
            }
            [role="navigation"] .bg-gray-800, 
            [role="navigation"] .dark\:bg-gray-800 {
                background-color: #ffffff !important;
                color: #374151 !important;
            }
            [role="navigation"] .text-gray-400,
            [role="navigation"] .dark\:text-gray-400 {
                color: #6b7280 !important;
            }
            [role="navigation"] .text-white,
            [role="navigation"] .dark\:text-white {
                color: #374151 !important;
            }
            [role="navigation"] span[aria-current="page"] span {
                background-color: #EB7716 !important;
                color: white !important;
                border-color: #EB7716 !important;
            }
            /* Fix search button fallback if tailwind fails */
            .btn-corporate {
                background-color: #EB7716 !important;
                color: white !important;
            }
            .btn-corporate:hover {
                background-color: #d66a14 !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-corporate-secondary bg-corporate-container">
        <div class="min-h-screen">
            <!-- Sidebar (Desktop) -->
            @include('layouts.sidebar')

            <!-- Main Content Area -->
            <div class="lg:ml-64 transition-all duration-300">
                <!-- Top Navigation (Mobile/Tablet & Global Actions) -->
                @include('layouts.navigation')

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-gray-100">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="p-4 sm:p-6 lg:p-8">
                    @if(app('impersonate')->isImpersonating())
                        <div class="bg-yellow-400 text-black font-bold p-3 rounded-lg mb-6 flex justify-between items-center shadow-sm">
                            <span>Estás navegando como {{ auth()->user()->name }}.</span>
                            <a href="{{ route('impersonate.leave') }}" class="px-4 py-1 bg-black text-white rounded hover:bg-gray-800 transition text-sm">Volver a Admin</a>
                        </div>
                    @endif

                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </main>
            </div>

            <!-- Motivational Quote Popup -->
            @role('Student')
            <div x-data="motivationalPopup()"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-4"
                 class="fixed bottom-6 right-6 w-80 bg-white rounded-xl shadow-2xl border-l-8 border-corporate-primary p-6 z-50"
                 style="display: none;">
                <button @click="show = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="flex items-start">
                    <div class="flex-shrink-0 bg-orange-100 rounded-full p-2 mr-4">
                        <svg class="h-6 w-6 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm italic text-gray-700 mb-2" x-text="'&ldquo;' + quote + '&rdquo;'"></p>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest" x-text="author"></p>
                    </div>
                </div>
            </div>

            <script>
                function motivationalPopup() {
                    return {
                        show: false,
                        quote: '',
                        author: '',
                        init() {
                            setTimeout(() => this.fetchQuote(), 2 * 60 * 1000); // 2 min for testing
                            setInterval(() => this.fetchQuote(), 15 * 60 * 1000);
                        },
                        fetchQuote() {
                            fetch('{{ route('frases.aleatoria') }}')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        this.quote = data.quote;
                                        this.author = data.author;
                                        this.show = true;
                                        setTimeout(() => this.show = false, 10000);
                                    }
                                });
                        }
                    }
                }
            </script>
            @endrole

            <!-- Toast Notifications -->
            <div x-data="{
                notifications: [],
                add(message, type = 'success') {
                    const id = Date.now();
                    this.notifications.push({ id, message, type });
                    setTimeout(() => this.remove(id), 5000);
                },
                remove(id) {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }
            }"
            @toast-notify.window="add($event.detail.message, $event.detail.type)"
            class="fixed bottom-6 left-6 z-[70] flex flex-col gap-3 w-full max-w-sm"
            style="pointer-events: none;">
                <template x-for="notification in notifications" :key="notification.id">
                    <div x-show="true"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-x-4"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-x-0"
                         x-transition:leave-end="opacity-0 transform -translate-x-4"
                         class="bg-white rounded-lg shadow-xl border-l-4 p-4 flex items-center justify-between pointer-events-auto"
                         :class="{
                             'border-green-500': notification.type === 'success',
                             'border-blue-500': notification.type === 'info',
                             'border-yellow-500': notification.type === 'warning',
                             'border-red-500': notification.type === 'error'
                         }">
                        <div class="flex items-center">
                            <template x-if="notification.type === 'success'">
                                <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </template>
                            <template x-if="notification.type === 'info'">
                                <svg class="h-6 w-6 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </template>
                            <template x-if="notification.type === 'warning'">
                                <svg class="h-6 w-6 text-yellow-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </template>
                            <template x-if="notification.type === 'error'">
                                <svg class="h-6 w-6 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </template>
                            <p class="text-sm font-medium text-gray-800" x-text="notification.message"></p>
                        </div>
                        <button @click="remove(notification.id)" class="text-gray-400 hover:text-gray-600 ml-4">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
