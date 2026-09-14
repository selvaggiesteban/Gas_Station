<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Historial de Actividad') }} </h1>
                <p class="text-gray-600 mt-1">{{ __('Registro detallado de acciones para:') }} <span class="font-semibold text-corporate-primary">{{ $user->name }} </span></p>
            </div>
            <a href="{{ route('admin.usuarios.show', $user) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('← Volver al Perfil') }}
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 lg:p-10">
                @if($activities->isEmpty())
                    <div class="text-center py-20">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 mb-6">
                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Sin actividad registrada') }} </h3>
                        <p class="text-gray-500 mt-2 max-w-sm mx-auto">{{ __('Este usuario aún no tiene registros de actividad en el sistema de telemetría.') }} </p>
                    </div>
                @else
                    <div class="relative">
                        <!-- Vertical Line -->
                        <div class="absolute left-4 md:left-6 top-2 bottom-0 w-0.5 bg-gray-100"></div>

                        <div class="space-y-10">
                            @foreach($activities as $activity)
                                @php
                                    $colorClass = match($activity->action_type) {
                                        'test_submit', 'submit', 'exam' => 'bg-orange-500',
                                        'view', 'download', 'read' => 'bg-blue-500',
                                        'payment', 'purchase' => 'bg-green-500',
                                        'heartbeat' => 'bg-gray-300',
                                        default => 'bg-gray-500'
                                    };
                                    
                                    $deviceInfo = is_array($activity->device_info) ? $activity->device_info : json_decode($activity->device_info, true) ?? [];
                                    $device = $deviceInfo['device'] ?? 'Desktop';
                                    $platform = $deviceInfo['platform'] ?? 'Windows';
                                    $browser = $deviceInfo['browser'] ?? 'Chrome';
                                @endphp

                                <div class="relative pl-12 md:pl-16">
                                    <!-- Timeline Dot -->
                                    <div class="absolute left-0 md:left-2 top-0 w-9 h-9 rounded-full border-4 border-white shadow-md flex items-center justify-center {{ $colorClass }} text-white z-10">
                                        @if(str_contains($activity->action_type, 'test') || $activity->action_type == 'exam')
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @elseif($activity->action_type == 'view' || $activity->action_type == 'read')
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        @elseif($activity->action_type == 'payment')
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @elseif($activity->action_type == 'heartbeat')
                                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                        @else
                                            <div class="w-2 h-2 rounded-full bg-white opacity-75"></div>
                                        @endif
                                    </div>

                                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:border-corporate-primary/30 hover:bg-white hover:shadow-xl transition-all duration-300 group">
                                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                                        {{ $activity->created_at->format('d M, Y · H:i:s') }}
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ str_replace('bg-', 'text-', $colorClass) }} bg-white border border-current shadow-sm">
                                                        {{ $activity->action_type }}
                                                    </span>
                                                </div>
                                                <h4 class="text-lg font-bold text-gray-900 group-hover:text-corporate-primary transition-colors">
                                                    {{ $activity->description ?? $activity->url }}
                                                </h4>
                                                <div class="flex items-center gap-2 mt-2 text-sm text-gray-500 bg-white/50 inline-flex px-2 py-1 rounded border border-gray-100">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                                    <span class="font-mono text-xs">{{ $activity->route_name ?? $activity->url }} </span>
                                                </div>
                                            </div>

                                            <div class="flex flex-row lg:flex-col items-center lg:items-end gap-4 shrink-0 border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="text-right hidden sm:block">
                                                        <p class="text-xs font-bold text-gray-700">{{ $device }} ({{ $platform }})</p>
                                                        <p class="text-[10px] text-gray-400">{{ $browser }} </p>
                                                    </div>
                                                    <div class="p-2.5 bg-white rounded-xl border border-gray-100 shadow-sm text-gray-400 group-hover:text-corporate-primary transition-colors">
                                                        @if($device == 'Mobile')
                                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                                        @else
                                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 21h6l-.75-4M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if($activity->time_spent > 0)
                                                    <div class="flex items-center gap-2 text-corporate-primary font-bold text-xs bg-orange-50 px-3 py-1.5 rounded-lg border border-orange-100 shadow-sm">
                                                        <svg class="w-3.5 h-3.5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        {{ gmdate("H:i:s", $activity->time_spent) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-12 px-4">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>
        </div>
        
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">{{ __('Lectura') }} </p>
                    <p class="text-sm text-gray-600">{{ __('Temas y recursos visualizados') }} </p>
                </div>
            </div>
            <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-orange-600 uppercase tracking-wider">{{ __('Evaluación') }} </p>
                    <p class="text-sm text-gray-600">{{ __('Tests y exámenes realizados') }} </p>
                </div>
            </div>
            <div class="bg-green-50 p-4 rounded-xl border border-green-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-green-600 uppercase tracking-wider">{{ __('Transacción') }} </p>
                    <p class="text-sm text-gray-600">{{ __('Pagos y gestión de suscripción') }} </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
