<?php

namespace App\Http\Controllers;

use App\Models\ProductExtra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductExtraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $extras = ProductExtra::orderBy('name')->get();
        return view('admin.product_extras.index', compact('extras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.product_extras.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,]+$/', 'unique:product_extras,name'],
            'cost_addition' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_addition' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'minutes_addition' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'name.required' => 'El nombre del extra es obligatorio.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'name.unique' => 'Ya existe un extra con este nombre.',
            'cost_addition.required' => 'El costo adicional es obligatorio.',
            'cost_addition.numeric' => 'El costo debe ser un número válido.',
            'price_addition.required' => 'El precio adicional es obligatorio.',
            'price_addition.numeric' => 'El precio debe ser un número válido.',
        ]);

        try {
            $extra = new ProductExtra();
            $extra->name = strtoupper(trim($request->name));
            $extra->cost_addition = $request->cost_addition;
            $extra->price_addition = $request->price_addition;
            $extra->minutes_addition = $request->minutes_addition ?? 0;
            $extra->save();

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra creado exitosamente');
        } catch (\Exception $e) {
            Log::error('[ProductExtra@store] Error al crear extra: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al crear el extra');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductExtra $productExtra)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $extra = ProductExtra::findOrFail($id);
        return view('admin.product_extras.edit', compact('extra'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,]+$/', 'unique:product_extras,name,' . $id],
            'cost_addition' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_addition' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'minutes_addition' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'name.required' => 'El nombre del extra es obligatorio.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'name.unique' => 'Ya existe un extra con este nombre.',
            'cost_addition.required' => 'El costo adicional es obligatorio.',
            'cost_addition.numeric' => 'El costo debe ser un número válido.',
            'price_addition.required' => 'El precio adicional es obligatorio.',
            'price_addition.numeric' => 'El precio debe ser un número válido.',
        ]);

        $extra = ProductExtra::findOrFail($id);

        try {
            $extra->name = strtoupper(trim($request->name));
            $extra->cost_addition = $request->cost_addition;
            $extra->price_addition = $request->price_addition;
            $extra->minutes_addition = $request->minutes_addition ?? 0;

            if (!$extra->isDirty()) {
                return redirect()->route('admin.product_extras.index')
                    ->with('info', 'No se realizaron cambios');
            }

            $extra->save();

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('[ProductExtra@update] Error al actualizar extra: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al actualizar el extra');
        }
    }

    /**
     * Confirm delete page
     */
    public function confirm_delete($id)
    {
        $extra = ProductExtra::findOrFail($id);
        return view('admin.product_extras.delete', compact('extra'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $extra = ProductExtra::findOrFail($id);

        try {
            // Verificar si tiene productos asociados
            if ($extra->products()->count() > 0) {
                return redirect()->route('admin.product_extras.index')
                    ->with('error', 'No se puede eliminar el extra porque tiene productos asociados.');
            }

            $extra->delete();

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('[ProductExtra@destroy] Error al eliminar extra: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al eliminar el extra');
        }
    }
}
