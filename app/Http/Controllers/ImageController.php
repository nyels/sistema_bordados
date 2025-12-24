<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Subir imagen
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|file|max:51200',
            'imageable_type' => 'required|string|in:App\Models\Design,App\Models\DesignVariant',
            'imageable_id' => 'required|integer',
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean'
        ]);

        $image = $this->imageService->uploadImage(
            $request->file('image'),
            $validated['imageable_type'],
            $validated['imageable_id'],
            [
                'alt_text' => $validated['alt_text'] ?? null,
                'is_primary' => $validated['is_primary'] ?? false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Archivo subido exitosamente',
            'image' => [
                'id' => $image->id,
                'url' => Storage::url($image->file_path),
                'file_name' => $image->file_name,
            ]
        ]);
    }

    /**
     * Actualizar metadatos de imagen
     */
    public function update(Request $request, Image $image)
    {
        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'order' => 'nullable|integer|min:0'
        ]);

        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Imagen actualizada exitosamente',
        ]);
    }

    /**
     * Eliminar imagen
     */
    public function destroy(Image $image)
    {
        $this->imageService->deleteImage($image);

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada exitosamente'
        ]);
    }

    /**
     * Reordenar imágenes
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:images,id',
            'images.*.order' => 'required|integer|min:0'
        ]);

        $this->imageService->reorderImages($validated['images']);

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado exitosamente'
        ]);
    }
}
