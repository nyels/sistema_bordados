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
            $units = Unit::with('compatibleBaseUnit')
                ->where('activo', true)
                ->ordered()
                ->get();
            return view('admin.units.index', compact('units'));
        } catch (\Exception $e) {
            Log::error('Error al listar unidades: ' . $e->getMessage());
            return view('admin.units.index', ['units' => collect()])
                ->with('error', 'Error al cargar las unidades');
        }
    }

    public function create()
    {
        $baseUnits = Unit::getBaseUnits();
        return view('admin.units.create', compact('baseUnits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/'],
            'symbol' => ['required', 'string', 'min:1', 'max:10', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/'],
            'is_base' => ['sometimes', 'boolean'],
            'compatible_base_unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.regex' => 'El símbolo solo puede contener letras, números y espacios.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
            'compatible_base_unit_id.exists' => 'La unidad base seleccionada no es válida.',
        ]);

        try {
            $name = mb_strtoupper(trim($request->name));
            $symbol = mb_strtolower(trim($request->symbol));
            $slug = Str::slug($request->name);

            // Verificar si existe una unidad con el mismo nombre, símbolo o slug
            $existingUnit = Unit::where(function ($query) use ($name, $symbol, $slug) {
                    $query->where('name', $name)
                          ->orWhere('symbol', $symbol)
                          ->orWhere('slug', $slug);
                })
                ->first();

            if ($existingUnit) {
                if ($existingUnit->activo) {
                    // Ya existe una unidad activa
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Ya existe una unidad con ese nombre o símbolo');
                }

                // Reactivar la unidad inactiva
                $existingUnit->name = $name;
                $existingUnit->slug = $slug;
                $existingUnit->symbol = $symbol;
                $existingUnit->is_base = $request->boolean('is_base');
                $existingUnit->compatible_base_unit_id = $request->boolean('is_base') ? null : $request->compatible_base_unit_id;
                $existingUnit->activo = true;
                $existingUnit->save();

                return redirect()->route('admin.units.index')->with('success', 'Unidad reactivada exitosamente');
            }

            // Crear nueva unidad
            $unit = new Unit();
            $unit->uuid = (string) Str::uuid();
            $unit->name = $name;
            $unit->slug = $slug;
            $unit->symbol = $symbol;
            $unit->is_base = $request->boolean('is_base');
            $unit->compatible_base_unit_id = $request->boolean('is_base') ? null : $request->compatible_base_unit_id;
            $unit->activo = true;
            $unit->save();

            return redirect()->route('admin.units.index')->with('success', 'Unidad creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear unidad: ' . $e->getMessage());
            return redirect()->route('admin.units.index')->with('error', 'Error al crear la unidad');
        }
    }

    public function edit($id)
    {
        try {
            $unit = Unit::where('activo', true)->findOrFail($id);
            $baseUnits = Unit::getBaseUnits()->where('id', '!=', $id);
            return view('admin.units.edit', compact('unit', 'baseUnits'));
        } catch (\Exception $e) {
            Log::error('Error al cargar unidad para editar: ' . $e->getMessage());
            return redirect()->route('admin.units.index')->with('error', 'Unidad no encontrada');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s0-9]+$/'],
            'symbol' => ['required', 'string', 'min:1', 'max:10', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s0-9]+$/'],
            'is_base' => ['sometimes', 'boolean'],
            'compatible_base_unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.regex' => 'El símbolo solo puede contener letras, números y espacios.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
            'compatible_base_unit_id.exists' => 'La unidad base seleccionada no es válida.',
        ]);

        try {
            $unit = Unit::where('activo', true)->findOrFail($id);

            $unit->name = mb_strtoupper(trim($request->name));
            $unit->slug = Str::slug($request->name);
            $unit->symbol = mb_strtolower(trim($request->symbol));
            $unit->is_base = $request->boolean('is_base');
            $unit->compatible_base_unit_id = $request->boolean('is_base') ? null : $request->compatible_base_unit_id;

            if (!$unit->isDirty()) {
                return redirect()->route('admin.units.index')->with('info', 'No se realizaron cambios');
            }

            $unit->save();
            return redirect()->route('admin.units.index')->with('success', 'Unidad actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar unidad: ' . $e->getMessage());
            return redirect()->route('admin.units.index')->with('error', 'Error al actualizar la unidad');
        }
    }

    public function confirmDelete($id)
    {
        try {
            $unit = Unit::with(['compatibleBaseUnit', 'purchaseUnits'])
                ->where('activo', true)
                ->findOrFail($id);
            return view('admin.units.delete', compact('unit'));
        } catch (\Exception $e) {
            Log::error('Error al cargar unidad para eliminar: ' . $e->getMessage());
            return redirect()->route('admin.units.index')->with('error', 'Unidad no encontrada');
        }
    }

    public function destroy($id)
    {
        try {
            $unit = Unit::with('purchaseUnits')->where('activo', true)->findOrFail($id);

            $deletedCount = 0;

            // Si es unidad base, eliminar también las unidades de compra vinculadas
            if ($unit->is_base && $unit->purchaseUnits->count() > 0) {
                foreach ($unit->purchaseUnits as $purchaseUnit) {
                    $purchaseUnit->activo = false;
                    $purchaseUnit->save();
                    $deletedCount++;
                }
            }

            $unit->activo = false;
            $unit->save();

            $message = 'Unidad eliminada exitosamente';
            if ($deletedCount > 0) {
                $message .= ". También se eliminaron {$deletedCount} unidad(es) de compra vinculada(s).";
            }

            return redirect()->route('admin.units.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error al eliminar unidad: ' . $e->getMessage());
            return redirect()->route('admin.units.index')->with('error', 'Error al eliminar la unidad');
        }
    }
}
