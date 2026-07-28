<?php

namespace App\Http\Controllers;

use App\Exceptions\IngresoDuplicadoException;
use App\Http\Requests\StoreIngresoMercanciaRequest;
use App\Http\Requests\UpdateIngresoRequest;
use App\Models\Ingreso;
use App\Models\Producto;
use App\Models\UbicacionPatio;
use App\Models\User;
use App\Services\IngresoMercanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IngresoMercanciaController extends Controller
{
    public function __construct(
        private readonly IngresoMercanciaService $ingresos,
    ) {}

    public function index(Request $request): View
    {
        $ingresos = $this->ingresos->listar($request->only('bl', 'cliente_id'));

        return view('ingreso.index', compact('ingresos'));
    }

    public function create(): View
    {
        $clientes = User::role('cliente')->orderBy('name')->get();
        $ubicaciones = UbicacionPatio::activas()->orderBy('modulo')->orderBy('posicion')->get();
        $productos = Producto::activos()->orderBy('nombre')->get();
        // Token de intento: viaja en el formulario y evita que un doble envío
        // cree dos ingresos idénticos.
        $idempotencyKey = (string) Str::uuid();

        return view('ingreso.create', compact('clientes', 'ubicaciones', 'productos', 'idempotencyKey'));
    }

    public function store(StoreIngresoMercanciaRequest $request): RedirectResponse
    {
        try {
            $ingreso = $this->ingresos->registrar(
                $request->validated(),
                [
                    'bl' => $request->file('documento_bl'),
                    'dim' => $request->file('documento_dim'),
                    'lista_empaque' => $request->file('documento_lista_empaque'),
                ],
                $request->user(),
            );
        } catch (IngresoDuplicadoException $e) {
            // Reenvío del mismo formulario: se lleva al ingreso ya creado en vez
            // de mostrar un error o duplicar el registro.
            return redirect()
                ->route('ingreso.show', $e->ingresoId())
                ->with('info', 'Este ingreso ya estaba registrado; no se creó un duplicado.');
        }

        return redirect()
            ->route('ingreso.show', $ingreso)
            ->with('success', "Ingreso registrado para el BL {$ingreso->bl}.");
    }

    public function show(Request $request, Ingreso $ingreso): View
    {
        $ingreso->load([
            'cliente',
            'documentos',
            'contenedores.referencias.ubicacionPatio',
            'contenedores.citaVigente.registroPorteria.photos',
            'contenedores.documentos', // compatibilidad: ingresos legados con docs en el contenedor
        ]);

        // Solo se calcula para quien puede eliminar: es una consulta con varios
        // conteos y no tiene sentido pagarla para el resto.
        $bloqueosEliminar = $request->user()?->can('ingreso.eliminar')
            ? $this->ingresos->bloqueosParaEliminar($ingreso)
            : [];

        return view('ingreso.show', compact('ingreso', 'bloqueosEliminar'));
    }

    public function edit(Ingreso $ingreso): View
    {
        $ingreso->load([
            'contenedores.referencias.producto',
            'contenedores.referencias.ubicacionPatio',
            'fotos',
        ]);

        $clientes = User::role('cliente')->orderBy('name')->get();
        $ubicaciones = UbicacionPatio::activas()->orderBy('modulo')->orderBy('posicion')->get();

        return view('ingreso.editar', compact('ingreso', 'clientes', 'ubicaciones'));
    }

    public function update(UpdateIngresoRequest $request, Ingreso $ingreso): RedirectResponse
    {
        $this->ingresos->actualizar(
            $ingreso,
            $request->validated(),
            $request->file('fotos', []),
            $request->validated('nueva_referencia'),
            $request->user(),
        );

        return redirect()
            ->route('ingreso.show', $ingreso)
            ->with('success', "Ingreso del BL {$ingreso->bl} actualizado.");
    }

    /**
     * Elimina un ingreso y todo lo que colgaba de él. Solo procede si la
     * mercancía sigue intacta: el servicio bloquea el borrado en cuanto algo se
     * despachó, se transfirió o se vació.
     */
    public function destroy(Request $request, Ingreso $ingreso): RedirectResponse
    {
        $bl = $ingreso->bl;

        try {
            $this->ingresos->eliminar($ingreso, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('ingreso.show', $ingreso)
                ->with('error', 'No se puede eliminar este ingreso: '
                    .implode(' ', $e->validator->errors()->get('ingreso')));
        }

        return redirect()
            ->route('ingreso.index')
            ->with('success', "Ingreso del BL {$bl} eliminado junto con sus contenedores y referencias.");
    }
}
