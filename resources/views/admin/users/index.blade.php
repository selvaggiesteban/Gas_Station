<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Gestión de Alumnos y Usuarios') }} </h1>
                <p class="text-gray-600 mt-2">{{ __('Administra roles, visualiza progresos y asigna suscripciones manuales.') }} </p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form action="{{ route('admin.usuarios.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" name="search" placeholder="Buscar nombre o email..." value="{{ request('search') }}" class="md:col-span-2 rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">

                <select name="role" class="rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                    <option value="">{{ __('Todos los roles') }}</option>
                    @foreach($roles ?? [] as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>

                <select name="cuerpo_id" class="rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                    <option value="">{{ __('Todos los cuerpos') }}</option>
                    @foreach($cuerpos ?? [] as $cuerpo)
                        <option value="{{ $cuerpo->id }}" {{ request('cuerpo_id') == $cuerpo->id ? 'selected' : '' }}>{{ $cuerpo->name }}</option>
                    @endforeach
                </select>

                <select name="subscription" class="rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                    <option value="">{{ __('Suscripción: todas') }}</option>
                    <option value="active" {{ request('subscription') === 'active' ? 'selected' : '' }}>{{ __('Con suscripción activa') }}</option>
                    <option value="inactive" {{ request('subscription') === 'inactive' ? 'selected' : '' }}>{{ __('Sin suscripción activa') }}</option>
                </select>

                <div class="md:col-span-5 flex gap-2">
                    <button type="submit" class="btn-corporate px-6 py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('Filtrar') }}</button>
                    <a href="{{ route('admin.usuarios.index') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-gray-800 hover:bg-gray-100">{{ __('Limpiar') }}</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Usuario') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Rol') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Cuerpo') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Suscripción Activa') }} </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Puntos') }} </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }} </div>
                                        <div class="text-sm text-gray-500">{{ $user->email }} </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $roleName = $user->roles->first()->name ?? null;
                                    $translatedRole = match($roleName) {
                                        'Student' => 'Alumno',
                                        'Teacher' => 'Profesor',
                                        'Content Manager' => 'Gestor de Contenido',
                                        'Admin' => 'Administrador',
                                        null => 'Sin rol',
                                        default => $roleName,
                                    };
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $translatedRole }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($user->cuerpos->isNotEmpty())
                                    @foreach($user->cuerpos as $cuerpo)
                                        <span class="inline-block px-2 py-0.5 mr-1 mb-1 text-xs rounded bg-purple-100 text-purple-800">{{ $cuerpo->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-gray-400 italic">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    $activeSub = $user->subscription()
                                        ->where('status', 'active')
                                        ->where('ends_at', '>', now())
                                        ->with('plan', 'product')
                                        ->first();
                                @endphp
                                @if($activeSub)
                                    <span class="text-green-600 font-medium">Sí ({{ $activeSub->plan?->translated_name ?? $activeSub->product?->name ?? 'Plan' }})</span>
                                @else
                                    <span class="text-red-500">{{ __('No activa') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($user->points) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.usuarios.show', $user) }}" class="text-corporate-primary hover:text-orange-900 font-bold">{{ __('Gestionar') }} </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">{{ __('No se encontraron usuarios.') }} </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-gray-200 bg-white pagination-container">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
