<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de Servicio #{{ $orden->id }}</title>
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
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header .logo-placeholder {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #7f8c8d;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table th,
        .info-table td {
            padding: 10px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .info-table th {
            background-color: #ecf0f1;
            font-weight: bold;
            color: #2c3e50;
            width: 35%;
        }

        .info-table td {
            background-color: #fff;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }

        .signatures {
            margin-top: 60px;
            width: 100%;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
            border: none;
        }

        .signatures .line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 0 auto 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-placeholder">CARGO EXPRESS</div>
        <h1>Orden de Servicio</h1>
        <div class="subtitle">Sistema de Trazabilidad de Carga</div>
    </div>

    <table class="info-table">
        <tr>
            <th>Numero de Orden</th>
            <td>{{ $orden->id }}</td>
        </tr>
        <tr>
            <th>Cliente</th>
            <td>{{ $solicitud->cliente->name }}</td>
        </tr>
        <tr>
            <th>Contenedor</th>
            <td>{{ $solicitud->numero_contenedor }}</td>
        </tr>
        <tr>
            <th>Naviera</th>
            <td>{{ $solicitud->naviera ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Vehiculo</th>
            <td>{{ $orden->vehiculo }}</td>
        </tr>
        <tr>
            <th>Conductor</th>
            <td>{{ $orden->conductor }}</td>
        </tr>
        <tr>
            <th>Cedula</th>
            <td>{{ $orden->conductor_documento ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Cita en Puerto</th>
            <td>{{ $orden->cita_puerto->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th>Fecha de Generacion</th>
            <td>{{ $orden->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                Coordinador
            </td>
            <td>
                <div class="line"></div>
                Conductor
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento generado automaticamente por Cargo Express - {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>