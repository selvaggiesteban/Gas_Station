<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.usuarios.show', $user) }}" class="text-sm text-gray-500 hover:text-gray-900">← Volver al perfil</a>
                <h1 class="text-3xl font-bold text-gray-900 mt-2">Cursos de: {{ $user->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $user->email }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bloqueo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($subscriptions as $row)
                        @php $sub = $row['subscription']; $course = $row['course']; @endphp
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $course?->title ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $row['product']?->name ?? $sub->plan?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $row['started_at']?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $row['ends_at']?->format('d/m/Y') ?? 'Indefinido' }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusLabel = match($sub->status) {
                                        'active' => 'Activa',
                                        'cancelled' => 'Cancelada',
                                        'expired' => 'Expirada',
                                        'payment_failed' => 'Error de pago',
                                        'suspended' => 'Suspendida',
                                        default => ucfirst($sub->status),
                                    };
                                    $statusClass = match($sub->status) {
                                        'active' => 'bg-green-100 text-green-800',
                                        'cancelled', 'expired' => 'bg-red-100 text-red-800',
                                        'payment_failed', 'suspended' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($row['is_blocked'])
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Bloqueado manual</span>
                                @elseif($row['blocked_auto'])
                                    <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">Bloqueado auto ({{ $statusLabel }})</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Acceso OK</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.usuarios.cursos.bloquear', [$user, $sub]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold {{ $row['is_blocked'] ? 'text-green-600 hover:text-green-800' : 'text-red-600 hover:text-red-800' }}">
                                        {{ $row['is_blocked'] ? 'Desbloquear' : 'Bloquear manualmente' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">Este usuario no tiene suscripciones a cursos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded text-xs text-gray-600">
            <p><strong>Bloqueo automático:</strong> cuando la suscripción pasa a <code>expired</code>, <code>cancelled</code>, <code>payment_failed</code> o <code>suspended</code>, el alumno pierde acceso al instante. El bloqueo manual es adicional y persistente.</p>
        </div>
    </div>
</x-app-layout>
