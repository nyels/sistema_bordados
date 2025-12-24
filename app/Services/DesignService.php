<?php

namespace App\Services;

use App\Models\Design;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;

class DesignService
{
    /**
     * Crear o reactivar un diseño
     *
     * @param array $data
     * @return Design
     * @throws \Throwable
     */
    public function createDesign(array $data): Design
    {
        DB::beginTransaction();

        try {
            // Generar slug ÚNICO
            $slugBase = Str::slug($data['name']);
            $slug = $slugBase;
            $counter = 1;

            // Garantizar unicidad incluso con soft deletes
            while (
                Design::withTrashed()->where('slug', $slug)->exists()
            ) {
                $slug = $slugBase . '-' . $counter++;
            }

            $design = Design::create([
                'name'        => $data['name'],
                'slug'        => $slug,
                'description' => $data['description'] ?? null,
                'is_active'   => 1,
            ]);

            if (isset($data['categories'])) {
                $design->categories()->sync($data['categories']);
            }

            DB::commit();

            return $design->load('categories');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear diseño', [
                'data'      => $data,
                'exception' => $e,
            ]);

            throw $e;
        }
    }


    /**
     * Actualizar diseño existente
     *
     * @param Design $design
     * @param array $data
     * @return Design
     * @throws \Throwable
     */
    public function updateDesign(Design $design, array $data): Design
    {
        DB::beginTransaction();

        try {
            if (isset($data['name']) && $design->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
            }

            $design->update($data);

            if (isset($data['categories'])) {
                $design->categories()->sync($data['categories']);
            }

            DB::commit();

            return $design->fresh(['categories']);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al actualizar diseño', [
                'design_id' => $design->id,
                'data'      => $data,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Eliminar diseño (soft delete)
     *
     * @param Design $design
     * @return bool
     * @throws \Throwable
     */
    public function deleteDesign(Design $design): bool
    {
        DB::beginTransaction();

        try {
            foreach ($design->images as $image) {
                app(ImageService::class)->deleteImage($image);
            }

            $design->update(['is_active' => 0]);

            $deleted = $design->delete();

            DB::commit();

            return $deleted;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al eliminar diseño', [
                'design_id' => $design->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Duplicar diseño con variantes
     *
     * @param Design $design
     * @return Design
     * @throws \Throwable
     */
    public function duplicateDesign(Design $design): Design
    {
        DB::beginTransaction();

        try {
            $newDesign = $design->replicate();
            $newDesign->name = $design->name . ' (Copia)';
            $newDesign->slug = Str::slug($newDesign->name);
            $newDesign->is_active = 1;
            $newDesign->save();

            $newDesign->categories()->sync(
                $design->categories->pluck('id')
            );

            foreach ($design->variants as $variant) {
                $newVariant = $variant->replicate();
                $newVariant->design_id = $newDesign->id;
                $newVariant->sku = $variant->sku . '-COPY';
                $newVariant->save();

                $newVariant->attributeValues()->sync(
                    $variant->attributeValues->pluck('id')
                );
            }

            DB::commit();

            return $newDesign->load(['categories', 'variants']);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al duplicar diseño', [
                'design_id' => $design->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
