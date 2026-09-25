<?php

namespace App\Http\Controllers;

use App\Http\Requests\RetirarReferenciaRequest;
use App\Http\Requests\UpdateReferenciaRequest;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AlmacenamientoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
    ) {}

    public function index(Request $request)
    {
        $filtros = $request->only(['cliente_id', 'codigo', 'modulo', 'fecha_desde', 'fecha_hasta']);

        // Ver mercancía retirada se condiciona al permiso, no al parámetro: quien
        // no puede retirar tampoco ve las bajas, aunque fuerce la URL.
        $puedeRetirar = $request->user()?->can('inventario.retirar') ?? false;
        $filtros['incluir_retiradas'] = $puedeRetirar && $request->boolean('incluir_retiradas');

        // El usuario va al servicio: si es un cliente, allí se fuerza el filtro a
        // su propia mercancía sin importar el cliente_id que llegue en la URL.
        $referencias = $this->inventarioService->consultarInventario($filtros, $request->user());

        $esCliente = $request->user()?->hasRole('cliente') ?? false;

        // Un cliente no elige de qué cliente ver el inventario: solo ve el suyo.
        $clientes = $esCliente ? collect() : User::role('cliente')->orderBy('name')->get();

        $modulos = UbicacionPatio::activas()
            ->select('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo');

        return view('almacenamiento.index', compact('referencias', 'clientes', 'modulos', 'filtros', 'esCliente', 'puedeRetirar'));
    }

    public function exportExcel(Request $request)
    {
        $filtros = $request->only(['cliente_id', 'codigo', 'modulo', 'fecha_desde', 'fecha_hasta']);

        $export = $this->inventarioService->exportarInventario($filtros, $request->user());

        return Excel::download($export, 'inventario_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $filtros = $request->only(['cliente_id', 'codigo', 'modulo', 'fecha_desde', 'fecha_hasta']);

        return $this->inventarioService->exportarInventarioPdf($filtros, $request->user());
    }

    public function edit(Referencia $referencia): View
    {
        $referencia->load('cambiosAuditoria.usuario', 'contenedor', 'ubicacionPatio');
        $ubicaciones = UbicacionPatio::activas()->orderBy('modulo')->orderBy('posicion')->get();

        return view('almacenamiento.editar', compact('referencia', 'ubicaciones'));
    }

    public function update(UpdateReferenciaRequest $request, Referencia $referencia, AuditoriaService $auditoria): RedirectResponse
    {
        $referencia->fill($request->validated());
        $auditoria->registrarCambios($referencia, $request->user());
        $referencia->save();

        return redirect()->route('inventario.index')
            ->with('success', "Referencia {$referencia->codigo} actualizada correctamente.");
    }

    /**
     * Retira una referencia del inventario vigente (feature 010 / US2).
     *
     * No borra: la referencia sale de los listados, exportables y operaciones
     * nuevas, pero su historial queda intacto y consultable.
     */
    public function retirar(RetirarReferenciaRequest $request, Referencia $referencia): RedirectResponse
    {
        $codigo = $referencia->codigo;

        $this->inventarioService->retirar($referencia, $request->user());

        return redirect()->route('inventario.index')
            ->with('success', "Referencia {$codigo} retirada del inventario. Su historial se conserva.");
    }

    public function ubicar()
    {
        $referencias = Referencia::whereNull('ubicacion_patio_id')
            ->with('contenedor')
            ->orderBy('codigo')
            ->get();

        $ubicaciones = UbicacionPatio::activas()
            ->orderBy('modulo')
            ->orderBy('posicion')
            ->get();

        return view('almacenamiento.ubicar', compact('referencias', 'ubicaciones'));
    }

    public function asignarUbicacion(Request $request)
    {
        $validated = $request->validate([
            'referencia_id' => 'required|'.Referencia::REGLA_EXISTE_VIGENTE,
            'ubicacion_patio_id' => 'required|exists:ubicaciones_patio,id',
        ]);

        $referencia = Referencia::findOrFail($validated['referencia_id']);
        $ubicacion = UbicacionPatio::findOrFail($validated['ubicacion_patio_id']);

        $this->inventarioService->asignarUbicacion($referencia, $ubicacion);

        return redirect()->route('inventario.index')
            ->with('success', "Ubicación asignada a la referencia {$referencia->codigo}.");
    }
}