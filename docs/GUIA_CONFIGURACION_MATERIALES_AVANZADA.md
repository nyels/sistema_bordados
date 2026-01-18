# GUÍA AVANZADA: CONFIGURACIÓN DE UNIDADES, MATERIALES Y CONVERSIONES

Esta guía detalla paso a paso cómo configurar el sistema para manejar escenarios complejos de compra y consumo, como cajas de conos de hilo o rollos de tela con metrajes variables.

## 1. CONCEPTOS CLAVE

Para que el sistema controle correctamente el inventario, debemos distinguir dos tipos de unidades:

*   **UNIDAD BASE (Consumo)**: Es la unidad real que se gasta al producir.
    *   Ejemplos: *Metro (m), Pieza (pz), Litro (L), Gramo (g)*.
    *   *El inventario siempre se guarda en esta unidad.*

*   **UNIDAD DE COMPRA (Logística)**: Es la unidad física que le compras al proveedor.
    *   Ejemplos: *Cono, Caja, Rollo, Paquete*.
    *   *Estas unidades se **convierten** a la unidad base al entrar al almacén.*

---

## 2. CASO PRÁCTICO: HILOS (Conos y Cajas)

**Objetivo:**
*   Consumimos el hilo en **Metros** (m).
*   Compramos **Conos** de 5000m.
*   Compramos **Cajas** que traen 24 conos (Total 120,000m).

### PASO 1: Crear las Unidades de Medida
Ve a **Menú > Unidades**. Asegúrate de tener creadas estas unidades:

1.  **Metro (m)**
    *   Tipo: **Unidad Base (Consumo)** (Activa el switch "¿Es Unidad Base?").
    *   *Nota: Esta suele venir creada por defecto.*
2.  **Cono**
    *   Tipo: **Unidad de Compra / Empaque** (Switch desactivado).
3.  **Caja con 24** (O simplemente "Caja 24 Pz")
    *   Tipo: **Unidad de Compra / Empaque** (Switch desactivado).

### PASO 2: Configurar la Categoría
Ve a **Menú > Categorías**.
1.  Edita o crea la categoría **HILOS**.
2.  Haz clic en el botón azul **"Gestionar Unidades Permitidas"**.
3.  En el modal, selecciona la categoría **HILOS**.
4.  Agrega las unidades permitidas:
    *   **Cono**
    *   **Caja con 24**
    *   *(Cualquier otra presentación que compres, ej: "Caja 12 Pz")*

### PASO 3: Crear el Material (El Hilo Específico)
Ve a **Menú > Materiales > Nuevo**.
1.  **Categoría**: HILOS.
2.  **Nombre**: Hilo Poliéster 40/2 (Aguja) - Turquesa / Cyan.
3.  **Unidad Base**: Selecciona **Metro (m)**. *Esto es vital para que el consumo por puntada funcione*.
4.  Guarda el material.

### PASO 4: Definir las Conversiones (La Magia)
Una vez guardado el material, busca el botón o pestaña de **Conversiones** (icono de intercambio). Aquí le decimos al sistema cuánto trae cada empaque.

**Conversión A (El Cono individual):**
1.  **De Unidad**: Selecciona **Cono**.
2.  **Factor de Conversión**: Escribe `5000`.
3.  *Interpretación: 1 Cono = 5000 Metros.*

**Conversión B (La Caja de 24):**
1.  **De Unidad**: Selecciona **Caja con 24**.
2.  **Factor de Conversión**: Escribe `120000`.
    *   *Cálculo: 24 conos * 5000 metros = 120,000 metros.*
3.  *Interpretación: 1 Caja = 120,000 Metros.*

**Resultado:**
Ahora el sistema sabe que si compras 1 Caja, debe sumar 120,000 metros al stock.

### PASO 5: Realizar la Compra
Ve a **Menú > Compras > Nueva**.
1.  Selecciona el proveedor "Selanusa".
2.  Agrega el ítem: "Hilo Poliéster 40/2 - Turquesa".
3.  **Unidad**: Selecciona **Caja con 24**.
4.  **Cantidad**: 1.
5.  **Precio Unitario**: $2,040 (Costo de la caja completa).
    *   *El sistema calculará internamente: $2040 / 120,000m = $0.017 por metro.*

---

## 3. CASO PRÁCTICO: TELA (Rollos Variables)

**Objetivo:**
*   Consumimos tela en **Metros**.
*   Compramos **Rollos** de diferentes medidas (50m, 25m).
*   Ejemplo: Comprar "2 Rollos de 25m".

### Configuración
1.  **Unidad**: Asegúrate de tener la unidad **Rollo 25m** (o "Rollo Estándar").
2.  **Categoría**: En la categoría **TELAS**, autoriza la unidad "Rollo 25m".
3.  **Material**: Crea "Lino Crudo - Azul". Unidad Base: **Metro**.
4.  **Conversión**:
    *   Define: 1 **Rollo 25m** = **25** Metros.

### Escenario de Compra
*   "Compra de 2 rollos de 25 metros".
1.  En la Compra, elige el material "Lino Crudo".
2.  Unidad: **Rollo 25m**.
3.  Cantidad: **2**.
4.  **Resultado en Inventario**: El sistema multiplicará `2 (cantidad) * 25 (factor) = 50 Metros` totales añadidos al stock.

---

## 4. ANÁLISIS DE COSTOS Y PRECIOS

El sistema calcula automáticamente el **Costo Real Unitario (por metro/pieza)** basándose en el precio de compra del empaque.

| Material | Unidad Compra | Precio Compra | Factor (Contenido) | Costo Real (Base) |
| :--- | :--- | :--- | :--- | :--- |
| **Hilo Poliéster** | Cono 5000m | $85.00 | 5000 | **$0.017 / metro** |
| **Hilo Poliéster** | Caja 24 (120k m)| $2,040.00 | 120000 | **$0.017 / metro** |
| **Listón Satinado**| Rollo 45m | $85.00 | 45 | **$1.88 / metro** |
| **Aguja DBxK5** | Paquete 10 | $95.00 | 10 | **$9.50 / aguja** |

**Nota Importante sobre Inventario:**
Al recibir la orden de compra, el inventario **siempre aumenta en la Unidad Base**.
*   Si recibes 1 Caja de Hilo -> Tu inventario sube +120,000 Metros.
*   Nunca verás "1 Caja" en el stock, verás su contenido total desglosado.

---

## RESUMEN DE FLUJO

1.  **Definir qué unidades físicas llegan al almacén** (Cajas, Rollos, Paquetes) -> Crear en *Unidades*.
2.  **Autorizarlas en la Categoría** correspondiente.
3.  **Crear el Material** siempre en su unidad de consumo (Metro, Pieza).
4.  **Crear las "Reglas de Juego" (Conversiones)** para cada material:
    *   *"Este material viene en Cajas de 24, que son 120,000 metros".*
    *   *"Este otro viene en Paquetes de 10, que son 10 piezas".*
5.  **Comprar** usando la unidad física (Caja/Paquete). El sistema hace la matemática por ti.
