# DATASHEET TÉCNICO: ARQUITECTURA DE MATERIALES Y CONVERSIONES (v3.0 - STRICT)

**Nivel de Documento:** Ingeniería de Software / Backend Developer
**Versión de Sistema:** Laravel 10 / PHP 8.2
**Fecha de Actualización:** 2026-01-17

---

## 1. MAPA DE COMPONENTES DEL SISTEMA

### 1.1 Estructura de Directorios Crítica
Archivos específicos que controlan esta lógica.

```text
/app
  /Http
    /Controllers
      /Admin
        ├── MaterialCategoryUnitController.php  (Gestión de Tabla Pivot Categoría-Unidad)
        ├── MaterialController.php              (CRUD Principal de Materiales)
        ├── MaterialUnitConversionController.php (Lógica del "Wizard" de conversión)
        └── PurchaseController.php              (Consumidor final de la lógica)
    /Requests
      ├── MaterialRequest.php                   (Validación de creación de material)
      └── MaterialUnitConversionRequest.php     (Validación de factores de conversión)
  /Models
    ├── Material.php                            (Modelo Eje)
    ├── Unit.php                                (Catálogo Maestro)
    └── MaterialUnitConversion.php              (Grafo de Relaciones)
/resources
  /views
    /admin
      /materials
        ├── create.blade.php                    (UI: Alta de Material)
      /material-conversions
        ├── create.blade.php                    (UI: Wizard Calculadora JS)
/routes
  ├── web.php                                   (Definición de Endpoints)
```

---

## 2. FLUJO TÉCNICO DETALLADO: FASE DE CONFIGURACIÓN

### FASE 1: Vinculación Categoría-Unidad (Setup Inicial)
**Objetivo:** Restringir qué unidades pueden usarse para "HILOS".

1.  **Ruta:** `GET /admin/material-categories/{id}/units`
2.  **Controlador:** `MaterialCategoryUnitController@edit`
3.  **Acción de Usuario:** Checkbox en Unidades (Metro, Cono, Caja).
4.  **Persistencia (Backend):**
    ```php
    // Archivo: app/Http/Controllers/MaterialCategoryUnitController.php
    // Método: update
    $category->allowedUnits()->sync($request->input('units')); 
    // Impacto SQL: DELETE/INSERT en tabla `category_unit`.
    ```

---

### FASE 2: Creación del Material (Definición de 'Base Unit')
**Objetivo:** Definir la unidad atómica de inventario.

1.  **Ruta:** `GET /admin/materials/create`
2.  **Vista:** `resources/views/admin/materials/create.blade.php`
3.  **Lógica AJAX (Carga de Unidades):**
    *   Al seleccionar categoría, JS llama a: `GET /admin/material-categories/{id}/units-json`
    *   **Controlador:** `MaterialCategoryController@getUnits`
    *   **Query Crítica:**
        ```php
        // Recupera TODO: Logísticas (Cajas) y Canónicas (Metros)
        // Ya no filtra por isLogistic(), permitiendo que 'Metro' sea base.
        $category->allowedUnits()->ordered()->get();
        ```
4.  **Validación (Request):**
    *   Archivo: `app/Http/Requests/MaterialRequest.php`
    *   Regla: Se eliminó la restricción `isLogistic`. Ahora acepta cualquier unidad vinculada a la categoría.

---

### FASE 3: Definición del Grafo de Conversiones (CORE)
**Objetivo:** Enseñar al sistema que "1 Caja = 24 Conos = 120,000 Metros".

#### 3.1 El Controlador (Inyección de Dependencias)
**Archivo:** `app/Http/Controllers/MaterialUnitConversionController.php`
**Método:** `create($materialId)`

El controlador prepara el terreno inyectando conversiones previas para habilitar el "Modo Wizard".

```php
// Recupera conversiones ya hechas (ej. Cono -> Metro) para usarlas de puente
$existingConversions = MaterialUnitConversion::where('material_id', $material->id)
    ->with('fromUnit')
    ->get();

return view('admin.material-conversions.create', compact(..., 'existingConversions'));
```

#### 3.2 La Vista y Lógica JavaScript (Frontend)
**Archivo:** `resources/views/admin/material-conversions/create.blade.php`

