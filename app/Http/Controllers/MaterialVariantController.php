<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialVariantRequest;
use App\Models\Material;
use App\Models\MaterialVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MaterialVariantController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX - Listar variantes de un material
    |--------------------------------------------------------------------------
    */

    public function index($materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || $materialId > 999999999) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
                ->findOrFail((int) $materialId);

            $variants = MaterialVariant::where('material_id', $material->id)
                ->where('activo', true)
                ->ordered()
                ->get();

            return view('admin.material-variants.index', compact('material', 'variants'));
        } catch (\Exception $e) {
            Log::error('Error al listar variantes de material: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
                ->with('error', 'Error al cargar las variantes');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create($materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || $materialId > 999999999) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
                ->findOrFail((int) $materialId);

            $suggestedSku = $this->generateSku($material);

            return view('admin.material-variants.create', compact('material', 'suggestedSku'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de variante: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
                ->with('error', 'Error al cargar el formulario');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(MaterialVariantRequest $request, $materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || $materialId > 999999999) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $variant = new MaterialVariant();
            $variant->uuid = (string) Str::uuid();
            $variant->material_id = $material->id;
            $variant->color = $request->filled('color') ? mb_strtoupper(trim($request->color)) : null;
            $variant->sku = mb_strtoupper(trim($request->sku));
            $variant->current_stock = 0;
            $variant->min_stock_alert = (float) $request->min_stock_alert;
            $variant->current_value = 0;
            $variant->average_cost = 0;
            $variant->last_purchase_cost = 0;
            $variant->activo = true;
            $variant->save();

            DB::commit();

            Log::info('Variante de material creada', [
                'variant_id' => $variant->id,
                'material_id' => $material->id,
                'sku' => $variant->sku,
                'color' => $variant->color,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.material-variants.index', $material->id)
                ->with('success', 'Variante creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear variante de material: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.material-variants.create', $materialId)
                ->withInput()
                ->with('error', 'Error al crear la variante');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
                ->findOrFail((int) $materialId);

            $variant = MaterialVariant::where('material_id', $material->id)
                ->where('activo', true)
                ->findOrFail((int) $id);

            return view('admin.material-variants.edit', compact('material', 'variant'));
        } catch (\Exception $e) {
            Log::error('Error al cargar variante para editar: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'variant_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
                ->with('error', 'Variante no encontrada');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(MaterialVariantRequest $request, $materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $variant = MaterialVariant::where('material_id', $material->id)
                ->where('activo', true)
                ->findOrFail((int) $id);

            $oldSku = $variant->sku;

            $variant->color = $request->filled('color') ? mb_strtoupper(trim($request->color)) : null;
            $variant->sku = mb_strtoupper(trim($request->sku));
            $variant->min_stock_alert = (float) $request->min_stock_alert;

            if (!$variant->isDirty()) {
                return redirect()->route('admin.material-variants.index', $material->id)
                    ->with('info', 'No se realizaron cambios');
            }

            $variant->save();

            DB::commit();

            Log::info('Variante de material actualizada', [
                'variant_id' => $variant->id,
                'material_id' => $material->id,
                'old_sku' => $oldSku,
                'new_sku' => $variant->sku,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.material-variants.index', $material->id)
                ->with('success', 'Variante actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar variante: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'variant_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.material-variants.edit', [$materialId, $id])
                ->withInput()
                ->with('error', 'Error al actualizar la variante');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM DELETE
    |--------------------------------------------------------------------------
    */

    public function confirmDelete($materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
                ->findOrFail((int) $materialId);

            $variant = MaterialVariant::where('material_id', $material->id)
                ->where('activo', true)
                ->findOrFail((int) $id);

            return view('admin.material-variants.delete', compact('material', 'variant'));
        } catch (\Exception $e) {
            Log::error('Error al cargar variante para eliminar: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'variant_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
                ->with('error', 'Variante no encontrada');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $variant = MaterialVariant::where('material_id', $material->id)
                ->where('activo', true)
                ->findOrFail((int) $id);

            // Validar que no tenga stock
            if ($variant->current_stock > 0) {
                return redirect()->route('admin.material-variants.index', $material->id)
                    ->with('error', 'No se puede eliminar: la variante tiene stock disponible (' . number_format($variant->current_stock, 2) . ')');
            }

            $variantSku = $variant->sku;

            $variant->activo = false;
            $variant->save();

            DB::commit();

            Log::info('Variante de material eliminada', [
                'variant_id' => $variant->id,
                'material_id' => $material->id,
                'sku' => $variantSku,
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return redirect()->route('admin.material-variants.index', $material->id)
                ->with('success', 'Variante eliminada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar variante: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'variant_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.material-variants.index', $materialId)
                ->with('error', 'Error al eliminar la variante');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: Obtener variantes por material
    |--------------------------------------------------------------------------
    */

    public function getByMaterial($materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1) {
                return response()->json(['error' => 'Material no válido'], 400);
            }

            $variants = MaterialVariant::where('material_id', (int) $materialId)
                ->where('activo', true)
                ->ordered()
                ->get(['id', 'color', 'sku', 'current_stock', 'average_cost']);

            return response()->json($variants);
        } catch (\Exception $e) {
            Log::error('Error AJAX getByMaterial: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener variantes'], 500);
        }
    }

    public function getByMaterial2($materialId)
    {
        try {
            $variants = MaterialVariant::where('material_id', (int) $materialId)
                ->where('activo', true)
                ->with([
                    'material.baseUnit',
                    'material.consumptionUnit'
                ])
                ->ordered()
                ->get();

            $results = $variants->map(function ($v) {
                // El material ahora tiene la info de unidades directo
                $material = $v->material;
                $baseUnit = $material->baseUnit;
                $consumoUnit = $material->consumptionUnit;

                // Factor de conversión (ej: 1 Cono = 5000 Metros)
                // Si base y consumo son diferentes, usar el factor del material
                // conversión: base_qty * factor = consumption_qty
                $factor = (float) $material->conversion_factor ?: 1.0;

                // Datos crudos
                $stockBase = (float) $v->current_stock; // Stock en unidad base (ej: Conos)
                $costBase = (float) $v->average_cost;   // Costo por unidad base

                // Calcular equivalentes en unidad de consumo si es necesario
                // OJO: La lógica de negocio depende de cómo quieran VER el stock
                // Por ahora devolvemos el stock base, y el símbolo base

                $simbolo = $baseUnit->symbol ?? 'unid';

                return [
                    'id' => $v->id,
                    'text' => "{$material->name} - {$v->color} ({$v->sku})",
                    'family_name' => $material->name,
                    'variant_name' => $v->color,
                    'sku' => $v->sku,
                    'stock_real' => number_format($stockBase, 4, '.', ''),
                    'cost_base' => number_format($costBase, 4, '.', ''),
                    'symbol' => $simbolo,
                    'factor' => $factor,
                    'consumption_symbol' => $consumoUnit->symbol ?? $simbolo,
                    'stock_display' => "Stock: " . number_format($stockBase, 2) . " {$simbolo}"
                ];
            });

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Error en getByMaterial: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar datos'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE: Generar SKU sugerido
    |--------------------------------------------------------------------------
    */

    private function generateSku(Material $material): string
    {
        $categoryPrefix = mb_strtoupper(mb_substr($material->category->slug ?? 'MAT', 0, 3));
        $materialPrefix = mb_strtoupper(mb_substr(Str::slug($material->name), 0, 4));

        $lastVariant = MaterialVariant::where('material_id', $material->id)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastVariant) {
            preg_match('/(\d+)$/', $lastVariant->sku, $matches);
            if (!empty($matches[1])) {
                $sequence = (int) $matches[1] + 1;
            }
        }

        return strtoupper("{$categoryPrefix}-{$materialPrefix}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT));
    }
}
