<?php

namespace App\Notifications;

use App\Models\Novedad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NovedadRegistradaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Novedad $novedad
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contenedor = $this->novedad->ordenVaciado->contenedor;

        return (new MailMessage)
            ->subject("Novedad Registrada - Contenedor #{$contenedor->numero}")
            ->line("Se ha registrado una novedad durante el vaciado del contenedor #{$contenedor->numero}.")
            ->line("Tipo: {$this->novedad->tipo->label()}")
            ->line("Descripcion: {$this->novedad->descripcion}")
            ->action('Ver Orden de Vaciado', route('vaciado.show', $this->novedad->ordenVaciado));
    }
}