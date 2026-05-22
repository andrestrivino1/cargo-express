<?php

namespace App\Notifications;

use App\Models\Referencia;
use App\Models\UbicacionPatio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UbicacionAsignadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Referencia $referencia,
        public readonly UbicacionPatio $ubicacion,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ubicación Asignada - Referencia {$this->referencia->codigo}")
            ->line("Se ha asignado una ubicación a su referencia **{$this->referencia->codigo}**.")
            ->line("**Módulo:** {$this->ubicacion->modulo}")
            ->line("**Posición:** {$this->ubicacion->posicion}")
            ->line("**Contenedor:** {$this->referencia->contenedor->numero}")
            ->action('Ver Inventario', route('inventario.index'));
    }
}