<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">Perfil de Usuario: {{ $user->name }} </h1>
            <a href="{{ route('admin.usuarios.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('← Volver a la lista') }} </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Estadísticas y Datos -->
            <div class="bg-white rounded-lg shadow-md p-6 col-span-1">
                <h3 class="text-xl font-bold border-b pb-2 mb-4">{{ __('Datos del Alumno') }} </h3>
                <p><strong>{{ __('Email:') }} </strong> {{ $user->email }} </p>
                <p class="mt-2"><strong>{{ __('Nivel:') }} </strong> {{ $stats['level'] ?? 1 }} </p>
                <p class="mt-2"><strong>{{ __('Puntos:') }} </strong> {{ number_format($stats['points'] ?? 0) }} </p>
                <p class="mt-2"><strong>{{ __('Temas Completados:') }} </strong> {{ $stats['completed_topics'] }} </p>
                <p class="mt-2"><strong>{{ __('Tests Realizados:') }} </strong> {{ $stats['tests_taken'] }} </p>

                <form action="{{ route('admin.usuarios.update', $user) }}" method="POST" class="mt-6 border-t pt-4">
                    @csrf @method('PUT')
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Cambiar Rol') }} </label>
                    <select name="role" class="w-full rounded-md border-gray-300 shadow-sm text-sm mb-3">
                        <option value="Student" {{ $user->hasRole('Student') ? 'selected' : '' }}>{{ __('Alumno') }} </option>
                        <option value="Teacher" {{ $user->hasRole('Teacher') ? 'selected' : '' }}>{{ __('Profesor') }} </option>
                        <option value="Content Manager" {{ $user->hasRole('Content Manager') ? 'selected' : '' }}>{{ __('Gestor de Contenido') }} </option>
                        <option value="Admin" {{ $user->hasRole('Admin') ? 'selected' : '' }}>{{ __('Administrador') }} </option>
                    </select>
                    <div class="flex flex-col gap-2">
                        <div class="flex gap-2">
                            <a href="{{ route('impersonate', $user->id) }}" class="flex-1 text-center bg-yellow-500 text-white px-3 py-2 rounded text-sm hover:bg-yellow-600">{{ __('Impersonar') }} </a>
                            <button type="submit" class="flex-1 bg-gray-800 text-white px-3 py-2 rounded text-sm hover:bg-gray-700">{{ __('Actualizar') }} </button>
                        </div>
                        <a href="{{ route('admin.usuarios.cursos', $user) }}" class="w-full text-center bg-blue-600 text-white px-3 py-2 rounded text-sm font-bold hover:bg-blue-700 transition">
                            <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            {{ __('Gestionar Cursos y Bloqueos') }}
                        </a>
                        <a href="{{ route('admin.usuarios.actividad', $user) }}" class="w-full text-center bg-blue-600 text-white px-3 py-2 rounded text-sm font-bold hover:bg-blue-700 transition">
                            <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Ver Historial de Actividad') }}
                        </a>
                    </div>
                </form>
                </div>

                <!-- Asignación Manual de Suscripción -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-2">
                <h3 class="text-xl font-bold border-b pb-2 mb-4 text-corporate-primary">{{ __('Asignar Suscripción Manual (Transferencia / Efectivo)') }} </h3>

                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">{{ __('Suscripciones Actuales:') }} </h4>
                    @forelse($user->subscriptions as $sub)<div class="p-3 bg-gray-50 border rounded mb-2 flex justify-between items-center">
                            <div>
                                <span class="font-bold">{{ $sub->plan->translated_name ?? 'Plan desconocido' }} </span>
                                <span class="text-sm text-gray-500 ml-2">Expira: {{ $sub->ends_at ? $sub->ends_at->format('d/m/Y') : 'Nunca' }} </span>
                            </div>
                            <span class="px-2 py-1 text-xs rounded {{ $sub->status === 'active' ? 'bg-green-100 text-green-800' : ($sub->status === 'payment_failed' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                @if($sub->status === 'active')
                                    ACTIVA
                                @elseif($sub->status === 'cancelled')
                                    CANCELADA
                                @elseif($sub->status === 'expired')
                                    EXPIRADA
                                @elseif($sub->status === 'payment_failed')
                                    ERROR PAGO
                                @elseif($sub->status === 'suspended')
                                    SUSPENDIDA
                                @else
                                    {{ strtoupper($sub->status) }}
                                @endif
                            </span>
                        </div>                    @empty
                        <p class="text-sm text-gray-500">{{ __('Este usuario no tiene suscripciones.') }} </p>
                    @endforelse
                </div>

                <form action="{{ route('admin.usuarios.suscripcion', $user) }}" method="POST" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    @csrf
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Seleccionar Plan') }} </label>
                            <select name="plan_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->translated_name }} ({{ $plan->price }}€)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Duración a otorgar (Meses)') }} </label>
                            <input type="number" name="duration_months" value="1" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Método de Pago') }} </label>
                        <select name="payment_method" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            <option value="transfer">{{ __('Transferencia Bancaria') }} </option>
                            <option value="cash">{{ __('Efectivo') }} </option>
                            <option value="manual">{{ __('Otorgado Manualmente (Cortesía)') }} </option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-corporate-primary text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition" onclick="return confirm('{{ __('¿Confirmas que has recibido el pago y deseas activar este plan al alumno?') }}');">{{ __('Activar Suscripción Ahora') }} </button>
                </form>

                <!-- Asignar Tutor y Cuerpos (N5 + N12) -->
                <form action="{{ route('admin.usuarios.perfil', $user) }}" method="POST" class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    @csrf
                    <h4 class="font-semibold text-gray-700 mb-3">{{ __('Tutor y Cuerpos') }} </h4>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Tutor asignado') }} </label>
                        <select name="assigned_teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">{{ __('— Sin tutor —') }}</option>
                            @foreach($teachers ?? \App\Models\User::role('Teacher')->orderBy('name')->get() as $teacher)
                                <option value="{{ $teacher->id }}" {{ $user->assigned_teacher_id == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }} ({{ $teacher->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Cuerpos (multi)') }} </label>
                        <select name="cuerpos[]" multiple size="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            @foreach(\App\Models\Cuerpo::where('is_active', true)->orderBy('name')->get() as $cuerpo)
                                <option value="{{ $cuerpo->id }}" {{ $user->cuerpos->contains($cuerpo->id) ? 'selected' : '' }}>{{ $cuerpo->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Mantén Ctrl/Cmd presionado para selección múltiple.') }} </p>
                    </div>

                    <button type="submit" class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-700 transition">
                        {{ __('Guardar tutor y cuerpos') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>