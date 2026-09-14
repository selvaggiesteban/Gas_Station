<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Figtree', sans-serif; background-color: #F1F1F1; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background-color: #000000; padding: 40px; text-align: center; }
        .content { padding: 40px; color: #333333; line-height: 1.6; }
        .footer { background-color: #F8F8F8; padding: 20px; text-align: center; font-size: 12px; color: #999999; }
        .button { display: inline-block; padding: 16px 32px; background-color: #EB7716; color: #FFFFFF !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .highlight-box { background-color: #FFF3E0; border-left: 4px solid #EB7716; padding: 20px; border-radius: 4px; margin: 20px 0; }
        h1 { color: #000000; font-size: 24px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.svg') }}" :alt="__('Academicus')" width="80">
            <div style="color: #EB7716; font-size: 20px; font-weight: bold; margin-top: 10px;">{{ __('Academicus') }} </div>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            © {{ date('Y') }} Academicus. Todos los derechos reservados.<br>{{ __('Preparación de Élite para Prisiones.') }} </div>
    </div>
</body>
</html>
