<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTransferenciaRequest;
use App\Models\Referencia;
use App\Models\Transferencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\TransferenciaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferenciaController extends Controller
{
    public function __construct(
        protected TransferenciaService $service
    ) {}

    /**
     * Listado de transferencias con filtros.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['tipo', 'fecha_desde', 'fecha_hasta', 'cliente_id']);
        $transferencias = $this->service->listarTransferencias($filtros);

        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('transferencias.index', compact('transferencias', 'clientes', 'filtros'));
    }

    /**
     * Formulario para transferencia entre módulos.
     */
    public function createEntreModulos()
    {
        $referencias = Referencia::with(['producto', 'cliente', 'ubicacionPatio', 'contenedor'])
            ->whereNotNull('ubicacion_patio_id')
            ->where('cantidad_actual', '>', 0)
            ->get();

        $ubicaciones = UbicacionPatio::activas()->orderBy('modulo')->orderBy('posicion')->get();

        return view('transferencias.entre-modulos', compact('referencias', 'ubicaciones'));
    }

    /**
     * Formulario para transferencia entre clientes.
     */
    public function createEntreClientes()
    {
        $referencias = Referencia::with(['producto', 'cliente', 'ubicacionPatio', 'contenedor'])
            ->whereNotNull('ubicacion_patio_id')
            ->where('cantidad_actual', '>', 0)
            ->get();

        $clientes = User::role('cliente')->orderBy('name')->get();
        $ubicaciones = UbicacionPatio::activas()->orderBy('modulo')->orderBy('posicion')->get();

        return view('transferencias.entre-clientes', compact('referencias', 'clientes', 'ubicaciones'));
    }

    /**
     * Procesar transferencia entre módulos.
     */
    public function storeEntreModulos(Request $request)
    {
        $validated = $request->validate([
            'referencia_id' => 'required|exists:referencias,id',
            'ubicacion_destino_id' => 'required|exists:ubicaciones_patio,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $referencia = Referencia::findOrFail($validated['referencia_id']);

        if ($validated['cantidad'] > $referencia->cantidad_actual) {
            return back()->withErrors(['cantidad' => 'La cantidad excede la disponible (' . $referencia->cantidad_actual . ').'])->withInput();
        }

        if ($validated['ubicacion_destino_id'] == $referencia->ubicacion_patio_id) {
            return back()->withErrors(['ubicacion_destino_id' => 'La ubicación destino debe ser diferente a la ubicación actual.'])->withInput();
        }

        try {
            $this->service->transferirEntreModulos($validated, $request->user());
            return redirect()->route('transferencias.index')->with('success', 'Transferencia entre módulos realizada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Procesar transferencia entre clientes.
     */
    public function storeEntreClientes(Request $request)
    {
        $validated = $request->validate([
            'referencia_id' => 'required|exists:referencias,id',
            'cliente_destino_id' => 'required|exists:users,id',
            'ubicacion_destino_id' => 'required|exists:ubicaciones_patio,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string',
            'autorizacion_cliente' => 'required|string|max:255',
        ]);

        $referencia = Referencia::findOrFail($validated['referencia_id']);

        if ($validated['cantidad'] > $referencia->cantidad_actual) {
            return back()->withErrors(['cantidad' => 'La cantidad excede la disponible (' . $referencia->cantidad_actual . ').'])->withInput();
        }

        if ($validated['cliente_destino_id'] == $referencia->cliente_id) {
            return back()->withErrors(['cliente_destino_id' => 'El cliente destino debe ser diferente al cliente actual.'])->withInput();
        }

        try {
            $this->service->transferirEntreClientes($validated, $request->user());
            return redirect()->route('transferencias.index')->with('success', 'Transferencia entre clientes realizada exitosamente. Se ha generado la constancia.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Detalle de una transferencia.
     */
    public function show(Transferencia $transferencia)
    {
        $transferencia->load([
            'usuario',
            'referenciaOrigen.producto',
            'referenciaOrigen.contenedor',
            'referenciaOrigen.cliente',
            'referenciaDestino.producto',
            'referenciaDestino.contenedor',
            'referenciaDestino.cliente',
            'ubicacionOrigen',
            'ubicacionDestino',
            'clienteOrigen',
            'clienteDestino',
        ]);

        return view('transferencias.show', compact('transferencia'));
    }

    /**
     * Formulario de edición correctiva de una transferencia.
     */
    public function edit(Transferencia $transferencia): View
    {
        $transferencia->load('cambiosAuditoria.usuario', 'referenciaOrigen', 'clienteOrigen', 'clienteDestino');

        return view('transferencias.editar', compact('transferencia'));
    }

    /**
     * Actualiza datos descriptivos de una transferencia (sin revertir cantidades).
     */
    public function update(UpdateTransferenciaRequest $request, Transferencia $transferencia, AuditoriaService $auditoria): RedirectResponse
    {
        $transferencia->fill($request->validated());
        $auditoria->registrarCambios($transferencia, $request->user());
        $transferencia->save();

        return redirect()->route('transferencias.show', $transferencia)
            ->with('success', 'Transferencia actualizada correctamente.');
    }

    /**
     * Generar constancia PDF para transferencias entre clientes.
     */
    public function constanciaPdf(Transferencia $transferencia)
    {
        if ($transferencia->tipo !== 'entre_clientes') {
            return back()->with('error', 'Solo se generan constancias para transferencias entre clientes.');
        }

        $transferencia->load([
            'usuario',
            'referenciaOrigen.producto',
            'referenciaOrigen.contenedor',
            'referenciaOrigen.cliente',
            'referenciaDestino.producto',
            'referenciaDestino.contenedor',
            'referenciaDestino.cliente',
            'ubicacionOrigen',
            'ubicacionDestino',
            'clienteOrigen',
            'clienteDestino',
        ]);

        $pdf = Pdf::loadView('pdf.constancia-transferencia', compact('transferencia'));

        return $pdf->download('constancia-transferencia-' . $transferencia->id . '.pdf');
    }
}
