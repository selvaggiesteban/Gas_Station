@extends('emails.layout')

@section('content')
    <h1>{{ __('Instrucciones para tu Pedido') }} </h1>
    <p>{{ __('Hemos recibido tu pedido') }} <strong>#{{ $order->order_number }} </strong>{{ __('. Actualmente se encuentra en estado **pendiente** de pago.') }} </p>
    
    <p>{{ __('Para activar tu acceso, por favor realiza una transferencia bancaria con el importe total indicado.') }} </p>

    <div class="highlight-box">
        <strong>{{ __('Datos para la transferencia:') }} </strong><br>
        <div style="margin-top: 10px;">
            <strong>{{ __('Importe:') }} </strong> <span style="font-size: 18px; color: #EB7716; font-weight: bold;">{{ number_format($order->total_amount, 2, ',', '.') }}€</span><br>
            <strong>{{ __('IBAN:') }} </strong> ES37 0049 4451 3226 1003 1820<br>
            <strong>{{ __('Concepto:') }} </strong> <span style="font-family: monospace; font-weight: bold;">{{ $order->order_number }} </span>
        </div>
    </div>

    <p style="font-size: 14px; color: #E53935;">
        <strong>{{ __('Importante:') }} </strong>{{ __('Es vital incluir el número de pedido en el concepto para poder identificar tu pago.') }} </p>

    <p>{{ __('Una vez verifiquemos el ingreso (suele tardar 24/48h), activaremos tu cuenta automáticamente y recibirás un email de confirmación.') }} </p>
@endsection
