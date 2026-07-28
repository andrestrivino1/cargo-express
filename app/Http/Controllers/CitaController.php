<?php

namespace App\Http\Controllers;

use App\Enums\CitaEstado;
use App\Enums\TamanoContenedor;
use App\Enums\TipoContenedor;
use App\Http\Requests\StoreCitaRequest;
use App\Http\Requests\UpdateCitaRequest;
use App\Models\Cita;
use App\Models\Ingreso;
use App\Services\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitaController extends Controller
{
    public function __construct(
        private readonly CitaService $citas,
    ) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['fecha_esperada', 'bl', 'numero_contenedor', 'placa', 'estado']);

        return view('citas.index', [
            'citas' => $this->citas->listar($filtros),
            'filtros' => $filtros,
            'estados' => CitaEstado::cases(),
        ]);
    }

    public function create(): View
    {
        return view('citas.create', $this->datosFormulario());
    }

    public function store(StoreCitaRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Se consulta ANTES de crear: si no, la cita recién creada se contaría a
        // sí misma como duplicada.
        $duplicada = $this->citas->tieneCitaProgramada((int) $datos['contenedor_id']);

        $cita = $this->citas->crear($datos, $request->user());

        $respuesta = redirect()->route('citas.show', $cita)
            ->with('success', "Cita agendada para el contenedor {$cita->numero_contenedor}.");

        // Se advierte sin bloquear: un contenedor puede volver legítimamente.
        if ($duplicada) {
            $respuesta->with('warning', 'Este contenedor ya tenía otra cita programada. Verifica que no sea un duplicado.');
        }

        return $respuesta;
    }

    public function show(Cita $cita): View
    {
        $cita->load(['ingreso.cliente', 'contenedor', 'creador', 'editor', 'registroPorteria.portero', 'registroPorteria.photos']);

        return view('citas.show', compact('cita'));
    }

    public function edit(Cita $cita): View
    {
        abort_unless($cita->puedeEditarse(), 403, 'Una cita atendida o cancelada no se puede editar.');

        return view('citas.editar', $this->datosFormulario() + [
            'cita' => $cita->load('ingreso', 'contenedor'),
        ]);
    }

    public function update(UpdateCitaRequest $request, Cita $cita): RedirectResponse
    {
        $this->citas->actualizar($cita, $request->validated(), $request->user());

        return redirect()->route('citas.show', $cita)
            ->with('success', 'Cita actualizada correctamente.');
    }

    public function cancelar(Request $request, Cita $cita): RedirectResponse
    {
        abort_unless($request->user()->can('citas.editar'), 403);

        $this->citas->cancelar($cita, $request->user());

        return redirect()->route('citas.index')
            ->with('success', "Cita del contenedor {$cita->numero_contenedor} cancelada.");
    }

    /**
     * Alimenta el selector dependiente de contenedores del formulario.
     */
    public function contenedoresDeIngreso(Ingreso $ingreso): JsonResponse
    {
        $contenedores = $ingreso->contenedores()
            ->orderBy('numero')
            ->get()
            ->map(fn ($contenedor) => [
                'id' => $contenedor->id,
                'numero' => $contenedor->numero,
                'tipo_mercancia' => $contenedor->tipo_mercancia,
                'tiene_cita_programada' => $this->citas->tieneCitaProgramada($contenedor->id),
            ]);

        return response()->json($contenedores);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        return [
            'ingresos' => Ingreso::with('cliente')->orderByDesc('fecha_ingreso')->orderByDesc('id')->limit(200)->get(),
            'tipos' => TipoContenedor::opciones(),
            'tamanos' => TamanoContenedor::opciones(),
        ];
    }
}
