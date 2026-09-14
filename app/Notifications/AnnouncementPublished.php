<?php

namespace App\Notifications;

use App\Models\AcademyAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AnnouncementPublished extends Notification
{
    use Queueable;

    public function __construct(public AcademyAnnouncement $announcement) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'message' => \Illuminate\Support\Str::limit(strip_tags($this->announcement->content), 200),
            'action_url' => route('anuncios.ver', $this->announcement->id),
            'icon' => 'megaphone',
            'type' => 'announcement',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $typeLabel = match($this->announcement->type) {
            'warning' => 'Aviso',
            'urgent' => 'Urgente',
            default => 'Información',
        };

        return (new MailMessage)
            ->subject("[{$typeLabel}] {$this->announcement->title} — Academicus")
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->announcement->title)
            ->line(\Illuminate\Support\Str::limit(strip_tags($this->announcement->content), 500))
            ->action('Ver anuncio completo', route('anuncios.ver', $this->announcement->id))
            ->salutation('El equipo de Academicus');
    }
}
