<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Sticker - {{ $referencia->codigo }}</title>
    <style>
        @page {
            size: 288pt 432pt;
            margin: 10pt;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            width: 268pt;
            height: 412pt;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sticker {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            padding: 12pt;
            text-align: center;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 8pt;
            margin-bottom: 12pt;
            text-transform: uppercase;
        }
        .field {
            margin-bottom: 10pt;
            text-align: left;
        }
        .field-label {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 2pt;
        }
        .field-value {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
        }
        .field-value.large {
            font-size: 18pt;
        }
        .barcode-section {
            margin: 15pt 0;
            text-align: center;
        }
        .barcode-section img {
            max-width: 100%;
            height: 50pt;
        }
        .barcode-text {
            font-size: 10pt;
            font-family: 'Courier New', monospace;
            margin-top: 4pt;
            letter-spacing: 2pt;
        }
        .date-section {
            margin-top: 10pt;
            border-top: 1px solid #ccc;
            padding-top: 8pt;
        }
        .date-value {
            font-size: 11pt;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="sticker">
        <div class="company-name">Cargo Express</div>

        <div class="field">
            <div class="field-label">Contenedor</div>
            <div class="field-value large">{{ $referencia->contenedor->numero }}</div>
        </div>

        <div class="field">
            <div class="field-label">Referencia</div>
            <div class="field-value large">{{ $referencia->codigo }}</div>
        </div>

        <div class="field">
            <div class="field-label">Cliente</div>
            <div class="field-value">{{ $referencia->cliente->name }}</div>
        </div>

        <div class="barcode-section">
            <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode">
            <div class="barcode-text">{{ $referencia->codigo }}</div>
        </div>

        <div class="date-section">
            <div class="field-label">Fecha de Ingreso</div>
            <div class="date-value">{{ $referencia->fecha_ingreso->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</body>
</html>