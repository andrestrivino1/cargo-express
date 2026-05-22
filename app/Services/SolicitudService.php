<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Solicitud;
use App\Models\User;
use App\Notifications\NuevaSolicitudNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class SolicitudService
{
    public function crear(array $data): Solicitud
    {
        $solicitud = Solicitud::create([
            'cliente_id' => $data['cliente_id'],
            'numero_contenedor' => $data['numero_contenedor'],
            'naviera' => $data['naviera'] ?? null,
            'puerto_origen' => $data['puerto_origen'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_solicitud' => $data['fecha_solicitud'],
        ]);

        if (!empty($data['documentos'])) {
            foreach ($data['documentos'] as $archivo) {
                $ruta = $archivo->store("documentos/{$solicitud->id}");

                Documento::create([
                    'solicitud_id' => $solicitud->id,
                    'nombre' => $archivo->getClientOriginalName(),
                    'ruta' => $ruta,
                    'tipo_mime' => $archivo->getClientMimeType(),
                    'tamaño' => $archivo->getSize(),
                ]);
            }
        }

        // Notificar a coordinadores
        $coordinadores = User::role('coordinador')->get();
        Notification::send($coordinadores, new NuevaSolicitudNotification($solicitud));

        // Notificar al cliente asociado
        $cliente = User::find($data['cliente_id']);
        if ($cliente) {
            $cliente->notify(new NuevaSolicitudNotification($solicitud));
        }

        return $solicitud;
    }

    public function listarPorCliente(User $cliente): LengthAwarePaginator
    {
        return Solicitud::where('cliente_id', $cliente->id)
            ->with(['documentos', 'ordenServicio'])
            ->latest()
            ->paginate(15);
    }

    public function listarTodas(): LengthAwarePaginator
    {
        return Solicitud::with(['cliente', 'documentos', 'ordenServicio'])
            ->latest()
            ->paginate(15);
    }
}