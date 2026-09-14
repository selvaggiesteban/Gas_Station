<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva Batalla') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 shadow-sm" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-sm" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('battles.store') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-4">
                            <label for="opponent_id" class="block font-medium text-sm text-gray-700">{{ __('Oponente') }} <span class="text-red-500">*</span></label>
                            <select id="opponent_id" name="opponent_id" required class="border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary rounded-md shadow-sm w-full">
                                <option value="">{{ __('Selecciona un oponente') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (string) old('opponent_id') === (string) $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('opponent_id')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <label for="topic_id" class="block font-medium text-sm text-gray-700">{{ __('Tema') }} <span class="text-red-500">*</span></label>
                            <select id="topic_id" name="topic_id" required class="border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary rounded-md shadow-sm w-full">
                                <option value="">{{ __('Selecciona un tema') }}</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->id }}" {{ (string) old('topic_id') === (string) $topic->id ? 'selected' : '' }}>
                                        {{ $topic->title }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('topic_id')" class="mt-2" />
                        </div>

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-corporate-primary focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Desafiar') }}
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
