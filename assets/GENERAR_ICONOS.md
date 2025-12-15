# Generar Iconos para PWA

## Iconos Requeridos

La aplicación PWA necesita los siguientes iconos en la carpeta `assets/`:

- `icon-72x72.png` (72x72 píxeles)
- `icon-96x96.png` (96x96 píxeles)
- `icon-128x128.png` (128x128 píxeles)
- `icon-144x144.png` (144x144 píxeles)
- `icon-152x152.png` (152x152 píxeles)
- `icon-192x192.png` (192x192 píxeles)
- `icon-384x384.png` (384x384 píxeles)
- `icon-512x512.png` (512x512 píxeles)
- `icon-32x32.png` (32x32 píxeles) - Favicon
- `icon-16x16.png` (16x16 píxeles) - Favicon
- `favicon.ico` (favicon tradicional)

## Opciones para Generar

### Opción 1: Generador Online (Recomendado)

1. **RealFaviconGenerator** - https://realfavicongenerator.net/
   - Sube una imagen de 512x512 o más grande
   - Genera automáticamente todos los tamaños
   - Descarga el paquete completo

2. **PWA Asset Generator** - https://www.pwabuilder.com/imageGenerator
   - Sube tu logo
   - Selecciona "Generate"
   - Descarga todos los iconos

### Opción 2: ImageMagick (Línea de comandos)

Si tienes ImageMagick instalado:

```bash
# Crear icono base de 512x512 primero
convert -size 512x512 -background "#1e2139" -fill white -font Arial-Bold -pointsize 200 -gravity center label:"AT" icon-512x512.png

# Generar los demás tamaños
convert icon-512x512.png -resize 384x384 icon-384x384.png
convert icon-512x512.png -resize 192x192 icon-192x192.png
convert icon-512x512.png -resize 152x152 icon-152x152.png
convert icon-512x512.png -resize 144x144 icon-144x144.png
convert icon-512x512.png -resize 128x128 icon-128x128.png
convert icon-512x512.png -resize 96x96 icon-96x96.png
convert icon-512x512.png -resize 72x72 icon-72x72.png
convert icon-512x512.png -resize 32x32 icon-32x32.png
convert icon-512x512.png -resize 16x16 icon-16x16.png
convert icon-512x512.png -resize 48x48 favicon.ico
```

### Opción 3: Photoshop/GIMP

1. Diseña tu icono en 512x512 píxeles
2. Usa "Export As" o "Save for Web"
3. Genera cada tamaño manualmente
4. Guarda como PNG con transparencia (opcional)

### Opción 4: Canva (Sin instalación)

1. Crea un diseño de 512x512 en Canva
2. Diseña tu logo/icono
3. Descarga como PNG
4. Usa RealFaviconGenerator para generar los demás tamaños

## Diseño Recomendado

### Colores del tema:
- Fondo: `#1e2139` (azul oscuro)
- Acento: `#00b4d8` (azul claro)
- Texto: `#ffffff` (blanco)

### Ideas para el icono:
1. **Iniciales**: "AT" para "App-Tareas"
2. **Checklist**: ✓ símbolo con líneas horizontales
3. **Calendario**: Icono de calendario estilizado
4. **Cohete**: 🚀 para representar deployments

### Consideraciones:
- Usa colores contrastantes
- El icono debe verse bien en pequeño (72x72)
- Evita detalles demasiado finos
- Prueba en fondo claro y oscuro
- Mantén un diseño simple y reconocible

## Verificación

Después de generar los iconos:

1. Coloca todos los archivos en `assets/`
2. Verifica que los nombres coincidan exactamente
3. Abre DevTools → Application → Manifest
4. Verifica que todos los iconos se carguen correctamente
5. Prueba "Add to Home Screen" en móvil
6. Prueba "Install" en Chrome/Edge desktop

## Iconos Temporales

Para pruebas rápidas, puedes usar este generador de iconos de texto:
https://favicon.io/favicon-generator/

O este servicio que genera desde emoji:
https://favicon.io/emoji-favicons/

## Troubleshooting

**Error: Icons not found**
- Verifica la ruta en `manifest.json` (debe ser `../assets/icon-...png`)
- Asegúrate de que los archivos estén en la carpeta correcta
- Revisa que los nombres sean exactamente iguales

**PWA no se puede instalar**
- Requiere HTTPS en producción (localhost funciona sin HTTPS)
- Todos los iconos mínimos requeridos: 192x192 y 512x512
- Service Worker debe estar registrado correctamente
- Manifest.json debe ser válido (prueba en DevTools)
