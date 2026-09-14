<?php

namespace App\Notifications;

use App\Models\TutoringQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TutoringAnswered extends Notification
{
    use Queueable;

    public function __construct(public TutoringQuestion $question) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Tu tutoría ha sido respondida',
            'message' => 'Un profesor ha respondido tu pregunta: "' . $this->question->title . '"',
            'action_url' => route('tutorias.ver', $this->question->id),
            'icon' => 'chat',
            'type' => 'tutoring_answered',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu tutoría ha sido respondida - Academicus')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Un profesor ha respondido a tu pregunta de tutoría.')
            ->line('Pregunta: ' . $this->question->title)
            ->action('Ver respuesta', route('tutorias.ver', $this->question->id))
            ->line('Gracias por usar Academicus.');
    }
}
