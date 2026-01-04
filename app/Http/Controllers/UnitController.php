<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnitController extends Controller
{
    public function index()
    {
        try {
            $units = Unit::where('activo', true)->ordered()->get();
            return view('admin.units.index', compact('units'));
        } catch (\Exception $e) {
            Log::error('Error al listar unidades: ' . $e->getMessage());
            return view('admin.units.index', ['units' => collect()])
                ->with('error', 'Error al cargar las unidades');
        }
    }

    public function create()
    {
        return view('admin.units.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'symbol' => ['required', 'string', 'min:1', 'max:10', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/'],
            'is_base' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.regex' => 'El símbolo solo puede contener letras.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
        ]);

        try {
            $unit = new Unit();
            $unit->uuid = (string) Str::uuid();
            $unit->name = mb_strtoupper(trim($request->name));
            $unit->slug = Str::slug($request->name);
            $unit->symbol = mb_strtolower(trim($request->symbol));
            $unit->is_base = $request->boolean('is_base');
            $unit->activo = true;
            $unit->save();

            return redirect()->route('units.index')->with('success', 'Unidad creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear unidad: ' . $e->getMessage());
            return redirect()->route('units.index')->with('error', 'Error al crear la unidad');
        }
    }

    public function edit($id)
    {
        try {
            $unit = Unit::where('activo', true)->findOrFail($id);
            return view('admin.units.edit', compact('unit'));
        } catch (\Exception $e) {
            Log::error('Error al cargar unidad para editar: ' . $e->getMessage());
            return redirect()->route('units.index')->with('error', 'Unidad no encontrada');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'symbol' => ['required', 'string', 'min:1', 'max:10', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/'],
            'is_base' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.regex' => 'El símbolo solo puede contener letras.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
        ]);

        try {
            $unit = Unit::where('activo', true)->findOrFail($id);

            $unit->name = mb_strtoupper(trim($request->name));
            $unit->slug = Str::slug($request->name);
            $unit->symbol = mb_strtolower(trim($request->symbol));
            $unit->is_base = $request->boolean('is_base');

            if (!$unit->isDirty()) {
                return redirect()->route('units.index')->with('info', 'No se realizaron cambios');
            }

            $unit->save();
            return redirect()->route('units.index')->with('success', 'Unidad actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar unidad: ' . $e->getMessage());
            return redirect()->route('units.index')->with('error', 'Error al actualizar la unidad');
        }
    }

    public function confirmDelete($id)
    {
        try {
            $unit = Unit::where('activo', true)->findOrFail($id);
            return view('admin.units.delete', compact('unit'));
        } catch (\Exception $e) {
            Log::error('Error al cargar unidad para eliminar: ' . $e->getMessage());
            return redirect()->route('units.index')->with('error', 'Unidad no encontrada');
        }
    }

    public function destroy($id)
    {
        try {
            $unit = Unit::where('activo', true)->findOrFail($id);
            $unit->activo = false;
            $unit->save();

            return redirect()->route('units.index')->with('success', 'Unidad eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar unidad: ' . $e->getMessage());
            return redirect()->route('units.index')->with('error', 'Error al eliminar la unidad');
        }
    }
}
