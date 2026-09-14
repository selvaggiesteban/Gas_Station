<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full lg:translate-x-0 bg-white border-r border-gray-100" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto flex flex-col">
        <!-- Logo -->
        <div class="flex flex-col ps-2.5 mb-8">
            <a href="{{ route('dashboard') }}" class="flex items-center mb-1">
                <x-application-logo class="h-10 me-3 fill-current text-corporate-primary" />
                <span class="self-center text-xl font-bold whitespace-nowrap text-corporate-secondary tracking-tight">Academicus</span>
            </a>
            <div class="flex flex-col border-l-2 border-corporate-primary/30 pl-3 mt-1">
                <span class="text-[10px] font-bold text-corporate-primary uppercase tracking-widest leading-none"></span>
            </div>
        </div>

        <ul class="space-y-2 font-medium flex-1">
            <!-- Mi Escritorio -->
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('dashboard') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="ms-3">Mi escritorio</span>
                </a>
            </li>

            @role('Student')
            <!-- Zona de Estudio / Cronograma -->
            <li>
                <a href="{{ route('cronograma.personal') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('cronograma.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />       
                    </svg>
                    <span class="ms-3">Zona de estudio</span>
                </a>
            </li>

            <!-- Temario / Mis Cursos -->
            <li>
                <a href="{{ route('cursos.mis-cursos') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('cursos.*') || request()->routeIs('temas.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="ms-3">Temario</span>
                </a>
            </li>

            <!-- Videoclases -->
            <li>
                <a href="{{ route('zoom.clases') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('zoom.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="ms-3">Videoclases</span>
                </a>
            </li>

            <!-- Tests / Exámenes -->
            <li>
                <a href="{{ route('examenes.lista') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('examenes*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span class="ms-3">Tests</span>
                </a>
            </li>

            <!-- Batalla de Opositores -->
            <li>
                <a href="{{ route('batallas.lista') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('batallas.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="ms-3">Batallas</span>
                </a>
            </li>

            <!-- Estadísticas / Clasificación -->
            <li>
                <a href="{{ route('clasificacion.ver') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('clasificacion.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="ms-3">Ranking</span>
                </a>
            </li>

            <!-- Tutorías -->
            <li>
                <a href="{{ route('tutorias.lista') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('tutorias.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="ms-3">Tutorías</span>
                </a>
            </li>

            <!-- Suscripciones -->
            <li>
                <a href="{{ route('suscripcion.gestionar') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('suscripcion.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />       
                    </svg>
                    <span class="ms-3">Suscripciones</span>
                </a>
            </li>
            @endrole

            @role('Teacher|Content Manager|Admin')
            <li class="pt-4 mt-4 border-t border-gray-100">
                <span class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Gestión</span>
            </li>
            @role('Teacher|Admin')
            <li>
                <a href="{{ route('teacher.tutoring.pending') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('teacher.tutoring*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>      
                    <span class="ms-3">Tutorías Pendientes</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.essays.index') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('teacher.essays*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <span class="ms-3">Exámenes</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.practical-cases.index') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('teacher.practical-cases*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h10" /></svg>
                    <span class="ms-3">Supuestos Prácticos</span>
                </a>
            </li>
            @endrole
            
            @role('Content Manager|Admin')
            <li>
                <a href="{{ route('content-manager.desafios.lista') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span class="ms-3">Retos Semanales</span>
                </a>
            </li>
            <li>
                <a href="{{ route('content-manager.announcements.index') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                    <span class="ms-3">Anuncios</span>
                </a>
            </li>
            <li>
                <a href="{{ route('content-manager.schedules.index') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span class="ms-3">Cronograma Oficial</span>
                </a>
            </li>
            <li>
                <a href="{{ route('zoom.clases') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('zoom.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    <span class="ms-3">Gestión de Videoclases</span>
                </a>
            </li>
            @endrole

            @role('Admin')
            <li>
                <a href="{{ route('admin.cursos.index') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('admin.cursos.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    <span class="ms-3">Gestión de cursos</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.usuarios.index') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('admin.usuarios.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span class="ms-3">Gestión de alumnos</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.metricas.panel') }}" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-gray-50 group {{ request()->routeIs('admin.metricas.*') ? 'bg-corporate-primary/10 text-corporate-primary' : '' }}">
                    <svg class="w-5 h-5 transition duration-75" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span class="ms-3">Estadísticas</span>
                </a>
            </li>
            @endrole
            @endrole
        </ul>

        <!-- Perfil y Logout -->
        <div class="pt-4 mt-auto border-t border-gray-100">
            <div class="flex items-center p-2 mb-2">
                <div class="flex-shrink-0">
                    <img class="h-8 w-8 rounded-full" src="{{ optional(Auth::user())->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(optional(Auth::user())->name ?? 'Invitado') }}" alt="">
                </div>
                <div class="ms-3">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ optional(Auth::user())->name ?? 'Invitado' }} </p>
                    <p class="text-xs text-gray-500 truncate">{{ optional(Auth::user())->getRankName() ?? '' }} </p>
                </div>
            </div>

            @role('Student')
                <a href="{{ route('avatar.personalizar') }}" class="flex items-center p-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                    {{ __('Avatar y Marcos') }}
                </a>
                <a href="{{ route('perfil.editar') }}" class="flex items-center p-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                    {{ __('Perfil') }}
                </a>
            @else
                <a href="{{ route('perfil.editar') }}" class="flex items-center p-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                    {{ __('Mi Perfil') }}
                </a>
            @endrole

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full p-2 text-sm text-red-600 rounded-lg hover:bg-red-50">
                    {{ __('Cerrar Sesión') }}
                </button>
            </form>
        </div>

    </div>
</aside>
