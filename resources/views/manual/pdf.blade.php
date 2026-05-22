<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Usuario - Cargo Express</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.5; }

        /* Cover Page */
        .cover { text-align: center; padding-top: 200px; page-break-after: always; }
        .cover h1 { font-size: 36px; color: #0d6efd; margin-bottom: 10px; }
        .cover h2 { font-size: 20px; color: #555; font-weight: normal; margin-bottom: 40px; }
        .cover .version { font-size: 14px; color: #888; margin-top: 60px; }
        .cover .logo-text { font-size: 48px; font-weight: bold; color: #0d6efd; margin-bottom: 5px; }
        .cover .logo-sub { font-size: 16px; color: #6c757d; letter-spacing: 3px; text-transform: uppercase; }

        /* TOC */
        .toc { page-break-after: always; padding: 40px; }
        .toc h2 { font-size: 22px; color: #0d6efd; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 8px; }
        .toc-item { padding: 6px 0; border-bottom: 1px dotted #ddd; display: flex; justify-content: space-between; }
        .toc-item span { font-size: 13px; }
        .toc-section { font-weight: bold; font-size: 14px; margin-top: 12px; color: #0d6efd; }

        /* Content */
        .page { padding: 30px 40px; page-break-after: always; }
        h2 { font-size: 20px; color: #0d6efd; margin-bottom: 12px; border-bottom: 2px solid #e9ecef; padding-bottom: 6px; }
        h3 { font-size: 15px; color: #333; margin: 14px 0 6px 0; }
        h4 { font-size: 13px; color: #555; margin: 10px 0 4px 0; }
        p { margin-bottom: 8px; text-align: justify; }
        ul, ol { margin: 6px 0 10px 20px; }
        li { margin-bottom: 4px; }

        .step-box { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: 10px 14px; margin: 8px 0; border-radius: 0 4px 4px 0; }
        .step-number { display: inline-block; background: #0d6efd; color: white; width: 22px; height: 22px; text-align: center; border-radius: 50%; font-weight: bold; font-size: 11px; line-height: 22px; margin-right: 6px; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 14px; margin: 8px 0; border-radius: 0 4px 4px 0; }
        .info-box { background: #d1ecf1; border-left: 4px solid #0dcaf0; padding: 10px 14px; margin: 8px 0; border-radius: 0 4px 4px 0; }
        .success-box { background: #d1e7dd; border-left: 4px solid #198754; padding: 10px 14px; margin: 8px 0; border-radius: 0 4px 4px 0; }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10px; }
        th { background: #0d6efd; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e9ecef; }
        tr:nth-child(even) { background: #f8f9fa; }

        .role-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; color: white; }
        .role-admin { background: #dc3545; }
        .role-gerente { background: #6f42c1; }
        .role-coordinador { background: #0d6efd; }
        .role-supervisor { background: #fd7e14; }
        .role-operador { background: #198754; }
        .role-portero { background: #20c997; }
        .role-despachador { background: #0dcaf0; }
        .role-cliente { background: #6c757d; }

        .flow-step { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px; margin: 6px 0; }
        .flow-arrow { text-align: center; color: #0d6efd; font-size: 18px; margin: 4px 0; }

        .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 9px; color: #999; }
        .header-bar { background: #0d6efd; color: white; padding: 8px 20px; font-size: 10px; margin: -30px -40px 20px -40px; }
    </style>
</head>
<body>

    {{-- ========== PORTADA ========== --}}
    <div class="cover">
        <div class="logo-text">CARGO EXPRESS</div>
        <div class="logo-sub">Sistema de Trazabilidad</div>
        <br><br>
        <h1>Manual de Usuario</h1>
        <h2>Guia completa de operacion del sistema</h2>
        <div class="version">
            Version 1.0<br>
            Fecha: {{ date('d/m/Y') }}<br><br>
            Sistema de Trazabilidad de Contenedores y Mercancias
        </div>
    </div>

    {{-- ========== TABLA DE CONTENIDO ========== --}}
    <div class="toc">
        <h2>Tabla de Contenido</h2>

        <div class="toc-section">1. Introduccion</div>
        <div class="toc-item"><span>1.1 Acerca del Sistema</span></div>
        <div class="toc-item"><span>1.2 Acceso al Sistema</span></div>
        <div class="toc-item"><span>1.3 Roles de Usuario</span></div>

        <div class="toc-section">2. Flujo Operativo General</div>
        <div class="toc-item"><span>2.1 Resumen del Proceso</span></div>

        <div class="toc-section">3. Modulos del Sistema</div>
        <div class="toc-item"><span>3.1 Dashboard (Panel Principal)</span></div>
        <div class="toc-item"><span>3.2 Solicitudes</span></div>
        <div class="toc-item"><span>3.3 Ingreso (Gate In)</span></div>
        <div class="toc-item"><span>3.4 Productos</span></div>
        <div class="toc-item"><span>3.5 Vaciado</span></div>
        <div class="toc-item"><span>3.6 Inventario / Almacenamiento</span></div>
        <div class="toc-item"><span>3.7 Transferencias</span></div>
        <div class="toc-item"><span>3.8 Salida (Gate Out)</span></div>
        <div class="toc-item"><span>3.9 Entregas</span></div>
        <div class="toc-item"><span>3.10 Trazabilidad</span></div>
        <div class="toc-item"><span>3.11 Reportes</span></div>

        <div class="toc-section">4. Administracion</div>
        <div class="toc-item"><span>4.1 Gestion de Usuarios</span></div>
        <div class="toc-item"><span>4.2 Gestion de Ubicaciones</span></div>

        <div class="toc-section">5. Exportaciones (PDF y Excel)</div>

        <div class="toc-section">6. Preguntas Frecuentes</div>
    </div>

    {{-- ========== 1. INTRODUCCION ========== --}}
    <div class="page">
        <h2>1. Introduccion</h2>

        <h3>1.1 Acerca del Sistema</h3>
        <p>
            Cargo Express es un sistema de trazabilidad integral disenado para gestionar el ciclo completo
            de operaciones logisticas: desde la solicitud de retiro de contenedores en puerto hasta la
            entrega final de mercancia al cliente.
        </p>
        <p>El sistema reemplaza el control manual en Excel, ofreciendo:</p>
        <ul>
            <li>Trazabilidad en tiempo real de contenedores y mercancia</li>
            <li>Control de inventario por modulos y ubicaciones</li>
            <li>Registro fotografico de cada etapa</li>
            <li>Generacion automatica de documentos (PDF/Excel)</li>
            <li>Gestion de novedades (averias, faltantes)</li>
            <li>Reportes por cliente, fecha y tipo de operacion</li>
        </ul>

        <h3>1.2 Acceso al Sistema</h3>
        <div class="step-box">
            <span class="step-number">1</span> Abra su navegador e ingrese la direccion del sistema.<br>
            <span class="step-number">2</span> Ingrese su correo electronico y contrasena.<br>
            <span class="step-number">3</span> Presione <strong>"Iniciar Sesion"</strong>.<br>
            <span class="step-number">4</span> Sera redirigido al Dashboard segun su rol.
        </div>

        <div class="warning-box">
            <strong>Importante:</strong> Si olvida su contrasena, presione "Olvidaste tu contrasena?" en la pantalla de login.
            Se le enviara un correo con instrucciones para restablecerla.
        </div>

        <h3>1.3 Roles de Usuario</h3>
        <p>Cada usuario tiene un rol que determina que puede ver y hacer en el sistema:</p>

        <table>
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Descripcion</th>
                    <th>Responsabilidades Principales</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="role-badge role-admin">Administrador</span></td>
                    <td>Administra el sistema</td>
                    <td>Gestionar usuarios, ubicaciones, acceso total</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-gerente">Gerente</span></td>
                    <td>Supervision general</td>
                    <td>Acceso a todos los modulos y reportes</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-coordinador">Coordinador</span></td>
                    <td>Planifica operaciones</td>
                    <td>Crear solicitudes, asignar vehiculos, generar ordenes de servicio</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-supervisor">Supervisor</span></td>
                    <td>Supervisa vaciado</td>
                    <td>Programar vaciados, ver inventario y reportes</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-operador">Operador</span></td>
                    <td>Ejecuta operaciones</td>
                    <td>Registrar referencias, vaciar contenedores, ubicar mercancia</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-portero">Portero</span></td>
                    <td>Control de acceso</td>
                    <td>Registrar ingresos y salidas de contenedores</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-despachador">Despachador</span></td>
                    <td>Despacha mercancia</td>
                    <td>Crear entregas, generar tarjas, transferir entre clientes</td>
                </tr>
                <tr>
                    <td><span class="role-badge role-cliente">Cliente</span></td>
                    <td>Consulta informacion</td>
                    <td>Ver su inventario, solicitar orden de cargue</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ========== 2. FLUJO OPERATIVO ========== --}}
    <div class="page">
        <h2>2. Flujo Operativo General</h2>

        <h3>2.1 Resumen del Proceso</h3>
        <p>El siguiente diagrama muestra el flujo completo de una operacion, desde que el cliente solicita el retiro hasta la entrega final:</p>

        <div class="flow-step">
            <strong>Paso 1 - Solicitud:</strong> El cliente envia solicitud por correo electronico. El coordinador la registra en el sistema con los datos del contenedor y la documentacion.
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 2 - Orden de Servicio:</strong> El coordinador asigna vehiculo, conductor y cita en puerto. Se genera la orden de servicio para el traslado.
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 3 - Ingreso al Patio (Gate In):</strong> A la llegada del contenedor, el portero registra placa, numero de contenedor, hora y toma fotos del estado. Se seleccionan los productos que vienen dentro.
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 4 - Registro de Referencias:</strong> El operador registra cada referencia (producto, codigo, cantidad). Se genera sticker de marcacion para cada una.
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 5 - Vaciado:</strong> El supervisor programa el vaciado. El operador ejecuta el descargue y registra novedades (averias, faltantes, danos).
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 6 - Almacenamiento:</strong> La mercancia se ubica en modulos del patio. Se asigna ubicacion especifica y se actualiza el inventario.
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 7 - Salida del Contenedor (Gate Out):</strong> El contenedor vacio se limpia y se registra su salida. Se genera tirilla de soporte.
        </div>
        <div class="flow-arrow">&darr;</div>

        <div class="flow-step">
            <strong>Paso 8 - Entrega al Cliente:</strong> El cliente solicita orden de cargue por correo. El despachador crea la orden, selecciona la mercancia y genera la tarja como constancia.
        </div>
    </div>

    {{-- ========== 3. MODULOS ========== --}}
    <div class="page">
        <h2>3. Modulos del Sistema</h2>

        <h3>3.1 Dashboard (Panel Principal)</h3>
        <p>Al iniciar sesion, vera el panel principal con informacion relevante segun su rol:</p>
        <ul>
            <li><strong>Administrador/Gerente:</strong> Total de contenedores, solicitudes pendientes, inventario general, contenedores por estado</li>
            <li><strong>Coordinador:</strong> Solicitudes pendientes de asignar, ordenes de servicio activas</li>
            <li><strong>Portero:</strong> Contenedores pendientes de ingreso/salida</li>
            <li><strong>Operador:</strong> Contenedores pendientes de vaciado, referencias por ubicar</li>
            <li><strong>Despachador:</strong> Entregas pendientes, inventario disponible</li>
            <li><strong>Cliente:</strong> Su inventario actual, entregas recientes</li>
        </ul>

        <h3>3.2 Solicitudes</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-coordinador">Coordinador</span> <span class="role-badge role-admin">Administrador</span></p>
        <p>Este modulo gestiona las solicitudes de retiro de contenedores en puerto.</p>

        <h4>Crear una solicitud:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Solicitudes &gt; Nueva Solicitud</strong>.<br>
            <span class="step-number">2</span> Seleccione el cliente (propietario de la mercancia).<br>
            <span class="step-number">3</span> Ingrese numero de contenedor, naviera, puerto de origen.<br>
            <span class="step-number">4</span> Adjunte documentos (BL, lista de empaque, factura).<br>
            <span class="step-number">5</span> Presione <strong>"Guardar Solicitud"</strong>.
        </div>

        <h4>Asignar orden de servicio:</h4>
        <div class="step-box">
            <span class="step-number">1</span> En la lista de solicitudes, haga clic en la solicitud pendiente.<br>
            <span class="step-number">2</span> Presione <strong>"Asignar"</strong>.<br>
            <span class="step-number">3</span> Ingrese: vehiculo (placa), conductor, fecha de cita en puerto.<br>
            <span class="step-number">4</span> Presione <strong>"Crear Orden de Servicio"</strong>.<br>
            <span class="step-number">5</span> Puede descargar la orden en PDF.
        </div>

        <h3>3.3 Ingreso (Gate In)</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-portero">Portero</span></p>
        <p>Registra la llegada de contenedores al patio.</p>

        <h4>Registrar un ingreso:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Ingreso &gt; Registrar Ingreso</strong>.<br>
            <span class="step-number">2</span> Ingrese el numero de Orden de Servicio (el sistema busca automaticamente).<br>
            <span class="step-number">3</span> Verifique la placa del vehiculo y numero del contenedor.<br>
            <span class="step-number">4</span> Seleccione los <strong>productos</strong> que vienen dentro del contenedor y sus cantidades.<br>
            <span class="step-number">5</span> Registre observaciones sobre el estado fisico del contenedor.<br>
            <span class="step-number">6</span> Adjunte fotos como evidencia.<br>
            <span class="step-number">7</span> Presione <strong>"Registrar Ingreso"</strong>.
        </div>
        <div class="info-box">
            <strong>Nota:</strong> El contenedor cambia automaticamente a estado "En Patio" y queda disponible para las siguientes operaciones.
        </div>
    </div>

    {{-- ========== 3 cont. - PRODUCTOS, VACIADO, INVENTARIO ========== --}}
    <div class="page">
        <h3>3.4 Productos</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-admin">Administrador</span> <span class="role-badge role-coordinador">Coordinador</span></p>
        <p>Catalogo maestro de productos. Estos se seleccionan al registrar el ingreso de un contenedor.</p>

        <h4>Crear un producto:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Productos</strong>.<br>
            <span class="step-number">2</span> Presione <strong>"Nuevo Producto"</strong>.<br>
            <span class="step-number">3</span> Complete: nombre, medidas, calibre, peso, tipo de empaque.<br>
            <span class="step-number">4</span> El estado por defecto es <strong>Activo</strong>.<br>
            <span class="step-number">5</span> Presione <strong>"Guardar"</strong>.
        </div>
        <div class="info-box">
            Los productos solo funcionan como catalogo. El inventario se actualiza segun las operaciones de ingreso, salida y entrega.
        </div>

        <h3>3.5 Vaciado</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-supervisor">Supervisor</span> <span class="role-badge role-operador">Operador</span></p>
        <p>Gestiona el descargue de mercancia de los contenedores.</p>

        <h4>Programar vaciado:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Vaciado &gt; Programar Vaciado</strong>.<br>
            <span class="step-number">2</span> Seleccione el contenedor (debe estar en estado "En Patio").<br>
            <span class="step-number">3</span> Asigne fecha y hora programada.<br>
            <span class="step-number">4</span> Presione <strong>"Programar"</strong>.
        </div>

        <h4>Ejecutar vaciado:</h4>
        <div class="step-box">
            <span class="step-number">1</span> En la lista de vaciados, seleccione la orden programada.<br>
            <span class="step-number">2</span> Presione <strong>"Iniciar Vaciado"</strong>.<br>
            <span class="step-number">3</span> Durante el descargue, registre novedades si las hay:<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Tipo: averia, faltante, sobrante, dano<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Descripcion detallada<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Foto como evidencia<br>
            <span class="step-number">4</span> Al terminar, presione <strong>"Finalizar Vaciado"</strong>.
        </div>
        <div class="warning-box">
            <strong>Novedades:</strong> Las novedades quedan registradas y se pueden exportar en PDF para enviar al cliente como soporte de reclamo.
        </div>

        <h3>3.6 Inventario / Almacenamiento</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-operador">Operador</span> <span class="role-badge role-supervisor">Supervisor</span> <span class="role-badge role-admin">Administrador</span></p>
        <p>Controla la ubicacion y cantidad de mercancia en el patio.</p>

        <h4>Ubicar mercancia:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Inventario &gt; Ubicar Mercancia</strong>.<br>
            <span class="step-number">2</span> Vera las referencias sin ubicacion asignada.<br>
            <span class="step-number">3</span> Para cada referencia, seleccione el modulo y posicion del patio.<br>
            <span class="step-number">4</span> Presione <strong>"Asignar Ubicacion"</strong>.
        </div>

        <h4>Consultar inventario:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Inventario</strong>.<br>
            <span class="step-number">2</span> Use los filtros: cliente, codigo, modulo, rango de fechas.<br>
            <span class="step-number">3</span> Puede exportar a <strong>Excel</strong> o <strong>PDF</strong>.
        </div>
    </div>

    {{-- ========== 3 cont. - TRANSFERENCIAS, GATE OUT ========== --}}
    <div class="page">
        <h3>3.7 Transferencias</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-operador">Operador</span> <span class="role-badge role-despachador">Despachador</span></p>
        <p>Permite mover mercancia entre modulos del patio o entre clientes.</p>

        <h4>Transferencia entre modulos:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Transferencias &gt; Entre Modulos</strong>.<br>
            <span class="step-number">2</span> Seleccione la referencia a mover.<br>
            <span class="step-number">3</span> Seleccione el modulo destino y posicion.<br>
            <span class="step-number">4</span> Indique la cantidad a transferir.<br>
            <span class="step-number">5</span> Presione <strong>"Transferir"</strong>.
        </div>
        <div class="info-box">
            La cantidad disminuye en el modulo de origen y aumenta en el modulo de destino.
        </div>

        <h4>Transferencia entre clientes:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Transferencias &gt; Entre Clientes</strong>.<br>
            <span class="step-number">2</span> Seleccione el cliente de origen y la referencia.<br>
            <span class="step-number">3</span> Seleccione el cliente de destino.<br>
            <span class="step-number">4</span> Indique la cantidad y el motivo.<br>
            <span class="step-number">5</span> Confirme la autorizacion verbal del cliente.<br>
            <span class="step-number">6</span> Presione <strong>"Transferir"</strong>.
        </div>
        <div class="warning-box">
            <strong>Importante:</strong> Las transferencias entre clientes requieren autorizacion verbal previa. El sistema genera automaticamente una <strong>constancia en PDF</strong> como soporte documental de la operacion.
        </div>

        <h3>3.8 Salida del Contenedor (Gate Out)</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-portero">Portero</span></p>
        <p>Registra la salida de contenedores vacios del patio despues del vaciado.</p>

        <h4>Registrar salida:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Salida</strong>.<br>
            <span class="step-number">2</span> En la pestana <strong>"Listos para Salida"</strong>, vera los contenedores con vaciado completado.<br>
            <span class="step-number">3</span> Seleccione el contenedor.<br>
            <span class="step-number">4</span> Registre la limpieza del contenedor (si aplica).<br>
            <span class="step-number">5</span> Indique el destino (puerto o patio de naviera).<br>
            <span class="step-number">6</span> Adjunte fotos del estado final.<br>
            <span class="step-number">7</span> Presione <strong>"Registrar Salida"</strong>.
        </div>
        <div class="success-box">
            Se genera automaticamente una <strong>tirilla de soporte</strong> que puede descargar en PDF para enviar al cliente como comprobante.
        </div>

        <h3>3.9 Entregas</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-despachador">Despachador</span></p>
        <p>Gestiona la entrega de mercancia al cliente final.</p>

        <h4>Crear orden de entrega:</h4>
        <div class="step-box">
            <span class="step-number">1</span> El cliente envia la <strong>orden de cargue</strong> por correo electronico.<br>
            <span class="step-number">2</span> Vaya al menu <strong>Entregas &gt; Nueva Entrega</strong>.<br>
            <span class="step-number">3</span> Seleccione el cliente.<br>
            <span class="step-number">4</span> Seleccione las referencias (productos) a entregar y las cantidades.<br>
            <span class="step-number">5</span> Presione <strong>"Crear Orden"</strong>.
        </div>

        <h4>Generar tarja:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Abra la orden de entrega creada.<br>
            <span class="step-number">2</span> Presione <strong>"Generar Tarja"</strong>.<br>
            <span class="step-number">3</span> La tarja indica: de donde se retiro la carga, que se entrego, cantidades y ubicaciones.<br>
            <span class="step-number">4</span> Descargue el PDF como constancia para el cliente.
        </div>
        <div class="info-box">
            <strong>El inventario se actualiza automaticamente</strong> al generar la tarja, disminuyendo las cantidades entregadas de cada modulo.
        </div>
    </div>

    {{-- ========== 3 cont. - TRAZABILIDAD, REPORTES ========== --}}
    <div class="page">
        <h3>3.10 Trazabilidad</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-gerente">Gerente</span> <span class="role-badge role-admin">Administrador</span> <span class="role-badge role-coordinador">Coordinador</span></p>
        <p>Permite rastrear el historial completo de un contenedor desde su ingreso hasta la entrega final.</p>

        <h4>Consultar trazabilidad:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Trazabilidad</strong>.<br>
            <span class="step-number">2</span> Ingrese el numero del contenedor en el buscador.<br>
            <span class="step-number">3</span> Vera la linea de tiempo completa:<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Fecha y hora de ingreso<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Quien registro el ingreso<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Referencias registradas<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Ubicaciones asignadas<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Vaciado y novedades<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Salida del contenedor<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Entregas realizadas<br>
            <span class="step-number">4</span> Puede exportar el historial completo a <strong>PDF</strong>.
        </div>

        <h3>3.11 Reportes</h3>
        <p><strong>Roles:</strong> <span class="role-badge role-gerente">Gerente</span> <span class="role-badge role-supervisor">Supervisor</span> <span class="role-badge role-admin">Administrador</span></p>
        <p>Genera reportes operativos con estadisticas y metricas del negocio.</p>

        <h4>Generar reporte operativo:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Reportes</strong>.<br>
            <span class="step-number">2</span> Seleccione filtros: cliente, rango de fechas.<br>
            <span class="step-number">3</span> El reporte muestra:<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Total de movimientos (ingresos, salidas, entregas)<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Novedades registradas<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Dias de almacenamiento por cliente (para facturacion)<br>
            &nbsp;&nbsp;&nbsp;&nbsp;- Ocupacion de modulos<br>
            <span class="step-number">4</span> Exporte a <strong>Excel</strong> o <strong>PDF</strong>.
        </div>
    </div>

    {{-- ========== 4. ADMINISTRACION ========== --}}
    <div class="page">
        <h2>4. Administracion</h2>
        <p><strong>Roles:</strong> <span class="role-badge role-admin">Administrador</span></p>

        <h3>4.1 Gestion de Usuarios</h3>
        <h4>Crear un usuario:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Admin &gt; Usuarios</strong>.<br>
            <span class="step-number">2</span> Presione <strong>"Nuevo Usuario"</strong>.<br>
            <span class="step-number">3</span> Complete: nombre, correo electronico, contrasena.<br>
            <span class="step-number">4</span> Seleccione el rol del usuario.<br>
            <span class="step-number">5</span> Presione <strong>"Guardar"</strong>.
        </div>

        <h4>Editar/Eliminar usuario:</h4>
        <div class="step-box">
            <span class="step-number">1</span> En la lista de usuarios, haga clic en <strong>"Editar"</strong> o <strong>"Eliminar"</strong>.<br>
            <span class="step-number">2</span> Modifique los datos necesarios (nombre, correo, rol, contrasena).<br>
            <span class="step-number">3</span> Presione <strong>"Actualizar"</strong>.
        </div>

        <h3>4.2 Gestion de Ubicaciones</h3>
        <p>Administra los modulos y posiciones del patio donde se almacena la mercancia.</p>

        <h4>Crear ubicacion:</h4>
        <div class="step-box">
            <span class="step-number">1</span> Vaya al menu <strong>Admin &gt; Ubicaciones</strong>.<br>
            <span class="step-number">2</span> Presione <strong>"Nueva Ubicacion"</strong>.<br>
            <span class="step-number">3</span> Ingrese: nombre del modulo, posicion, descripcion.<br>
            <span class="step-number">4</span> El estado por defecto es <strong>Disponible</strong>.<br>
            <span class="step-number">5</span> Presione <strong>"Guardar"</strong>.
        </div>

        <h2>5. Exportaciones (PDF y Excel)</h2>
        <p>El sistema permite descargar informacion en diferentes formatos para consulta, impresion y archivo:</p>

        <table>
            <thead>
                <tr>
                    <th>Modulo</th>
                    <th>PDF</th>
                    <th>Excel</th>
                    <th>Que descarga</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Solicitudes</td><td>Si</td><td>-</td><td>Orden de servicio</td></tr>
                <tr><td>Ingreso</td><td>Si</td><td>-</td><td>Resumen de ingreso con fotos</td></tr>
                <tr><td>Vaciado</td><td>Si</td><td>-</td><td>Reporte de novedades</td></tr>
                <tr><td>Inventario</td><td>Si</td><td>Si</td><td>Listado de inventario filtrado</td></tr>
                <tr><td>Transferencias</td><td>Si</td><td>-</td><td>Constancia de transferencia</td></tr>
                <tr><td>Salida</td><td>Si</td><td>Si</td><td>Tirilla de soporte / historial de salidas</td></tr>
                <tr><td>Entregas</td><td>Si</td><td>Si</td><td>Tarja de entrega / historial de despachos</td></tr>
                <tr><td>Trazabilidad</td><td>Si</td><td>-</td><td>Historial completo del contenedor</td></tr>
                <tr><td>Reportes</td><td>Si</td><td>Si</td><td>Reporte operativo</td></tr>
            </tbody>
        </table>
    </div>

    {{-- ========== 6. FAQ ========== --}}
    <div class="page">
        <h2>6. Preguntas Frecuentes</h2>

        <h4>No puedo ver un modulo del menu</h4>
        <p>Su rol de usuario no tiene permiso para acceder a ese modulo. Contacte al administrador para verificar sus permisos.</p>

        <h4>El numero de Orden de Servicio no se encuentra al registrar ingreso</h4>
        <p>Verifique que la solicitud haya sido asignada y que se haya generado la orden de servicio. El coordinador debe completar la asignacion antes del ingreso.</p>

        <h4>No puedo ubicar mercancia</h4>
        <p>Verifique que existan ubicaciones disponibles en el sistema (Admin &gt; Ubicaciones). El administrador debe crear modulos y posiciones antes de poder asignar mercancia.</p>

        <h4>La cantidad del inventario no coincide</h4>
        <p>Revise en Trazabilidad el historial completo del contenedor. Verifique si hubo novedades (faltantes) durante el vaciado o transferencias entre modulos/clientes.</p>

        <h4>Como solicito la entrega de mi mercancia? (Cliente)</h4>
        <p>Envie la orden de cargue por correo electronico al area de despacho. El despachador registrara la orden en el sistema y generara la tarja con los detalles de la entrega.</p>

        <h4>Como veo el estado de mi contenedor? (Cliente)</h4>
        <p>Ingrese al sistema con sus credenciales. En el Dashboard vera el resumen de su inventario y entregas recientes.</p>

        <h4>Olvide mi contrasena</h4>
        <p>En la pantalla de login presione "Olvidaste tu contrasena?" e ingrese su correo. Recibira un enlace para restablecerla.</p>

        <h4>Como genero una constancia de transferencia entre clientes?</h4>
        <p>Al completar la transferencia, el sistema genera automaticamente la constancia. Tambien puede descargarla despues desde Transferencias &gt; Ver Detalle &gt; Descargar Constancia.</p>

        <br>
        <div class="info-box" style="text-align: center;">
            <strong>Soporte Tecnico</strong><br>
            Para asistencia tecnica contacte al administrador del sistema.
        </div>

        <br><br>
        <p style="text-align: center; color: #999; font-size: 10px;">
            Manual de Usuario - Cargo Express v1.0 - {{ date('d/m/Y') }}<br>
            Sistema de Trazabilidad de Contenedores y Mercancias
        </p>
    </div>

</body>
</html>