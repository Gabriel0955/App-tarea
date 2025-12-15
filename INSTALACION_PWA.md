# 📱 Instalación PWA - App-Tareas

## ¿Qué es PWA?

**Progressive Web App** permite instalar la aplicación web como si fuera una app nativa:
- ✅ Funciona sin conexión (con caché)
- ✅ Se abre en ventana independiente (sin barra del navegador)
- ✅ Aparece en el menú de aplicaciones
- ✅ Notificaciones push
- ✅ Carga más rápida
- ✅ Menos consumo de datos

---

## 📱 Instalación en Android

### Chrome/Edge/Brave:

1. Abre la aplicación en el navegador
2. Toca el menú (⋮) en la esquina superior derecha
3. Selecciona **"Agregar a la pantalla de inicio"** o **"Instalar app"**
4. Confirma la instalación
5. ¡Listo! El icono aparecerá en tu pantalla de inicio

### Samsung Internet:

1. Toca el menú (≡)
2. Selecciona **"Agregar página a"** → **"Pantalla de inicio"**
3. Edita el nombre si lo deseas
4. Toca **"Agregar"**

### Firefox (Android):

1. Toca el menú (⋮)
2. Selecciona **"Instalar"**
3. Confirma la instalación

---

## 🍎 Instalación en iOS (iPhone/iPad)

### Safari (único navegador compatible en iOS):

1. Abre la aplicación en Safari
2. Toca el botón de compartir (□↑) en la parte inferior
3. Desplázate hacia abajo y toca **"Agregar a la pantalla de inicio"**
4. Edita el nombre si lo deseas
5. Toca **"Agregar"** en la esquina superior derecha
6. ¡Listo! El icono aparecerá en tu pantalla de inicio

**Nota:** En iOS, Chrome, Firefox y otros navegadores NO permiten instalar PWAs. Debes usar Safari.

---

## 💻 Instalación en Windows

### Chrome/Edge:

1. Abre la aplicación en el navegador
2. Busca el icono de instalación (⊕) en la barra de direcciones
3. O usa el menú (⋮) → **"Instalar App-Tareas"**
4. Confirma la instalación
5. La app se abrirá en una ventana independiente
6. Aparecerá en el menú de Windows (Inicio)

### Alternativa:

1. Abre el menú de Chrome/Edge (⋮)
2. Ve a **"Guardar y compartir"** → **"Instalar página como aplicación"**
3. Asigna un nombre
4. Toca **"Instalar"**

---

## 🍎 Instalación en macOS

### Chrome/Edge:

1. Abre la aplicación en el navegador
2. Busca el icono de instalación (⊕) en la barra de direcciones
3. O usa el menú (⋮) → **"Instalar App-Tareas"**
4. Confirma la instalación
5. La app aparecerá en Aplicaciones y Launchpad

### Safari:

Safari en macOS actualmente NO soporta instalación PWA completa.
Usa Chrome o Edge para mejor experiencia.

---

## 🐧 Instalación en Linux

### Chrome/Chromium/Brave:

1. Abre la aplicación en el navegador
2. Busca el icono de instalación (⊕) en la barra de direcciones
3. O usa el menú (⋮) → **"Instalar App-Tareas"**
4. Confirma la instalación
5. La app aparecerá en el menú de aplicaciones del sistema

---

## ✅ Verificar Instalación

La aplicación se instaló correctamente si:

