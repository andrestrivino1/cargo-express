<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pendientes\CompletarContenedorRequest;
use App\Http\Requests\Pendientes\CompletarOrdenCargueRequest;
use App\Http\Requests\Pendientes\CompletarOrdenServicioRequest;
use App\Http\Requests\Pendientes\CompletarSolicitudRequest;
use App\Http\Requests\Pendientes\CompletarTarjaRequest;
use App\Models\Contenedor;
use App\Models\ImportPendingRecord;
use App\Models\OrdenCargue;
use App\Models\OrdenServicio;
use App\Models\Solicitud;
use App\Models\Tarja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PendientesCompletarController extends Controller
{
    /** Mapa autoritativo de tipo de URL → clase Eloquent + vista + FormRequest. */
    private const TIPOS = [
        'contenedor' => [
            'class' => Contenedor::class,
            'vista' => 'pendientes.completar.contenedor',
            'request' => CompletarContenedorRequest::class,
            'label' => 'Contenedor',
        ],
        'orden-servicio' => [
            'class' => OrdenServicio::class,
            'vista' => 'pendientes.completar.orden-servicio',
            'request' => CompletarOrdenServicioRequest::class,
            'label' => 'Orden de servicio',
        ],
        'solicitud' => [
            'class' => Solicitud::class,
            'vista' => 'pendientes.completar.solicitud',
            'request' => CompletarSolicitudRequest::class,
            'label' => 'Solicitud',
        ],
        'tarja' => [
            'class' => Tarja::class,
            'vista' => 'pendientes.completar.tarja',
            'request' => CompletarTarjaRequest::class,
            'label' => 'Tarja',
        ],
        'orden-cargue' => [
            'class' => OrdenCargue::class,
            'vista' => 'pendientes.completar.orden-cargue',
            'request' => CompletarOrdenCargueRequest::class,
            'label' => 'Orden de cargue',
        ],
    ];

    public function index(Request $request): View
    {
        $pendientes = ImportPendingRecord::query()
            ->vivos()
            ->with(['pendienteable', 'batch'])
            ->when($request->filled('tipo'), function ($q) use ($request) {
                $cfg = self::TIPOS[$request->string('tipo')->value()] ?? null;
                if ($cfg !== null) {
                    $q->where('pendienteable_type', $cfg['class']);
                }
            })
            ->when($request->filled('import_batch_id'), fn ($q) => $q->where('import_batch_id', $request->integer('import_batch_id')))
            ->orderByDesc('prioridad')
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('pendientes.index', [
            'pendientes' => $pendientes,
            'tipos' => collect(self::TIPOS)->map(fn ($c) => $c['label'])->all(),
        ]);
    }

    public function editar(Request $request, string $type, int $id): View
    {
        [$cfg, $modelo, $pendiente] = $this->resolverTipoYRegistro($type, $id);

        return view($cfg['vista'], [
            'modelo' => $modelo,
            'pendiente' => $pendiente,
            'campos' => $pendiente->campos_pendientes,
            'tipoLabel' => $cfg['label'],
            'tipoSlug' => $type,
        ]);
    }

    public function actualizar(Request $request, string $type, int $id): RedirectResponse
    {
        [$cfg, $modelo, $pendiente] = $this->resolverTipoYRegistro($type, $id);

        /** @var FormRequest $formRequest */
        $formRequest = app($cfg['request']);
        $datos = $formRequest->validate($formRequest->rules());

        // Campos virtuales (no son columnas del modelo) — los extraemos del payload
        // antes del forceFill() y los procesamos por separado abajo.
        $clienteCorrectoId = $datos['cliente_correcto_id'] ?? null;
        $eliminarDuplicado = (bool) ($datos['eliminar_duplicado'] ?? false);
        unset($datos['cliente_correcto_id'], $datos['eliminar_duplicado']);

        DB::transaction(function () use ($modelo, $pendiente, $datos, $request, $type, $clienteCorrectoId, $eliminarDuplicado) {
            $modelo->forceFill($datos)->save();

            // Reasignación de cliente para Contenedor en conflicto
            if ($type === 'contenedor' && $clienteCorrectoId !== null) {
                $this->reasignarClienteContenedor($modelo, (int) $clienteCorrectoId, $eliminarDuplicado);
            }

            $pendiente->completar($datos, $request->user());
        });

        // ¿Quedan otros pendientes vivos sobre la misma entidad?
        $siguiente = $modelo->pendientesImportacion()->vivos()->first();
        if ($siguiente !== null) {
            return redirect()->route('pendientes.editar', ['type' => $type, 'id' => $id])
                ->with('status', 'Campo completado. Quedan otros pendientes en este registro.');
        }

        return redirect()->route('pendientes.index')
            ->with('status', $cfg['label'].' completado exitosamente.');
    }

    /**
     * Mueve un contenedor (con sus referencias y órdenes de cargue) al cliente
     * correcto, actualizando la Solicitud asociada. Si se pide, elimina los
     * contenedores duplicados con el mismo número en otros clientes (resolución
     * definitiva del conflicto detectado durante la importación).
     */
    private function reasignarClienteContenedor(Contenedor $cont, int $clienteCorrectoId, bool $eliminarDuplicado): void
    {
        $solicitud = $cont->ordenServicio?->solicitud;
        if ($solicitud !== null && $solicitud->cliente_id !== $clienteCorrectoId) {
            $solicitud->forceFill(['cliente_id' => $clienteCorrectoId])->save();
            \App\Models\Referencia::where('contenedor_id', $cont->id)->update(['cliente_id' => $clienteCorrectoId]);
        }

        if ($eliminarDuplicado) {
            $duplicados = Contenedor::query()
                ->where('numero', $cont->numero)
                ->where('id', '!=', $cont->id)
                ->get();

            foreach ($duplicados as $dup) {
                // Borra cascada: pendientes, tarjas (vía orden cargue), referencias, contenedor + padres
                DB::table('import_pending_records')
                    ->where('pendienteable_type', Contenedor::class)
                    ->where('pendienteable_id', $dup->id)
                    ->delete();
                \App\Models\Referencia::where('contenedor_id', $dup->id)->delete();
                $dup->delete();

                // Sus padres sintéticos (Solicitud, OrdenServicio) — solo si quedan huérfanos
                if ($dup->orden_servicio_id) {
                    $os = \App\Models\OrdenServicio::find($dup->orden_servicio_id);
                    if ($os && $os->contenedor()->doesntExist()) {
                        DB::table('import_pending_records')
                            ->where('pendienteable_type', \App\Models\OrdenServicio::class)
                            ->where('pendienteable_id', $os->id)
                            ->delete();
                        $solId = $os->solicitud_id;
                        $os->delete();
                        if ($solId) {
                            $sol = \App\Models\Solicitud::find($solId);
                            if ($sol && $sol->ordenServicio()->doesntExist()) {
                                DB::table('import_pending_records')
                                    ->where('pendienteable_type', \App\Models\Solicitud::class)
                                    ->where('pendienteable_id', $sol->id)
                                    ->delete();
                                $sol->delete();
                            }
                        }
                    }
                }
            }
        }
    }

    /** @return array{0:array<string,mixed>, 1:Model, 2:ImportPendingRecord} */
    private function resolverTipoYRegistro(string $type, int $id): array
    {
        $cfg = self::TIPOS[$type] ?? null;
        if ($cfg === null) {
            throw new NotFoundHttpException("Tipo de pendiente desconocido: {$type}");
        }

        /** @var class-string<Model> $class */
        $class = $cfg['class'];
        $modelo = $class::query()->findOrFail($id);

        $pendiente = $modelo->pendientesImportacion()->vivos()->orderByDesc('prioridad')->orderBy('created_at')->first();
        if ($pendiente === null) {
            throw new NotFoundHttpException('Este registro ya está completo.');
        }

        return [$cfg, $modelo, $pendiente];
    }
}
