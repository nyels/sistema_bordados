# INFORME COMPARATIVO DE ARQUITECTURA UX (V1 vs V2 REFINADA)

**Estado:** IMPLEMENTADO
**Fecha de Implementación:** 2026-01-17
**Documentos Base:**
1.  Propuesta Anterior (`ANALISIS_UX_SIMPLIFICACION.md`)
2.  Nuevos Requerimientos (UX Lead / ERP SaaS)

---

## 1. PRINCIPALES DIFERENCIAS CONCEPTUALES

| Dimension | Propuesta V1 (Anterior) | Propuesta V2 (Refinada / Actual) | Veredicto |
| :--- | :--- | :--- | :--- |
| **Inferencia de Unidad Base** | **Agresiva:** Ocultar selector si solo hay 1 opcion canonica posible. | **Balanceada:** Mostrar selector siempre, pero filtrado inteligente. El usuario confirma explicitamente "Unidad de Inventario". | **V2 Gana:** Mas seguro para ERP. La inferencia total es riesgosa si la configuracion maestra falla. |
| **Semantica** | "Consumo" vs "Compra" | **"Inventario" vs "Presentacion"**. | **V2 Gana:** "Inventario" es termino contable estandar. "Consumo" puede confundirse con Materia Prima vs MP Disp. |
| **Flujo de Alta Material** | Enfocado en velocidad (menos clics). | Enfocado en **Precision y Control**. Se mantiene la decision explicita de la unidad base. | **V2 Gana:** En sistemas empresariales, la precision > velocidad. Un error en unidad de inventario es fatal. |
| **Integridad de Datos** | Dependia de "limpieza" en configuracion de categorias. | Robusto ante configuraciones "sucias" porque el usuario sigue teniendo la ultima palabra (guiada). | **V2 Gana:** Menor riesgo de corrupcion de datos por error humano en config. |

---

## 2. IMPLEMENTACION REALIZADA

### 2.1 Cambios en Terminologia (UnitType Enum)

| Valor Interno | Label V1 | Label V2 (Implementado) |
|---------------|----------|-------------------------|
| `canonical` | Consumo | **Inventario** |
| `logistic` | Compra | Compra |
| `metric_pack` | Presentacion | Presentacion |

**Archivo:** `app/Enums/UnitType.php`

### 2.2 Nuevos Campos en MaterialCategory

```sql
ALTER TABLE material_categories ADD COLUMN default_inventory_unit_id INT NULL;
ALTER TABLE material_categories ADD COLUMN allow_unit_override BOOLEAN DEFAULT TRUE;
```

- `default_inventory_unit_id`: Unidad de inventario por defecto para la categoria (FK a units)
- `allow_unit_override`: Permite que materiales usen una unidad diferente a la por defecto

**Archivo:** `database/migrations/2026_01_17_200000_add_inventory_config_to_material_categories.php`

### 2.3 Nuevos Metodos en Modelo Unit

```php
// Verificar si la unidad esta en uso
public function isInUse(): bool

// Obtener informacion detallada de uso
public function getUsageInfo(): array

// Verificar si puede eliminarse (no sistema + no en uso)
public function canBeDeleted(): bool

// Verificar si puede cambiarse el tipo
public function canChangeType(): bool
```

**Archivo:** `app/Models/Unit.php`

### 2.4 Vista de Catalogo de Unidades (Agrupacion Visual)

Nueva estructura con 3 secciones:
1. **Unidades de Inventario** (canonical) - Badge verde
2. **Unidades de Compra** (logistic) - Badge azul
3. **Presentaciones con Contenido Fijo** (metric_pack) - Badge amarillo

**Archivo:** `resources/views/admin/units/index.blade.php`

### 2.5 Wizard de Material (3 Pasos)

| Paso | Titulo | Contenido |
|------|--------|-----------|
| 1 | Datos | Categoria, Nombre, Composicion, has_color |
| 2 | Inventario | Selector de unidad de inventario (filtrado por categoria) |
| 3 | Presentaciones | Wizard de conversiones con lenguaje natural |

**Caracteristicas:**
- Guardado transaccional (Material + Conversiones en una sola operacion)
- Pregunta en lenguaje natural: "?Cuantos metros tiene cada cono?"
- Unidad destino pre-fijada automaticamente
- Opcion de omitir paso 3 (configurar despues)

