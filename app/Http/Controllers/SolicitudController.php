<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolicitudRequest;
use App\Http\Requests\UpdateSolicitudRequest;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\SolicitudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SolicitudController extends Controller
{
    public function __construct(
        private readonly SolicitudService $solicitudService
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasRole('cliente')) {
            $solicitudes = $this->solicitudService->listarPorCliente($user);
        } else {
            $solicitudes = $this->solicitudService->listarTodas();
        }

        return view('solicitudes.index', compact('solicitudes'));
    }

    public function create(): View
    {
        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('solicitudes.create', compact('clientes'));
    }

    public function store(StoreSolicitudRequest $request): RedirectResponse
    {
        $this->solicitudService->crear($request->validated());

        return redirect()->route('solicitudes.index')
            ->with('success', 'Solicitud creada exitosamente.');
    }

    public function show(Solicitud $solicitud): View
    {
        $solicitud->load(['cliente', 'documentos', 'ordenServicio.coordinador']);

        return view('solicitudes.show', compact('solicitud'));
    }

    public function asignar(Solicitud $solicitud): View
    {
        $solicitud->load('cliente');

        return view('solicitudes.asignar', compact('solicitud'));
    }

    public function edit(Solicitud $solicitud): View
    {
        $clientes = User::role('cliente')->orderBy('name')->get();
        $solicitud->load('cambiosAuditoria.usuario');

        return view('solicitudes.editar', compact('solicitud', 'clientes'));
    }

    public function update(UpdateSolicitudRequest $request, Solicitud $solicitud, AuditoriaService $auditoria): RedirectResponse
    {
        $solicitud->fill($request->validated());
        $auditoria->registrarCambios($solicitud, $request->user());
        $solicitud->save();

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Solicitud actualizada correctamente.');
    }
}