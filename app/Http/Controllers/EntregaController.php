<?php

namespace App\Http\Controllers;

use App\Exports\EntregasExport;
use App\Http\Requests\StoreOrdenCargueRequest;
use App\Http\Requests\UpdateOrdenCargueRequest;
use App\Models\OrdenCargue;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\EntregaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EntregaController extends Controller
{
    public function __construct(
        private readonly EntregaService $entregaService
    ) {}

    /**
     * Lista las órdenes de cargue con filtros y botón de exportación.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['fecha_desde', 'fecha_hasta', 'cliente_id', 'estado']);
        $ordenes = $this->entregaService->listarEntregas($filtros);
        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('entregas.index', compact('ordenes', 'filtros', 'clientes'));
    }

    /**
     * Muestra formulario para crear una orden de cargue.
     */
    public function create()
    {
        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('entregas.create', compact('clientes'));
    }

    /**
     * Almacena una nueva orden de cargue.
     */
    public function store(StoreOrdenCargueRequest $request): RedirectResponse
    {
        $orden = $this->entregaService->crearOrdenCargue($request->validated());

        return redirect()->route('entregas.show', $orden)
            ->with('success', 'Orden de cargue creada exitosamente.');
    }

    /**
     * Muestra el detalle de una orden de cargue con referencias disponibles para tarja.
     */
    public function show(OrdenCargue $ordenCargue)
    {
        $ordenCargue->load(['cliente', 'despachador', 'tarjas.detalles.referencia', 'tarjas.despachador']);

        $referencias = \App\Models\Referencia::where('cliente_id', $ordenCargue->cliente_id)
            ->where('cantidad_actual', '>', 0)
            ->with('ubicacionPatio')
            ->get();

        return view('entregas.show', compact('ordenCargue', 'referencias'));
    }

    /**
     * Formulario de edición correctiva de una orden de cargue (entrega).
     */
    public function edit(OrdenCargue $ordenCargue): View
    {
        $ordenCargue->load('cambiosAuditoria.usuario', 'cliente');
        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('entregas.editar', compact('ordenCargue', 'clientes'));
    }

    /**
     * Actualiza datos descriptivos de una entrega (sin alterar tarjas ni cantidades).
     */
    public function update(UpdateOrdenCargueRequest $request, OrdenCargue $ordenCargue, AuditoriaService $auditoria): RedirectResponse
    {
        $ordenCargue->fill($request->validated());
        $auditoria->registrarCambios($ordenCargue, $request->user());
        $ordenCargue->save();

        return redirect()->route('entregas.show', $ordenCargue)
            ->with('success', 'Entrega actualizada correctamente.');
    }

    /**
     * Exporta entregas a Excel.
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filtros = $request->only(['fecha_desde', 'fecha_hasta', 'cliente_id', 'estado']);

        return Excel::download(
            new EntregasExport($filtros),
            'entregas-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
