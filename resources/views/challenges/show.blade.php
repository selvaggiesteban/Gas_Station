{{-- resources/views/challenges/show.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">{{ __('Detalle del Desafío') }} </h1>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="mb-6">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mb-4">{{ __('Pendiente de revisión') }} </span>

                    <h2 class="text-xl font-semibold mb-4">{{ __('Pregunta propuesta:') }} </h2>
                    <p class="text-lg text-gray-700 mb-6">{{ $challenge->question_text ?? 'Pregunta de ejemplo para el desafío.' }} </p>

                    <h3 class="font-medium mb-2">{{ __('Opciones:') }} </h3>
                    <ul class="list-disc list-inside space-y-2 mb-6">
                        <li>{{ __('Opción A') }} </li>
                        <li>{{ __('Opción B (Correcta)') }} </li>
                        <li>{{ __('Opción C') }} </li>
                        <li>{{ __('Opción D') }} </li>
                    </ul>

                    <div class="text-sm text-gray-500">
                        <p>Propuesto por: {{ $challenge->user?->name ?? 'Usuario' }} </p>
                        <p>Fecha: {{ $challenge->created_at?->format('d/m/Y') ?? now()->format('d/m/Y') }} </p>
                    </div>
                </div>

                @can('approve', $challenge ?? new \App\Models\UserQuestionChallenge())
                    <div class="flex gap-4 border-t pt-6">
                        <form action="{{ route('gestor-contenido.desafios.aprobar', $challenge ?? 1) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">{{ __('✅ Aprobar') }} </button>
                        </form>

                        <form action="{{ route('gestor-contenido.desafios.rechazar', $challenge ?? 1) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">{{ __('❌ Rechazar') }} </button>
                        </form>
                    </div>
                @endcan
            </div>

            <div class="text-center">
                <a href="{{ route('retos.actual') }}" class="text-corporate-primary hover:text-orange-600">{{ __('← Volver a Retos') }} </a>
            </div>
        </div>
    </div>
</x-app-layout>
