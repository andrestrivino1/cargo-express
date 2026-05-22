<?php

namespace App\Http\Controllers;

use App\Models\UbicacionPatio;
use Illuminate\Http\Request;

class UbicacionPatioController extends Controller
{
    public function index()
    {
        $ubicaciones = UbicacionPatio::orderBy('modulo')
            ->orderBy('posicion')
            ->paginate(15);

        return view('admin.ubicaciones.index', compact('ubicaciones'));
    }

    public function create()
    {
        return view('admin.ubicaciones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'modulo'      => 'required|string|max:50',
            'posicion'    => 'required|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        UbicacionPatio::create($validated);

        return redirect()->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación creada exitosamente.');
    }

    public function edit(UbicacionPatio $ubicacione)
    {
        return view('admin.ubicaciones.edit', ['ubicacion' => $ubicacione]);
    }

    public function update(Request $request, UbicacionPatio $ubicacione)
    {
        $validated = $request->validate([
            'modulo'      => 'required|string|max:50',
            'posicion'    => 'required|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        $ubicacione->update($validated);

        return redirect()->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación actualizada exitosamente.');
    }

    public function destroy(UbicacionPatio $ubicacione)
    {
        $ubicacione->delete();

        return redirect()->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación eliminada exitosamente.');
    }
}
