<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReferenciaRequest;
use App\Models\Contenedor;
use App\Models\Producto;
use App\Models\Referencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferenciaController extends Controller
{
    public function index(Contenedor $contenedor): View
    {
        $contenedor->load([
            'referencias.cliente',
            'referencias.ubicacionPatio',
            'referencias.producto',
            'ordenServicio.solicitud.cliente',
        ]);

        return view('contenedores.referencias.index', compact('contenedor'));
    }

    public function create(Contenedor $contenedor): View
    {
        $contenedor->load('ordenServicio.solicitud.cliente');

        $productos = Producto::activos()->orderBy('nombre')->get();

        return view('contenedores.referencias.create', compact('contenedor', 'productos'));
    }

    public function store(StoreReferenciaRequest $request, Contenedor $contenedor): RedirectResponse
    {
        $contenedor->load('ordenServicio.solicitud');

        $clienteId = $contenedor->ordenServicio->solicitud->cliente_id;

        foreach ($request->validated()['referencias'] as $ref) {
            Referencia::create([
                'contenedor_id' => $contenedor->id,
                'producto_id' => $ref['producto_id'],
                'cliente_id' => $clienteId,
                'codigo' => $ref['codigo'],
                'descripcion' => $ref['descripcion'] ?? null,
                'cantidad_inicial' => $ref['cantidad'],
                'cantidad_actual' => $ref['cantidad'],
                'unidad_medida' => $ref['unidad_medida'] ?? 'unidades',
                'fecha_ingreso' => now(),
            ]);
        }

        return redirect()->route('referencias.index', $contenedor)
            ->with('success', 'Referencias registradas exitosamente.');
    }
}