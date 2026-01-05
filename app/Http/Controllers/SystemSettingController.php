<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SystemSettingController extends Controller
{
    private const ALLOWED_GROUPS = ['general', 'inventario', 'facturacion', 'produccion'];

    public function index(Request $request)
    {
        try {
            $request->validate([
                'group' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z]+$/'],
            ]);

            $groups = SystemSetting::getGroups();
            $activeGroup = $request->get('group', 'general');

            if (!in_array($activeGroup, self::ALLOWED_GROUPS, true)) {
                $activeGroup = 'general';
            }

            $settings = SystemSetting::getByGroup($activeGroup);

            return view('admin.settings.index', compact('groups', 'activeGroup', 'settings'));
        } catch (\Exception $e) {
            Log::error('Error al cargar configuraciones: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);
            return view('admin.settings.index', [
                'groups' => [],
                'activeGroup' => 'general',
                'settings' => collect()
            ])->with('error', 'Error al cargar las configuraciones');
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'group' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z]+$/',
                function ($attribute, $value, $fail) {
                    if (!in_array($value, self::ALLOWED_GROUPS, true)) {
                        $fail('Grupo de configuración no válido.');
                    }
                },
            ],
            'settings' => ['required', 'array', 'max:50'],
            'settings.*' => ['nullable'], // Quitamos la restricción de string para permitir archivos
        ]);

        $group = $request->input('group');

        try {
            DB::beginTransaction();

            $updated = 0;
            $settingsInput = $request->input('settings', []);

            // Obtener solo las keys válidas del grupo actual
            $validKeys = SystemSetting::where('group', $group)
                ->pluck('key')
                ->toArray();

            foreach ($settingsInput as $key => $value) {
                // 1. Validar que la key pertenezca al grupo
                if (!in_array($key, $validKeys, true)) {
                    continue;
                }

                $setting = SystemSetting::where('key', $key)
                    ->where('group', $group)
                    ->first();

                if (!$setting) {
                    continue;
                }

                // MANEJO DE ARCHIVOS (IMÁGENES)
                if ($setting->type === 'image') {
                    if ($request->hasFile("settings.$key")) {
                        $file = $request->file("settings.$key");

                        // Validación manual del archivo
                        if (!$file->isValid()) {
                            continue; // O lanzar error
                        }

                        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                        if (!in_array($file->getMimeType(), $allowedMimes)) {
                            DB::rollBack();
                            return redirect()->back()->with('error', 'Formato de imagen no válido para ' . $setting->label);
                        }

                        // Eliminar imagen anterior si existe
                        if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                            Storage::disk('public')->delete($setting->value);
                        }

                        // Guardar nueva imagen
                        $path = $file->store('settings', 'public');
                        $sanitizedValue = $path;
                    } else {
                        // Si no se subió archivo pero viene "null" o vacío, ¿borramos?
                        // Por UX del Dropzone, si el usuario da "eliminar", podríamos enviar un input hidden vacío.
                        // Asumiremos que si viene vacío string, es borrar. Si no viene nada, es mantener.
                        // Pero el request->input incluye todo. 

                        // Si el valor es una cadena vacía y antes había algo, significa borrar?
                        // Vamos a manejar que si viene texto "DELETE_IMAGE", borramos.
                        if ($value === '__DELETE__') {
                            if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                                Storage::disk('public')->delete($setting->value);
                            }
                            $sanitizedValue = null;
                        } else {
                            // Si no es archivo ni flag de borrado, mantenemos el valor actual (no actualizar)
                            continue;
                        }
                    }
                } else {
                    // 2. Sanitizar y validar según tipo (NO IMAGEN)
                    $sanitizedValue = $this->sanitizeValue($value, $setting);
                }

                // CORRECCIÓN: Si el valor es inválido, informamos la causa exacta
                if ($sanitizedValue === false) {
                    DB::rollBack();
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'El formato para ' . $setting->label . ' es inválido o contiene caracteres no permitidos.');
                }

                if ($setting->value !== $sanitizedValue) {
                    $setting->value = $sanitizedValue;
                    $setting->updated_by = Auth::id();
                    $setting->save();

                    Cache::forget("setting_{$key}");
                    $updated++;

                    Log::info('Configuración actualizada', [
                        'key' => $key,
                        'old_value' => $setting->getOriginal('value'),
                        'new_value' => $sanitizedValue,
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            if ($updated === 0) {
                return redirect()
                    ->route('settings.index', ['group' => $group])
                    ->with('info', 'No se detectaron cambios en la configuración.');
            }

            return redirect()
                ->route('settings.index', ['group' => $group])
                ->with('success', 'Se actualizaron ' . $updated . ' configuraciones correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar configuraciones: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error inesperado al procesar la solicitud.');
        }
    }

    /**
     * Sanitiza y valida el valor según el tipo de configuración
     */
    private function sanitizeValue(?string $value, SystemSetting $setting): string|false
    {
        if ($value === null) {
            $value = '';
        }

        $value = strip_tags($value);
        $value = trim($value);

        switch ($setting->type) {
            case 'boolean':
                return in_array($value, ['1', '0', 'true', 'false', ''], true)
                    ? ($value === '1' || $value === 'true' ? '1' : '0')
                    : false;

            case 'integer':
                if ($value === '') return '0';
                if (!preg_match('/^[0-9]{1,10}$/', $value)) {
                    return false;
                }
                return (string) (int) $value;

            case 'select':
                $options = $setting->options ?? [];
                return array_key_exists($value, $options) ? $value : false;

            case 'string':
            default:
                if ($value === '') return '';

                // Regex actualizado: Acepta eñes, acentos, y caracteres como * . [ ] ( ) # @ $ % &
                // El flag /u es indispensable para soporte UTF-8
                $pattern = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.,\/()\[\]*#@$%&!]{1,255}$/u';

                if (!preg_match($pattern, $value)) {
                    return false;
                }
                if (!preg_match($pattern, $value)) {
                    return false;
                }
                return $value;

            case 'image':
                // Para tipos imagen, sanitizeValue no se usa para el upload, 
                // pero por seguridad retornamos null si llega aquí por error.
                return $value;
        }
    }
}