- ✅ Aparece un icono en tu pantalla de inicio (móvil) o menú de aplicaciones (desktop)
- ✅ Se abre en ventana independiente sin barra del navegador
- ✅ Funciona sin conexión (carga páginas visitadas previamente)
- ✅ Muestra el nombre "App-Tareas" en el título de la ventana
- ✅ Usa los colores del tema (#1e2139)

---

## 🔧 Requisitos Técnicos

Para que la instalación funcione:

### En Producción:
- ✅ HTTPS obligatorio (excepto localhost)
- ✅ Manifest.json válido
- ✅ Service Worker registrado
- ✅ Iconos 192x192 y 512x512 mínimos

### En Desarrollo (localhost):
- ✅ HTTP permitido en localhost
- ✅ Manifest.json válido
- ✅ Service Worker registrado
- ✅ Los iconos son recomendados pero no obligatorios

---

## 🚨 Problemas Comunes

### "No aparece opción de instalar"

**Causas:**
- No estás en HTTPS (producción)
- Manifest.json tiene errores
- Service Worker no está registrado
- Faltan iconos mínimos (192x192, 512x512)
- Navegador no compatible (Safari iOS 14+, Chrome 79+, Edge 79+)

**Solución:**
1. Abre DevTools (F12)
2. Ve a **Application** → **Manifest**
3. Revisa errores en consola
4. Verifica que Service Worker esté activo en **Application** → **Service Workers**
5. Revisa que los iconos se carguen en **Application** → **Manifest** → Icons

### "App instalada pero no funciona offline"

**Causas:**
- Service Worker no está interceptando requests
- Cache no se llenó correctamente

**Solución:**
1. Abre DevTools → **Application** → **Service Workers**
2. Click en **Unregister** y recarga la página
3. Verifica en **Cache Storage** que se guardaron los archivos
4. Prueba desconectando WiFi

### "Iconos no se ven"

**Causas:**
- Archivos de iconos no existen
- Ruta incorrecta en manifest.json
- Tamaños incorrectos

**Solución:**
1. Genera los iconos (ver `assets/GENERAR_ICONOS.md`)
2. Verifica rutas: `../assets/icon-192x192.png`
3. Confirma tamaños con herramienta online
4. Recarga aplicación (Ctrl+Shift+R)

---

## 🔄 Desinstalar PWA

### Android:
1. Mantén presionado el icono
2. Selecciona **"Información de la aplicación"**
3. Toca **"Desinstalar"** o arrastra a papelera

### iOS:
1. Mantén presionado el icono
2. Selecciona **"Eliminar app"**
3. Confirma

### Windows/Mac/Linux:
1. Abre Chrome/Edge
2. Ve a `chrome://apps` o `edge://apps`
3. Haz clic derecho en el icono
4. Selecciona **"Eliminar de Chrome/Edge"**

O simplemente elimina desde el menú de aplicaciones del sistema.

---

## 📊 Testing

### Lighthouse (Chrome DevTools):

1. Abre DevTools (F12)
2. Ve a la pestaña **Lighthouse**
3. Marca **Progressive Web App**
4. Click en **Generate report**
5. Revisa puntuación (objetivo: >90/100)

### PWABuilder:

Prueba tu PWA en: https://www.pwabuilder.com/

1. Ingresa la URL de tu app
2. Click en **"Start"**
3. Revisa reporte de compatibilidad
4. Descarga assets faltantes si es necesario

---

## 🎯 Ventajas de Instalar

| Característica | Web Normal | PWA Instalada |
|----------------|------------|---------------|
| Funciona offline | ❌ | ✅ |
| Ventana independiente | ❌ | ✅ |
| Icono en pantalla | ❌ | ✅ |
| Notificaciones push | ⚠️ | ✅ |
| Carga rápida (caché) | ❌ | ✅ |
| Menos datos | ❌ | ✅ |
| Sin barra navegador | ❌ | ✅ |

---

## 📞 Soporte

Si tienes problemas:

1. Revisa `assets/GENERAR_ICONOS.md` para iconos
2. Abre DevTools → Console para ver errores
3. Verifica que HTTPS esté activo (producción)
4. Revisa que Service Worker esté registrado
5. Prueba en Chrome/Edge (mejor compatibilidad)

---

## 🔗 Referencias

- [MDN: Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Google: PWA Checklist](https://web.dev/pwa-checklist/)
- [Can I Use: PWA](https://caniuse.com/serviceworkers)
- [PWABuilder](https://www.pwabuilder.com/)
