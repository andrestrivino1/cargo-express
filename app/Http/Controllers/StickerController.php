<?php

namespace App\Http\Controllers;

use App\Models\Referencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorPNG;

class StickerController extends Controller
{
    public function show(Referencia $referencia): View
    {
        $referencia->load(['contenedor', 'cliente']);

        $barcodeGenerator = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $barcodeGenerator->getBarcode($referencia->codigo, $barcodeGenerator::TYPE_CODE_128)
        );

        return view('pdf.sticker', compact('referencia', 'barcodeImage'));
    }

    public function print(Referencia $referencia): Response
    {
        $referencia->load(['contenedor', 'cliente']);

        $barcodeGenerator = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $barcodeGenerator->getBarcode($referencia->codigo, $barcodeGenerator::TYPE_CODE_128)
        );

        $pdf = Pdf::loadView('pdf.sticker', compact('referencia', 'barcodeImage'))
            ->setPaper([0, 0, 288, 432]); // 4"x6" in points

        return $pdf->download("sticker-{$referencia->codigo}.pdf");
    }
}