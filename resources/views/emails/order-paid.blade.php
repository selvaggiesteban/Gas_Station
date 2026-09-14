@extends('emails.layout')

@section('content')
    <h1>{{ __('Confirmación de Pago') }} </h1>
    <p>{{ __('Tu pago para el pedido') }} <strong>#{{ $order->order_number }} </strong>{{ __('ha sido confirmado con éxito.') }} </p>
    
    <p>{{ __('Ya tienes acceso ilimitado a los cursos adquiridos. Tu preparación de élite comienza ahora.') }} </p>

    <div style="border: 1px solid #EEEEEE; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #000000;">{{ __('Resumen del Pedido:') }} </h3>
        @foreach($order->items as $item)<div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                <span>{{ $item->name }} </span>
                <strong>{{ number_format($item->price, 2, ',', '.') }}€</strong>
            </div>
        @endforeach
        <div style="border-top: 1px solid #EEEEEE; margin-top: 15px; padding-top: 15px; text-align: right;">
            <span style="font-size: 18px; font-weight: bold;">Total: {{ number_format($order->total_amount, 2, ',', '.') }}€</span>
        </div>
    </div>

    <a href="{{ url('/dashboard') }}" class="button">{{ __('Empezar a Estudiar') }} </a>

    <p style="margin-top: 40px; font-size: 14px; color: #666666;">{{ __('Recibirás la factura oficial de Holded en un correo separado en los próximos minutos.') }} </p>
@endsection
