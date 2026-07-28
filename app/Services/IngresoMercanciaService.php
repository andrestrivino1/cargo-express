<?php

namespace App\Services;

use App\Enums\ContenedorEstado;
use App\Enums\DocumentoCategoria;
use App\Enums\MovimientoTipo;
use App\Exceptions\IngresoDuplicadoException;
use App\Models\CambioAuditoria;
use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class IngresoMercanciaService
{
    private const IDEMPOTENCY_SCOPE = 'ingreso';

    public function __construct(
        private readonly MovimientoInventarioService $movimientos,
        private readonly IdempotencyService $idempotencia,
    ) {}

    /**
     * Registra un ingreso: un BL (Ingreso padre) con sus documentos y fecha, que
     * agrupa uno o varios contenedores, cada uno con sus referencias. La fecha de
     * ingreso (posiblemente retroactiva) se propaga a contenedores y referencias.
     *
     * Idempotencia: si el mismo intento (idempotency_key) ya creó un ingreso, no
     * se crea otro; se lanza {@see IngresoDuplicadoException} con el id del que ya
     * existe para que el controlador redirija allí. Es la misma barrera que usa
     * Salida desde la feature 008, y evita los duplicados por doble envío.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $documentos  ['bl' => ..., 'dim' => ..., 'lista_empaque' => ...]
     *
     * @throws IngresoDuplicadoException si el intento ya fue procesado.
     */
    public function registrar(array $data, array $documentos, User $usuario): Ingreso
    {
        return DB::transaction(function () use ($data, $documentos, $usuario) {
            // Barrera de idempotencia: primera sentencia de la transacción.
            $token = $data['idempotency_key'] ?? null;

            if ($token !== null && ! $this->idempotencia->reservar($token, self::IDEMPOTENCY_SCOPE, $usuario->id)) {
                $existenteId = $this->idempotencia->recursoReservado($token, self::IDEMPOTENCY_SCOPE);

                if ($existenteId === null) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'El registro está en proceso. Intente de nuevo en unos segundos.',
                    ]);
                }

                throw new IngresoDuplicadoException($existenteId);
            }

            $fecha = $data['fecha_ingreso'];

            $ingreso = Ingreso::create([
                'bl' => $data['bl'],
                'cliente_id' => $data['cliente_id'],
                'fecha_ingreso' => $fecha,
                'usuario_id' => $usuario->id,
            ]);

            foreach ($data['contenedores'] as $filaContenedor) {
                $contenedor = $ingreso->contenedores()->create([
                    'numero' => $filaContenedor['numero'],
                    'tipo_mercancia' => $filaContenedor['tipo_mercancia'],
                    'bl' => $data['bl'],
                    'estado' => ContenedorEstado::EnPatio,
                    'fecha_ingreso' => $fecha,
                ]);

                foreach ($filaContenedor['referencias'] as $filaReferencia) {
                    $this->crearReferencia($contenedor, $filaReferencia, $usuario, $ingreso);
                }
            }

            $carpeta = "ingresos/{$ingreso->id}";
            $ingreso->guardarArchivo($documentos['bl'], $carpeta, 'documento', DocumentoCategoria::Bl->value);
            $ingreso->guardarArchivo($documentos['dim'], $carpeta, 'documento', DocumentoCategoria::Dim->value);
            $ingreso->guardarArchivo($documentos['lista_empaque'], $carpeta, 'documento', DocumentoCategoria::ListaEmpaque->value);

            // Enlaza el token con el ingreso creado: un reenvío posterior aterriza
            // aquí en vez de crear otro.
            if ($token !== null) {
                $this->idempotencia->asociarRecurso($token, $ingreso->id);
            }

            return $ingreso;
        });
    }

    /**
     * Actualiza un ingreso desde la pantalla de edición: confirma BL/cliente/fecha,
     * adjunta imágenes (aditivo) y, opcionalmente, agrega una referencia nueva a un
     * contenedor del ingreso (con su movimiento de inventario). Todo es atómico.
     *
     * @param  array<string, mixed>  $data  bl, cliente_id, fecha_ingreso
     * @param  array<int, UploadedFile>  $fotos  imágenes a agregar (puede estar vacío)
     * @param  array<string, mixed>|null  $nuevaReferencia  contenedor_id + datos de la referencia, o null
     */
    public function actualizar(Ingreso $ingreso, array $data, array $fotos, ?array $nuevaReferencia, User $usuario): Ingreso
    {
        return DB::transaction(function () use ($ingreso, $data, $fotos, $nuevaReferencia, $usuario) {
            $ingreso->update([
                'bl' => $data['bl'],
                'cliente_id' => $data['cliente_id'],
                'fecha_ingreso' => $data['fecha_ingreso'],
                'bl_por_confirmar' => false,
            ]);

            if (! empty($fotos)) {
                $ingreso->guardarFotos($fotos, "ingresos/{$ingreso->id}");
            }

            if (! empty($nuevaReferencia['codigo'])) {
                $contenedor = $ingreso->contenedores()->findOrFail($nuevaReferencia['contenedor_id']);
                $this->crearReferencia($contenedor, $nuevaReferencia, $usuario, $ingreso);
            }

            return $ingreso;
        });
    }

    /**
     * Crea una referencia en un contenedor (heredando cliente y fecha del ingreso)
     * y registra su movimiento de inventario de entrada. Reutilizado por el alta y
     * por la edición de ingresos.
     *
     * @param  array<string, mixed>  $fila  codigo, descripcion, cantidad, unidad_medida, peso?, ubicacion_patio_id?
     */
    private function crearReferencia(Contenedor $contenedor, array $fila, User $usuario, Ingreso $ingreso): Referencia
    {
        $referencia = $contenedor->referencias()->create([
            'cliente_id' => $ingreso->cliente_id,
            'codigo' => $fila['codigo'],
            'descripcion' => $fila['descripcion'],
            'cantidad_inicial' => $fila['cantidad'],
            'cantidad_actual' => $fila['cantidad'],
            'unidad_medida' => $fila['unidad_medida'],
            'peso' => $fila['peso'] ?? null,
            'ubicacion_patio_id' => $fila['ubicacion_patio_id'] ?? null,
            'fecha_ingreso' => $ingreso->fecha_ingreso,
        ]);

        $this->movimientos->registrarEntrada($referencia, (int) $fila['cantidad'], $usuario, $ingreso);

        return $referencia;
    }

    /**
     * Lista los ingresos (por BL) paginados, con su cliente y conteos.
     */
    public function listar(array $filtros)
    {
        $query = Ingreso::query()
            ->with('cliente')
            ->withCount('contenedores');

        if (! empty($filtros['bl'])) {
            $query->where('bl', 'like', '%'.$filtros['bl'].'%');
        }

        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        return $query->orderByDesc('fecha_ingreso')->orderByDesc('id')->paginate(15);
    }

    /**
     * Razones por las que un ingreso NO se puede eliminar.
     *
     * Solo se permite borrar un ingreso cuya mercancía siga intacta tal como
     * entró. En cuanto algo se movió —se despachó, se transfirió, se vació o se
     * ajustó por novedad— borrar el ingreso dejaría registros huérfanos y
     * descuadraría el inventario, así que se bloquea y se explica por qué.
     *
     * @return array<int, string> vacío = se puede eliminar
     */
    public function bloqueosParaEliminar(Ingreso $ingreso): array
    {
        $referenciaIds = Referencia::whereIn(
            'contenedor_id',
            $ingreso->contenedores()->select('id')
        )->pluck('id');

        $contenedorIds = $ingreso->contenedores()->pluck('id');

        $bloqueos = [];

        if ($referenciaIds->isNotEmpty()) {
            $despachadas = DB::table('tarja_detalles')->whereIn('referencia_id', $referenciaIds)->count();
            if ($despachadas > 0) {
                $bloqueos[] = "Tiene {$despachadas} referencia(s) ya despachadas en una Orden de Salida.";
            }

            $consumidas = Referencia::whereIn('id', $referenciaIds)
                ->whereColumn('cantidad_actual', '!=', 'cantidad_inicial')
                ->count();
            if ($consumidas > 0) {
                $bloqueos[] = "Tiene {$consumidas} referencia(s) cuya cantidad ya cambió (salida, transferencia o novedad de vaciado).";
            }

            $transferidas = DB::table('transferencias')
                ->where(function ($q) use ($referenciaIds) {
                    $q->whereIn('referencia_origen_id', $referenciaIds)
                        ->orWhereIn('referencia_destino_id', $referenciaIds);
                })
                ->count();
            if ($transferidas > 0) {
                $bloqueos[] = "Participa en {$transferidas} transferencia(s) de inventario.";
            }

            $movimientosExtra = MovimientoInventario::whereIn('referencia_id', $referenciaIds)
                ->where('tipo', '!=', MovimientoTipo::Entrada)
                ->count();
            if ($movimientosExtra > 0) {
                $bloqueos[] = "Tiene {$movimientosExtra} movimiento(s) de inventario posteriores al ingreso.";
            }
        }

        if ($contenedorIds->isNotEmpty()) {
            $vaciados = DB::table('ordenes_vaciado')->whereIn('contenedor_id', $contenedorIds)->count();
            if ($vaciados > 0) {
                $bloqueos[] = "Tiene {$vaciados} orden(es) de vaciado asociadas.";
            }

            $gates = DB::table('gate_events')->whereIn('contenedor_id', $contenedorIds)->count();
            if ($gates > 0) {
                $bloqueos[] = "Tiene {$gates} evento(s) de gate registrados.";
            }
        }

        return $bloqueos;
    }

    public function puedeEliminarse(Ingreso $ingreso): bool
    {
        return $this->bloqueosParaEliminar($ingreso) === [];
    }

    /**
     * Elimina un ingreso completo: sus contenedores, referencias, movimientos de
     * entrada, citas asociadas y todos los archivos adjuntos.
     *
     * Deja constancia en la auditoría ANTES de borrar, para que quede rastro de
     * qué se eliminó y quién lo hizo aunque el registro ya no exista.
     *
     * @throws ValidationException si la mercancía ya se movió.
     */
    public function eliminar(Ingreso $ingreso, User $usuario): void
    {
        $bloqueos = $this->bloqueosParaEliminar($ingreso);

        if ($bloqueos !== []) {
            throw ValidationException::withMessages(['ingreso' => $bloqueos]);
        }

        DB::transaction(function () use ($ingreso, $usuario) {
            $contenedorIds = $ingreso->contenedores()->pluck('id');
            $referenciaIds = Referencia::whereIn('contenedor_id', $contenedorIds)->pluck('id');

            CambioAuditoria::create([
                'auditable_type' => $ingreso->getMorphClass(),
                'auditable_id' => $ingreso->getKey(),
                'usuario_id' => $usuario->getKey(),
                'cambios' => ['eliminado' => [
                    'anterior' => [
                        'bl' => $ingreso->bl,
                        'cliente_id' => $ingreso->cliente_id,
                        'fecha_ingreso' => $ingreso->fecha_ingreso?->toDateString(),
                        'contenedores' => $contenedorIds->count(),
                        'referencias' => $referenciaIds->count(),
                    ],
                    'nuevo' => null,
                ]],
            ]);

            // Archivos: primero los del ingreso y los de sus contenedores. Las
            // citas y sus evidencias de portería caen por cascada de FK, pero los
            // archivos en disco hay que borrarlos a mano.
            $this->borrarArchivos($ingreso);

            foreach ($ingreso->contenedores as $contenedor) {
                $this->borrarArchivos($contenedor);
            }

            foreach ($ingreso->citas()->with('registroPorteria')->get() as $cita) {
                if ($cita->registroPorteria) {
                    $this->borrarArchivos($cita->registroPorteria);
                }
            }

            MovimientoInventario::whereIn('referencia_id', $referenciaIds)->delete();
            Referencia::whereIn('id', $referenciaIds)->delete();

            // Las citas apuntan a ingreso y contenedor con cascadeOnDelete; se
            // borran explícitamente para no depender del orden de las FK.
            $ingreso->citas()->delete();

            Contenedor::whereIn('id', $contenedorIds)->delete();

            $ingreso->delete();
        });
    }

    /**
     * Borra las fotos/documentos de un modelo, en disco y en base de datos.
     */
    private function borrarArchivos(Model $modelo): void
    {
        foreach ($modelo->photos as $photo) {
            Storage::disk('public')->delete($photo->ruta);
            $photo->delete();
        }
    }
}
