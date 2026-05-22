<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen de Ingreso</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 22px;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10px;
            color: #666;
        }
        .logo-text {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            font-size: 14px;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            width: 100%;
        }
        .info-grid td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .info-grid .label {
            font-weight: bold;
            color: #555;
            width: 180px;
        }
        .info-grid .value {
            color: #1a1a1a;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th {
            background-color: #2c3e50;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        table.data-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .observaciones {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-text">CARGO EXPRESS</div>
        <h1>Resumen de Ingreso</h1>
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info-section">
        <h3>Datos del Ingreso</h3>
        <table class="info-grid">
            <tr>
                <td class="label">No. Contenedor:</td>
                <td class="value">{{ $gateEvent->contenedor->numero }}</td>
            </tr>
            <tr>
                <td class="label">Placa del Vehiculo:</td>
                <td class="value">{{ $gateEvent->contenedor->placa_vehiculo }}</td>
            </tr>
            <tr>
                <td class="label">Portero:</td>
                <td class="value">{{ $gateEvent->usuario->name }}</td>
            </tr>
            <tr>
                <td class="label">Fecha / Hora de Ingreso:</td>
                <td class="value">{{ $gateEvent->hora->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="label">Cliente:</td>
                <td class="value">{{ $gateEvent->contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    @if($gateEvent->estado_fisico)
    <div class="info-section">
        <h3>Estado Fisico</h3>
        <div class="observaciones">{{ $gateEvent->estado_fisico }}</div>
    </div>
    @endif

    @if($gateEvent->notas)
    <div class="info-section">
        <h3>Notas</h3>
        <div class="observaciones">{{ $gateEvent->notas }}</div>
    </div>
    @endif

    <div class="info-section">
        <h3>Referencias Registradas</h3>
        @if($gateEvent->contenedor->referencias->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Codigo</th>
                    <th>Descripcion</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gateEvent->contenedor->referencias as $index => $referencia)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $referencia->codigo }}</td>
                    <td>{{ $referencia->descripcion ?? '-' }}</td>
                    <td>{{ $referencia->cantidad_inicial }}</td>
                    <td>{{ $referencia->unidad_medida ?? 'unidades' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; font-style: italic;">No se han registrado referencias aun.</p>
        @endif
    </div>

    <div class="footer">
        <p>Cargo Express - Sistema de Trazabilidad de Carga</p>
        <p>Este documento es generado automaticamente y no requiere firma.</p>
    </div>
</body>
</html>