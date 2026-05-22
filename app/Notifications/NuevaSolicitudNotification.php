<?php

namespace App\Notifications;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevaSolicitudNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Solicitud $solicitud
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nueva Solicitud de Retiro #{$this->solicitud->id}")
            ->line("Contenedor: {$this->solicitud->numero_contenedor}")
            ->line("Cliente: {$this->solicitud->cliente->name}")
            ->action('Ver Solicitud', route('solicitudes.show', $this->solicitud));
    }
}