# DOCUMENTO MAESTRO DE ARQUITECTURA: CICLO DE VIDA DE MATERIALES Y UNIDADES (V4.0 - FULL TRACEABILITY)

**Nivel de Acceso:** Arquitecto de Software / Lead Developer
**Scope:** Trazabilidad End-to-End (Desde `Alta Unidad` hasta `Compra`)
**Framework:** Laravel 10 + Bootstrap/AdminLTE + jQuery
**Fecha:** 2026-01-17

---

## ÍNDICE DE NAVEGACIÓN

1.  [Etapa 0: Fundamentos (Base de Datos)](#etapa-0-fundamentos)
2.  [Etapa 1: Gestión de Unidades (Alta)](#etapa-1-gestión-de-unidades)
3.  [Etapa 2: Configuración de Categoría (Vinculación)](#etapa-2-configuración-de-categoría)
4.  [Etapa 3: Creación de Material (Definición Base)](#etapa-3-creación-de-material)
5.  [Etapa 4: Configuración de Conversiones (El Wizard)](#etapa-4-configuración-de-conversiones)
6.  [Etapa 5: Ejecución de Compra (Consumo)](#etapa-5-ejecución-de-compra)

---

## ETAPA 0: FUNDAMENTOS (BASE DE DATOS)
Antes de tocar código, entendamos dónde viven los datos.

### 0.1 Esquema Relacional (DDL Simplificado)

```sql
-- TABLA MAESTRA DE UNIDADES
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL, -- "Caja 24pz"
  `symbol` varchar(10) NOT NULL, -- "caja24"
  `unit_type` enum('canonical', 'logistic', 'metric_pack') NOT NULL, 
  -- canonical: Metro, Litro (Consumo real)
  -- logistic: Rollo, Cono (Presentación unitaria)
  -- metric_pack: Caja, Bulto (Contenedor)
  PRIMARY KEY (`id`)
);

-- TABLA PIVOT (NUCLEO DE LA REGLA DE NEGOCIO)
CREATE TABLE `category_unit` (
  `material_category_id` bigint(20) NOT NULL, -- "HILOS"
  `unit_id` bigint(20) NOT NULL, -- "CONO 5000m"
  PRIMARY KEY (`material_category_id`, `unit_id`)
);

-- TABLA DE CONVERSIONES (EL CEREBRO MATEMÁTICO)
CREATE TABLE `material_unit_conversions` (
  `id` bigint(20) NOT NULL,
  `material_id` bigint(20) NOT NULL, -- "Hilo Poliéster Azul"
  `from_unit_id` bigint(20) NOT NULL, -- "CAJA 24"
  `to_unit_id` bigint(20) NOT NULL, -- "METRO" (Unidad Base del Material)
  `conversion_factor` decimal(10,4) NOT NULL, -- 120000.0000
  UNIQUE KEY `unique_conversion` (`material_id`, `from_unit_id`)
);
```

---

## ETAPA 1: GESTIÓN DE UNIDADES (ALTA MAESTRA)
**Objetivo:** Registrar las piezas de lego (Metro, Cono, Caja).

### 1.1 Rutas y Controladores
*   **Ruta:** `resource('units', UnitController::class)` en `routes/web.php`
*   **Controller:** `app/Http/Controllers/Admin/UnitController.php`
*   **View:** `resources/views/admin/units/create.blade.php`

### 1.2 Lógica Crítica (Store)
El usuario define si es una unidad de "Inventario" (Canonical) o "Empaque" (Logistic).

```php
// UnitController.php@store
public function store(StoreUnitRequest $request) {
    Unit::create([
        'name' => $request->name, // "Cono 5000m"
        'unit_type' => $request->unit_type, // 'logistic'
        'symbol' => $request->symbol // 'cono5k'
    ]);
}
```

---

## ETAPA 2: CONFIGURACIÓN DE CATEGORÍA (VINCULACIÓN)
**Objetivo:** Decirle al sistema "La categoría HILOS permite usar CONOS y CAJAS".

### 2.1 Archivos Involucrados
*   **Ruta:** `GET/PUT admin/material-categories/{category}/units`
*   **Controller:** `app/Http/Controllers/Admin/MaterialCategoryUnitController.php`
*   **View:** `resources/views/admin/material-categories/units.blade.php`

### 2.2 Trazabilidad de Código
Cuando el usuario marca los checkboxes y guarda:

```php
// MaterialCategoryUnitController.php@update
public function update(Request $request, MaterialCategory $category) {
    // $request->units = [10 (Metro), 20 (Cono), 30 (Caja)]
    
    // IMPACTO SQL: 
    // DELETE FROM category_unit WHERE category_id = 1;
    // INSERT INTO category_unit (category_id, unit_id) VALUES (1, 10), (1, 20), (1, 30);
    $category->allowedUnits()->sync($request->input('units', []));
}
```

---

## ETAPA 3: CREACIÓN DE MATERIAL (DEFINICIÓN BASE)
**Objetivo:** Crear "Hilo Azul" y definir que se inventaría en "METROS".

### 3.1 Archivos Involucrados
*   **Controller:** `app/Http/Controllers/MaterialController.php`
*   **Request:** `app/Http/Requests/MaterialRequest.php`
*   **View:** `resources/views/admin/materials/create.blade.php`

### 3.2 El Flujo AJAX (El dropdown de unidades)
1.  Frontend (`create.blade.php`): Evento `change` en select de Categoría.
2.  AJAX Call: `GET /admin/material-categories/{id}/units-json`
3.  **Backend Response (`MaterialCategoryController.php@getUnits`):**
    ```php
    // Recupera SÓLO las unidades vinculadas en Etapa 2
    return $category->allowedUnits()->ordered()->get();
    // JSON: [{id: 10, name: "Metro"}, {id: 20, name: "Cono"}]
    ```
4.  Usuario selecciona "Metro".
5.  **Persistencia:** `materials.base_unit_id = 10`.

---

## ETAPA 4: CONFIGURACIÓN DE CONVERSIONES (EL WIZARD)
**Objetivo:** Enseñar al sistema que: 1 Cono = 5,000 Metros.

### 4.1 Archivos Involucrados
*   **Controller:** `app/Http/Controllers/MaterialUnitConversionController.php`
*   **View:** `resources/views/admin/material-conversions/create.blade.php` **(MODIFICADO RECIENTEMENTE)**

### 4.2 Lógica del Algoritmo "Preventivo"
El controlador inyecta la inteligencia al cargar la vista:

```php
// MaterialUnitConversionController.php@create
public function create($materialId) {
    // Busca si ya existen conversiones previas (ej. Cono)
    $existingConversions = MaterialUnitConversion::where('material_id', $materialId)->get();
    
    // Pasa esta variable a la vista para activar/desactivar el Wizard
    return view(..., compact('existingConversions'));
}
```

### 4.3 Comportamiento Condicional de la UI (`create.blade.php`)
1.  **Caso Inicial (Vacío):** `existingConversions` es NULL.
    *   Tab "Por Contenido": **Deshabilitado**.
    *   Tab "Directa": **Activo**.
    *   *Acción:* Usuario crea "Cono -> 5000 Metros" manualmente.

2.  **Caso Secundario (Cono ya existe):** `existingConversions` tiene datos.
    *   Tab "Por Contenido": **Activo por Defecto**.
    *   Tab "Directa": Oculto/Secundario.
    *   Input "Contenedor": Usuario elige "Caja".
    *   Input "Contenido": Usuario elige "Cono" (Select dinámico).
    *   Input "Cantidad": Usuario pone "24".
    *   **Cálculo JS:** `24 * 5000 = 120,000`.
    *   **Envío:** Se envía `conversion_factor = 120000` (invisible al usuario).

---

## ETAPA 5: EJECUCIÓN DE COMPRA (CONSUMO)
**Objetivo:** Comprar 10 Cajas y que el inventario suba 1,200,000 Metros.

### 5.1 Archivos Involucrados
*   **Controller:** `app/Http/Controllers/PurchaseController.php`
*   **Service:** `app/Services/PurchaseService.php`

### 5.2 Trazabilidad de Ejecución (`PurchaseController@store`)
1.  **Recepción:** Recibe `items[0][unit_id] = 30` (Caja) y `quantity = 10`.
2.  **Consulta de Factor:**
    ```php
    // Busca en la tabla material_unit_conversions
    // WHERE material_id = Hilo AND from_unit_id = Caja
    // RETURN conversion_factor = 120,000
    ```
3.  **Normalización:**
    *   Cantidad Nominal: 10 (Cajas).
    *   Cantidad Real: 10 * 120,000 = **1,200,000 (Metros)**.
4.  **Persistencia:** Guarda en `purchase_items` los 1,200,000.
5.  **Impacto en Inventario:** Cuando se recibe la compra, `EntryTransaction` suma 1,200,000 al stock del material.

---

## DIAGRAMA DE FLUJO DE DATOS (DATA FLOW)

```text
[Unit: CAJA 24] --(Etapa 2)--> [Category: HILOS] --(Etapa 3)--> [Material: HILO AZUL (Base: Metro)]
       |
       +--(Etapa 4: Wizard)--> [Conversion: 1 CAJA = 120,000 METROS]
                                      ^
                                      |
[Compra UI] --(Etapa 5)--> Selecciona "CAJA" x 2
                                      |
                                      v
                             [Backend Lookup]
                             2 * 120,000 = 240,000
                                      |
                                      v
                             [DB: INVENTARIO +240,000 METROS]
```