**Archivos:**
- `resources/views/admin/materials/create-wizard.blade.php`
- `app/Http/Controllers/MaterialController.php` (metodos `createWizard`, `storeWizard`)

### 2.6 Rutas Agregadas

```php
Route::get('admin/materials/create-wizard', ...)->name('admin.materials.create-wizard');
Route::post('admin/materials/store-wizard', ...)->name('admin.materials.store-wizard');
```

---

## 3. ARCHIVOS MODIFICADOS

| Archivo | Tipo de Cambio |
|---------|----------------|
| `app/Enums/UnitType.php` | Labels actualizados |
| `app/Models/Unit.php` | Nuevos metodos `isInUse()`, `getUsageInfo()`, `canBeDeleted()`, `canChangeType()` |
| `app/Models/MaterialCategory.php` | Nuevos campos y relacion `defaultInventoryUnit()` |
| `app/Http/Controllers/MaterialController.php` | Nuevos metodos `createWizard()`, `storeWizard()` |
| `app/Http/Controllers/MaterialCategoryController.php` | Carga de `inventoryUnits` en create/edit |
| `app/Http/Requests/MaterialCategoryRequest.php` | Nuevas reglas de validacion |
| `resources/views/admin/units/index.blade.php` | Nueva vista con agrupacion |
| `resources/views/admin/materials/index.blade.php` | Boton "Nuevo Material" apunta al wizard |
| `resources/views/admin/materials/create-wizard.blade.php` | **NUEVO** - Wizard 3 pasos |
| `resources/views/admin/material-categories/create.blade.php` | Nuevos campos |
| `resources/views/admin/material-categories/edit.blade.php` | Nuevos campos |
| `routes/web.php` | Nuevas rutas del wizard |
| `database/migrations/2026_01_17_200000_...` | **NUEVA** - Migracion |

---

## 4. GARANTIAS DE INTEGRIDAD

### 4.1 Inventario NO Se Rompe

| Tabla | Cambio | Impacto |
|-------|--------|---------|
| `units` | NINGUNO | Sin cambios |
| `material_categories` | 2 campos nuevos NULLABLE | Retrocompatible |
| `category_unit` | NINGUNO | Sin cambios |
| `materials` | NINGUNO | Sin cambios |
| `material_unit_conversions` | NINGUNO | Sin cambios |
| `material_variants` | NINGUNO | Sin cambios |

### 4.2 Flujo de Compra

El flujo de compra sigue funcionando EXACTAMENTE igual:
1. Usuario compra en CONO/ROLLO/etc.
2. Sistema busca conversion a unidad de inventario
3. Inventario se actualiza en unidad de inventario
4. Costos se calculan correctamente

---

## 5. CHECKLIST DE SEGURIDAD SAAS (CUMPLIMIENTO V2)

- [x] **Auditable:** Cada decision de unidad queda registrada explicitamente por el usuario.
- [x] **Modelo de Datos:** 100% Compatible (No cambia tablas criticas).
- [x] **Escalabilidad:** Soporta N presentaciones para 1 material.
- [x] **Claridad Semantica:** Diferenciacion clara entre LO QUE TENGO (Metro) y COMO ME LLEGA (Caja).
- [x] **Guardado Transaccional:** Material + conversiones en una sola operacion atomica.
- [x] **Bloqueo por Uso Real:** Unidades se bloquean cuando estan en uso, no por tipo.

---

## 6. PROXIMOS PASOS (OPCIONALES)

1. **Test E2E:** Crear tests automatizados para el wizard
2. **Video Tutorial:** Documentar el nuevo flujo para usuarios
3. **Deprecacion V1:** Despues de 2 semanas, considerar eliminar `/materials/create` antiguo
4. **Migracion de Datos:** Opcional - asignar `default_inventory_unit_id` a categorias existentes

---

## 7. CONCLUSION

La **Propuesta V2 (Refinada)** ha sido implementada exitosamente. El sistema ahora ofrece:

- **UX mas guiada:** Wizard de 3 pasos con lenguaje natural
- **Terminologia clara:** "Unidad de Inventario" en lugar de "Unidad Base de Consumo"
- **Control granular:** Override de unidad por material configurable por categoria
- **Seguridad:** Bloqueo de unidades basado en uso real, no en tipo

**"Lo implicito es enemigo de lo exacto"** - El sistema ahora es explicito donde importa.
