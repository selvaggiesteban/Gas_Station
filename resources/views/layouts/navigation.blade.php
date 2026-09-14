<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-30">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Sidebar Toggle (Mobile) -->
                <button @click="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Title (Desktop) -->
                <div class="hidden lg:flex items-center space-x-4">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        @php
                            $routeNames = [
                                'dashboard' => 'Panel Principal',
                                'cronograma.personal' => 'Zona de Estudio',
                                'cursos.mis-cursos' => 'Temario',
                                'examenes.lista' => 'Tests',
                                'batallas.lista' => 'Batalla de Opositores',
                            ];
                            echo $routeNames[Route::currentRouteName()] ?? '';
                        @endphp
                    </h2>
                    <div class="flex flex-col border-l border-gray-200 pl-4">
                        <span class="text-[10px] font-bold text-corporate-primary uppercase tracking-wider leading-none"></span>
                    </div>
                </div>
            </div>

            <!-- Right Side -->
            <div class="flex items-center">
                <!-- Gamification Points -->
                @role('Student')
                <div class="hidden sm:flex items-center mr-4">
                    <div class="flex items-center px-4 py-1.5 bg-corporate-primary/10 text-corporate-primary rounded-full text-sm font-bold border border-corporate-primary/20">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ number_format(optional(Auth::user())->points ?? 0) }} <span class="ml-1 font-normal text-xs uppercase">pts</span>
                    </div>
                </div>
                @endrole

                <!-- Notifications -->
                <div class="relative mr-2 flex items-center" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="p-2 text-gray-400 hover:text-corporate-primary transition relative">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if(optional(auth()->user())->unreadNotifications && auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        @endif
                    </button>
                    <!-- Notification Dropdown Menu -->
                    <div x-show="open" style="display: none; position: fixed; top: 5.5rem; right: 1rem;" class="w-80 bg-white rounded-lg shadow-xl border border-gray-100 py-2 z-50">
                         <div class="px-4 py-2 border-b border-gray-50 font-bold text-sm text-gray-700 flex justify-between items-center">
                            <span>Notificaciones</span>
                            @if(auth()->user()->unreadNotifications && auth()->user()->unreadNotifications->count() > 0)
                                <form action="{{ route('notificaciones.marcar-todas') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-corporate-primary hover:underline font-normal">Marcar todas</button>
                                </form>
                            @endif
                         </div>
                         <div class="max-h-60 overflow-y-auto">
                            @forelse(optional(auth()->user())->unreadNotifications ?? collect() as $n)
                                <a href="{{ route('notificaciones.marcar-leida', $n->id) }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0">
                                    <p class="text-sm font-medium">{{ $n->data['title'] ?? '' }}</p>
                                    <p class="text-xs text-gray-500">{{ $n->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <div class="p-4 text-center text-gray-400 text-sm italic">Sin notificaciones</div>
                            @endforelse
                         </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition focus:outline-none">
                            <span class="mr-3 font-bold text-corporate-primary italic hidden md:block"></span>
                            <img class="h-8 w-8 rounded-full border-2 border-corporate-primary/20 object-cover" src="{{ optional(Auth::user())->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(optional(Auth::user())->name ?? 'Invitado') }}" alt="">
                            <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @if(auth()->user()->hasRole('Student'))
                            <x-dropdown-link :href="route('cursos.mis-cursos')">Mis Cursos</x-dropdown-link>
                            <x-dropdown-link :href="route('avatar.personalizar')">Avatar y Marcos</x-dropdown-link>
                            <x-dropdown-link :href="route('suscripcion.gestionar')">Suscripción</x-dropdown-link>
                        @endif

                        <x-dropdown-link :href="route('perfil.editar')">Perfil</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar Sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
