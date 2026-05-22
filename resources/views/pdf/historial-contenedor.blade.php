<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de Contenedor {{ $contenedor->numero }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
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
        .info-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .info-section h3 {
            font-size: 13px;
            color: #2c3e50;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        .info-grid {
            width: 100%;
        }
        .info-grid td {
            padding: 3px 10px;
            font-size: 11px;
            vertical-align: top;
        }
        .info-grid td.label {
            font-weight: bold;
            color: #555;
            width: 180px;
        }
        .timeline-section {
            margin-bottom: 20px;
        }
        .timeline-section h3 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 4px;
        }
        .timeline-event {
            margin-bottom: 12px;
            padding: 8px 10px;
            border-left: 4px solid #6c757d;
            background-color: #fafafa;
            page-break-inside: avoid;
        }
        .timeline-event.solicitud { border-left-color: #6f42c1; }
        .timeline-event.orden_servicio { border-left-color: #0d6efd; }
        .timeline-event.gate_event { border-left-color: #198754; }
        .timeline-event.vaciado_programada { border-left-color: #0dcaf0; }
        .timeline-event.vaciado_iniciada { border-left-color: #fd7e14; }
        .timeline-event.vaciado_finalizada { border-left-color: #20c997; }
        .timeline-event.novedad { border-left-color: #dc3545; }
        .timeline-event.ubicacion { border-left-color: #ffc107; }
        .event-header {
            display: inline;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .event-date {
            font-weight: bold;
            color: #2c3e50;
        }
        .event-type {
            display: inline-block;
            padding: 1px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
            background-color: #6c757d;
            border-radius: 3px;
            margin-left: 5px;
        }
        .event-type.solicitud { background-color: #6f42c1; }
        .event-type.orden_servicio { background-color: #0d6efd; }
        .event-type.gate_event { background-color: #198754; }
        .event-type.vaciado_programada { background-color: #0dcaf0; color: #000; }
        .event-type.vaciado_iniciada { background-color: #fd7e14; }
        .event-type.vaciado_finalizada { background-color: #20c997; color: #000; }
        .event-type.novedad { background-color: #dc3545; }
        .event-type.ubicacion { background-color: #ffc107; color: #000; }
        .event-description {
            font-size: 11px;
            margin-top: 4px;
        }
        .event-user {
            font-size: 10px;
            color: #666;
            font-style: italic;
        }
        .event-details {
            font-size: 10px;
            color: #555;
            margin-top: 3px;
        }
        .event-photos {
            font-size: 10px;
            color: #0d6efd;
            margin-top: 3px;
        }
        .summary-section {
            margin-top: 20px;
            padding: 10px;
            background-color: #e9ecef;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .summary-section h3 {
            font-size: 13px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        table.summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.summary-table th {
            background-color: #2c3e50;
            color: #fff;
            padding: 6px 10px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.summary-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
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
        <h1>Historial Completo de Contenedor</h1>
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Datos del contenedor --}}
    <div class="info-section">
        <h3>Datos del Contenedor</h3>
        <table class="info-grid">
            <tr>
                <td class="label">Número de Contenedor:</td>
                <td>{{ $contenedor->numero }}</td>
                <td class="label">Estado:</td>
                <td>{{ $contenedor->estado?->value ?? $contenedor->estado }}</td>
            </tr>
            <tr>
                <td class="label">Tipo:</td>
                <td>{{ $contenedor->tipo ?? 'N/A' }}</td>
                <td class="label">Placa Vehículo:</td>
                <td>{{ $contenedor->placa_vehiculo ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Fecha Ingreso:</td>
                <td>{{ $contenedor->fecha_ingreso?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                <td class="label">Fecha Salida:</td>
                <td>{{ $contenedor->fecha_salida?->format('d/m/Y H:i') ?? 'En patio' }}</td>
            </tr>
        </table>
    </div>

    {{-- Datos del cliente y solicitud --}}
    @if($contenedor->ordenServicio?->solicitud)
    @php $solicitud = $contenedor->ordenServicio->solicitud; @endphp
    <div class="info-section">
        <h3>Datos de la Solicitud</h3>
        <table class="info-grid">
            <tr>
                <td class="label">Cliente:</td>
                <td>{{ $solicitud->cliente?->name ?? 'N/A' }}</td>
                <td class="label">Naviera:</td>
                <td>{{ $solicitud->naviera ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Puerto Origen:</td>
                <td>{{ $solicitud->puerto_origen ?? 'N/A' }}</td>
                <td class="label">Estado Solicitud:</td>
                <td>{{ $solicitud->estado?->label() ?? $solicitud->estado }}</td>
            </tr>
            <tr>
                <td class="label">Descripción:</td>
                <td colspan="3">{{ $solicitud->descripcion ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Timeline --}}
    <div class="timeline-section">
        <h3>Línea de Tiempo de Eventos</h3>

        @forelse($historial as $evento)
        <div class="timeline-event {{ $evento['tipo'] }}">
            <div class="event-header">
                <span class="event-date">
                    @if($evento['fecha'] instanceof \Carbon\Carbon)
                        {{ $evento['fecha']->format('d/m/Y H:i') }}
                    @else
                        {{ $evento['fecha'] }}
                    @endif
                </span>
                <span class="event-type {{ $evento['tipo'] }}">{{ str_replace('_', ' ', $evento['tipo']) }}</span>
            </div>
            <div class="event-description">{{ $evento['descripcion'] }}</div>
            <div class="event-user">Usuario: {{ $evento['usuario'] }}</div>

            @if(!empty($evento['detalles']))
            <div class="event-details">
                @foreach($evento['detalles'] as $clave => $valor)
                    @if($valor)
                        <strong>{{ ucfirst(str_replace('_', ' ', $clave)) }}:</strong> {{ $valor }} &nbsp;
                    @endif
                @endforeach
            </div>
            @endif

            @if($evento['fotos']->count() > 0)
            <div class="event-photos">
                Fotos adjuntas: {{ $evento['fotos']->count() }} archivo(s)
                @foreach($evento['fotos'] as $foto)
                    - {{ $foto->nombre }}
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <p style="text-align: center; color: #999;">No se encontraron eventos para este contenedor.</p>
        @endforelse
    </div>

    {{-- Resumen --}}
    <div class="summary-section">
        <h3>Resumen</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Referencias</td>
                    <td>{{ $contenedor->referencias->count() }}</td>
                </tr>
                <tr>
                    <td>Días de Almacenamiento</td>
                    <td>{{ $diasAlmacenamiento }}</td>
                </tr>
                <tr>
                    <td>Total Eventos Gate</td>
                    <td>{{ $contenedor->gateEvents->count() }}</td>
                </tr>
                <tr>
                    <td>Total Órdenes de Vaciado</td>
                    <td>{{ $contenedor->ordenesVaciado->count() }}</td>
                </tr>
                <tr>
                    <td>Total Novedades</td>
                    <td>{{ $contenedor->ordenesVaciado->sum(fn($ov) => $ov->novedades->count()) }}</td>
                </tr>
                @if($contenedor->ordenServicio?->solicitud?->documentos)
                <tr>
                    <td>Documentos Adjuntos</td>
                    <td>{{ $contenedor->ordenServicio->solicitud->documentos->count() }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Cargo Express - Sistema de Trazabilidad de Carga</p>
        <p>Este documento es generado automaticamente y no requiere firma.</p>
    </div>
</body>
</html>
