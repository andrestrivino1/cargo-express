<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tarja de Entrega #{{ $tarja->id }}</title>
    <style>
        @page {
            margin: 15mm;
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
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 10pt;
            margin-bottom: 15pt;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1pt;
        }
        .document-title {
            font-size: 13pt;
            font-weight: bold;
            color: #555;
            margin-top: 5pt;
        }
        .info-section {
            margin-bottom: 15pt;
        }
        .info-row {
            display: block;
            margin-bottom: 4pt;
            line-height: 1.5;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
            margin-bottom: 15pt;
        }
        th {
            background-color: #2c3e50;
            color: #fff;
            padding: 8pt 6pt;
            text-align: left;
            font-size: 9pt;
            text-transform: uppercase;
        }
        td {
            padding: 6pt;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            font-size: 11pt;
            text-align: right;
            margin-top: 10pt;
            padding: 8pt;
            background-color: #ecf0f1;
            border: 1px solid #bdc3c7;
        }
        .footer {
            margin-top: 30pt;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 8pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Cargo Express</div>
        <div class="document-title">Tarja de Entrega</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Tarja N&deg;:</span> {{ $tarja->id }}
        </div>
        <div class="info-row">
            <span class="info-label">Orden de Cargue:</span> #{{ $tarja->ordenCargue->id }}
        </div>
        <div class="info-row">
            <span class="info-label">Cliente:</span> {{ $tarja->ordenCargue->cliente->name ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Despachador:</span> {{ $tarja->despachador->name ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Fecha de Entrega:</span> {{ $tarja->fecha_entrega->format('d/m/Y H:i') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>C&oacute;digo Referencia</th>
                <th>Descripci&oacute;n</th>
                <th style="text-align: center;">Cantidad Entregada</th>
                <th>Ubicaci&oacute;n Origen</th>
            </tr>
        </thead>
        <tbody>
            @php $totalUnidades = 0; @endphp
            @foreach($tarja->detalles as $detalle)
            @php $totalUnidades += $detalle->cantidad_entregada; @endphp
            <tr>
                <td>{{ $detalle->referencia->codigo }}</td>
                <td>{{ $detalle->referencia->descripcion }}</td>
                <td style="text-align: center;">{{ number_format($detalle->cantidad_entregada) }}</td>
                <td>{{ $detalle->ubicacionOrigen->modulo ?? '' }} - {{ $detalle->ubicacionOrigen->posicion ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-row">
        Total Unidades: {{ number_format($totalUnidades) }}
    </div>

    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y H:i') }} &mdash; Cargo Express
    </div>
</body>
</html>
