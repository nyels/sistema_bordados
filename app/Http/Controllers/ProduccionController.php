<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DesignExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProduccionController extends Controller
{
    /**
     * Muestra la lista de todas las producciones.
     * Este método obtiene los registros de la base de datos y los envía a la vista principal.
     */
    public function index()
    {
        try {
            // Obtenemos las exportaciones que no han sido eliminadas, con sus relaciones
            $exportaciones = DesignExport::whereNull('deleted_at')
                ->with(['design', 'variant', 'creator'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Retornamos la vista con los datos
            return view('admin.produccion.index', compact('exportaciones'));
        } catch (\Exception $e) {
            // Si ocurre un error, lo registramos y notificamos al usuario
            Log::error('Error en index de producción: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al cargar las producciones: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para crear una nueva producción.
     * Aquí cargamos los diseños disponibles para que el usuario pueda elegir uno.
     */
    public function create()
    {
        try {
            // Obtenemos todos los diseños ordenados por nombre
            $designs = \App\Models\Design::orderBy('name')->get();
            return view('admin.produccion.create', compact('designs'));
        } catch (\Exception $e) {
            Log::error('Error en create de producción: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al cargar el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Guarda la nueva producción en la base de datos.
     * Este método valida los datos del formulario y crea el registro.
     */
    public function store(Request $request)
    {
        try {
            // Validamos que los datos sean correctos
            $request->validate([
                'design_id' => 'required|exists:designs,id',
                'file' => 'nullable|file|max:51200', // Máximo 50MB
                'notes' => 'nullable|string',
            ]);

            // Preparamos los datos básicos
            $data = [
                'design_id' => $request->design_id,
                'status' => 'borrador', // Estado inicial
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ];

            // Si se subió un archivo, lo procesamos
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('exports', 'public'); // Guardamos en la carpeta 'exports'

                Log::info('Archivo de producción subido', ['original_name' => $file->getClientOriginalName(), 'stored_path' => $path]);

                // Agregamos la información del archivo a los datos
                $data['file_path'] = $path;
                $data['file_name'] = $file->getClientOriginalName();
                $data['mime_type'] = $file->getClientMimeType();
                $data['file_size'] = $file->getSize();
            }

            // Creamos el registro en la base de datos
            DesignExport::create($data);

            // Redirigimos al usuario con un mensaje de éxito
            return redirect()->route('admin.production.index')
                ->with('success', 'Producción creada correctamente en estado Borrador.');
        } catch (\Exception $e) {
            Log::error('Error al guardar producción: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al guardar la producción: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Muestra los detalles de una producción específica.
     * Si la petición es AJAX (modal), devuelve una vista parcial.
     */
    public function show(string $id)
    {
        try {
            // Buscamos la producción por su ID con todas las relaciones necesarias
            $export = DesignExport::with([
                'design',
                'variant',
                'creator',
                'image',
                'statusHistory.changedByUser',
                'applicationType'
            ])->findOrFail($id);

            // Si la petición viene de un modal (AJAX), devolvemos solo la vista parcial
            if (request()->ajax()) {
                $type = request('type');
                if ($type === 'details') {
                    return view('admin.produccion.show_modal', compact('export'));
                }
                return view('admin.produccion.show_modal_especificaciones', compact('export'));
            }

            // Si es una visita normal, devolvemos la vista completa
            return view('admin.produccion.show', compact('export'));
        } catch (\Exception $e) {
            Log::error('Error al mostrar producción: ' . $e->getMessage());
            // Si es ajax, devolvemos error texto plano o json, si no, redirect
            if (request()->ajax()) {
                return response('Error al cargar detalles: ' . $e->getMessage(), 500);
            }
            return back()->with('error', 'Ocurrió un error al cargar los detalles: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para editar una producción existente.
     */
    public function edit(string $id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            return view('admin.produccion.edit', compact('export'));
        } catch (\Exception $e) {
            Log::error('Error en edit de producción: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al cargar el formulario de edición: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza los datos de la producción en la base de datos.
     * Permite cambiar notas y reemplazar el archivo adjunto.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validamos los datos
            $request->validate([
                'file' => 'nullable|file|max:51200',
                'notes' => 'nullable|string',
            ]);

            // Buscamos el registro
            $export = DesignExport::findOrFail($id);

            $data = [
                'notes' => $request->notes,
            ];

            // Si se sube un nuevo archivo, reemplazamos el anterior
            if ($request->hasFile('file')) {
                // Borrar archivo viejo del almacenamiento
                if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
                    Storage::disk('public')->delete($export->file_path);
                }

                // Guardar nuevo archivo
                $file = $request->file('file');
                $path = $file->store('exports', 'public');

                Log::info('Archivo de producción actualizado', ['id' => $id, 'new_path' => $path]);

                $data['file_path'] = $path;
                $data['file_name'] = $file->getClientOriginalName();
                $data['mime_type'] = $file->getClientMimeType();
                $data['file_size'] = $file->getSize();
            }

            // Actualizamos el registro
            $export->update($data);

            return redirect()->route('admin.production.index')
                ->with('success', 'Producción actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar producción: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una producción de la base de datos.
     */
    public function destroy(string $id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            // Si tuviera archivo físico, idealmente se borra también o se mantiene según política.
            // Aquí usamos SoftDeletes o delete normal.
            $export->delete();
            return back()->with('success', 'Producción eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar producción: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al eliminar: ' . $e->getMessage());
        }
    }

    // --- ACCIONES DE ESTADO ---

    /**
     * Registra el cambio de estado en el historial.
     * Helper method para evitar duplicación de código.
     */
    private function recordStatusChange(DesignExport $export, string $previousStatus, string $newStatus): void
    {
        \App\Models\DesignExportStatusHistory::create([
            'design_export_id' => $export->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::id(),
        ]);
    }

    /**
     * Cambia el estado a 'pendiente' para solicitar revisión.
     */
    public function requestApproval($id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            $previousStatus = $export->status;

            // Registrar en historial antes de actualizar
            $this->recordStatusChange($export, $previousStatus, 'pendiente');

            $export->update(['status' => 'pendiente']);
            return back()->with('success', 'Solicitud de aprobación enviada.');
        } catch (\Exception $e) {
            Log::error('Error al solicitar aprobación: ' . $e->getMessage());
            return back()->with('error', 'Error al procesar solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Aprueba la producción, registrando quién y cuándo aprobó.
     */
    public function approve($id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            $previousStatus = $export->status;

            // Registrar en historial antes de actualizar
            $this->recordStatusChange($export, $previousStatus, 'aprobado');

            $export->update([
                'status' => 'aprobado',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
            return back()->with('success', 'Producción aprobada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al aprobar producción: ' . $e->getMessage());
            return back()->with('error', 'Error al aprobar: ' . $e->getMessage());
        }
    }

    /**
     * Archiva la producción (estado final).
     */
    public function archive($id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            $previousStatus = $export->status;

            // Registrar en historial antes de actualizar
            $this->recordStatusChange($export, $previousStatus, 'archivado');

            $export->update(['status' => 'archivado']);
            return back()->with('success', 'Producción archivada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al archivar producción: ' . $e->getMessage());
            return back()->with('error', 'Error al archivar: ' . $e->getMessage());
        }
    }

    /**
     * Revierte la producción a estado 'pendiente' (útil para correcciones).
     */
    public function revert($id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            $previousStatus = $export->status;

            // Registrar en historial antes de actualizar
            $this->recordStatusChange($export, $previousStatus, 'pendiente');

            $export->update(['status' => 'pendiente']);
            return back()->with('success', 'Estado revertido a pendiente.');
        } catch (\Exception $e) {
            Log::error('Error al revertir producción: ' . $e->getMessage());
            return back()->with('error', 'Error al revertir: ' . $e->getMessage());
        }
    }

    /**
     * Restaura una producción archivada a estado 'aprobado'.
     */
    public function restore($id)
    {
        try {
            $export = DesignExport::findOrFail($id);
            $previousStatus = $export->status;

            // Registrar en historial antes de actualizar
            $this->recordStatusChange($export, $previousStatus, 'aprobado');

            $export->update(['status' => 'aprobado']);
            return back()->with('success', 'Producción restaurada a aprobado.');
        } catch (\Exception $e) {
            Log::error('Error al restaurar producción: ' . $e->getMessage());
            return back()->with('error', 'Error al restaurar: ' . $e->getMessage());
        }
    }

    /**
     * Descarga el archivo de la producción.
     */
    /**
     * Descarga el archivo de producción de forma directa y segura.
     * 
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($id)
    {
        $export = DesignExport::findOrFail($id);

        // 1. Resolver ruta absoluta física
        // Evitamos el facade Storage para no depender de configuraciones de discos virtuales
        // DB path: exports/2026/01/archivo.pes
        $relativePath = ltrim($export->file_path, '/');
        $absolutePath = storage_path("app/public/{$relativePath}");

        // 2. Validación estricta de existencia
        if (!file_exists($absolutePath)) {
            Log::error("Download failed 404: Path not found", ['path' => $absolutePath]);
            abort(404, 'File not found on server storage.');
        }

        // 3. Limpieza de buffers (CRÍTICO)
        // Elimina cualquier caracter invisible (espacios, saltos de línea) prevenientes de includes
        // que corrompen el binario y causan que el navegador lo detecte como texto/html
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 4. Preparar nombre y headers
        // ASCII mapping for safe Content-Disposition
        $filename = \Illuminate\Support\Str::ascii($export->file_name ?: basename($absolutePath));

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($absolutePath),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        // 5. Retorno directo de archivo
        return response()->download($absolutePath, $filename, $headers);
    }
}
