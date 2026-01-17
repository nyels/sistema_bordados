<?php

namespace App\Http\Controllers;

use App\Enums\UnitType;
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
                ->orderBy('sort_order')
                ->orderBy('name')
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
        $canonicalUnits = Unit::getCanonicalUnits();
        return view('admin.units.create', compact('canonicalUnits'));
    }

    public function store(Request $request)
    {
        // Validación condicional: si NO es base, compatible_base_unit_id es opcional (para genéricos)
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/'],
            'symbol' => ['required', 'string', 'min:1', 'max:10', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/'],
            'is_base' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'default_conversion_factor' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
        ];

        // Si NO es unidad de consumo, compatible_base_unit_id es opcional (Generic Container)
        if (!$request->boolean('is_base')) {
            $rules['compatible_base_unit_id'] = ['nullable', 'integer', 'exists:units,id'];
        } else {
            $rules['compatible_base_unit_id'] = ['nullable', 'integer', 'exists:units,id'];
        }

        $request->validate($rules, [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.regex' => 'El símbolo solo puede contener letras, números y espacios.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
            'compatible_base_unit_id.exists' => 'La unidad de consumo seleccionada no es válida.',
            'default_conversion_factor.numeric' => 'La cantidad por unidad debe ser un número.',
            'default_conversion_factor.max' => 'La cantidad por unidad no puede exceder 999999.9999.',
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
            })->first();

            if ($existingUnit) {
                if ($existingUnit->activo) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Ya existe una unidad con ese nombre o símbolo');
                }

                // Reactivar la unidad inactiva con los nuevos valores
                $this->fillUnitFromRequest($existingUnit, $request, $name, $slug, $symbol);
                $existingUnit->activo = true;
                $existingUnit->save();

                return redirect()->route('admin.units.index')
                    ->with('success', 'Unidad reactivada exitosamente');
            }

            // Crear nueva unidad
            $unit = new Unit();
            $unit->uuid = (string) Str::uuid();
            $this->fillUnitFromRequest($unit, $request, $name, $slug, $symbol);
            $unit->activo = true;
            $unit->save();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Unidad creada exitosamente',
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'symbol' => $unit->symbol,
                    'unit_type' => $unit->unit_type->value,
                ]);
            }

            return redirect()->route('admin.units.index')
                ->with('success', 'Unidad creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear unidad: ' . $e->getMessage(), [
                'request' => $request->except(['_token']),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Error al crear la unidad'], 500);
            }
            return redirect()->route('admin.units.index')
                ->with('error', 'Error al crear la unidad');
        }
    }

    public function edit($id)
    {
        try {
            $unit = Unit::where('activo', true)->findOrFail($id);
            // Excluir la unidad actual para evitar auto-referencia
            $canonicalUnits = Unit::getCanonicalUnits()->where('id', '!=', $id);
            return view('admin.units.edit', compact('unit', 'canonicalUnits'));
        } catch (\Exception $e) {
            Log::error('Error al cargar unidad para editar: ' . $e->getMessage());
            return redirect()->route('admin.units.index')
                ->with('error', 'Unidad no encontrada');
        }
    }

    public function update(Request $request, $id)
    {
        // Validación condicional: si NO es base, compatible_base_unit_id es opcional (para genéricos)
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s0-9]+$/'],
            'symbol' => ['required', 'string', 'min:1', 'max:10', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s0-9]+$/'],
            'is_base' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'default_conversion_factor' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
        ];

        if (!$request->boolean('is_base')) {
            $rules['compatible_base_unit_id'] = ['nullable', 'integer', 'exists:units,id'];
        } else {
            $rules['compatible_base_unit_id'] = ['nullable', 'integer', 'exists:units,id'];
        }

        $request->validate($rules, [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.regex' => 'El símbolo solo puede contener letras, números y espacios.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
            'compatible_base_unit_id.exists' => 'La unidad de consumo seleccionada no es válida.',
            'default_conversion_factor.numeric' => 'La cantidad por unidad debe ser un número.',
        ]);

        try {
            $unit = Unit::where('activo', true)->findOrFail($id);

            $name = mb_strtoupper(trim($request->name));
            $slug = Str::slug($request->name);
            $symbol = mb_strtolower(trim($request->symbol));

            $this->fillUnitFromRequest($unit, $request, $name, $slug, $symbol);

            if (!$unit->isDirty()) {
                return redirect()->route('admin.units.index')
                    ->with('info', 'No se realizaron cambios');
            }

            $unit->save();

            return redirect()->route('admin.units.index')
                ->with('success', 'Unidad actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar unidad: ' . $e->getMessage(), [
                'unit_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('admin.units.index')
                ->with('error', 'Error al actualizar la unidad');
        }
    }

    /**
     * Rellena los campos de la unidad desde el request.
     * Determina automáticamente el unit_type basado en:
     * - is_base = true → CANONICAL
     * - is_base = false + default_conversion_factor → METRIC_PACK
     * - is_base = false + sin factor → LOGISTIC
     */
    private function fillUnitFromRequest(Unit $unit, Request $request, string $name, string $slug, string $symbol): void
    {
        $unit->name = $name;
        $unit->slug = $slug;
        $unit->symbol = $symbol;
        $unit->is_base = $request->boolean('is_base');
        $unit->sort_order = $request->input('sort_order', 0);

        if ($unit->is_base) {
            // CANONICAL: unidad de consumo
            $unit->unit_type = UnitType::CANONICAL;
            $unit->compatible_base_unit_id = null;
            $unit->default_conversion_factor = null;
        } else {
            // Compra o Presentación
            $unit->compatible_base_unit_id = $request->input('compatible_base_unit_id');

            $factor = $request->input('default_conversion_factor');
            if ($factor !== null && $factor !== '' && floatval($factor) > 0) {
                // METRIC_PACK: presentación con cantidad fija
                $unit->unit_type = UnitType::METRIC_PACK;
                $unit->default_conversion_factor = floatval($factor);
            } else {
                // LOGISTIC: unidad de compra genérica
                $unit->unit_type = UnitType::LOGISTIC;
                $unit->default_conversion_factor = null;
            }
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
