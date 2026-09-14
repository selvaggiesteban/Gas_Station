<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SubscriptionRenewed extends Notification
{
    use Queueable;

    public function __construct(public Subscription $subscription)
    {
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
            'message' => "Tu suscripción a {$this->subscription->plan->name} ha sido renovada exitosamente.",
            'ends_at' => $this->subscription->ends_at->toIso8601String(),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Suscripción Renovada - Academicus')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu suscripción a ' . $this->subscription->plan->name . ' ha sido renovada exitosamente.')
            ->line('Próxima fecha de renovación: ' . $this->subscription->ends_at->format('d/m/Y'))
            ->action('Ver Mi Suscripción', url('/subscriptions'))
            ->line('Gracias por continuar con nosotros!');
    }
}
