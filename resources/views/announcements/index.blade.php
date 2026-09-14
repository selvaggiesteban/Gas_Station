<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Anuncios de la Academia') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-6">{{ __('Últimos Anuncios') }} </h3>

                    @if($announcements->isEmpty())
                        <div class="text-center py-12">
                            <p class="text-gray-500">{{ __('No hay anuncios disponibles') }} </p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($announcements as $announcement)
                                @php
                                    $isRead = in_array($announcement->id, auth()->user()->read_announcements ?? []);
                                @endphp
                                <div class="border rounded-lg p-4 transition {{ $isRead ? 'bg-white' : 'bg-blue-50 border-orange-300' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-lg mb-2">{{ $announcement->title }} </h4>
                                            <p class="text-gray-600 text-sm">{{ Str::limit(strip_tags($announcement->content), 200) }} </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6">
                            {{ $announcements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>