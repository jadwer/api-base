<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $url = "{$frontendUrl}/auth/verify-email?" . http_build_query([
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        return (new MailMessage)
            ->subject('Verifica tu correo electronico')
            ->greeting('Hola ' . ($notifiable->name ?? '') . ',')
            ->line('Gracias por registrarte. Por favor verifica tu correo electronico haciendo clic en el boton de abajo.')
            ->action('Verificar correo electronico', $url)
            ->line('Si no creaste una cuenta, puedes ignorar este correo.')
            ->salutation('Saludos, ' . config('app.name'));
    }
}
