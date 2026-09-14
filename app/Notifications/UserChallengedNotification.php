<?php

namespace App\Notifications;

use App\Models\Battle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserChallengedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $battle;

    public function __construct(Battle $battle)
    {
        $this->battle = $battle;
    }

    public function via($notifiable)
    {
        // For now, we'll use database notifications to show within the app.
        // We can add 'mail' here later if email notifications are needed.
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $challengerName = $this->battle->challenger->name;
        
        return [
            'title' => '¡Has sido retado!',
            'message' => "{$challengerName} te ha desafiado a una batalla de conocimientos.",
            'action_url' => route('battles.show', $this->battle->id),
            'icon' => 'swords', // An icon name for the frontend to use
        ];
    }
}
