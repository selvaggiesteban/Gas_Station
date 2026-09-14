<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class InvoiceGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    public Invoice $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->subject('Tu factura de Academicus - ' . $this->invoice->invoice_number)
            ->line('Te enviamos la factura correspondiente a tu suscripción en Academicus.')
            ->line('Número de factura: ' . $this->invoice->invoice_number)
            ->line('Fecha de emisión: ' . $this->invoice->issue_date->format('d/m/Y'))
            ->line('Importe total: ' . number_format($this->invoice->total_amount, 2, ',', '.') . ' €')
            ->action('Ver factura', route('invoices.show', $this->invoice))
            ->line('Gracias por confiar en Academicus.');

        // Attach PDF if exists
        if ($this->invoice->hasPdf()) {
            $mail->attach(Storage::path($this->invoice->pdf_path), [
                'as' => 'Factura-' . $this->invoice->invoice_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'type' => 'invoice_generated',
            'title' => 'Nueva factura disponible',
            'message' => 'Tu factura ' . $this->invoice->invoice_number . ' está disponible',
        ];
    }
}