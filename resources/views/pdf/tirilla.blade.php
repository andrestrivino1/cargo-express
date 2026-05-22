<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tirilla Salida - {{ $contenedor->numero }}</title>
    <style>
        @page {
            margin: 5mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            width: 100%;
        }
        .receipt {
            width: 100%;
            text-align: center;
        }
        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 8pt;
            margin-bottom: 10pt;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1pt;
        }
        .receipt-title {
            font-size: 11pt;
            font-weight: bold;
            color: #555;
            margin-top: 4pt;
        }
        .divider {
            border-top: 1px dashed #999;
            margin: 8pt 0;
        }
        .field {
            text-align: left;
            margin-bottom: 6pt;
            line-height: 1.4;
        }
        .field-label {
            font-size: 7pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        .field-value {
            font-size: 11pt;
            font-weight: bold;
            color: #1a1a1a;
        }
        .field-value.large {
            font-size: 14pt;
        }
        .barcode-section {
            margin: 12pt 0;
            text-align: center;
        }
        .barcode-section img {
            max-width: 100%;
            height: 40pt;
        }
        .barcode-text {
            font-size: 9pt;
            font-family: 'Courier New', monospace;
            margin-top: 3pt;
            letter-spacing: 2pt;
        }
        .status-badge {
            display: inline-block;
            padding: 3pt 8pt;
            border-radius: 3pt;
            font-size: 9pt;
            font-weight: bold;
            color: #fff;
        }
        .status-clean {
            background-color: #28a745;
        }
        .status-dirty {
            background-color: #dc3545;
        }
        .footer {
            margin-top: 12pt;
            border-top: 2px solid #2c3e50;
            padding-top: 6pt;
            font-size: 7pt;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="company-name">Cargo Express</div>
            <div class="receipt-title">Tirilla de Soporte - Salida</div>
        </div>

        <!-- Contenedor Number -->
        <div class="field" style="text-align: center;">
            <div class="field-label">Contenedor</div>
            <div class="field-value large">{{ $contenedor->numero }}</div>
        </div>

        <div class="divider"></div>

        <!-- Details -->
        <div class="field">
            <div class="field-label">Placa del Vehículo</div>
            <div class="field-value">{{ $contenedor->placa_vehiculo ?? 'N/A' }}</div>
        </div>

        <div class="field">
            <div class="field-label">Cliente</div>
            <div class="field-value">{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</div>
        </div>

        <div class="field">
            <div class="field-label">Fecha de Ingreso</div>
            <div class="field-value">{{ $contenedor->fecha_ingreso ? $contenedor->fecha_ingreso->format('d/m/Y H:i') : 'N/A' }}</div>
        </div>

        <div class="field">
            <div class="field-label">Fecha de Salida</div>
            <div class="field-value">{{ $contenedor->fecha_salida ? $contenedor->fecha_salida->format('d/m/Y H:i') : 'N/A' }}</div>
        </div>

        <div class="field">
            <div class="field-label">Destino</div>
            <div class="field-value">{{ $contenedor->destino_salida ?? 'N/A' }}</div>
        </div>

        <div class="field">
            <div class="field-label">Estado de Limpieza</div>
            <div>
                @if($contenedor->limpieza_registrada)
                    <span class="status-badge status-clean">LIMPIO</span>
                @else
                    <span class="status-badge status-dirty">SIN LIMPIEZA</span>
                @endif
            </div>
        </div>

        <div class="divider"></div>

        <!-- Barcode -->
        <div class="barcode-section">
            <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode">
            <div class="barcode-text">{{ $contenedor->numero }}</div>
        </div>

        <!-- Portero -->
        @php
            $gateOutEvent = $contenedor->gateEvents->where('tipo', \App\Enums\GateEventTipo::GateOut)->first();
        @endphp
        @if($gateOutEvent)
        <div class="divider"></div>
        <div class="field">
            <div class="field-label">Registrado por</div>
            <div class="field-value">{{ $gateOutEvent->usuario->name ?? 'N/A' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Hora de Registro</div>
            <div class="field-value">{{ $gateOutEvent->hora->format('d/m/Y H:i') }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Documento generado el {{ now()->format('d/m/Y H:i') }}<br>
            Cargo Express - Sistema de Trazabilidad
        </div>
    </div>
</body>
</html>