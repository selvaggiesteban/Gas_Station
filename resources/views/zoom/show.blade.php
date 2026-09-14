{{-- resources/views/zoom/show.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-3xl font-bold mb-4">{{ $meeting->topic?->title ?? 'Clase en vivo' }} </h1>

                <div class="flex items-center gap-2 mb-6">
                    @if($meeting->status === \App\Models\ZoomMeeting::STATUS_LIVE)<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">{{ __('🔴 EN VIVO') }} </span>
                    @elseif($meeting->status === \App\Models\ZoomMeeting::STATUS_SCHEDULED)<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ __('Programada') }} </span>
                    @elseif($meeting->status === \App\Models\ZoomMeeting::STATUS_FINISHED)<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">{{ __('Finalizada') }} </span>
                    @elseif($meeting->status === \App\Models\ZoomMeeting::STATUS_CANCELLED)<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">{{ __('Cancelada') }} </span>
                    @endif
                </div>

                <div class="space-y-4 mb-8">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-gray-600">{{ __('Fecha y hora:') }} </p>
                            <p class="font-medium">{{ $meeting->start_time->format('d/m/Y H:i') }} ({{ $meeting->duration_minutes }} min)</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <div>
                            <p class="text-gray-600">{{ __('Profesor:') }} </p>
                            <p class="font-medium">{{ $meeting->creator?->name ?? 'Por asignar' }} </p>
                        </div>
                    </div>

                    @if($meeting->password)<div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <div>
                                <p class="text-gray-600">{{ __('Contraseña:') }} </p>
                                <p class="font-medium">{{ $meeting->password }} </p>
                            </div>
                        </div>
                    @endif
                </div>

                @if($meeting->recording_url && $meeting->status === \App\Models\ZoomMeeting::STATUS_FINISHED)<div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold mb-2">{{ __('Grabación disponible') }} </h3>
                        <a href="{{ $meeting->recording_url }}" target="_blank" class="inline-flex items-center text-corporate-primary hover:text-orange-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>{{ __('Ver grabación') }} </a>
                    </div>
                @endif

                <div class="flex gap-4">
                    @if($meeting->status === \App\Models\ZoomMeeting::STATUS_SCHEDULED || $meeting->status === \App\Models\ZoomMeeting::STATUS_LIVE)<a href="{{ route('zoom.entrar', $meeting) }}" class="flex-1 inline-flex justify-center items-center px-6 py-3 bg-corporate-primary text-white font-semibold rounded-lg hover:bg-orange-600 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>{{ __('Unirse a la clase') }} </a>
                    @endif

                    @if($meeting->status === \App\Models\ZoomMeeting::STATUS_CANCELLED)<div class="w-full bg-red-50 text-red-700 px-4 py-3 rounded-lg text-center">{{ __('Esta clase ha sido cancelada') }} </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
