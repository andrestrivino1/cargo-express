@props(['excelRoute' => null, 'pdfRoute' => null, 'filtros' => []])

@if($excelRoute || $pdfRoute)
    @php
        $queryString = !empty($filtros) ? '?' . http_build_query(array_filter($filtros)) : '';
    @endphp

    <div class="btn-group" role="group" aria-label="Exportar">
        @if($excelRoute)
            <a href="{{ $excelRoute . $queryString }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
        @endif
        @if($pdfRoute)
            <a href="{{ $pdfRoute . $queryString }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
        @endif
    </div>
@endif
