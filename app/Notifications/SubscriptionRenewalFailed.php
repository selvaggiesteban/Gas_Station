<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SubscriptionRenewalFailed extends Notification
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public string $errorMessage
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan->name,
            'message' => "No pudimos renovar tu suscripción. Por favor actualiza tu método de pago.",
            'error' => $this->errorMessage,
            'retry_count' => $this->subscription->payment_failure_count,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Problema con tu Suscripción - Academicus')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Hubo un problema al renovar tu suscripción a ' . $this->subscription->plan->name)
            ->line('Error: ' . $this->errorMessage)
            ->line('Intentos fallidos: ' . $this->subscription->payment_failure_count)
            ->action('Actualizar Método de Pago', url('/subscriptions/payment'))
            ->line('Por favor actualiza tu método de pago para evitar la suspensión de tu cuenta.')
            ->line('Si tienes preguntas, contacta a soporte@academicus.com');
    }
}
