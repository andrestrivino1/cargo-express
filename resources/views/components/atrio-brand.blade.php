{{--
    Marca del desarrollador (ATRIO, estudio de software de Andrés Triviño): la placa y, opcionalmente, el nombre.
    Los colores siguen la guía de marca: tinta clara sobre fondos claros y oscura sobre fondos oscuros
    (ver resources/css/atrio.css). Es solo un crédito: nunca reemplaza el nombre del sistema ni va en documentos.
--}}
@props(['size' => 20, 'wordmark' => true])
@php $gradientId = 'atrio-gradient-'.\Illuminate\Support\Str::random(6); @endphp
<span {{ $attributes->class(['atrio-brand', 'd-inline-flex', 'align-items-center']) }} data-atrio-brand>
    <svg class="atrio-mark" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#E8C26A" />
                <stop offset="0.35" stop-color="#B4743A" />
                <stop offset="0.65" stop-color="#7A4588" />
                <stop offset="1" stop-color="#2E5A8C" />
            </linearGradient>
        </defs>
        <polygon points="40,10 160,10 190,40 190,160 160,190 40,190 10,160 10,40" fill="url(#{{ $gradientId }})" />
        <polygon class="atrio-mark-ink" points="38,154 63,154 97,46 72,46" />
        <rect class="atrio-mark-ink" x="72" y="46" width="94" height="23" />
        <rect class="atrio-mark-ink" x="109" y="69" width="23" height="85" />
        <rect class="atrio-mark-ink" x="56" y="110" width="53" height="19" />
        <circle cx="160" cy="36" r="7" fill="#FFE3A3" />
    </svg>
    @if ($wordmark)
        <span class="atrio-wordmark"><span class="atrio-wordmark-at">AT</span>RIO</span>
    @endif
</span>
