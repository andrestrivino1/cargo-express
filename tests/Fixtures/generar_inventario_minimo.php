<?php

/**
 * Genera tests/Fixtures/inventario_minimo.xlsx — fixture sintético usado por
 * los tests de feature 002. Idempotente: sobreescribe si ya existe.
 *
 * Uso: php tests/Fixtures/generar_inventario_minimo.php
 */

require __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$wb = new Spreadsheet;

// Hoja 1: vacía
$wb->getActiveSheet()->setTitle('Hoja1');

// Hoja 2: estándar
$ws = $wb->createSheet();
$ws->setTitle('CLIENTE STANDARD SAS');
$encabezado = ['fecha documentos','Modulo','Cliente','Mercancia','#Referencia','Detalle','Observación','Unidad','Contenedor','fecha deposito','FECHA DE DESPACHO','DESPACHO','INVENTARIO'];
$ws->fromArray($encabezado, null, 'A1');

$datos = [
    ['9/4/2026','Modulo6 Bloque B','CLIENTE STANDARD SAS','VIDRIO','REF-001','4MM 2440*3300MM','','10','MRKU9517467','2/05/2026','22/4/2026','3','7'],
    ['2/5/2026','Modulo 2 Bloque A','CLIENTE STANDARD SAS','ESPEJO','REF-002','6*3300*2140MM','','5','ONEU5898563','3/05/2026','','','5'],
    ['13/02/2026','Modulo 3-Bloque C','CLIENTE STANDARD SAS','VIDRIO LAMINADO','REF-003','8.38 3.660 X 2.440MM','','8','TCNU7060814','13/02/2026','15-03-2026','3','5'],
    // 17 filas más válidas
];
for ($i = 4; $i <= 20; $i++) {
    $datos[] = ['9/4/2026','Modulo'.$i.' Bloque A','CLIENTE STANDARD SAS','VIDRIO','REF-'.str_pad($i, 3, '0', STR_PAD_LEFT),'5*3300*2250','','10','MRKU95174'.str_pad($i, 2, '0', STR_PAD_LEFT),'2/05/2026','','','10'];
}
$ws->fromArray($datos, null, 'A2');

// Hoja 3: columna en blanco al inicio, sin Mercancia, datos sucios
$ws = $wb->createSheet();
$ws->setTitle('CLIENTE BLANCO SAS');
$encabezado = ['','fecha documento','Ubicación','Cliente','#Referencia','Detalle','Observación','Unidad','Contenedor','Fecha','FECHA DE DESPACHO','DESPACHO','FECHA DE DESPACHO','DESPACHO','Inventario fisico'];
$ws->fromArray($encabezado, null, 'A1');

$datos = [
    ['','27/4/2026','Modulo 3-Bloque C','CLIENTE BLANCO SAS','REF-101','3*3300*2140','','8','TCKU3628500','13/02/2026','15-03-2026','2','16-05-2026','2','4'],
    ['','30/04/2026','','CLIENTE BLANCO SAS','REF-102','4*3300*2140MM','','#','MRKU9517467','30/04/2026','','','','','5'], // cantidad inválida
    ['','','Modulo 2-Bloque A','CLIENTE BLANCO SAS','REF-103','5*3300*2250','','10','BMOU5566097','XX','','','','','10'], // fecha basura
    ['','12/12/2025','Modulo 4-Bloque A','CLIENTE BLANCO SAS','REF-104','5*3300*2250','','8','CMAU4713745','12/12/2025','','','','','8'],
];
for ($i = 5; $i <= 15; $i++) {
    $datos[] = ['','12/12/2025','Modulo 4-Bloque A','CLIENTE BLANCO SAS','REF-1'.str_pad($i, 2, '0', STR_PAD_LEFT),'5*3300*2250','','8','CMAU471374'.($i % 10),'12/12/2025','','','','','8'];
}
$ws->fromArray($datos, null, 'A2');

$wb->setActiveSheetIndex(0);

$output = __DIR__.'/inventario_minimo.xlsx';
(new Xlsx($wb))->save($output);

echo "Fixture generado: {$output}\n";
