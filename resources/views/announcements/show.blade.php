<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $announcement->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('anuncios.lista') }}" class="text-orange-600 hover:text-orange-800">{{ __('�?� Volver a Anuncios') }} </a>
                    </div>

                    <div class="border-b pb-4 mb-4">
                        <div class="flex items-center gap-3 mb-4">
                            @if($announcement->priority === \'high\')<span class="px-3 py-1 text-sm bg-red-600 text-white rounded-full">{{ __('Importante') }} </span>
                            @endif
                            <span class="text-gray-500">
                                Publicado el {{ $announcement->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <h1 class="text-2xl font-bold mb-4">{{ $announcement->title }} </h1>
                    </div>

                    <div class="prose max-w-none">
                        {!! $announcement->content !!}
                    </div>

                    @if($announcement->attachment)<div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-semibold mb-2">{{ __('Adjunto:') }} </h4>
                            <a href="{{ Storage::url($announcement->attachment) }}" target="_blank" class="text-orange-600 hover:text-orange-800 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>{{ __('Descargar archivo') }} </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
