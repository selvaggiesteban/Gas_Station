<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Panel de Administración') }} </h1>
            <p class="text-gray-600 mt-2">Bienvenido, {{ auth()->user()->name }}. Tienes control total sobre la plataforma.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Gestión de Cursos') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Crea, edita y organiza bloques y temas.') }} </p>
                <a href="{{ route('admin.cursos.index') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Entrar a Gestión') }} </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Alumnos y Pagos') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Gestiona usuarios, roles y asigna suscripciones manuales.') }} </p>
                <a href="{{ route('admin.usuarios.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">{{ __('Gestión de Alumnos') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Métricas Globales') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Ingresos, altas y estadísticas de la academia.') }} </p>
                <a href="{{ route('admin.metricas.panel') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">{{ __('Ver Estadísticas') }} </a>
            </div>
        </div>

        <!-- Content Manager & Videoclases -->
        <h2 class="text-xl font-bold text-gray-900 mt-10 mb-4">{{ __('Gestión de Contenidos') }} </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Retos Semanales') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Crea y gestiona los desafíos semanales para los alumnos.') }} </p>
                <a href="{{ route('content-manager.desafios.lista') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700">{{ __('Gestionar Retos') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Anuncios') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Publica y administra anuncios para los alumnos.') }} </p>
                <a href="{{ route('content-manager.announcements.index') }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">{{ __('Ir a Anuncios') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-indigo-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Cronograma Oficial') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Administra el cronograma de estudio de la academia.') }} </p>
                <a href="{{ route('content-manager.schedules.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">{{ __('Ver Cronograma') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Gestión de Videoclases') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Administra las sesiones Zoom y videoclases.') }} </p>
                <a href="{{ route('zoom.clases') }}" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">{{ __('Ir a Videoclases') }} </a>
            </div>
        </div>

        <!-- Teacher -->
        <h2 class="text-xl font-bold text-gray-900 mt-10 mb-4">{{ __('Gestión Académica') }} </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-pink-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Tutorías Pendientes') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Revisa y responde las consultas de los alumnos.') }} </p>
                <a href="{{ route('teacher.tutoring.pending') }}" class="inline-flex items-center px-4 py-2 bg-pink-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-pink-700">{{ __('Ver Tutorías') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-teal-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Exámenes') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Corrige y califica las respuestas abiertas.') }} </p>
                <a href="{{ route('teacher.essays.index') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700">{{ __('Corregir Exámenes') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-cyan-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Supuestos Prácticos') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Revisa y califica los supuestos prácticos.') }} </p>
                <a href="{{ route('teacher.practical-cases.index') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyan-700">{{ __('Revisar Supuestos') }} </a>
            </div>
        </div>
    </div>
</x-app-layout>