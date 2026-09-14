<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Panel Docente') }} </h1>
            <p class="text-gray-600 mt-2">Bienvenido, profesor {{ auth()->user()->name }}.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-corporate-primary">
                <h3 class="text-lg font-semibold mb-2">{{ __('Tutorías Pendientes') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Responde a las dudas de los alumnos.') }} </p>
                <a href="{{ route('teacher.tutoring.pending') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600">{{ __('Ver Tutorías') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Corrección de exámenes') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Corrige y evalúa las respuestas abiertas de los alumnos.') }} </p>
                <a href="{{ route('teacher.essays.index') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700">{{ __('Calificar Exámenes') }} </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-indigo-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Casos Prácticos') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Revisa las entregas de supuestos prácticos.') }} </p>
                <a href="{{ route('teacher.practical-cases.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">{{ __('Ver Entregas') }} </a>
            </div>
        </div>

        <!-- Alumnos asignados a este tutor -->
        @php
            $assignedStudents = auth()->user()->assignedStudents()->with('cuerpos')->orderBy('name')->get();
        @endphp
        <div class="mt-10 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="h-5 w-5 text-corporate-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ __('Mis alumnos asignados') }}
                <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-corporate-primary text-white">{{ $assignedStudents->count() }}</span>
            </h3>
            @if($assignedStudents->isEmpty())
                <p class="text-gray-500 text-sm">{{ __('Aún no tienes alumnos asignados.') }}</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($assignedStudents as $student)
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ $student->name }}</p>
                                <p class="text-xs text-gray-500">{{ $student->email }}</p>
                                @if($student->cuerpos->isNotEmpty())
                                    <p class="text-xs text-purple-600 mt-1">
                                        {{ $student->cuerpos->pluck('name')->join(' · ') }}
                                    </p>
                                @endif
                            </div>
                            <a href="{{ route('admin.usuarios.show', $student) }}" class="text-xs text-corporate-primary hover:underline font-bold">Ver perfil</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>