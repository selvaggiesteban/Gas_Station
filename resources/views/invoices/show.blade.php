<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $invoice->invoice_number ?? $invoice->id }} </title>
    <style>
        body { font-family: sans-serif; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #EB7716; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #EB7716; }
        .details { margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .total { font-size: 20px; font-weight: bold; text-align: right; }
        .print-btn { display: block; width: 200px; margin: 0 auto; padding: 10px; text-align: center; background: #EB7716; color: white; text-decoration: none; border-radius: 5px; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ __('AcademicUS') }} </div>
        <div>
            <strong>{{ __('Factura Nº:') }} </strong> {{ $invoice->invoice_number ?? str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }} <br>
            <strong>{{ __('Fecha:') }} </strong> {{ $invoice->created_at->format('d/m/Y') }}
        </div>
    </div>

    <div class="details">
        <strong>{{ __('Facturado a:') }} </strong><br>
        {{ $invoice->user->name ?? 'Cliente' }} <br>
        {{ $invoice->user->email ?? '' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Descripción') }} </th>
                <th>{{ __('Importe') }} </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->description ?? 'Suscripción a Plataforma AcademicUS' }} </td>
                <td>{{ number_format($invoice->amount ?? 0, 2) }} €</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        Total: {{ number_format($invoice->amount ?? 0, 2) }} €
    </div>

    <button class="print-btn" onclick="window.print()">{{ __('Imprimir / Guardar como PDF') }} </button>
</body>
</html>
