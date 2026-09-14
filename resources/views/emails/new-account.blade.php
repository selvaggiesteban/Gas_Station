@extends('emails.layout')

@section('content')
    <h1>{{ __('¡Bienvenido a Academicus!') }} </h1>
    <p>{{ __('Hola,') }} <strong>{{ $user->name }} </strong>.</p>
    <p>{{ __('Estamos encantados de tenerte con nosotros. Hemos creado tu cuenta automáticamente tras tu pedido para que comiences tu preparación cuanto antes.') }} </p>
    
    <div class="highlight-box">
        <strong>{{ __('Tus credenciales de acceso:') }} </strong><br>
        Email: {{ $user->email }} <br>{{ __('Contraseña:') }} <span style="font-family: monospace; font-weight: bold; font-size: 18px;">{{ $password }} </span>
    </div>

    <p>{{ __('Te recomendamos cambiar tu contraseña una vez accedas por primera vez desde tu perfil de usuario.') }} </p>
    
    <a href="{{ url('/login') }}" class="button">{{ __('Acceder a mi Panel') }} </a>

    <p style="margin-top: 40px; font-size: 14px; color: #666666;">{{ __('Si tienes cualquier problema con el acceso, contacta con nuestro equipo de soporte respondiendo a este correo.') }} </p>
@endsection
