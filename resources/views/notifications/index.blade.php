<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Mis Notificaciones') }} </h1>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <form action="{{ route('notificaciones.marcar-todas') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-corporate-primary hover:underline font-medium">{{ __('Marcar todas como leídas') }} </button>
                </form>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            @forelse($notifications as $notification)
                <div class="p-6 border-b border-gray-100 last:border-0 {{ $notification->read() ? 'opacity-60 bg-gray-50' : 'bg-white' }}">
                    <div class="flex justify-between items-start">
                        <div class="flex gap-4">
                            <div class="mt-1">
                                @php
                                    $icon = $notification->data['icon'] ?? 'bell';
                                @endphp
                                <div class="p-2 rounded-full {{ $notification->read() ? 'bg-gray-200' : 'bg-corporate-primary/10 text-corporate-primary' }}">
                                    @if($icon === 'swords')
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $notification->data['title'] ?? 'Nueva notificación' }} </h3>
                                <p class="text-gray-600 mt-1">{{ $notification->data['message'] ?? '' }} </p>
                                <p class="text-sm text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }} </p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 items-end">
                            @if(!$notification->read())<form action="{{ route('notificaciones.marcar-leida', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-corporate-primary text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-orange-600 transition">{{ __('Ver y marcar como leída') }} </button>
                                </form>
                            @elseif(isset($notification->data['action_url']))<a href="{{ $notification->data['action_url'] }}" class="text-corporate-primary hover:underline text-sm font-medium">{{ __('Ver de nuevo →') }} </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    <p class="text-xl">{{ __('No tienes notificaciones aún.') }} </p>
                    <p class="mt-2 text-sm">{{ __('Aquí verás los retos de batalla, anuncios importantes y actualizaciones del cronograma.') }} </p>
                </div>
            @endforelse
        </div>
        
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>