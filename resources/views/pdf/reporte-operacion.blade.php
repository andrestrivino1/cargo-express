<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Operación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
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
        .section-title {
            font-size: 14px;
            color: #2c3e50;
            margin-top: 20px;
            margin-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 4px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #2c3e50;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .totals-row td {
            font-weight: bold;
            background-color: #e9ecef !important;
            border-top: 2px solid #333;
        }
        .empty-message {
            text-align: center;
            color: #999;
            padding: 15px;
            font-style: italic;
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
        <h1>Reporte de Operación</h1>
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Filtros aplicados --}}
    @if(!empty(array_filter($filtros)))
    <div class="filters-section">
        <h3>Filtros Aplicados</h3>
        <p>
            @if(!empty($filtros['cliente_id']))
                Cliente ID: {{ $filtros['cliente_id'] }} |
            @endif
            @if(!empty($filtros['fecha_desde']))
                Desde: {{ \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d/m/Y') }} |
            @endif
            @if(!empty($filtros['fecha_hasta']))
                Hasta: {{ \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y') }}
            @endif
        </p>
    </div>
    @endif

    {{-- Tabla de Movimientos --}}
    <h3 class="section-title">Movimientos (Gate Events)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha/Hora</th>
                <th>Tipo</th>
                <th>Contenedor</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Estado Físico</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
            <tr>
                <td>{{ $mov->hora?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                <td>{{ $mov->tipo?->label() ?? $mov->tipo }}</td>
                <td>{{ $mov->contenedor?->numero ?? 'N/A' }}</td>
                <td>{{ $mov->contenedor?->ordenServicio?->solicitud?->cliente?->name ?? 'N/A' }}</td>
                <td>{{ $mov->usuario?->name ?? 'N/A' }}</td>
                <td>{{ $mov->estado_fisico ?? 'N/A' }}</td>
                <td>{{ $mov->notas ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-message">No se encontraron movimientos.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Tabla de Novedades --}}
    <h3 class="section-title">Novedades</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Contenedor</th>
                <th>Cliente</th>
                <th>Referencia</th>
                <th>Operador</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($novedades as $nov)
            <tr>
                <td>{{ $nov->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                <td>{{ $nov->tipo?->label() ?? $nov->tipo }}</td>
                <td>{{ $nov->ordenVaciado?->contenedor?->numero ?? 'N/A' }}</td>
                <td>{{ $nov->ordenVaciado?->contenedor?->ordenServicio?->solicitud?->cliente?->name ?? 'N/A' }}</td>
                <td>{{ $nov->referencia?->codigo ?? 'N/A' }}</td>
                <td>{{ $nov->operador?->name ?? 'N/A' }}</td>
                <td>{{ $nov->descripcion ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-message">No se encontraron novedades.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Tabla de Resumen --}}
    <h3 class="section-title">Resumen por Cliente - Días de Almacenamiento</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Total Referencias</th>
                <th>Promedio Días Almacenamiento</th>
                <th>Total Días Almacenamiento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumen as $item)
            <tr>
                <td>{{ $item['cliente_nombre'] }}</td>
                <td>{{ $item['total_referencias'] }}</td>
                <td>{{ $item['promedio_dias'] }}</td>
                <td>{{ $item['total_dias'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="empty-message">No se encontraron datos de almacenamiento.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Cargo Express - Sistema de Trazabilidad de Carga</p>
        <p>Este documento es generado automaticamente y no requiere firma.</p>
    </div>
</body>
</html>
