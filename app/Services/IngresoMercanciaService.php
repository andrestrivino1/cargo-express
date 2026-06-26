<?php

namespace App\Services;

use App\Enums\ContenedorEstado;
use App\Enums\DocumentoCategoria;
use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\Referencia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class IngresoMercanciaService
{
    public function __construct(
        private readonly MovimientoInventarioService $movimientos,
    ) {}

    /**
     * Registra un ingreso: un BL (Ingreso padre) con sus documentos y fecha, que
     * agrupa uno o varios contenedores, cada uno con sus referencias. La fecha de
     * ingreso (posiblemente retroactiva) se propaga a contenedores y referencias.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $documentos  ['bl' => ..., 'dim' => ..., 'lista_empaque' => ...]
     */
    public function registrar(array $data, array $documentos, User $usuario): Ingreso
    {
        return DB::transaction(function () use ($data, $documentos, $usuario) {
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
}
