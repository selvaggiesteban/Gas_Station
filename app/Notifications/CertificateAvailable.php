<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CertificateAvailable extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'course_id' => $this->course->id,
            'course_name' => $this->course->name,
            'message' => "¡Has completado el curso '{$this->course->name}'! Tu certificado está disponible para descargar.",
            'type' => 'certificate_available',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Certificado disponible - Academicus!')
            ->greeting('¡Felicitaciones ' . $notifiable->name . '!')
            ->line('Has completado exitosamente el curso: ' . $this->course->name)
            ->line('Tu certificado ya está disponible para descargar.')
            ->action('Descargar Certificado', route('certificates.download', $this->course))
            ->line('Gracias por completar el curso con nosotros.');
    }
}
