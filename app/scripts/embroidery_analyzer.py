#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Embroidery File Analyzer
========================
Script para analizar archivos de bordado y extraer información técnica.
Formatos soportados: PES, DST, EXP, JEF, VP3, VIP, XXX, y 40+ más.

Uso:
    python embroidery_analyzer.py /ruta/al/archivo.pes

Retorna JSON con:
    - total_stitches: Total de puntadas
    - colors_count: Cantidad de colores
    - width_mm: Ancho en milímetros
    - height_mm: Alto en milímetros
    - colors: Lista de colores detectados (RGB y HEX)
    - file_format: Formato del archivo
    - success: Boolean indicando si el análisis fue exitoso
    - error: Mensaje de error (si aplica)

Autor: Sistema de Gestión de Diseños
Versión: 1.0.0
"""

import sys
import json
import os

try:
    import pyembroidery
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "PyEmbroidery no está instalado. Ejecuta: pip install pyembroidery"
    }))
    sys.exit(1)


def rgb_to_hex(r, g, b):
    """Convierte RGB a código hexadecimal."""
    return "#{:02x}{:02x}{:02x}".format(r, g, b)


def analyze_embroidery_file(file_path):
    """
    Analiza un archivo de bordado y extrae información técnica.
    
    Args:
        file_path: Ruta completa al archivo de bordado
        
    Returns:
        dict: Diccionario con los datos del archivo
    """
    result = {
        "success": False,
        "error": None,
        "file_name": None,
        "file_format": None,
        "file_size": 0,
        "total_stitches": 0,
        "colors_count": 0,
        "width_mm": 0,
        "height_mm": 0,
        "colors": [],
        "min_x": 0,
        "min_y": 0,
        "max_x": 0,
        "max_y": 0
    }
    
    # Verificar que el archivo existe
    if not os.path.exists(file_path):
        result["error"] = f"Archivo no encontrado: {file_path}"
        return result
    
    # Obtener información básica del archivo
    result["file_name"] = os.path.basename(file_path)
    result["file_format"] = os.path.splitext(file_path)[1].upper().replace(".", "")
    result["file_size"] = os.path.getsize(file_path)
    
    try:
        # Leer el archivo de bordado
        pattern = pyembroidery.read(file_path)
        
        if pattern is None:
            result["error"] = "No se pudo leer el archivo. Formato no soportado o archivo corrupto."
            return result
        
        # Contar puntadas (solo comandos STITCH)
        stitch_count = 0
        color_changes = 0
        
        for stitch in pattern.stitches:
            command = stitch[2] & pyembroidery.COMMAND_MASK
            if command == pyembroidery.STITCH:
                stitch_count += 1
            elif command == pyembroidery.COLOR_CHANGE:
                color_changes += 1
        
        result["total_stitches"] = stitch_count
        
        # Obtener dimensiones (bounds retorna min_x, min_y, max_x, max_y en 1/10 mm)
        bounds = pattern.bounds()
        if bounds:
            min_x, min_y, max_x, max_y = bounds
            
            # Convertir de 1/10 mm a mm y redondear
            result["width_mm"] = round((max_x - min_x) / 10, 1)
            result["height_mm"] = round((max_y - min_y) / 10, 1)
            result["min_x"] = round(min_x / 10, 1)
            result["min_y"] = round(min_y / 10, 1)
            result["max_x"] = round(max_x / 10, 1)
            result["max_y"] = round(max_y / 10, 1)
        
        # Extraer colores de los hilos
        colors = []
        for thread in pattern.threadlist:
            if thread.color is not None:
                # El color viene como entero RGB
                color_int = thread.color
                
                # Extraer componentes RGB
                r = (color_int >> 16) & 0xFF
                g = (color_int >> 8) & 0xFF
                b = color_int & 0xFF
                
                color_info = {
                    "rgb": {
                        "r": r,
                        "g": g,
                        "b": b
                    },
                    "hex": rgb_to_hex(r, g, b),
                    "name": thread.description if thread.description else None,
                    "brand": thread.brand if hasattr(thread, 'brand') and thread.brand else None,
                    "catalog_number": thread.catalog_number if hasattr(thread, 'catalog_number') and thread.catalog_number else None
                }
                colors.append(color_info)
        
        result["colors"] = colors
        result["colors_count"] = len(colors) if colors else (color_changes + 1 if color_changes > 0 else 1)
        
        # Si no hay colores en threadlist pero hay cambios de color
        if not colors and color_changes > 0:
            result["colors_count"] = color_changes + 1
        
        result["success"] = True
        
    except Exception as e:
        result["error"] = f"Error al procesar el archivo: {str(e)}"
    
    return result


def main():
    """Función principal del script."""
    # Verificar argumentos
    if len(sys.argv) < 2:
        print(json.dumps({
            "success": False,
            "error": "Uso: python embroidery_analyzer.py <ruta_archivo>"
        }))
        sys.exit(1)
    
    file_path = sys.argv[1]
    
    # Analizar el archivo
    result = analyze_embroidery_file(file_path)
    
    # Imprimir resultado como JSON
    print(json.dumps(result, ensure_ascii=False))
    
    # Código de salida
    sys.exit(0 if result["success"] else 1)


if __name__ == "__main__":
    main()
