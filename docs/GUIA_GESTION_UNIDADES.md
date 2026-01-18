# 📘 Guía Maestra de Unidades y Conversiones - Sistema Bordados

Esta guía explica el flujo correcto para gestionar unidades de medida, compras e inventario en el sistema.

---

## 1. Conceptos Fundamentales

Para que el inventario funcione, el sistema divide las unidades en dos mundos:

### 🌍 Mundo 1: Consumo (Lo que gastas)
Son las unidades con las que mides tu trabajo real. Son las **Unidades Fundamentales** y están protegidas por el sistema.
- **METRO (m):** Para hilos, telas, pellón.
- **PIEZA (pz):** Para prendas, agujas, botones.
- **LITRO (l):** Para químicos, aceites.
- **GRAMO (g):** Para tintas, polvos.

> 🔒 **Nota:** Estas unidades NO se deben borrar ni modificar, ya que son la base física del sistema.

### 📦 Mundo 2: Compra (Cómo lo recibes)
Son los envases o presentaciones en las que el proveedor te entrega el material. Tú las creas según necesites.
- Caja
- Cono
- Rollo
- Paquete
- Bulto

---

## 2. El Problema de la Compatibilidad
La regla de oro del sistema es: **No puedes mezclar peras con manzanas.**

Al crear una **Unidad de Compra** (ej. "Caja"), el sistema te pregunta:
> *"¿Esta caja es exclusiva para un tipo de medida?"*

### Caso A: Caja Específica (Restrictiva)
Si al crear la "Caja" seleccionas **Unidad Base: PIEZA**:
- ✅ Podrás usarla en playeras, agujas, gorras.
- ❌ **NO** podrás usarla en Hilos (porque el Hilo es METRO).
- *El sistema ocultará esta "Caja" cuando intentes hacer conversiones de Hilo.*

### Caso B: Caja Genérica (Universal)
Si al crear la "Caja" dejas la **Unidad Base: NINGUNA / VACÍA**:
- ✅ Podrás usarla en TODOS los materiales.
- El sistema asume que es un contenedor genérico.

### Caso C: Unidad Métrica (Presentación)
Si creas una unidad llamada "Rollo 50m":
- Debes asignarla a **METRO**.
- El sistema sabrá automáticamente que 1 Rollo = 50 Metros.

---

## 3. Flujo de Trabajo: Paso a Paso

Si quieres comprar Hilo por Cajas, sigue estos pasos:

### Paso 1: Verifica tu Material
1. Ve a **Materiales**.
2. Busca tu "Hilo Poliéster".
3. Revisa su **Unidad de Consumo**. Debería ser **METRO**.

### Paso 2: Crea/Verifica tu Unidad de Compra
1. Ve a **Configuración > Unidades**.
2. Busca o crea la unidad "**CAJA**".
3. **IMPORTANTE:**
   - Si quieres que sirva para el Hilo, asegúrate de que en "Unidad Base Compatible" diga **METRO** o esté **VACÍO**.
   - Si dice "PIEZA", **no te servirá** para el Hilo.

### Paso 3: Crea la Conversión
1. Ve a **Materiales > Conversiones** (del Hilo).
2. Click en **Nueva Conversión**.
3. Ahora sí aparecerá "**CAJA**" en la lista.
4. Define el factor:
   - *"1 Caja trae 12 Conos y cada cono trae 5000 metros..."*
   - Factor Final = 60,000 Metros.
   - O simplifica: Crear unidad "Cono" y decir 1 Cono = 5000 Metros.

---

## 4. Solución de Problemas Comunes

| Problema | Causa Probable | Solución |
| :--- | :--- | :--- |
| **"No sale mi unidad en la lista"** | Incompatibilidad física. | Tu material es METROS pero tu unidad de compra está ligada a PIEZAS. Edita la unidad de compra y quítale la restricción (déjala en blanco). |
| **"No puedo borrar una unidad"** | Uso activo o Protección. | Si es METRO/PIEZA, está protegida por sistema. Si es una unidad tuya, seguro ya se usa en materiales o compras. |
| **"El stock me sale en decimales"** | Conversión fraccionada. | Es normal. Si compras 1 Caja de 100m y gastas 1m, te quedan 0.99 Cajas. El sistema prefiere mostrarte "99 Metros". |