Aquí reside la "Inteligencia de UI".

1.  **Tab "Directa" (Manual):** Input simple `conversion_factor`.
    *   *Alerta de Seguridad:* Si usuario elige `metric_pack` (Caja) en este modo, JS dispara `#direct_pack_warning` (Ver líneas ~370 del blade).

2.  **Tab "Por Contenido" (Wizard Automático):**
    *   Solo visible si `existingConversions > 0`.
    *   **Inputs:**
        *   `#intermediate_unit_id` (Select: "Cono") -> `data-factor="5000"`
        *   `#intermediate_qty` (Input: "24")
    *   **Algoritmo JS (Tiempo Real):**
        ```javascript
        // Evento: change keyup
        var factorBase = 5000; // Viene del data-factor del option seleccionado
        var qty = 24;          // Input del usuario
        
        var totalBase = factorBase * qty; // 120,000
        
        // Inyección Silenciosa al Formulario Real
        $('#conversion_factor').val(totalBase.toFixed(4)); 
        ```

#### 3.3 Persistencia (Store)
**Método:** `store`
**Validación:** `app/Http/Requests/MaterialUnitConversionRequest.php`
*   Se eliminaron reglas obsoletas (`to_unit_id` must be `canonical`) para permitir flexibilidad total.
*   **Redirect:** Ahora retorna a `create` (no `index`) para permitir flujo rápido: Cono -> Guardar -> Caja -> Guardar.

---

## 4. IMPACTO EN MÓDULO DE COMPRAS (CONSUMIDOR FINAL)
**Archivo:** `app/Http/Controllers/PurchaseController.php`

Cuando se guarda una compra, el sistema "aplana" la complejidad.

1.  **Método:** `store`
2.  **Servicio:** `PurchaseService::create`
3.  **Cálculo de Inventario:**
    El sistema no guarda "Cajas" en el inventario. Transforma todo a Unidad Base al vuelo.

    ```php
    // Lógica interna simplificada
    $conversion = MaterialUnitConversion::where(...) ->first();
    $factor = $conversion->conversion_factor; // 120,000 (Calculado previamente en Fase 3)
    
    $compra->items()->create([
        'unit_id' => $cajaId,
        'quantity' => 1,
        'conversion_factor' => 120000, 
        'converted_quantity' => 120000, // IMPORTANTE: Esto es lo que suma a stock
    ]);
    ```

---

## 5. TABLA DE RUTAS CRÍTICAS (DEBUGGING)

| Acción | Verbo | URI | Controlador @ Método |
| :--- | :--- | :--- | :--- |
| **Ver Unidades de Categoría** | GET | `admin/material-categories/{id}/units` | `MaterialCategoryUnitController@edit` |
| **JSON de Unidades (AJAX)** | GET | `admin/material-categories/{id}/units-json` | `MaterialCategoryController@getUnits` |
| **Crear Material** | GET | `admin/materials/create` | `MaterialController@create` |
| **Crear Conversión** | GET | `admin/materials/{id}/conversions/create` | `MaterialUnitConversionController@create` |
| **Guardar Conversión** | POST | `admin/materials/{id}/conversions` | `MaterialUnitConversionController@store` |

---

## 6. DIAGRAMA DE RELACIONES SQL (ERD ESPECÍFICO)

```sql
-- 1. MATERIAL define su "Lenguaje Base" (Metros)
SELECT * FROM materials WHERE id = 1;
-- base_unit_id = 10 (Metro)

-- 2. CONVERSIONES definen los "Dialectos" (Cajas, Conos)
SELECT * FROM material_unit_conversions WHERE material_id = 1;
-- Row A: from_unit_id = 20 (Cono) -> to_unit_id = 10 (Metro) -> factor = 5000
-- Row B: from_unit_id = 30 (Caja) -> to_unit_id = 10 (Metro) -> factor = 120000

-- 3. COMPRAS usan el "Dialecto" pero guardan el "Significado"
SELECT * FROM purchase_items WHERE material_variant_id = ...;
-- unit_id = 30 (Caja)
-- quantity = 1
-- conversion_factor = 120000 (Copia snapshot del Row B)
-- converted_quantity = 120000 (El valor real para inventario)
```
