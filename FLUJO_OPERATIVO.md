# FLUJO OPERATIVO DEL SISTEMA ERP DE BORDADOS

Este documento describe el flujo real de trabajo del sistema, desde que llega un cliente hasta que se entrega su pedido.

---

## RESUMEN VISUAL DEL FLUJO

```
CLIENTE → PEDIDO (Borrador) → CONFIRMAR → DISEÑO → PRODUCCIÓN → LISTO → ENTREGA
              ↓                    ↓          ↓         ↓
         [Medidas]            [Precio]   [Aprobar]  [Materiales]
```

---

## 1. REGISTRO DEL CLIENTE

### ¿Qué ve el usuario?
- Formulario de cliente con datos básicos (nombre, teléfono, email)
- Sección de medidas corporales (si aplica para productos personalizados)

### ¿Qué acción puede hacer?
- Crear nuevo cliente
- Editar datos existentes
- Registrar/actualizar medidas

### ¿Qué lo bloquea?
- Nada. Los clientes se pueden crear en cualquier momento.

### ¿Cómo se libera?
- N/A

---

## 2. CREACIÓN DEL PEDIDO (Estado: BORRADOR)

### ¿Qué ve el usuario?
- Formulario de pedido con:
  - Selector de cliente
  - Lista de productos disponibles
  - Campos para cantidad, personalización, urgencia
  - Fecha de compromiso (prometida al cliente)
  - Cálculo automático de totales con IVA

### ¿Qué acción puede hacer?
- Agregar productos al pedido
- Definir personalización (texto a bordar, colores, etc.)
- Establecer nivel de urgencia (Normal, Urgente, Express)
- Guardar como borrador o confirmar

### ¿Qué lo bloquea?
- El pedido no puede confirmarse si:
  - No tiene al menos un producto
  - Faltan datos obligatorios

### ¿Cómo se libera?
- Completar los campos requeridos

---

## 3. CONFIRMACIÓN DEL PEDIDO (Estado: CONFIRMADO)

### ¿Qué ve el usuario?
- Pedido confirmado con número asignado (ej: ORD-2026-00001)
- Panel de "Situación" que indica si está listo para producción o bloqueado
- Detalle de productos con precios

### ¿Qué acción puede hacer?
- Ver detalle del pedido
- Agregar notas operativas
- Gestionar diseño/personalización
- Enviar a producción (si no hay bloqueos)

### ¿Qué lo bloquea?
1. **Ajuste de precio pendiente**: Si hubo cambios en el precio después de confirmar, el cliente debe aprobar.
2. **Diseño pendiente de aprobación**: Si el producto requiere diseño personalizado y no está aprobado.
3. **Medidas modificadas**: Si las medidas del cliente cambiaron después de aprobar el diseño.

### ¿Cómo se libera?
1. Contactar al cliente para aprobar el nuevo precio
2. Subir diseño y obtener aprobación del cliente
3. Re-aprobar el diseño con las nuevas medidas

---

## 4. GESTIÓN DE DISEÑO

### ¿Qué ve el usuario?
- Sección "Diseño/Personalización" en la vista del pedido
- Estado del diseño: Pendiente, En Revisión, Aprobado, Rechazado
- Botón para subir archivos de diseño (AI, DST, PNG, PDF)
- Texto personalizado a bordar

### ¿Qué acción puede hacer?
- Subir archivo de diseño
- Enviar a revisión del cliente
- Registrar aprobación o rechazo
- Agregar notas sobre el diseño

### ¿Qué lo bloquea?
- Diseño en estado "Pendiente" o "En Revisión" bloquea el paso a producción
- Si el diseño fue rechazado, debe corregirse y re-enviarse

### ¿Cómo se libera?
- Obtener aprobación del cliente (marcar como "Aprobado")

---

## 5. PRODUCCIÓN (Estado: EN PRODUCCIÓN)

### ¿Qué ve el usuario?
- Cola de producción con todos los pedidos pendientes
- Prioridad calculada automáticamente (urgencia + fecha compromiso)
- Estado de materiales (disponibles/insuficientes)
- Reservas de material activas

### ¿Qué acción puede hacer?
- Iniciar producción (reserva materiales automáticamente)
- Ver detalle de materiales requeridos
- Marcar como "Listo" cuando termine

### ¿Qué lo bloquea?
- **Material insuficiente**: No hay stock para producir
- **Bloqueos previos no resueltos**: Ajustes, diseño, medidas

### ¿Cómo se libera?
- Comprar material faltante (crear orden de compra)
- Resolver bloqueos pendientes

---

## 6. LISTO PARA ENTREGAR (Estado: LISTO)

### ¿Qué ve el usuario?
- Pedido marcado como completado
- Información de contacto del cliente
- Total a cobrar

### ¿Qué acción puede hacer?
- Marcar como entregado
- Registrar forma de pago (si aplica)
- Agregar notas finales

### ¿Qué lo bloquea?
- Nada. Solo falta que el cliente pase a recoger.

### ¿Cómo se libera?
- Cliente recoge y se marca como entregado

---

## 7. ENTREGADO (Estado: ENTREGADO)

### ¿Qué ve el usuario?
- Pedido cerrado con fecha de entrega
- Historial completo de eventos
- Liberación de reservas de material (si las había)

### ¿Qué acción puede hacer?
- Ver historial
- Generar factura (si aplica)

### ¿Qué lo bloquea?
- Nada. El pedido está terminado.

### ¿Cómo se libera?
- N/A

---

## ESTADOS ESPECIALES

### CANCELADO
- Pedido cancelado por el cliente o el negocio
- Libera cualquier reserva de material activa
- No puede reactivarse

---

## GLOSSARIO DE TÉRMINOS

| Término | Significado |
|---------|-------------|
| **Backlog** | Pedidos confirmados que esperan iniciar producción |
| **Bloqueado** | Pedido que no puede avanzar por falta de aprobaciones o materiales |
| **Lead Time** | Tiempo desde creación del pedido hasta entrega |
| **Reserva** | Material apartado exclusivamente para un pedido en producción |
| **Prioridad** | Número que determina el orden de producción (menor = más urgente) |

---

## ROLES Y SECCIONES

### Panel OPERATIVO (Equipo del día a día)
- Pedidos en producción
- Listos para entregar
- Pedidos con retraso
- Pedidos bloqueados

### Panel de PRODUCCIÓN (Equipo de taller)
- Consumo de materiales
- Top materiales usados
- Estado de pedidos activos
- Cola de producción

### Panel GERENCIAL (Administración)
- Ventas del mes
- Comparativa con mes anterior
- Top productos vendidos
- Métricas de eficiencia (Lead Time, % entregas a tiempo)

---

## ALERTAS Y NOTIFICACIONES

El sistema muestra alertas cuando:
- Hay materiales bajo el stock mínimo
- Hay pedidos con fecha de compromiso vencida
- Hay pedidos bloqueados esperando acción

Las notificaciones en tiempo real avisan sobre:
- Pedidos que inician producción
- Pedidos listos para entregar
- Nuevos mensajes operativos
- Bloqueos detectados

---

*Documento generado para el Sistema ERP de Bordados - Versión UX Humanizada*
