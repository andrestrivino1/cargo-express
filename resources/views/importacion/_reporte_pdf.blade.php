<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Reporte importación #{{ $batch->id }}</title>
<style>
    body { font-family: sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; }
    table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
    th { background: #eee; }
    .small { font-size: 9px; color: #666; }
</style>
</head><body>
    <h1>Importación #{{ $batch->id }} — {{ $batch->modo }}</h1>
    <p class="small">
        Archivo: {{ $batch->archivo_nombre }} · Hash: {{ $batch->archivo_hash }}<br>
        Usuario: {{ $batch->usuario?->name }} · Estado final: {{ $batch->estado->value }}<br>
        Inicio: {{ $batch->started_at }} · Fin: {{ $batch->finished_at }}
    </p>

    <h3>Contadores</h3>
    <table>
        <tr><th>Total filas</th><td>{{ $batch->total_filas }}</td>
            <th>Importables</th><td>{{ $batch->importables }}</td>
            <th>Errores</th><td>{{ $batch->errores }}</td></tr>
        <tr><th>Advertencias</th><td>{{ $batch->advertencias }}</td>
            <th>Ignoradas</th><td>{{ $batch->ignoradas }}</td>
            <th>Clientes auto-creados</th><td>{{ $batch->clientes_autocreados }}</td></tr>
    </table>

    <h3>Resultado por hoja</h3>
    <table>
        <thead><tr><th>Hoja</th><th>Estado</th><th>Filas</th></tr></thead>
        <tbody>
        @foreach ($hojas as $hoja => $estados)
            @foreach ($estados as $e)
                <tr><td>{{ $hoja }}</td><td>{{ $e->estado }}</td><td>{{ $e->total }}</td></tr>
            @endforeach
        @endforeach
        </tbody>
    </table>

    <h3>Clientes a auto-crear</h3>
    @if (empty($clientes))
        <p class="small">Ninguno.</p>
    @else
        <ul>
            @foreach ($clientes as $nombre)
                <li>{{ $nombre }}</li>
            @endforeach
        </ul>
    @endif
</body></html>
