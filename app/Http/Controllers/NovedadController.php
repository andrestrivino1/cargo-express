<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNovedadRequest;
use App\Models\OrdenVaciado;
use App\Services\VaciadoService;
use Illuminate\Http\RedirectResponse;

class NovedadController extends Controller
{
    public function __construct(
        private readonly VaciadoService $vaciadoService
    ) {}

    public function store(StoreNovedadRequest $request, OrdenVaciado $ordenVaciado): RedirectResponse
    {
        $this->vaciadoService->registrarNovedad(
            $ordenVaciado,
            $request->validated(),
            $request->user()
        );

        return redirect()->route('vaciado.show', $ordenVaciado)
            ->with('success', 'Novedad registrada exitosamente.');
    }
}