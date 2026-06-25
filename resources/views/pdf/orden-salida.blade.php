<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de Salida ODC-{{ $tarja->consecutivo_odc }}</title>
    <style>
        @page { margin: 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; }
        .head-table td { vertical-align: top; padding: 2pt 4pt; }
        .company-name { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .doc-title { font-size: 12pt; font-weight: bold; text-align: center; }
        .doc-consecutivo { font-size: 11pt; font-weight: bold; text-align: center; }
        .label { font-weight: bold; }
        .section-bar {
            background-color: #333; color: #fff; font-weight: bold;
            text-transform: uppercase; padding: 4pt 6pt; font-size: 9pt;
            text-align: center; margin-top: 10pt;
        }
        .detalle { width: 100%; border-collapse: collapse; margin-top: 2pt; }
        .detalle th { background-color: #555; color: #fff; padding: 4pt; font-size: 8pt; text-transform: uppercase; border: 1px solid #333; }
        .detalle td { padding: 4pt; border: 1px solid #999; font-size: 8.5pt; }
        .detalle .num { text-align: center; }
        .total-cell { font-weight: bold; text-align: right; }
        .driver-table td { vertical-align: top; padding: 3pt 6pt; }
        .photo { width: 150pt; height: 110pt; border: 1px solid #999; object-fit: cover; }
        .photo-box { width: 160pt; height: 115pt; border: 1px solid #999; text-align: center; }
        .sign-table { margin-top: 35pt; }
        .sign-cell { width: 50%; text-align: center; padding-top: 6pt; }
        .sign-line { border-top: 1px solid #333; margin: 0 20pt; padding-top: 3pt; }
    </style>
</head>
<body>
    {{-- Encabezado --}}
    <table class="head-table">
        <tr>
            <td style="width: 33%;">
                <div class="label">Consecutivo: {{ $tarja->consecutivo_odc }}</div>
                <div style="margin-top:6pt;"><span class="label">Cliente:</span> {{ $cliente->name ?? '—' }}</div>
                <div><span class="label">NIT:</span> {{ $cliente->nit ?? '—' }}</div>
                <div><span class="label">Fecha de salida:</span> {{ $tarja->fecha_entrega?->format('d/m/Y') }}</div>
            </td>
            <td style="width: 34%; text-align:center;">
                <div class="company-name">{{ $empresa['razon_social'] }}</div>
                <div class="doc-title">ORDEN DE SALIDA</div>
                <div class="doc-consecutivo">ODC-{{ $tarja->consecutivo_odc }}</div>
            </td>
            <td style="width: 33%; text-align:right;">
                @php $logoPath = public_path($empresa['logo'] ?? ''); @endphp
                @if ($empresa['logo'] && file_exists($logoPath))
                    <img src="{{ $logoPath }}" style="max-height: 55pt;">
                @else
                    <div class="company-name" style="color:#999;">CARGA<br>TRANS XPRESS</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Detalle de la carga --}}
    <div class="section-bar">Detalle de la carga</div>
    <table class="detalle">
        <thead>
            <tr>
                <th style="width: 22%;">Contenedor</th>
                <th style="width: 38%;">Descripci&oacute;n</th>
                <th style="width: 25%;">Observaciones</th>
                <th style="width: 15%;">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalles as $fila)
            <tr>
                <td>{{ $fila['contenedor'] }}</td>
                <td>{{ $fila['descripcion'] }}</td>
                <td>{{ $fila['observaciones'] }}</td>
                <td class="num">{{ $fila['cantidad'] }}</td>
            </tr>
            @endforeach
            {{-- filas vacías para conservar el formato --}}
            @for ($i = $detalles->count(); $i < 8; $i++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
            @endfor
            <tr>
                <td colspan="3" class="total-cell">Total</td>
                <td class="num"><strong>{{ $total }}</strong></td>
            </tr>
        </tbody>
    </table>

    {{-- Datos del conductor y vehículo --}}
    <div class="section-bar">Datos del conductor y veh&iacute;culo</div>
    <table class="driver-table">
        <tr>
            <td style="width: 40%;">
                <div><span class="label">Nombre Conductor:</span> {{ $tarja->conductor }}</div>
                <div><span class="label">C&eacute;dula:</span> {{ $tarja->conductor_cedula ?? '' }}</div>
                <div><span class="label">Placa Veh&iacute;culo:</span> {{ $tarja->vehiculo }}</div>
                <div><span class="label">Transportador:</span> {{ $tarja->transportador }}</div>
                <div><span class="label">Destino:</span> {{ $tarja->destino }}</div>
            </td>
            <td style="width: 30%; text-align:center;">
                @if ($fotoConductor && file_exists(storage_path('app/public/'.$fotoConductor->ruta)))
                    <img src="{{ storage_path('app/public/'.$fotoConductor->ruta) }}" class="photo">
                @else
                    <div class="photo-box"><br><br><br>Foto conductor</div>
                @endif
            </td>
            <td style="width: 30%; text-align:center;">
                @if ($fotoMercancia && file_exists(storage_path('app/public/'.$fotoMercancia->ruta)))
                    <img src="{{ storage_path('app/public/'.$fotoMercancia->ruta) }}" class="photo">
                @else
                    <div class="photo-box"><br><br><br>Foto mercanc&iacute;a</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Firmas --}}
    <table class="sign-table">
        <tr>
            <td class="sign-cell"><div class="sign-line">Firma conductor</div></td>
            <td class="sign-cell"><div class="sign-line">Firma empresa</div></td>
        </tr>
    </table>
</body>
</html>
