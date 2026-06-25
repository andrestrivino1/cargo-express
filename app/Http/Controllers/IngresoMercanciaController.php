<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIngresoMercanciaRequest;
use App\Models\Contenedor;
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
        $contenedores = $this->ingresos->listar($request->only('bl', 'numero'));

        return view('ingreso.index', compact('contenedores'));
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
        $contenedor = $this->ingresos->registrar(
            $request->validated(),
            [
                'bl' => $request->file('documento_bl'),
                'dim' => $request->file('documento_dim'),
                'lista_empaque' => $request->file('documento_lista_empaque'),
            ],
            $request->user(),
        );

        return redirect()
            ->route('ingreso.show', $contenedor)
            ->with('success', "Ingreso registrado para el contenedor {$contenedor->numero}.");
    }

    public function show(Contenedor $contenedor): View
    {
        $contenedor->load(['referencias.cliente', 'referencias.ubicacionPatio', 'documentos']);

        return view('ingreso.show', compact('contenedor'));
    }
}
