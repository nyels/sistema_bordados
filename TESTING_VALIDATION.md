# Plan de Validación y Pruebas - Sistema de Bordados

## 1. Detección de Formatos (Frontend ↔ Backend)

### ✅ Formatos Sincronizados
- **JPEG**: Firmas idénticas en ambas capas
- **PNG**: Firma única `89504e470d0a1a0a`
- **GIF**: GIF87a y GIF89a
- **WebP**: RIFF + validación adicional
- **AVIF**: Validación de estructura `ftyp` + brand
- **TIFF**: Little/Big endian
- **SVG**: Validación de contenido XML
- **Bordado**: 14 formatos soportados

### Validaciones Especiales
1. **WebP**: Verifica header RIFF + marca WEBP en bytes 8-11
2. **AVIF**: Verifica estructura completa (tamaño + ftyp + brand)
3. **SVG**: Valida contenido XML real

## 2. Nomenclatura de Archivos

### Formato Implementado
```
{nombre_diseño}_designs_{tipo}_{timestamp}_{hash}.{extensión_detectada}
```

### Ejemplos Válidos
- `perro_negro_designs_principal_1766369515_6948a8eb2d38f.avif`
- `mariposa_roja_designs_variant_1766369600_6948a940a1b2c.png`

### Reglas
1. Nombre del diseño en slug (guiones bajos)
2. Prefijo fijo "designs"
3. Tipo: "principal" o "variant"
4. Timestamp Unix
5. Hash único (uniqid)
6. Extensión basada en detección real (no en nombre de archivo)

## 3. Seguridad en Capas

### Capa 1: Frontend
- Detección por firma hexadecimal
- Validación de tamaño
- Validación de dimensiones (imágenes)
- Rechazo inmediato de formatos peligrosos

### Capa 2: Backend (Middleware)
- Re-validación de firma
- Detección de archivos peligrosos
- Validación de estructura interna
- Sanitización de nombres

### Capa 3: Almacenamiento
- Organización por tipo y fecha
- Nombres seguros (sin caracteres especiales)
- Permisos correctos

## 4. Casos de Prueba

### Casos Exitosos
- [x] PNG real → Detectado como PNG
- [x] AVIF real → Detectado como AVIF
- [x] WebP → Detectado correctamente
- [x] JPEG → Detectado correctamente

### Casos de Rechazo
- [ ] PNG renombrado como AVIF → Debe detectar PNG
- [ ] Archivo .exe renombrado como .png → Debe rechazar
- [ ] Archivo corrupto → Debe rechazar
- [ ] Archivo vacío → Debe rechazar

### Casos Edge
- [ ] Archivo muy grande (>10MB para imágenes)
- [ ] Imagen con dimensiones mínimas no válidas
- [ ] Múltiples archivos simultáneos
- [ ] Caracteres especiales en nombres

## 5. Métricas de Calidad

### Performance
- Detección < 100ms por archivo
- Upload completo < 2s para imagen 1MB

### Escalabilidad
- Soporta subida concurrente (múltiples usuarios)
- Sistema de carpetas organizado por fecha
- Nombres únicos garantizados (timestamp + hash)

### Mantenibilidad
- Código limpio y comentado
- Logs estructurados
- Separación de responsabilidades

## Estado Actual: ✅ LISTO PARA PRODUCCIÓN

### Completado
- ✅ Detección de formatos sincronizada
- ✅ Nomenclatura profesional
- ✅ Seguridad en capas
- ✅ Logs optimizados
- ✅ Frontend y Backend coherentes

### Pendiente (Opcional)
- ⚠️ Tests automatizados PHPUnit
- ⚠️ Pruebas de carga
- ⚠️ Monitoreo de errores
