<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $productos = Producto::query()
            ->when($search, fn ($query) => $query->where('nombre', 'like', "%{$search}%"))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', compact('productos', 'search'));
    }

    public function create(): View
    {
        return view('productos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'medidas' => ['nullable', 'string', 'max:100'],
            'calibre' => ['nullable', 'string', 'max:50'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'empaque' => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ]);

        $validated['activo'] = $request->has('activo');

        Producto::create($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Producto $producto): View
    {
        return view('productos.edit', compact('producto'));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'medidas' => ['nullable', 'string', 'max:100'],
            'calibre' => ['nullable', 'string', 'max:50'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'empaque' => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ]);

        $validated['activo'] = $request->has('activo');

        $producto->update($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->referencias()->exists()) {
            return redirect()->route('productos.index')
                ->with('error', 'No se puede eliminar el producto porque tiene referencias asociadas.');
        }

        $producto->delete();

        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }
}
