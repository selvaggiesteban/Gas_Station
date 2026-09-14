{{-- resources/views/zoom/create.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">{{ __('Programar Clase en Zoom') }} </h1>

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('zoom.guardar') }}" method="POST" class="bg-white rounded-lg shadow-md p-6">
                @csrf

                <div class="mb-6">
                    <label for="topic_id" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tema *') }} </label>
                    <select name="topic_id" id="topic_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        <option value="">{{ __('Selecciona un tema') }} </option>
                        @foreach($topics as $topic)
                            <option value="{{ $topic->id }}" {{ old('topic_id') == $topic->id ? 'selected' : '' }}>
                                {{ $topic->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('topic_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }} </p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Fecha y hora *') }} </label>
                    <input type="datetime-local" name="start_time" id="start_time" required value="{{ old('start_time') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                    @error('start_time')
                        <p class="text-red-500 text-sm mt-1">{{ $message }} </p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Duración (minutos) *') }} </label>
                    <select name="duration_minutes" id="duration_minutes" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        <option value="30" {{ old('duration_minutes') == 30 ? 'selected' : '' }}>{{ __('30 minutos') }} </option>
                        <option value="60" {{ old('duration_minutes') == 60 ? 'selected' : '' }}>{{ __('1 hora') }} </option>
                        <option value="90" {{ old('duration_minutes') == 90 ? 'selected' : '' }}>{{ __('1 hora 30 minutos') }} </option>
                        <option value="120" {{ old('duration_minutes') == 120 ? 'selected' : '' }}>{{ __('2 horas') }} </option>
                    </select>
                    @error('duration_minutes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }} </p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Zona horaria') }} </label>
                    <select name="timezone" id="timezone" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-corporate-primary focus:ring focus:ring-corporate-primary focus:ring-opacity-50">
                        <option value="Europe/Madrid" selected>{{ __('España (Europe/Madrid)') }} </option>
                        <option value="Europe/London">{{ __('Reino Unido (Europe/London)') }} </option>
                        <option value="America/Mexico_City">{{ __('México (America/Mexico_City)') }} </option>
                        <option value="America/Bogota">{{ __('Colombia (America/Bogota)') }} </option>
                    </select>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="{{ route('zoom.clases') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">{{ __('Cancelar') }} </a>
                    <button type="submit" class="px-6 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('Crear Clase') }} </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
