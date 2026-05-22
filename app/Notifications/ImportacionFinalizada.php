<?php

namespace App\Notifications;

use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportacionFinalizada extends Notification
{
    use Queueable;

    public function __construct(public readonly ImportBatch $batch) {}

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'import_batch_id' => $this->batch->getKey(),
            'archivo_nombre' => $this->batch->archivo_nombre,
            'modo' => $this->batch->modo,
            'estado_final' => $this->batch->estado->value,
            'resumen' => [
                'total_filas' => $this->batch->total_filas,
                'importables' => $this->batch->importables,
                'errores' => $this->batch->errores,
                'advertencias' => $this->batch->advertencias,
                'ignoradas' => $this->batch->ignoradas,
                'contenedores_creados' => $this->batch->contenedores_creados,
                'referencias_creadas' => $this->batch->referencias_creadas,
                'clientes_autocreados' => $this->batch->clientes_autocreados,
                'despachos_historicos_creados' => $this->batch->despachos_historicos_creados,
            ],
            'url' => route('importaciones.show', $this->batch),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('importaciones.show', $this->batch);

        return (new MailMessage)
            ->subject('Importación '.$this->batch->modo.' '.$this->batch->estado->value.' — '.$this->batch->archivo_nombre)
            ->greeting('Hola, '.$notifiable->name)
            ->line('Tu importación del archivo "'.$this->batch->archivo_nombre.'" terminó en estado '.$this->batch->estado->value.'.')
            ->action('Ver reporte', $url);
    }
}
