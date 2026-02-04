<?php

namespace App\Http\Controllers;

use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UrgencyLevelController extends Controller
{
    /**
     * Listado de niveles de urgencia
     */
    public function index()
    {
        $urgencyLevels = UrgencyLevel::where('activo', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.urgency-levels.index', compact('urgencyLevels'));
    }

    /**
     * Formulario para crear nuevo nivel
     */
    public function create()
    {
        return view('admin.urgency-levels.create');
    }

    /**
     * Guardar nuevo nivel
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'time_percentage' => 'required|integer|min:1|max:200',
            'price_multiplier' => 'required|numeric|min:0.5|max:5',
            'color' => 'required|string|max:20',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            // Generar slug único
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (UrgencyLevel::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            UrgencyLevel::create([
                'name' => mb_strtoupper($validated['name']),
                'slug' => $slug,
                'time_percentage' => $validated['time_percentage'],
                'price_multiplier' => $validated['price_multiplier'],
                'color' => $validated['color'],
                'icon' => $validated['icon'] ?? null,
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'activo' => true,
            ]);

            return redirect()
                ->route('admin.urgency-levels.index')
                ->with('success', 'Nivel de urgencia creado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error al crear nivel de urgencia: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el nivel de urgencia.');
        }
    }

    /**
     * Formulario para editar nivel
     */
    public function edit($id)
    {
        $urgencyLevel = UrgencyLevel::findOrFail($id);
        return view('admin.urgency-levels.edit', compact('urgencyLevel'));
    }

    /**
     * Actualizar nivel
     */
    public function update(Request $request, $id)
    {
        $urgencyLevel = UrgencyLevel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'time_percentage' => 'required|integer|min:1|max:200',
            'price_multiplier' => 'required|numeric|min:0.5|max:5',
            'color' => 'required|string|max:20',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $urgencyLevel->fill([
                'name' => mb_strtoupper($validated['name']),
                'time_percentage' => $validated['time_percentage'],
                'price_multiplier' => $validated['price_multiplier'],
                'color' => $validated['color'],
                'icon' => $validated['icon'] ?? null,
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if ($urgencyLevel->isDirty()) {
                $urgencyLevel->save();
                return redirect()
                    ->route('admin.urgency-levels.index')
                    ->with('success', 'Nivel de urgencia actualizado correctamente.');
            }

            return redirect()
                ->route('admin.urgency-levels.index')
                ->with('info', 'No se detectaron cambios.');

        } catch (\Exception $e) {
            Log::error('Error al actualizar nivel de urgencia: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el nivel de urgencia.');
        }
    }

    /**
     * Confirmación de eliminación
     */
    public function confirmDelete($id)
    {
        $urgencyLevel = UrgencyLevel::findOrFail($id);
        return view('admin.urgency-levels.delete', compact('urgencyLevel'));
    }

    /**
     * Eliminar nivel (borrado lógico)
     */
    public function destroy($id)
    {
        $urgencyLevel = UrgencyLevel::findOrFail($id);

        try {
            // Verificar que no sea el único nivel activo
            $activeCount = UrgencyLevel::where('activo', true)->count();
            if ($activeCount <= 1) {
                return redirect()
                    ->route('admin.urgency-levels.index')
                    ->with('error', 'No se puede eliminar. Debe existir al menos un nivel de urgencia.');
            }

            $urgencyLevel->activo = false;
            $urgencyLevel->save();

            return redirect()
                ->route('admin.urgency-levels.index')
                ->with('success', 'Nivel de urgencia eliminado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error al eliminar nivel de urgencia: ' . $e->getMessage());
            return redirect()
                ->route('admin.urgency-levels.index')
                ->with('error', 'Error al eliminar el nivel de urgencia.');
        }
    }
}
