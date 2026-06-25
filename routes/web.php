<?php

use App\Http\Controllers\AlmacenamientoController;
use App\Http\Controllers\Auth\PrimerLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\GateInController;
use App\Http\Controllers\GateOutController;
use App\Http\Controllers\ImportacionInventarioController;
use App\Http\Controllers\IngresoMercanciaController;
use App\Http\Controllers\NovedadController;
use App\Http\Controllers\PendientesCompletarController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferenciaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SalidaMercanciaController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\StickerController;
use App\Http\Controllers\TarjaController;
use App\Http\Controllers\TransferenciaController;
use App\Http\Controllers\TrazabilidadController;
use App\Http\Controllers\UbicacionPatioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\VaciadoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Primer login forzado (no aplica el middleware primer_login a sí mismo)
Route::middleware('auth')->prefix('primer-login')->name('primer-login.')->group(function () {
    Route::get('/password', [PrimerLoginController::class, 'password'])->name('password');
    Route::post('/password', [PrimerLoginController::class, 'actualizarPassword'])->name('password.update');
    Route::get('/email', [PrimerLoginController::class, 'email'])->name('email');
    Route::post('/email', [PrimerLoginController::class, 'actualizarEmail'])->name('email.update');
});

Route::middleware(['auth', 'primer_login'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Solicitudes
    Route::prefix('solicitudes')->name('solicitudes.')->middleware(['modulo:solicitudes', 'permission:solicitudes.ver'])->group(function () {
        Route::get('/', [SolicitudController::class, 'index'])->name('index');
        Route::get('/crear', [SolicitudController::class, 'create'])->name('create')->middleware('permission:solicitudes.crear');
        Route::post('/', [SolicitudController::class, 'store'])->name('store')->middleware('permission:solicitudes.crear');
        Route::get('/{solicitud}', [SolicitudController::class, 'show'])->name('show');
        Route::get('/{solicitud}/editar', [SolicitudController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/{solicitud}', [SolicitudController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
        Route::get('/{solicitud}/asignar', [SolicitudController::class, 'asignar'])->name('asignar')->middleware('permission:solicitudes.asignar');
        Route::post('/{solicitud}/orden-servicio', [OrdenServicioController::class, 'store'])->name('orden-servicio.store')->middleware('permission:solicitudes.asignar');
        Route::get('/{solicitud}/orden-servicio/pdf', [OrdenServicioController::class, 'pdf'])->name('orden-servicio.pdf');
    });

    // Gate In
    Route::prefix('gate-in')->name('gate-in.')->middleware(['modulo:gate_in', 'permission:gate-in.ver'])->group(function () {
        Route::get('/', [GateInController::class, 'index'])->name('index');
        Route::get('/crear', [GateInController::class, 'create'])->name('create')->middleware('permission:gate-in.crear');
        Route::post('/', [GateInController::class, 'store'])->name('store')->middleware('permission:gate-in.crear');
        Route::get('/orden/{id}', [GateInController::class, 'buscarOrden'])->name('buscar-orden');
        Route::get('/{gateEvent}/pdf', [GateInController::class, 'resumenPdf'])->name('pdf');
        Route::get('/{gateEvent}/editar', [GateInController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/{gateEvent}', [GateInController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
    });

    // Ingreso de mercancía (formulario consolidado — reemplaza el flujo Solicitud/Gate-In)
    Route::prefix('ingreso')->name('ingreso.')->middleware('permission:ingreso.ver')->group(function () {
        Route::get('/', [IngresoMercanciaController::class, 'index'])->name('index');
        Route::get('/crear', [IngresoMercanciaController::class, 'create'])->name('create')->middleware('permission:ingreso.crear');
        Route::post('/', [IngresoMercanciaController::class, 'store'])->name('store')->middleware('permission:ingreso.crear');
        Route::get('/{contenedor}', [IngresoMercanciaController::class, 'show'])->name('show');
    });

    // Salida de mercancía (formulario consolidado + Orden de Salida ODC — reemplaza Entregas/Tarja)
    Route::prefix('salida')->name('salida.')->middleware('permission:salida.ver')->group(function () {
        Route::get('/', [SalidaMercanciaController::class, 'index'])->name('index');
        Route::get('/crear', [SalidaMercanciaController::class, 'create'])->name('create')->middleware('permission:salida.crear');
        Route::post('/', [SalidaMercanciaController::class, 'store'])->name('store')->middleware('permission:salida.crear');
        Route::get('/cliente/{cliente}/referencias', [SalidaMercanciaController::class, 'referenciasCliente'])->name('referencias-cliente');
        Route::get('/{tarja}', [SalidaMercanciaController::class, 'show'])->name('show');
        Route::get('/{tarja}/orden-salida.pdf', [SalidaMercanciaController::class, 'ordenSalidaPdf'])->name('orden-salida.pdf');
    });

    // Referencias (nested under contenedores)
    Route::prefix('contenedores/{contenedor}/referencias')->name('referencias.')->middleware('permission:referencias.ver')->group(function () {
        Route::get('/', [ReferenciaController::class, 'index'])->name('index');
        Route::get('/crear', [ReferenciaController::class, 'create'])->name('create')->middleware('permission:referencias.crear');
        Route::post('/', [ReferenciaController::class, 'store'])->name('store')->middleware('permission:referencias.crear');
    });

    // Sticker
    Route::get('/sticker/{referencia}', [StickerController::class, 'show'])->name('sticker.show')->middleware('permission:referencias.ver');
    Route::get('/sticker/{referencia}/print', [StickerController::class, 'print'])->name('sticker.print')->middleware('permission:referencias.ver');

    // Vaciado
    Route::prefix('vaciado')->name('vaciado.')->middleware('permission:vaciado.ver')->group(function () {
        Route::get('/', [VaciadoController::class, 'index'])->name('index');
        Route::get('/crear', [VaciadoController::class, 'create'])->name('create')->middleware('permission:vaciado.programar');
        Route::post('/', [VaciadoController::class, 'store'])->name('store')->middleware('permission:vaciado.programar');
        Route::get('/{ordenVaciado}', [VaciadoController::class, 'show'])->name('show');
        Route::get('/{ordenVaciado}/editar', [VaciadoController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/{ordenVaciado}', [VaciadoController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
        Route::post('/{ordenVaciado}/iniciar', [VaciadoController::class, 'iniciar'])->name('iniciar')->middleware('permission:vaciado.programar');
        Route::post('/{ordenVaciado}/finalizar', [VaciadoController::class, 'finalizar'])->name('finalizar')->middleware('permission:vaciado.programar');
        Route::post('/{ordenVaciado}/novedades', [NovedadController::class, 'store'])->name('novedades.store')->middleware('permission:vaciado.registrar-novedad');
        Route::get('/{ordenVaciado}/novedades/pdf', [VaciadoController::class, 'novedadesPdf'])->name('novedades.pdf');
    });

    // Productos
    Route::prefix('productos')->name('productos.')->middleware('permission:inventario.ver')->group(function () {
        Route::get('/', [ProductoController::class, 'index'])->name('index');
        Route::get('/crear', [ProductoController::class, 'create'])->name('create')->middleware('permission:inventario.ubicar');
        Route::post('/', [ProductoController::class, 'store'])->name('store')->middleware('permission:inventario.ubicar');
        Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->name('edit')->middleware('permission:inventario.ubicar');
        Route::put('/{producto}', [ProductoController::class, 'update'])->name('update')->middleware('permission:inventario.ubicar');
        Route::delete('/{producto}', [ProductoController::class, 'destroy'])->name('destroy')->middleware('permission:inventario.ubicar');
    });

    // Transferencias
    Route::prefix('transferencias')->name('transferencias.')->middleware(['modulo:transferencias', 'permission:inventario.ubicar'])->group(function () {
        Route::get('/', [TransferenciaController::class, 'index'])->name('index');
        Route::get('/entre-modulos', [TransferenciaController::class, 'createEntreModulos'])->name('entre-modulos.create');
        Route::post('/entre-modulos', [TransferenciaController::class, 'storeEntreModulos'])->name('entre-modulos.store');
        Route::get('/entre-clientes', [TransferenciaController::class, 'createEntreClientes'])->name('entre-clientes.create');
        Route::post('/entre-clientes', [TransferenciaController::class, 'storeEntreClientes'])->name('entre-clientes.store');
        Route::get('/{transferencia}', [TransferenciaController::class, 'show'])->name('show');
        Route::get('/{transferencia}/editar', [TransferenciaController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/{transferencia}', [TransferenciaController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
        Route::get('/{transferencia}/constancia', [TransferenciaController::class, 'constanciaPdf'])->name('constancia');
    });

    // Inventario / Almacenamiento
    Route::prefix('inventario')->name('inventario.')->middleware('permission:inventario.ver')->group(function () {
        Route::get('/', [AlmacenamientoController::class, 'index'])->name('index');
        Route::get('/export/excel', [AlmacenamientoController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AlmacenamientoController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/ubicar', [AlmacenamientoController::class, 'ubicar'])->name('ubicar')->middleware('permission:inventario.ubicar');
        Route::post('/ubicar', [AlmacenamientoController::class, 'asignarUbicacion'])->name('asignar-ubicacion')->middleware('permission:inventario.ubicar');
        Route::get('/{referencia}/editar', [AlmacenamientoController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/{referencia}', [AlmacenamientoController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
    });

    // Gate Out
    Route::prefix('gate-out')->name('gate-out.')->middleware(['modulo:gate_out', 'permission:gate-out.ver'])->group(function () {
        Route::get('/', [GateOutController::class, 'index'])->name('index');
        Route::get('/export/excel', [GateOutController::class, 'exportExcel'])->name('export.excel');
        Route::get('/evento/{gateEvent}/editar', [GateOutController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/evento/{gateEvent}', [GateOutController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
        Route::get('/{contenedor}', [GateOutController::class, 'show'])->name('show');
        Route::post('/{contenedor}/limpieza', [GateOutController::class, 'registrarLimpieza'])->name('limpieza')->middleware('permission:gate-out.crear');
        Route::post('/{contenedor}/salida', [GateOutController::class, 'store'])->name('store')->middleware('permission:gate-out.crear');
        Route::get('/{contenedor}/tirilla', [GateOutController::class, 'tirilla'])->name('tirilla');
    });

    // Entregas
    Route::prefix('entregas')->name('entregas.')->middleware(['modulo:entregas', 'permission:entregas.ver'])->group(function () {
        Route::get('/', [EntregaController::class, 'index'])->name('index');
        Route::get('/crear', [EntregaController::class, 'create'])->name('create')->middleware('permission:entregas.crear');
        Route::post('/', [EntregaController::class, 'store'])->name('store')->middleware('permission:entregas.crear');
        Route::get('/export/excel', [EntregaController::class, 'exportExcel'])->name('export.excel');
        Route::get('/{ordenCargue}', [EntregaController::class, 'show'])->name('show');
        Route::get('/{ordenCargue}/editar', [EntregaController::class, 'edit'])->name('editar')->middleware('role:administrador|coordinador');
        Route::put('/{ordenCargue}', [EntregaController::class, 'update'])->name('update')->middleware('role:administrador|coordinador');
        Route::post('/{ordenCargue}/tarja', [TarjaController::class, 'store'])->name('tarja.store')->middleware('permission:entregas.generar-tarja');
        Route::get('/tarjas/{tarja}', [TarjaController::class, 'show'])->name('tarja.show');
        Route::get('/tarjas/{tarja}/pdf', [TarjaController::class, 'pdf'])->name('tarja.pdf');
    });

    // Trazabilidad
    Route::prefix('trazabilidad')->name('trazabilidad.')->middleware('permission:reportes.ver')->group(function () {
        Route::get('/', [TrazabilidadController::class, 'index'])->name('index');
        Route::get('/{contenedor}', [TrazabilidadController::class, 'show'])->name('show');
        Route::get('/{contenedor}/pdf', [TrazabilidadController::class, 'historialPdf'])->name('pdf');
    });

    // Reportes
    Route::prefix('reportes')->name('reportes.')->middleware('permission:reportes.ver')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/operacion', [ReporteController::class, 'operacion'])->name('operacion');
        Route::post('/export', [ReporteController::class, 'export'])->name('export');
        Route::get('/inventario-por-cliente', [ReporteController::class, 'inventarioPorCliente'])->name('inventario-por-cliente');
        Route::get('/ingresos', [ReporteController::class, 'ingresos'])->name('ingresos');
        Route::get('/salidas', [ReporteController::class, 'salidas'])->name('salidas');
        Route::get('/movimientos', [ReporteController::class, 'movimientos'])->name('movimientos');
        Route::get('/novedades', [ReporteController::class, 'novedades'])->name('novedades');
        Route::get('/evidencias', [ReporteController::class, 'evidencias'])->name('evidencias');
    });

    // Admin
    Route::prefix('admin')->name('admin.')->middleware('role:administrador')->group(function () {
        Route::resource('ubicaciones', UbicacionPatioController::class)->except(['show']);
        Route::resource('usuarios', UserController::class)->except(['show']);
    });

    // Pendientes de completar (feature 002 — patrón "importar ahora, completar al consultar")
    Route::prefix('pendientes')->name('pendientes.')->middleware('modulo:importaciones')->group(function () {
        Route::get('/', [PendientesCompletarController::class, 'index'])->name('index');
        Route::get('/{type}/{id}/completar', [PendientesCompletarController::class, 'editar'])->name('editar');
        Route::post('/{type}/{id}/completar', [PendientesCompletarController::class, 'actualizar'])->name('actualizar');
    });

    // Importación de inventario histórico (feature 002)
    Route::prefix('admin/importaciones')->name('importaciones.')->middleware(['modulo:importaciones', 'role:administrador|coordinador'])->group(function () {
        Route::get('/', [ImportacionInventarioController::class, 'index'])->name('index');
        Route::post('/', [ImportacionInventarioController::class, 'store'])->name('store');
        Route::get('/{importacione}', [ImportacionInventarioController::class, 'show'])->name('show');
        Route::post('/{importacione}/cancelar', [ImportacionInventarioController::class, 'cancelar'])->name('cancelar');
        Route::get('/{importacione}/reporte.xlsx', [ImportacionInventarioController::class, 'descargarReporteExcel'])->name('reporte.xlsx');
        Route::get('/{importacione}/reporte.pdf', [ImportacionInventarioController::class, 'descargarReportePdf'])->name('reporte.pdf');
        Route::get('/{importacione}/errores.xlsx', [ImportacionInventarioController::class, 'descargarErroresExcel'])->name('errores');
    });

    // Manual de usuario
    Route::get('/manual/pdf', [ManualController::class, 'pdf'])->name('manual.pdf');
});

require __DIR__.'/auth.php';