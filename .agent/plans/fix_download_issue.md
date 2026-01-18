# Plan de Solución Definitiva para Descargas

El problema actual es que al intentar descargar el archivo, el navegador recibe un documento HTML (posiblemente una página de error o contenido corrupto) en lugar del archivo binario, guardándolo como `download.htm`.

Para solucionar esto, implementaremos un enfoque "bare-metal" usando la respuesta directa de archivos de Laravel (`response()->download`), evitando intermediarios que puedan estar fallando silenciosamente o corrompiendo el flujo.

## Pasos de Implementación

1.  **Limpiar Buffer de Salida**: Antes de enviar el archivo, forzaremos la limpieza de cualquier buffer de salida (`ob_end_clean()`) para asegurar que no se inyecte HTML o espacios en blanco en el archivo descargado.
2.  **Uso de Ruta Absoluta**: En lugar de confiar en el facade `Storage`, resolveremos la ruta absoluta del archivo en el sistema de archivos del servidor y usaremos esa ruta directa.
3.  **Respuesta Directa**: Usaremos `return response()->download($path, $name, $headers)` que maneja los encabezados HTTP de forma más estricta para forzar la descarga correcta.
4.  **Headers Explícitos**: Añadiremos encabezados `Content-Type` explícitos.

## Cambios en Archivos

### `app/Http/Controllers/ProduccionController.php`

Modificaremos el método `download` para:
- Validar existencia física usando rutas absolutas.
- Limpiar buffers.
- Retornar la descarga directa.

```php
public function download($id)
{
    try {
        $export = DesignExport::findOrFail($id);
        
        // Obtener ruta absoluta
        $relativePath = $export->file_path;
        // Asegurar que no tenga slash inicial extra si ya lo tiene
        $relativePath = ltrim($relativePath, '/');
        $absolutePath = storage_path('app/public/' . $relativePath);

        if (!file_exists($absolutePath)) {
            Log::error('Archivo no encontrado físico:', ['path' => $absolutePath]);
            return back()->with('error', 'El archivo físico no existe en el servidor.');
        }

        // Limpiar cualquier salida previa (espacios, html, errores)
        if (ob_get_length()) ob_end_clean();

        // Sanitizar nombre
        $filename = $export->file_name ?: basename($absolutePath);
        // ASCII only para evitar problemas de headers
        $filename = \Illuminate\Support\Str::ascii($filename);
        
        // Headers para forzar descarga binaria
        $headers = [
            'Content-Type' => 'application/octet-stream',
        ];

        return response()->download($absolutePath, $filename, $headers);

    } catch (\Exception $e) {
        Log::error('Error crítico descarga:', ['msg' => $e->getMessage()]);
        return back()->with('error', 'Error del sistema: ' . $e->getMessage());
    }
}
```

## Verificación
- El usuario deberá probar el botón de descarga nuevamente.
- Al usar `target="_blank"` (que ya añadimos), si ocurre un error, se verá en la nueva pestaña en lugar de descargarse un `.htm`. Si la descarga es correcta, la pestaña se cerrará o quedará en segundo plano mientras baja el archivo.
