<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <a href="{{ route('batallas.lista') }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">← Volver a la lista</a>
                    <h1 class="text-3xl font-bold text-gray-900">Batalla #{{ $battle->id }} </h1>
                </div>
                <div class="px-4 py-2 rounded-full font-bold uppercase text-sm 
                    {{ $battle->status === 'completed' ? 'bg-blue-100 text-blue-700' : 
                       ($battle->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') }}">
                    {{ __($battle->status) }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Players Column -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                        <div class="p-8">
                            <div class="flex items-center justify-between gap-4">
                                <div class="text-center flex-1">
                                    <img class="h-20 w-20 rounded-full mx-auto mb-3 border-4 border-corporate-primary" src="{{ $battle->challenger->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($battle->challenger->name) }}" alt="">
                                    <p class="font-bold text-gray-900">{{ $battle->challenger->name }} </p>
                                    <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $battle->challenger->getRankName() }} </p>
                                    @if($battle->challenger_score !== null)
                                        <p class="mt-4 text-2xl font-black text-corporate-primary">{{ $battle->challenger_score }} pts</p>
                                    @else
                                        <p class="mt-4 text-sm text-gray-400 italic">Esperando...</p>
                                    @endif
                                </div>

                                <div class="text-center px-4">
                                    <div class="text-4xl font-black text-gray-200 uppercase italic">VS</div>
                                </div>

                                <div class="text-center flex-1">
                                    <img class="h-20 w-20 rounded-full mx-auto mb-3 border-4 border-gray-200" src="{{ $battle->opponent->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($battle->opponent->name) }}" alt="">
                                    <p class="font-bold text-gray-900">{{ $battle->opponent->name }} </p>
                                    <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $battle->opponent->getRankName() }} </p>
                                    @if($battle->opponent_score !== null)
                                        <p class="mt-4 text-2xl font-black text-gray-800">{{ $battle->opponent_score }} pts</p>
                                    @else
                                        <p class="mt-4 text-sm text-gray-400 italic">Esperando...</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($battle->status === 'completed')
                            <div class="bg-corporate-primary p-4 text-white text-center font-bold">
                                @if($battle->winner_id === auth()->id())
                                    ¡FELICIDADES! HAS GANADO LA BATALLA 🎉
                                @elseif($battle->winner_id === null)
                                    LA BATALLA HA QUEDADO EN EMPATE 🤝
                                @else
                                    HAS SIDO DERROTADO... ¡ENTRENA MÁS! 💪
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Battle Logic / Buttons -->
                    <div class="bg-white rounded-xl shadow-md p-8 border border-gray-100 text-center">
                        @if($battle->status === 'pending')
                            @if($battle->opponent_id === auth()->id())
                                <h3 class="text-xl font-bold mb-4">{{ __('¿Aceptas el desafío?') }} </h3>
                                <form action="{{ route('battles.accept', $battle) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg font-bold uppercase tracking-widest hover:bg-green-700 transition shadow-lg shadow-green-100">
                                        {{ __('Aceptar Batalla') }}
                                    </button>
                                </form>
                            @else
                                <div class="py-4">
                                    <svg class="h-12 w-12 text-yellow-400 mx-auto mb-3 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="text-gray-600">{{ __('Esperando a que el oponente acepte...') }} </p>
                                </div>
                            @endif
                        @elseif($battle->status === 'accepted')
                            @if(!$userFinished)
                                <h3 class="text-xl font-bold mb-4">{{ __('¡Es hora de luchar!') }} </h3>
                                <p class="text-gray-600 mb-6">{{ __('Tienes 10 preguntas para demostrar tus conocimientos sobre:') }} <br><span class="font-bold text-gray-900">{{ $battle->topic->title }}</span></p>
                                <a href="{{ route('battles.play', $battle) }}" class="inline-block bg-corporate-primary text-white px-10 py-4 rounded-xl font-black uppercase tracking-widest hover:bg-orange-600 transition shadow-xl shadow-orange-200 transform hover:scale-105 active:scale-95">
                                    {{ __('Entrar a la Arena') }}
                                </a>
                            @else
                                <div class="py-4">
                                    <svg class="h-12 w-12 text-blue-400 mx-auto mb-3 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="text-gray-900 font-bold">{{ __('¡Tu parte ha terminado!') }} </p>
                                    <p class="text-gray-600 text-sm mt-1">{{ __('Esperando a que tu oponente complete su ronda.') }} </p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Info Column -->
                <div class="space-y-6">
                    <div class="bg-gray-900 rounded-xl shadow-md p-6 text-white">
                        <h4 class="text-xs font-bold text-corporate-primary uppercase tracking-widest mb-4">{{ __('Reglas de Batalla') }} </h4>
                        <ul class="space-y-4 text-sm">
                            <li class="flex items-start">
                                <span class="bg-corporate-primary/20 text-corporate-primary rounded p-1 mr-3 mt-0.5"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></span>
                                {{ __('10 preguntas de respuesta múltiple.') }}
                            </li>
                            <li class="flex items-start">
                                <span class="bg-corporate-primary/20 text-corporate-primary rounded p-1 mr-3 mt-0.5"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></span>
                                {{ __('Cada acierto suma 10 puntos.') }}
                            </li>
                            <li class="flex items-start">
                                <span class="bg-corporate-primary/20 text-corporate-primary rounded p-1 mr-3 mt-0.5"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></span>
                                {{ __('El ganador se lleva 100 puntos extra para el ranking.') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
