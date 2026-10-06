{{--
    Ícono de la pestaña: la placa de ATRIO (guía de marca: el símbolo solo, sin degradado, para íconos).
    Va incrustado como data URI porque en producción el document root es public_html y el despliegue
    solo sincroniza public/build: un archivo suelto en public/ no llegaría al navegador.
--}}
@php
    $atrioFavicon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
        .'<polygon points="40,10 160,10 190,40 190,160 160,190 40,190 10,160 10,40" fill="#E8C26A"/>'
        .'<polygon points="38,154 63,154 97,46 72,46" fill="#1E1726"/>'
        .'<rect x="72" y="46" width="94" height="23" fill="#1E1726"/>'
        .'<rect x="109" y="69" width="23" height="85" fill="#1E1726"/>'
        .'<rect x="56" y="110" width="53" height="19" fill="#1E1726"/>'
        .'<circle cx="160" cy="36" r="7" fill="#1E1726"/>'
        .'</svg>';
@endphp
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,{{ rawurlencode($atrioFavicon) }}" data-atrio-favicon>
