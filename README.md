# App-Tareas# App-Tareas (PHP + MySQL)



Sistema profesional de gestión y seguimiento de tareas con documentación obligatoria.Aplicación moderna y profesional para registrar tareas, indicar su urgencia y notas "lo que debo tener en cuenta". Incluye una marca para saber si la tarea ya fue pasada a producción (deployed).



## Despliegue en Azure desde GitLab## ✨ Características



### Variables de Entorno en Azure- ✅ Añadir, editar, eliminar tareas

- 🎯 Marcar tareas como "en producción"

Configurar en **App Service → Configuración → Configuración de la aplicación**:- 🔍 Filtrar tareas pendientes de pasar a producción

- 🎨 **Diseño moderno con tema oscuro personalizable**

```- 📱 **100% Responsive - perfecto en móviles, tablets y desktop**

DB_HOST=tu-servidor.mysql.database.azure.com- 🔄 **Adaptación inteligente según tamaño de pantalla**

DB_NAME=tasks_app- ⚡ **Animaciones sutiles y transiciones suaves**

DB_USER=tu-usuario- 🌈 **8 temas predefinidos incluidos**

DB_PASS=tu-contraseña- ♿ **Accesible y optimizado para touch**

DB_PORT=3306

APP_DEBUG=false## 📱 Diseño Responsive

```

La aplicación se adapta automáticamente a todos los dispositivos:

### Después del despliegue- 📱 **Móviles pequeños** (320px+): Tabla en modo tarjeta, botones ancho completo

- 📱 **Móviles grandes** (481px+): Grilla de 2 columnas, tabla scrollable

Ejecutar el script SQL en tu base de datos Azure MySQL:- 📱 **Tablets** (769px+): Layout optimizado, tabla normal

- 💻 **Desktop** (1025px+): Diseño completo con hover effects

```bash- 🖥️ **Pantallas grandes** (1441px+): Container expandido, espaciado generoso

mysql -h tu-servidor.mysql.database.azure.com -u tu-usuario -p tasks_app < db/schema.sql

```**Ver la [Guía Responsive completa](RESPONSIVE.md)** con:

- 5 breakpoints implementados

## Características- Instrucciones de prueba en dispositivos reales

- Modo landscape optimizado

- ✅ Gestión de tareas con urgencias- Página de test interactiva incluida

- ✅ Documentos obligatorios antes de producción

- ✅ 8 temas personalizables## 🎨 Personalización

- ✅ Diseño responsive (móvil, tablet, desktop)

- ✅ Modal para crear tareasEsta aplicación incluye un diseño moderno completamente personalizable mediante variables CSS. Puedes cambiar colores, espaciado, bordes y más sin tocar el código HTML.

- ✅ Checkboxes en tiempo real para documentos

**Ver la [Guía de Personalización completa](PERSONALIZACION.md)** con:
- 8 temas predefinidos listos para copiar (Océano, Fuego, Naturaleza, Cyberpunk, Sunset, Galaxia, Claro, Negro Absoluto)
- Instrucciones para crear tu propio tema
- Cómo cambiar iconos y emojis
- Opciones para tema claro/oscuro

## 📁 Archivos principales

- `public/` - archivos PHP públicos (index, add, edit, delete, temas, test-responsive)
- `src/db.php` - conexión PDO a la base de datos
- `config.php` - configuración de base de datos (crear desde config.sample.php)
- `config.sample.php` - ejemplo de configuración
- `db/schema.sql` - script para crear la base y la tabla
- `assets/style.css` - estilos modernos y personalizables (500+ líneas)
- `assets/temas-alternativos.css` - 8 temas predefinidos
- `PERSONALIZACION.md` - guía completa de temas y personalización
- `RESPONSIVE.md` - documentación de diseño responsive
- `RESUMEN.md` - resumen de todas las mejoras implementadas

## 🚀 Instalación y uso local (Wamp64)

1. Copia `config.sample.php` a `config.php` y completa los datos de conexión (Wamp por defecto: usuario `root`, contraseña vacía `''`).
2. Importa la base de datos ejecutando el script `db/schema.sql`:
   - **Opción A - phpMyAdmin:** Abre http://localhost/phpmyadmin y usa la pestaña "Importar"
   - **Opción B - Terminal:** `mysql -u root < db/schema.sql`
3. Abre en tu navegador: **http://localhost/App-Tareas/public/**
4. ¡Listo! La app está funcionando con el nuevo diseño moderno

## 🎯 Uso rápido

- **Ver todas las tareas:** Botón "📋 Ver todas"
- **Filtrar pendientes:** Botón "⏳ Pendientes de producción"
- **Crear nueva tarea:** Botón "➕ Nueva tarea" o scroll hasta el formulario
- **Ver temas:** Botón "🎨 Ver Temas" para probar los 8 temas incluidos
- **Test responsive:** Accede a `/public/test-responsive.php` para probar en diferentes dispositivos
- **Editar/Eliminar:** Botones en cada fila de la tabla
- **Marcar en producción:** Botón disponible para tareas pendientes

## 📱 Prueba el Diseño Responsive

**En navegador:**
1. Abre http://localhost/App-Tareas/public/test-responsive.php
2. Presiona F12 y luego Ctrl+Shift+M para modo responsive
3. Prueba diferentes dispositivos

**En dispositivo real:**
1. Obtén tu IP local con `ipconfig`
2. Accede desde móvil/tablet: `http://TU_IP/App-Tareas/public/`
3. Prueba todas las funciones y rota el dispositivo

Lee más en [RESPONSIVE.md](RESPONSIVE.md)

## 🌐 Despliegue en Azure (resumen)
1. Provisiona un servidor MySQL en Azure Database for MySQL (Flexible Server o Single Server).
2. Crea la base de datos y ejecuta `db/schema.sql` (puedes usar MySQL Workbench, Azure Data Studio o cualquier cliente).
3. Crea un App Service (Linux) para PHP o un App Service (Windows) y configura el Deployment (ZIP, Git, GitHub Actions). 
4. En la configuración de la App Service, agrega las siguientes Application Settings (variables de entorno):

- DB_HOST (por ejemplo: myserver.mysql.database.azure.com)
- DB_NAME
- DB_USER (incluye el usuario@servername si Azure lo requiere)
- DB_PASS
- DB_PORT (por defecto 3306)

Azure App Service leerá esas variables con getenv() en PHP.

## 📝 Notas

- No incluyas credenciales reales en `config.php` en repositorios públicos
- El diseño es completamente responsive y funciona en móviles
- Todas las animaciones son sutiles y mejoran la experiencia sin ser molestas
- Si prefieres tema claro, consulta [PERSONALIZACION.md](PERSONALIZACION.md)

## 🔮 Posibles mejoras futuras

- 🔔 Envío de recordatorios por email
- 👥 Autenticación (sistema de usuarios)
- 🔌 API REST para integrar otras herramientas
- 📊 Dashboard con estadísticas
- 🏷️ Sistema de etiquetas/categorías
- 🔍 Búsqueda y filtros avanzados

---

Hecho con ❤️ para desarrolladores que quieren gestionar sus tareas con estilo.

**¿Te gusta el diseño?** Personalízalo con tu propio tema siguiendo la [Guía de Personalización](PERSONALIZACION.md).
