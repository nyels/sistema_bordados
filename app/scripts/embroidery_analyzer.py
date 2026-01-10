#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Embroidery File Analyzer v6.0 - Raw Color Mode
==============================================
Muestra los colores EXACTOS del archivo de bordado sin modificaciones.
Opcionalmente sugiere hilos comerciales más cercanos como referencia.

Autor: Sistema de Gestión de Diseños
Versión: 6.0.0 (Raw Color Mode)
"""

import sys
import json
import os
import tempfile
import re
import math

sys.stderr = open(os.devnull, 'w')

try:
    import pyembroidery
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "PyEmbroidery no instalado"
    }))
    sys.exit(1)


# =============================================================================
# FUNCIONES AUXILIARES
# =============================================================================

def safe_str(val):
    """Convierte cualquier valor a string de forma segura."""
    if val is None:
        return ""
    try:
        if isinstance(val, bytes):
            return val.decode('utf-8', errors='ignore').strip()
        return str(val).encode('utf-8', 'ignore').decode('utf-8').strip()
    except:
        return ""


def rgb_to_hex(r, g, b):
    """Convierte RGB a formato hexadecimal."""
    return "#{:02x}{:02x}{:02x}".format(int(r), int(g), int(b))


def get_color_name(r, g, b):
    """
    Genera un nombre descriptivo para el color basado en su valor RGB.
    No depende de ninguna base de datos externa.
    """
    # Calcular luminosidad y saturación aproximadas
    max_c = max(r, g, b)
    min_c = min(r, g, b)
    l = (max_c + min_c) / 2

    if max_c == min_c:
        # Gris
        if l < 30:
            return "Negro"
        elif l < 80:
            return "Gris Oscuro"
        elif l < 150:
            return "Gris"
        elif l < 220:
            return "Gris Claro"
        else:
            return "Blanco"

    # Calcular matiz
    if max_c == r:
        h = 60 * (((g - b) / (max_c - min_c)) % 6)
    elif max_c == g:
        h = 60 * (((b - r) / (max_c - min_c)) + 2)
    else:
        h = 60 * (((r - g) / (max_c - min_c)) + 4)

    if h < 0:
        h += 360

    # Determinar nombre según matiz
    if h < 15 or h >= 345:
        base = "Rojo"
    elif h < 45:
        base = "Naranja"
    elif h < 75:
        base = "Amarillo"
    elif h < 150:
        base = "Verde"
    elif h < 195:
        base = "Cyan"
    elif h < 255:
        base = "Azul"
    elif h < 285:
        base = "Violeta"
    elif h < 345:
        base = "Magenta"
    else:
        base = "Rojo"

    # Modificadores de luminosidad
    if l < 60:
        return f"{base} Oscuro"
    elif l > 200:
        return f"{base} Claro"
    return base


def extract_thread_color(thread):
    """
    Extrae el color RGB de un hilo de forma robusta.
    Intenta múltiples métodos según el formato del archivo.
    """
    r, g, b = 0, 0, 0

    if thread is None:
        return r, g, b

    try:
        # Método 1: Color como entero (0xRRGGBB) - más común
        if hasattr(thread, 'color') and thread.color is not None:
            c = thread.color
            if isinstance(c, int):
                r = (c >> 16) & 0xFF
                g = (c >> 8) & 0xFF
                b = c & 0xFF
                return r, g, b

        # Método 2: Usar hex_color() si existe
        if hasattr(thread, 'hex_color') and callable(thread.hex_color):
            hex_str = thread.hex_color()
            if hex_str and hex_str.startswith('#'):
                r = int(hex_str[1:3], 16)
                g = int(hex_str[3:5], 16)
                b = int(hex_str[5:7], 16)
                return r, g, b

        # Método 3: Atributos individuales r, g, b
        if hasattr(thread, 'red') and hasattr(thread, 'green') and hasattr(thread, 'blue'):
            r = thread.red or 0
            g = thread.green or 0
            b = thread.blue or 0
            return r, g, b

    except Exception:
        pass

    return r, g, b


# =============================================================================
# FUNCIÓN PRINCIPAL
# =============================================================================

def analyze_embroidery_file(file_path, output_svg=False):
    """
    Analiza un archivo de bordado y devuelve información técnica.

    IMPORTANTE: Esta versión usa los colores ORIGINALES del archivo
    sin ninguna corrección o aproximación a bases de datos de hilos.
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
        "min_x": 0, "min_y": 0, "max_x": 0, "max_y": 0,
        "jumps": 0,
        "machine_compatibility": "Desconocida"
    }

    if not os.path.exists(file_path):
        result["error"] = f"Archivo no encontrado: {file_path}"
        return result

    file_name = os.path.basename(file_path)
    file_ext = os.path.splitext(file_path)[1].lower()

    result["file_name"] = file_name
    result["file_format"] = file_ext.replace(".", "").upper()
    result["file_size"] = os.path.getsize(file_path)

    if file_ext == '.emb':
        result["error"] = "Archivo .EMB no soportado."
        return result

    try:
        pattern = pyembroidery.read(file_path)

        if pattern is None:
            result["error"] = "No se pudo leer el archivo."
            return result

        # =========================================================================
        # 1. EXTRACCIÓN DE COLORES ORIGINALES (SIN MODIFICAR)
        # =========================================================================
        final_colors_data = []

        if hasattr(pattern, 'threadlist') and pattern.threadlist:
            for idx, thread in enumerate(pattern.threadlist):
                if thread is None:
                    continue

                # Extraer color ORIGINAL del archivo
                r, g, b = extract_thread_color(thread)

                # Obtener descripción del archivo si existe
                description = safe_str(getattr(thread, 'description', ''))
                catalog = safe_str(getattr(thread, 'catalog_number', ''))

                # Si no hay descripción, generar nombre basado en color
                if not description:
                    description = get_color_name(r, g, b)

                hex_color = rgb_to_hex(r, g, b)

                # Guardar datos del color ORIGINAL
                final_colors_data.append({
                    "index": idx + 1,
                    "rgb": {"r": r, "g": g, "b": b},
                    "hex": hex_color,
                    "name": description,
                    "catalog": catalog if catalog else None
                })

        # =========================================================================
        # 2. MODO SVG (Visualización)
        # =========================================================================
        if output_svg:
            fd, temp_path = tempfile.mkstemp(suffix=".svg")
            try:
                # Escribir SVG usando pyembroidery (colores originales del archivo)
                pyembroidery.write(pattern, temp_path)

                with open(temp_path, "r", encoding="utf-8", errors="ignore") as f:
                    svg_content = f.read()

                # Ajustar viewBox para mejor encuadre
                try:
                    bounds = pattern.bounds()
                    if bounds:
                        min_x, min_y, max_x, max_y = bounds
                        w, h = max_x - min_x, max_y - min_y
                        margin = max(w, h) * 0.05
                        new_vb = f'viewBox="{min_x - margin} {min_y - margin} {w + margin*2} {h + margin*2}"'
                        svg_content = re.sub(r'viewBox="[^"]+"', new_vb, svg_content)
                        if 'width=' not in svg_content:
                            svg_content = svg_content.replace('<svg ', '<svg width="100%" height="100%" ')
                except:
                    pass

                # Estilos visuales mejorados
                style_block = '''
                <style>
                    path, polyline, line {
                        stroke-width: 3.2 !important;
                        stroke-linecap: butt;
                        stroke-linejoin: round;
                        filter: drop-shadow(0.2px 0.2px 0px rgba(0,0,0,0.15));
                    }
                </style>
                '''
                svg_content = svg_content.replace('</svg>', style_block + '</svg>')

                return {"success": True, "svg": svg_content}
            finally:
                os.close(fd)
                if os.path.exists(temp_path):
                    os.remove(temp_path)

        # =========================================================================
        # 3. MODO ANÁLISIS (JSON)
        # =========================================================================
        stitch_count = jumps = 0
        for stitch in pattern.stitches:
            cmd = stitch[2] & pyembroidery.COMMAND_MASK
            if cmd == pyembroidery.STITCH:
                stitch_count += 1
            elif cmd == pyembroidery.JUMP:
                jumps += 1

        result["total_stitches"] = stitch_count
        result["jumps"] = jumps

        try:
            bounds = pattern.bounds()
            if bounds:
                min_x, min_y, max_x, max_y = bounds
                result["width_mm"] = round((max_x - min_x) / 10, 1)
                result["height_mm"] = round((max_y - min_y) / 10, 1)
                result["min_x"] = round(min_x / 10, 1)
                result["min_y"] = round(min_y / 10, 1)
                result["max_x"] = round(max_x / 10, 1)
                result["max_y"] = round(max_y / 10, 1)
        except:
            pass

        result["colors"] = final_colors_data
        result["colors_count"] = len(final_colors_data) if final_colors_data else 1

        # Mapeo de compatibilidad por formato
        mapping = {
            '.pes': "Brother", '.dst': "Tajima", '.jef': "Janome",
            '.vp3': "Husqvarna-Viking", '.exp': "Melco"
        }
        result["machine_compatibility"] = mapping.get(file_ext, "Industrial")
        result["success"] = True

        return result

    except Exception as e:
        result["error"] = f"Error: {str(e)}"
        return result


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Uso: python embroidery_analyzer.py <archivo> [--svg]"}))
        sys.exit(1)
    
    result = analyze_embroidery_file(sys.argv[1], '--svg' in sys.argv)
    print(json.dumps(result, ensure_ascii=True))


if __name__ == "__main__":
    main()
