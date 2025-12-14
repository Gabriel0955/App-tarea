# App-Tareas

Sistema profesional de gestión y seguimiento de tareas con documentación obligatoria.

## ✨ Características

- 🔐 **Sistema de autenticación** - Registro e inicio de sesión seguro
- 👤 **Usuarios independientes** - Cada usuario ve solo sus tareas
- ✅ Gestión completa de tareas (crear, editar, eliminar)
- 📋 Documentos obligatorios antes de producción (4 documentos)
- ⚡ Niveles de urgencia (Alta, Media, Baja)
- 🎯 Estado: Pendiente / En Producción
- 🎨 8 temas personalizables
- 📱 Diseño 100% responsive
- 🔄 Actualización en tiempo real de documentos
- 🌙 Tema oscuro moderno con degradados
- 🔒 Contraseñas encriptadas con bcrypt

## 🚀 Despliegue en Azure desde GitLab

### 1. Crear Recursos en Azure

#### Base de Datos PostgreSQL
1. En Azure Portal, crear **Azure Database for PostgreSQL - Servidor flexible**
2. Configuración:
   - Nombre: `app-tareas-db`
   - Usuario admin: tu usuario (ej: `adminuser`)
   - Contraseña: tu contraseña segura
   - PostgreSQL version: 14 o superior
   - Permitir acceso público desde servicios de Azure
   - En **Redes**: Agregar regla de firewall para tu IP

#### App Service
1. Crear **App Service**
2. Configuración:
   - Runtime: **PHP 8.2**
   - Sistema operativo: **Linux**
   - Plan: F1 (gratis) o B1 (producción)

### 2. Configurar Variables de Entorno

En **App Service → Configuración → Configuración de la aplicación**, agregar:

```
DB_HOST=app-tareas-db.postgres.database.azure.com
DB_NAME=tasks_app
DB_USER=adminuser
DB_PASS=tu_contraseña
DB_PORT=5432
APP_DEBUG=false
```

### 3. Conectar GitLab con Azure

1. En **App Service → Centro de implementación**
2. Seleccionar **GitLab**
3. Autorizar y conectar tu cuenta
4. Seleccionar:
   - Repositorio: tu repositorio
   - Rama: `master` o `main`
5. Guardar

Azure desplegará automáticamente cada vez que hagas push.

### 4. ⚠️ IMPORTANTE: Crear Base de Datos (OBLIGATORIO)

**La base de datos NO se crea automáticamente**. Debes ejecutar el script SQL manualmente:

#### Opción A: Desde tu máquina local (psql)
```bash
psql -h app-tareas-db.postgres.database.azure.com -U adminuser -d postgres -f db/schema.sql
```

#### Opción B: pgAdmin / Azure Data Studio
1. Conectar a tu servidor PostgreSQL de Azure
2. Crear base de datos `tasks_app` (si no existe)
3. Abrir el archivo `db/schema.sql`
4. Ejecutar el script completo

#### Opción C: Azure Cloud Shell
1. Ir a **Azure Portal → Cloud Shell** (icono `>_` arriba a la derecha)
2. Subir el archivo `schema.sql`
3. Ejecutar:
```bash
psql -h app-tareas-db.postgres.database.azure.com -U adminuser -d postgres -f schema.sql
```

**Esto creará:**
- Base de datos `tasks_app`
- Tabla `users` (para login)
- Tabla `tasks` (tareas vinculadas a usuarios)
- Índices y foreign keys

### 5. Crear tu primer usuario

1. Abre tu app en Azure: `https://tu-app.azurewebsites.net`
2. Te redirigirá a `/public/login.php`
3. Click en "Crear cuenta nueva"
4. Regístrate con tu usuario y contraseña
5. ¡Listo! Ya puedes usar la app

## 🛠️ Desarrollo Local

### Requisitos
- PHP 7.4 o superior
- PostgreSQL 12 o superior
- Apache/Nginx (opcional, puede usar servidor PHP integrado)

### Instalación

#### Opción 1: Con PostgreSQL instalado localmente
1. Clonar el repositorio
2. Crear base de datos:
   ```bash
   psql -U postgres
   CREATE DATABASE tasks_app;
   \q
   ```
3. Importar estructura:
   ```bash
   psql -U postgres -d tasks_app -f db/schema.sql
   ```
4. Configurar `config.php`:
   ```php
   DB_HOST=localhost
   DB_USER=postgres
   DB_PASS=tu_password
   DB_PORT=5432
   ```
5. Iniciar servidor:
   ```bash
   php -S localhost:8000 -t public
   ```
6. Acceder: `http://localhost:8000`

#### Opción 2: Con Docker (PostgreSQL en contenedor)
```bash
docker run --name postgres-tasks -e POSTGRES_PASSWORD=postgres -p 5432:5432 -d postgres:14
psql -h localhost -U postgres -d postgres -f db/schema.sql
php -S localhost:8000 -t public
```

## 📱 Temas Disponibles

Visita `/public/temas.php` para ver los 8 temas:
- 🌊 Ocean (por defecto)
- 🔥 Fire
- 🌿 Nature
- 💜 Cyberpunk
- 🌅 Sunset
- 🌌 Galaxy
- ☀️ Light
- ⚫ AMOLED Black

## 📋 Documentos Obligatorios

Cuando una tarea requiere documentación:
1. Plan de Prueba Interna
2. Plan Puesta en Producción
3. Control de Objeto
4. Política de Respaldo

Los 4 documentos deben completarse antes de marcar como "En Producción".

## 🔒 Seguridad

- Headers de seguridad configurados
- Variables de entorno para credenciales
- Validación de datos en servidor
- Protección XSS y SQL Injection (PDO preparadas)

## 📞 Soporte

Problemas comunes resueltos en el código:
- ✅ Rutas relativas (funcionan en subdirectorios)
- ✅ Compatible con IIS (Azure) y Apache
- ✅ Variables de entorno configurables
- ✅ Responsive en todos los dispositivos

---

Desarrollado con ❤️ | PHP + MySQL + CSS3

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
