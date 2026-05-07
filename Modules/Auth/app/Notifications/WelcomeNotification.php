<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $appName = app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Demo Company'));

        return (new MailMessage)
            ->subject('Bienvenido a ' . $appName)
            ->greeting('Bienvenido, ' . ($notifiable->name ?? '') . '!')
            ->line('Tu cuenta ha sido creada exitosamente en ' . $appName . '.')
            ->line('Ya puedes acceder al sistema para explorar nuestro catalogo de productos, solicitar cotizaciones y gestionar tus pedidos.')
            ->action('Acceder al sistema', $frontendUrl . '/auth/login')
            ->line('Si tienes alguna duda, no dudes en contactarnos.')
            ->salutation('Saludos, el equipo de ' . $appName);
    }
}
