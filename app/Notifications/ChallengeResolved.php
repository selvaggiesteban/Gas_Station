<?php

namespace App\Notifications;

use App\Models\QuestionChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ChallengeResolved extends Notification
{
    use Queueable;

    public function __construct(public QuestionChallenge $challenge)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        $message = $this->challenge->status === 'approved'
            ? 'Tu impugnación fue aprobada. Se han ajustado tus puntos.'
            : 'Tu impugnación fue revisada pero no fue aprobada.';

        return [
            'challenge_id' => $this->challenge->id,
            'question_id' => $this->challenge->question_id,
            'status' => $this->challenge->status,
            'message' => $message,
            'reviewer_notes' => $this->challenge->reviewer_notes,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        if ($this->challenge->status === 'approved') {
            return (new MailMessage)
                ->subject('Impugnación Aprobada - Academicus')
                ->greeting('¡Hola ' . $notifiable->name . '!')
                ->line('Tu impugnación para la pregunta ha sido aprobada.')
                ->line('Se han ajustado tus puntos y corregido tu respuesta.')
                ->line('Notas del revisor: ' . ($this->challenge->reviewer_notes ?: 'Ninguna'))
                ->action('Ver Mis Tests', url('/tests/history'))
                ->line('Gracias por tu participación!');
        }

        return (new MailMessage)
            ->subject('Resolución de Impugnación - Academicus')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu impugnación ha sido revisada.')
            ->line('Desafortunadamente, después de revisar la pregunta, nuestra respuesta original se mantiene correcta.')
            ->line('Notas del revisor: ' . ($this->challenge->reviewer_notes ?: 'Ninguna'))
            ->action('Ver Detalles', url('/tests/history'))
            ->line('Si tienes más dudas, puedes contactar con soporte.');
    }
}
