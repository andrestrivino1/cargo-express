<?php

namespace App\Notifications;

use App\Models\Contenedor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TirillaGateOutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Contenedor $contenedor
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Salida de Contenedor #{$this->contenedor->numero}")
            ->greeting("Estimado(a) {$notifiable->name},")
            ->line("Le informamos que el contenedor **{$this->contenedor->numero}** ha salido de nuestras instalaciones.")
            ->line("**Placa del vehículo:** {$this->contenedor->placa_vehiculo}")
            ->line("**Destino:** {$this->contenedor->destino_salida}")
            ->line("**Fecha de salida:** {$this->contenedor->fecha_salida->format('d/m/Y H:i')}")
            ->line("**Estado de limpieza:** " . ($this->contenedor->limpieza_registrada ? 'Limpio' : 'Sin limpieza'))
            ->action('Ver Detalles', route('gate-out.show', $this->contenedor))
            ->line('Gracias por confiar en Cargo Express.');
    }
}