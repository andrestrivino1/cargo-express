<?php

namespace App\Http\Controllers;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenVaciadoEstado;
use App\Enums\SolicitudEstado;
use App\Models\Contenedor;
use App\Models\OrdenCargue;
use App\Models\OrdenVaciado;
use App\Models\Referencia;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $data = [];

        // Cliente: su inventario activo y sus entregas
        if ($user->hasRole('cliente')) {
            $data['clienteReferenciasCount'] = Referencia::where('cliente_id', $user->id)
                ->where('cantidad_actual', '>', 0)
                ->count();

            $data['clienteEntregasCount'] = OrdenCargue::where('cliente_id', $user->id)->count();
        }

        // Portero: contenedores pendientes ingreso y pendientes salida
        if ($user->hasAnyRole(['portero', 'supervisor', 'gerente', 'administrador'])) {
            $data['porteroIngresosCount'] = Contenedor::where('estado', ContenedorEstado::Solicitado)->count();
            $data['porteroSalidasCount'] = Contenedor::where('estado', ContenedorEstado::VaciadoCompletado)->count();
        }

        // Coordinador: solicitudes pendientes
        if ($user->hasAnyRole(['coordinador', 'supervisor', 'gerente', 'administrador'])) {
            $data['coordinadorSolicitudesCount'] = Solicitud::where('estado', SolicitudEstado::Pendiente)->count();
        }

        // Operador: vaciados en proceso y referencias sin ubicar
        if ($user->hasAnyRole(['operador', 'supervisor', 'gerente', 'administrador'])) {
            $data['operadorVaciadosCount'] = OrdenVaciado::where('estado', OrdenVaciadoEstado::EnProceso)->count();
            $data['operadorSinUbicarCount'] = Referencia::whereNull('ubicacion_patio_id')
                ->where('cantidad_actual', '>', 0)
                ->count();
        }

        return view('dashboard', $data);
    }
}
