# BOLETÍN DE ARQUITECTURA UX: SIMPLIFICACIÓN SAAS ERP (DEFINITIVO)

**Proyecto:** Sistema de Bordados - Módulo de Inventarios
**Versión:** 2.0 (Refinada para SaaS/ERP)
**Enfoque:** Claridad Semántica + Seguridad Contable

---

## 1. PRINCIPIOS RECTORES (MANDAMIENTOS)

1.  **Modelo Intacto:** Database schemas, FKs y lógica de conversión `MaterialUnitConversionController` se mantienen. No se toca SQL.
2.  **Cero Magia:** No inferir datos críticos (Unidad de Inventario). El usuario decide explícitamente, pero guiado.
3.  **Lenguaje Contable:** Hablar de "Inventario" y "Presentación", no de "Canónica" y "Logística".

---

## 2. FLUJO UX SIMPLIFICADO PASO A PASO

### ETAPA 1: ALTA DE UNIDADES (SETUP GLOBAL)
**Objetivo:** Crear el diccionario de términos.

*   **Pantalla:** `admin/units/create`
*   **Simplificación:**
    *   Ocultar terminología "Unit Type" en select.
    *   **Label Propuesto:** "¿Para qué se usa esta unidad?"
    *   **Opciones:**
        *   🔵 "Para controlar INVENTARIO (Metros, Kilos, Piezas)" -> Maps to `canonical`
        *   📦 "Para PRESENTACIÓN DE COMPRA (Cajas, Conos, Rollos)" -> Maps to `logistic`/`metric_pack`

### ETAPA 2: REGLAS DE CATEGORÍA
**Objetivo:** Definir el universo permitido (El filtro maestro).

*   **Pantalla:** `admin/material-categories/{id}/units`
*   **Diseño Visual:**
    *   **Panel Izquierdo (Inventario):** "¿En qué unidades se puede inventariar esta categoría?" (Checkboxes: Metro, Kilo).
    *   **Panel Derecho (Presentación):** "¿En qué presentaciones se compra?" (Checkboxes: Rollo, Caja, Cono).

### ETAPA 3: ALTA DE MATERIAL (UX CLAVE)
**Objetivo:** Nacer con la identidad correcta.

*   **Pantalla:** `admin/materials/create`
*   **Lógica de Filtrado:**
    *   Al seleccionar Categoría "HILOS"...
    *   El select "Unidad de Inventario" se llena **SOLO** con las unidades marcadas como "Inventario" en Etapa 2 (Metro).
    *   El select de Unidades de Presentación/Compra desaparece (se mueve a siguientes pasos).
*   **Resultado:** El usuario elige explícitamente "METRO". Sin error posible.

### ETAPA 4: CONVERSIONES (GESTIÓN DE PRESENTACIONES)
**Objetivo:** Enseñar equivalencias.

*   **Concepto:** "Catálogo de Presentaciones" (No "Conversiones").
*   **Flujo:**
    *   Tabla con las presentaciones permitidas (Caja, Cono).
    *   Botón "Activar/Configurar" en cada una.
*   **Wizard:**
    *   Si activo "Caja" -> Lanza Wizard "¿Qué contiene?".
    *   Si activo "Cono" -> Lanza Input Directo "¿Cuántos [UnidadInventario] trae?".

### ETAPA 5: COMPRA (TRANSPARENCIA)
*   **Pantalla:** `admin/purchases/create`
*   **UX:** Select de Unidad muestra TODO lo activo (Caja, Cono, Metro).
*   **Badge Informativo:** Al elegir "Caja", mostrar pequeño texto: *"Stock: +120,000 m"*.

---

## 3. GARANTÍA DE SEGURIDAD

| Riesgo Potencial | Mitigación Arquitectónica |
| :--- | :--- |
| **Error en Factor:** | El Wizard (Etapa 4) obliga a derivar Cajas de Conos. |
| **Confusión de Stock:** | La Etapa 3 fuerza a elegir UNA sola unidad de inventario clara. |
| **Inconsistencia:** | La Etapa 2 actúa como firewall. No puedo crear un Hilo en "Litros" si la categoría no lo permite. |

---

## 4. CHECKLIST DE IMPLEMENTACIÓN (ORDEN DE ATAQUE)

1.  [ ] **Refactor de Vistas (Blade):** Cambiar labels y textos de ayuda en 3 archivos clave.
2.  [ ] **Lógica de Filtrado (Controller):** Asegurar que `MaterialCategoryController@getUnits` pueda devolver grupos separados (Inventario vs Presentación).
3.  [ ] **JS de Alta Material:** Consumir el JSON agrupado y llenar el select correcto.

**Veredicto:** Esta arquitectura es **Sólida, Escalable y Segura**. Puede procederse a código.
