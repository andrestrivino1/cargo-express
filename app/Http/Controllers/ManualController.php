<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class ManualController extends Controller
{
    public function pdf()
    {
        $pdf = Pdf::loadView('manual.pdf')
            ->setPaper('letter')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'Helvetica');

        return $pdf->download('Manual_Usuario_CargoExpress.pdf');
    }
}