<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LevelUpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $newLevel,
        public array $unlockedItems = []
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        $itemCount = count($this->unlockedItems);

        return [
            'new_level' => $this->newLevel,
            'unlocked_items_count' => $itemCount,
            'unlocked_items' => collect($this->unlockedItems)->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
            ]),
            'message' => "¡Has alcanzado el nivel {$this->newLevel}!" .
                ($itemCount > 0 ? " Desbloqueaste {$itemCount} nuevos items." : ''),
            'type' => 'level_up',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('¡Has subido de nivel en Academicus!')
            ->greeting('¡Felicidades ' . $notifiable->name . '!')
            ->line('Has alcanzado el nivel ' . $this->newLevel . '.')
            ->line('Tu dedicación al estudio está dando frutos.')
            ->action('Ver tu perfil', url('/profile'));

        if (count($this->unlockedItems) > 0) {
            $message->line('Has desbloqueado nuevos items para tu avatar:');
            foreach ($this->unlockedItems as $item) {
                $message->line('• ' . $item->name);
            }
            $message->line('Visita tu perfil para equiparlos.');
        }

        $message->line('¡Sigue así!');

        return $message;
    }
}
