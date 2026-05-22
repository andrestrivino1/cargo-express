<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Inventario</title>
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
        .filters-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filters-section h3 {
            font-size: 12px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .filters-section p {
            font-size: 10px;
            color: #555;
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
        .totals-row td {
            font-weight: bold;
            background-color: #e9ecef !important;
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-text">CARGO EXPRESS</div>
        <h1>Inventario</h1>
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if(!empty(array_filter($filtros)))
    <div class="filters-section">
        <h3>Filtros Aplicados</h3>
        <p>
            @if(!empty($filtros['cliente_id']))
                Cliente ID: {{ $filtros['cliente_id'] }} |
            @endif
            @if(!empty($filtros['codigo']))
                Código: {{ $filtros['codigo'] }} |
            @endif
            @if(!empty($filtros['modulo']))
                Módulo: {{ $filtros['modulo'] }} |
            @endif
            @if(!empty($filtros['fecha_desde']))
                Desde: {{ $filtros['fecha_desde'] }} |
            @endif
            @if(!empty($filtros['fecha_hasta']))
                Hasta: {{ $filtros['fecha_hasta'] }}
            @endif
        </p>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th>Referencia</th>
                <th>Contenedor</th>
                <th>Cliente</th>
                <th>Módulo</th>
                <th>Posición</th>
                <th>Cantidad Actual</th>
                <th>Días Almacenamiento</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalesPorCliente = [];
            @endphp

            @forelse($referencias as $ref)
                @php
                    $clienteNombre = $ref->cliente->name ?? 'N/A';
                    if (!isset($totalesPorCliente[$clienteNombre])) {
                        $totalesPorCliente[$clienteNombre] = [
                            'cantidad' => 0,
                            'referencias' => 0,
                        ];
                    }
                    $totalesPorCliente[$clienteNombre]['cantidad'] += $ref->cantidad_actual;
                    $totalesPorCliente[$clienteNombre]['referencias']++;
                @endphp
                <tr>
                    <td>{{ $ref->codigo }}</td>
                    <td>{{ $ref->contenedor->numero ?? 'N/A' }}</td>
                    <td>{{ $clienteNombre }}</td>
                    <td>{{ $ref->ubicacionPatio->modulo ?? 'Sin asignar' }}</td>
                    <td>{{ $ref->ubicacionPatio->posicion ?? 'Sin asignar' }}</td>
                    <td>{{ $ref->cantidad_actual }}</td>
                    <td>{{ $ref->dias_almacenamiento }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">No se encontraron referencias.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(count($totalesPorCliente) > 0)
    <table class="data-table" style="margin-top: 20px;">
        <thead>
            <tr>
                <th colspan="3">Totales por Cliente</th>
            </tr>
            <tr>
                <th>Cliente</th>
                <th>Referencias</th>
                <th>Cantidad Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($totalesPorCliente as $cliente => $totales)
            <tr class="totals-row">
                <td>{{ $cliente }}</td>
                <td>{{ $totales['referencias'] }}</td>
                <td>{{ $totales['cantidad'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>Cargo Express - Sistema de Trazabilidad de Carga</p>
        <p>Este documento es generado automaticamente y no requiere firma.</p>
    </div>
</body>
</html>