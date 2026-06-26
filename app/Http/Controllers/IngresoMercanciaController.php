<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIngresoMercanciaRequest;
use App\Http\Requests\UpdateIngresoRequest;
use App\Models\Ingreso;
use App\Models\Producto;
use App\Models\UbicacionPatio;
use App\Models\User;
use App\Services\IngresoMercanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('ingreso.create', compact('clientes', 'ubicaciones', 'productos'));
    }

    public function store(StoreIngresoMercanciaRequest $request): RedirectResponse
    {
        $ingreso = $this->ingresos->registrar(
            $request->validated(),
            [
                'bl' => $request->file('documento_bl'),
                'dim' => $request->file('documento_dim'),
                'lista_empaque' => $request->file('documento_lista_empaque'),
            ],
            $request->user(),
        );

        return redirect()
            ->route('ingreso.show', $ingreso)
            ->with('success', "Ingreso registrado para el BL {$ingreso->bl}.");
    }

    public function show(Ingreso $ingreso): View
    {
        $ingreso->load([
            'cliente',
            'documentos',
            'contenedores.referencias.ubicacionPatio',
            'contenedores.documentos', // compatibilidad: ingresos legados con docs en el contenedor
        ]);

        return view('ingreso.show', compact('ingreso'));
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
}
