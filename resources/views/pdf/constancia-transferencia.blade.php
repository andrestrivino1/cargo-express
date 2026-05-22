<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Transferencia #{{ $transferencia->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .header .empresa {
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            border-left: 4px solid #333;
            margin-bottom: 10px;
        }
        .section-body {
            padding: 0 12px;
        }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .label {
            display: table-cell;
            width: 40%;
            font-weight: bold;
            padding: 3px 0;
        }
        .value {
            display: table-cell;
            width: 60%;
            padding: 3px 0;
        }
        .two-columns {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        .column:last-child {
            padding-right: 0;
            padding-left: 15px;
        }
        .firmas {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .firma {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 10px;
        }
        .firma-linea {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .highlight {
            background-color: #fffde7;
            padding: 8px 12px;
            border: 1px solid #fdd835;
            border-radius: 4px;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="empresa">Cargo Express</div>
        <h1>Constancia de Transferencia de Mercancia</h1>
    </div>

    <!-- Seccion 1: Datos de la transferencia -->
    <div class="section">
        <div class="section-title">1. Datos de la Transferencia</div>
        <div class="section-body">
            <div class="row">
                <span class="label">No. de Transferencia:</span>
                <span class="value">#{{ $transferencia->id }}</span>
            </div>
            <div class="row">
                <span class="label">Fecha:</span>
                <span class="value">{{ $transferencia->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Realizada por:</span>
                <span class="value">{{ $transferencia->usuario->name ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Seccion 2: Producto transferido -->
    <div class="section">
        <div class="section-title">2. Producto Transferido</div>
        <div class="section-body">
            <div class="row">
                <span class="label">Producto:</span>
                <span class="value">{{ $transferencia->referenciaOrigen->producto->nombre ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Codigo Referencia:</span>
                <span class="value">{{ $transferencia->referenciaOrigen->codigo ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Cantidad:</span>
                <span class="value">{{ $transferencia->cantidad }}</span>
            </div>
            <div class="row">
                <span class="label">Medidas:</span>
                <span class="value">{{ $transferencia->referenciaOrigen->producto->medidas ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Empaque:</span>
                <span class="value">{{ $transferencia->referenciaOrigen->producto->empaque ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Seccion 3 y 4: Origen y Destino -->
    <div class="two-columns">
        <div class="column">
            <div class="section">
                <div class="section-title">3. Origen</div>
                <div class="section-body">
                    <div class="row">
                        <span class="label">Cliente:</span>
                        <span class="value">{{ $transferencia->clienteOrigen->name ?? '-' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Modulo/Posicion:</span>
                        <span class="value">{{ $transferencia->ubicacionOrigen->modulo ?? '-' }} / {{ $transferencia->ubicacionOrigen->posicion ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="section">
                <div class="section-title">4. Destino</div>
                <div class="section-body">
                    <div class="row">
                        <span class="label">Cliente:</span>
                        <span class="value">{{ $transferencia->clienteDestino->name ?? '-' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Modulo/Posicion:</span>
                        <span class="value">{{ $transferencia->ubicacionDestino->modulo ?? '-' }} / {{ $transferencia->ubicacionDestino->posicion ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seccion 5: Motivo -->
    <div class="section">
        <div class="section-title">5. Motivo de la Transferencia</div>
        <div class="section-body">
            <p>{{ $transferencia->motivo ?? '-' }}</p>
        </div>
    </div>

    <!-- Seccion 6: Autorizacion -->
    <div class="section">
        <div class="section-title">6. Autorizacion</div>
        <div class="section-body">
            <div class="highlight">
                {{ $transferencia->autorizacion_cliente }}
            </div>
        </div>
    </div>

    <!-- Seccion 7: Firmas -->
    <div class="section">
        <div class="section-title">7. Firmas</div>
        <div class="firmas">
            <div class="firma">
                <div class="firma-linea">Cliente Origen</div>
            </div>
            <div class="firma">
                <div class="firma-linea">Cliente Destino</div>
            </div>
            <div class="firma">
                <div class="firma-linea">Responsable de la Operacion</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y H:i') }} - Cargo Express
    </div>

</body>
</html>
