<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Batallas de Opositores') }} </h1>
                <p class="text-gray-600 mt-2">{{ __('Desafía a otros alumnos y demuestra quién sabe más.') }} </p>
            </div>
            <a href="{{ route('batallas.nueva') }}" class="bg-corporate-primary text-white px-6 py-3 rounded-lg font-bold uppercase tracking-widest hover:bg-orange-600 transition shadow-lg shadow-orange-100 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                {{ __('Nueva Batalla') }}
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($battles as $battle)
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition group">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase 
                                {{ $battle->status === 'completed' ? 'bg-blue-50 text-blue-600' : 
                                   ($battle->status === 'accepted' ? 'bg-green-50 text-green-600' : 'bg-yellow-50 text-yellow-600') }}">
                                {{ __($battle->status) }}
                            </span>
                            <span class="text-xs text-gray-400 font-medium">{{ $battle->created_at->diffForHumans() }} </span>
                        </div>

                        <div class="flex items-center gap-4 mb-6">
                            <div class="relative">
                                <img class="h-12 w-12 rounded-full border-2 border-corporate-primary" src="{{ $battle->challenger->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($battle->challenger->name) }}" alt="">
                                @if($battle->winner_id === $battle->challenger_id)
                                    <span class="absolute -top-1 -right-1 bg-yellow-400 text-white rounded-full p-0.5 shadow-sm"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg></span>
                                @endif
                            </div>
                            <div class="text-xs font-black text-gray-200 italic uppercase">VS</div>
                            <div class="relative">
                                <img class="h-12 w-12 rounded-full border-2 border-gray-100" src="{{ $battle->opponent->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($battle->opponent->name) }}" alt="">
                                @if($battle->winner_id === $battle->opponent_id)
                                    <span class="absolute -top-1 -right-1 bg-yellow-400 text-white rounded-full p-0.5 shadow-sm"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg></span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-6">
                            <p class="text-sm font-bold text-gray-900 group-hover:text-corporate-primary transition-colors">{{ $battle->topic->title }} </p>
                            <p class="text-xs text-gray-500 mt-1">{{ $battle->challenger->name }} vs {{ $battle->opponent->name }} </p>
                        </div>

                        <div class="flex justify-between items-center">
                            @if($battle->status === 'completed')
                                <div class="text-xs font-bold">
                                    <span class="text-corporate-primary">{{ $battle->challenger_score }} </span> - <span>{{ $battle->opponent_score }} </span>
                                </div>
                            @else
                                <div class="text-xs text-gray-400 italic">{{ __('En curso...') }} </div>
                            @endif
                            <a href="{{ route('batallas.ver', $battle) }}" class="text-xs font-black uppercase tracking-tighter text-corporate-primary hover:text-orange-600 underline decoration-2 underline-offset-4">
                                {{ __('Ver detalles') }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-xl border-2 border-dashed border-gray-100">
                    <svg class="h-16 w-16 text-gray-200 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <p class="text-gray-400 font-medium">{{ __('No tienes batallas activas.') }} </p>
                    <a href="{{ route('batallas.nueva') }}" class="text-corporate-primary hover:underline mt-2 inline-block font-bold">{{ __('¡Lanza el primer desafío! →') }} </a>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $battles->links() }}
        </div>
    </div>
</x-app-layout>
