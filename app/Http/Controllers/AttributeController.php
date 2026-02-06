<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::orderBy('order')->get();
        $attributeValues = AttributeValue::with('attribute')->orderBy('order')->get();
        return view('admin.attributes.index', compact('attributes', 'attributeValues'));
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:attributes,name'
            ],
            'type' => ['nullable', 'in:select,color,text'],
            'is_required' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999']
        ], [
            'name.required' => 'El nombre del atributo es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.unique' => 'Ya existe un atributo con este nombre.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
        ]);

        DB::beginTransaction();
        try {
            $attribute = Attribute::create([
                'name' => mb_strtoupper(trim($request->name), 'UTF-8'),
                'slug' => Str::slug($request->name),
                'type' => 'select',
                'is_required' => $request->boolean('is_required'),
                'order' => $request->order ?? 0,
            ]);

            DB::commit();

            Log::info('[Attribute@store] Atributo creado', ['id' => $attribute->id, 'user_id' => Auth::id()]);

            $msg = 'Atributo creado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $attribute]);
            }
            return redirect()->route('admin.attributes.index')->with('success', $msg);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Attribute@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al crear el atributo';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.attributes.index')->with('error', $msg);
        }
    }

    public function show(Attribute $attribute)
    {
        return redirect()->route('admin.attributes.index');
    }

    public function edit($id)
    {
        $attribute = Attribute::findOrFail($id);
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:attributes,name,' . $id
            ],
            'type' => ['in:select,color,text'],
            'is_required' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999']
        ], [
            'name.required' => 'El nombre del atributo es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.unique' => 'Ya existe un atributo con este nombre.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
        ]);

        DB::beginTransaction();
        try {
            $attribute = Attribute::findOrFail($id);

            $attribute->fill([
                'name' => mb_strtoupper(trim($request->name), 'UTF-8'),
                'slug' => Str::slug($request->name),
                'type' => 'select',
                'is_required' => $request->boolean('is_required'),
                'order' => $request->order ?? 0,
            ]);

            if (!$attribute->isDirty()) {
                DB::rollBack();
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.attributes.index')->with('info', $msg);
            }

            $attribute->save();
            DB::commit();

            Log::info('[Attribute@update] Atributo actualizado', ['id' => $attribute->id, 'user_id' => Auth::id()]);

            $msg = 'Atributo actualizado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $attribute]);
            }
            return redirect()->route('admin.attributes.index')->with('success', $msg);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Attribute@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el atributo';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.attributes.index')->with('error', $msg);
        }
    }

    public function confirm_delete($id)
    {
        $attribute = Attribute::with('values')->findOrFail($id);
        return view('admin.attributes.delete', compact('attribute'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        DB::beginTransaction();
        try {
            $attribute = Attribute::with('values')->findOrFail($id);
            $attributeName = $attribute->name;
            $valuesIds = $attribute->values->pluck('id');

            $isInUse = DB::table('design_variant_attributes')
                ->whereIn('attribute_value_id', $valuesIds)
                ->exists();

            if ($isInUse) {
                DB::rollBack();
                $msg = "No se puede eliminar '$attributeName' porque sus valores están asignados a variantes de diseño activas.";
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.attributes.index')->with('error', $msg);
            }

            if ($attribute->values()->exists()) {
                $attribute->values()->delete();
            }

            $attribute->delete();

            DB::commit();

            Log::info('[Attribute@destroy] Atributo eliminado', [
                'id' => $id,
                'name' => $attributeName,
                'values_count' => $valuesIds->count(),
                'user_id' => Auth::id()
            ]);

            $msg = 'Atributo y sus valores asociados eliminados correctamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.attributes.index')->with('success', $msg);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Attribute@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el atributo';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.attributes.index')->with('error', $msg);
        }
    }
}
