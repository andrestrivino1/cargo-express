<?php

namespace App\Services;

use App\Enums\ContenedorEstado;
use App\Enums\DocumentoCategoria;
use App\Models\Contenedor;
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
     * Registra un ingreso de mercancía consolidado: contenedor + referencias +
     * documentos soporte (BL, DIM, Lista de empaque) + movimientos de entrada.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $documentos  ['bl' => ..., 'dim' => ..., 'lista_empaque' => ...]
     */
    public function registrar(array $data, array $documentos, User $usuario): Contenedor
    {
        return DB::transaction(function () use ($data, $documentos, $usuario) {
            $contenedor = Contenedor::create([
                'numero' => $data['numero_contenedor'],
                'bl' => $data['bl'],
                'tipo_mercancia' => $data['tipo_mercancia'],
                'estado' => ContenedorEstado::EnPatio,
                'fecha_ingreso' => now(),
            ]);

            foreach ($data['referencias'] as $fila) {
                $referencia = Referencia::create([
                    'contenedor_id' => $contenedor->id,
                    'cliente_id' => $data['cliente_id'],
                    'codigo' => $fila['codigo'],
                    'descripcion' => $fila['descripcion'],
                    'cantidad_inicial' => $fila['cantidad'],
                    'cantidad_actual' => $fila['cantidad'],
                    'unidad_medida' => $fila['unidad_medida'],
                    'peso' => $fila['peso'],
                    'ubicacion_patio_id' => $fila['ubicacion_patio_id'],
                    'fecha_ingreso' => now(),
                ]);

                $this->movimientos->registrarEntrada(
                    $referencia,
                    (int) $fila['cantidad'],
                    $usuario,
                    $contenedor,
                );
            }

            $carpeta = "ingresos/{$contenedor->id}";
            $contenedor->guardarArchivo($documentos['bl'], $carpeta, 'documento', DocumentoCategoria::Bl->value);
            $contenedor->guardarArchivo($documentos['dim'], $carpeta, 'documento', DocumentoCategoria::Dim->value);
            $contenedor->guardarArchivo($documentos['lista_empaque'], $carpeta, 'documento', DocumentoCategoria::ListaEmpaque->value);

            return $contenedor;
        });
    }

    /**
     * Lista los ingresos (contenedores con referencias) paginados.
     */
    public function listar(array $filtros)
    {
        $query = Contenedor::query()
            ->whereNotNull('bl')
            ->with(['referencias.cliente', 'referencias.ubicacionPatio']);

        if (! empty($filtros['bl'])) {
            $query->where('bl', 'like', '%'.$filtros['bl'].'%');
        }

        if (! empty($filtros['numero'])) {
            $query->where('numero', 'like', '%'.$filtros['numero'].'%');
        }

        return $query->orderByDesc('fecha_ingreso')->paginate(15);
    }
}
