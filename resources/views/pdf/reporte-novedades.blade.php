<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Novedades</title>
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
        .badge-averia {
            background-color: #dc3545;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-faltante {
            background-color: #ffc107;
            color: #333;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-dano_visible {
            background-color: #0dcaf0;
            color: #333;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .photo-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .photo-section h4 {
            font-size: 12px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .photo-item {
            margin-bottom: 10px;
        }
        .photo-item img {
            max-width: 300px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .photo-item p {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
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
        <h1>Reporte de Novedades</h1>
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info-section">
        <h3>Datos del Vaciado</h3>
        <table class="info-grid">
            <tr>
                <td class="label">No. Contenedor:</td>
                <td class="value">{{ $ordenVaciado->contenedor->numero }}</td>
            </tr>
            <tr>
                <td class="label">Cliente:</td>
                <td class="value">{{ $ordenVaciado->contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Supervisor:</td>
                <td class="value">{{ $ordenVaciado->supervisor->name }}</td>
            </tr>
            <tr>
                <td class="label">Fecha Programada:</td>
                <td class="value">{{ $ordenVaciado->fecha_programada->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Fecha Inicio:</td>
                <td class="value">{{ $ordenVaciado->fecha_inicio ? $ordenVaciado->fecha_inicio->format('d/m/Y H:i:s') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Fecha Fin:</td>
                <td class="value">{{ $ordenVaciado->fecha_fin ? $ordenVaciado->fecha_fin->format('d/m/Y H:i:s') : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <h3>Novedades Registradas ({{ $ordenVaciado->novedades->count() }})</h3>
        @if($ordenVaciado->novedades->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Descripcion</th>
                    <th>Referencia Afectada</th>
                    <th>Operador</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordenVaciado->novedades as $index => $novedad)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <span class="badge-{{ $novedad->tipo->value }}">{{ $novedad->tipo->label() }}</span>
                    </td>
                    <td>{{ $novedad->descripcion }}</td>
                    <td>{{ $novedad->referencia ? $novedad->referencia->codigo : '-' }}</td>
                    <td>{{ $novedad->operador->name }}</td>
                    <td>{{ $novedad->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; font-style: italic;">No se han registrado novedades.</p>
        @endif
    </div>

    {{-- Photos Section --}}
    @foreach($ordenVaciado->novedades as $index => $novedad)
        @if($novedad->photos->count() > 0)
        <div class="photo-section">
            <h3>Fotos - Novedad #{{ $index + 1 }}: {{ $novedad->tipo->label() }}</h3>
            @foreach($novedad->photos as $photo)
            <div class="photo-item">
                <img src="{{ storage_path('app/public/' . $photo->ruta) }}" alt="{{ $photo->nombre }}">
                <p>{{ $photo->nombre }}</p>
            </div>
            @endforeach
        </div>
        @endif
    @endforeach

    <div class="footer">
        <p>Cargo Express - Sistema de Trazabilidad de Carga</p>
        <p>Este documento es generado automaticamente y no requiere firma.</p>
    </div>
</body>
</html>