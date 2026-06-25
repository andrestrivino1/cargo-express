<?php

namespace App\Services;

use App\Enums\ContenedorEstado;
use App\Enums\DocumentoCategoria;
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
                    $referencia = Referencia::create([
                        'contenedor_id' => $contenedor->id,
                        'cliente_id' => $data['cliente_id'],
                        'codigo' => $filaReferencia['codigo'],
                        'descripcion' => $filaReferencia['descripcion'],
                        'cantidad_inicial' => $filaReferencia['cantidad'],
                        'cantidad_actual' => $filaReferencia['cantidad'],
                        'unidad_medida' => $filaReferencia['unidad_medida'],
                        'peso' => $filaReferencia['peso'] ?? null,
                        'ubicacion_patio_id' => $filaReferencia['ubicacion_patio_id'] ?? null,
                        'fecha_ingreso' => $fecha,
                    ]);

                    $this->movimientos->registrarEntrada(
                        $referencia,
                        (int) $filaReferencia['cantidad'],
                        $usuario,
                        $ingreso,
                    );
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
