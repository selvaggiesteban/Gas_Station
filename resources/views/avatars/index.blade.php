<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Personalizar mi Avatar') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Columna Izquierda: Previsualización y Género -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                        <h3 class="text-lg font-bold mb-4">Vista Previa</h3>
                        <div class="relative inline-block">
                            <div class="w-48 h-48 rounded-full border-4 border-corporate-primary/20 overflow-hidden mx-auto bg-gray-100">
                                <img src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&size=200' }}" 
                                     alt="Avatar" class="w-full h-full object-cover">
                            </div>
                            <div class="absolute -bottom-2 -right-2 bg-corporate-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                                {{ Auth::user()->getRankName() }}
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <p class="text-sm text-gray-500 mb-2">Género del personaje</p>
                            <form action="{{ route('avatar.set-gender') }}" method="POST" class="flex justify-center gap-2">
                                @csrf
                                <button name="gender" value="male" class="px-4 py-2 rounded-lg border {{ $user->gender === 'male' ? 'bg-blue-500 text-white border-blue-600' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100' }} transition">
                                    Hombre
                                </button>
                                <button name="gender" value="female" class="px-4 py-2 rounded-lg border {{ $user->gender === 'female' ? 'bg-pink-500 text-white border-pink-600' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100' }} transition">
                                    Mujer
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Progreso de Nivel -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Tu progreso</h3>
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-2xl font-black text-corporate-primary">{{ number_format($user->points) }} <span class="text-xs font-normal text-gray-400">pts</span></span>
                            <span class="text-xs font-bold text-gray-400 uppercase">Siguiente rango: {{ $nextLevelProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-corporate-primary h-2.5 rounded-full" style="width: {{ $nextLevelProgress }}%"></div>
                        </div>
                    </div>

                    <!-- Subir Avatar Personalizado -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Foto de perfil</h3>
                        <form action="{{ route('avatar.subir') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="avatar" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-corporate-primary/10 file:text-corporate-primary hover:file:bg-corporate-primary/20" accept="image/*">
                            <button type="submit" class="mt-4 w-full bg-gray-800 text-white text-sm font-bold py-2 rounded-lg hover:bg-gray-900 transition">
                                Subir imagen
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Columna Derecha: Tienda de Uniformes -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800">Tienda de Uniformes</h3>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 bg-orange-100 text-orange-700 text-[10px] font-bold rounded uppercase">Bronce</span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-[10px] font-bold rounded uppercase">Plata</span>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-[10px] font-bold rounded uppercase">Oro</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @forelse($allEquipment as $item)
                                @php
                                    $isUnlocked = in_array($item->id, $unlockedIds);
                                    $isEquipped = in_array($item->id, $equippedIds);
                                    $canUnlock = $item->canBeUnlockedBy($user);
                                @endphp
                                <div class="relative border rounded-xl p-4 transition-all {{ $isEquipped ? 'border-corporate-primary bg-corporate-primary/5 ring-2 ring-corporate-primary/20' : ($isUnlocked ? 'border-gray-200 bg-white hover:border-gray-300' : 'border-gray-100 bg-gray-50 opacity-75') }}">
                                    <!-- Thumbnail Placeholder (Ideally the image_path) -->
                                    <div class="w-full aspect-square bg-gray-200 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                                        @if($item->image_path)
                                            <img src="{{ asset('storage/' . $item->image_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <h4 class="text-sm font-bold text-gray-800 truncate">{{ $item->name }} </h4>
                                    <p class="text-[10px] text-gray-500 mb-3">{{ $item->getUnlockDescription() }} </p>

                                    @if($isUnlocked)
                                        <form action="{{ route('avatar.actualizar') }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                                            @if($isEquipped)
                                                <button name="action" value="unequip" class="w-full py-1.5 bg-red-50 text-red-600 text-xs font-bold rounded-lg hover:bg-red-100 transition">
                                                    Quitar
                                                </button>
                                            @else
                                                <button name="action" value="equip" class="w-full py-1.5 bg-corporate-primary text-white text-xs font-bold rounded-lg hover:bg-corporate-primary-dark transition shadow-md">
                                                    Equipar
                                                </button>
                                            @endif
                                        </form>
                                    @elseif($canUnlock)
                                        <form action="{{ route('avatar.actualizar') }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                                            <button name="action" value="equip" class="w-full py-1.5 bg-green-500 text-white text-xs font-bold rounded-lg hover:bg-green-600 transition shadow-md">
                                                Desbloquear
                                            </button>
                                        </form>
                                    @else
                                        <div class="flex items-center justify-center py-1.5 bg-gray-200 text-gray-500 text-xs font-bold rounded-lg cursor-not-allowed">
                                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            Bloqueado
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="col-span-full py-12 text-center text-gray-400 italic">
                                    No hay uniformes disponibles para tu selección actual.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
