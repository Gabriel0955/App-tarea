<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Test Responsive - App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .device-info {
      position: fixed;
      bottom: 10px;
      left: 10px;
      background: var(--bg-card);
      padding: 12px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-color);
      font-size: 0.8rem;
      z-index: 1000;
      box-shadow: var(--shadow-md);
    }
    .device-info div {
      margin: 4px 0;
      color: var(--text-secondary);
    }
    .device-info strong {
      color: var(--accent-blue);
    }
    .test-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 16px;
      margin: 24px 0;
    }
    .test-card {
      background: var(--bg-input);
      padding: 20px;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
    }
  </style>
</head>
<body>

<div class="device-info">
  <div><strong>Ancho:</strong> <span id="width">-</span>px</div>
  <div><strong>Alto:</strong> <span id="height">-</span>px</div>
  <div><strong>Dispositivo:</strong> <span id="device">-</span></div>
  <div><strong>Orientación:</strong> <span id="orientation">-</span></div>
</div>

<div class="container">
  <h1>📱 Test Responsive</h1>
  <p class="subtitle" style="color: var(--text-secondary); font-size: 1.1rem; margin-top: -12px; margin-bottom: 24px; font-weight: 400;">
    Prueba el diseño en diferentes tamaños de pantalla
  </p>

  <div class="top-actions">
    <a class="btn" href="#">📋 Botón 1</a>
    <a class="btn" href="#">⏳ Botón 2</a>
    <a class="btn" href="#">➕ Botón 3</a>
    <a class="btn" href="#" style="background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));">🎨 Botón 4</a>
  </div>

  <h2>Test de Badges</h2>
  <div class="test-grid">
    <div class="test-card">
      <h3 style="margin: 0 0 12px 0; color: var(--text-primary);">Urgencia Baja</h3>
      <span class="badge baja">Baja</span>
    </div>
    <div class="test-card">
      <h3 style="margin: 0 0 12px 0; color: var(--text-primary);">Urgencia Media</h3>
      <span class="badge media">Media</span>
    </div>
    <div class="test-card">
      <h3 style="margin: 0 0 12px 0; color: var(--text-primary);">Urgencia Alta</h3>
      <span class="badge alta">Alta</span>
    </div>
  </div>

  <h2>Test de Tabla Responsive</h2>
  <table>
    <thead>
      <tr>
        <th>Título</th>
        <th>Urgencia</th>
        <th>Descripción</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td data-label="Título">Tarea de ejemplo 1<br><span class="small">Creada: 2025-11-08</span></td>
        <td data-label="Urgencia"><span class="badge alta">Alta</span></td>
        <td data-label="Descripción">Esta es una descripción de prueba para ver cómo se comporta el texto largo en diferentes dispositivos.</td>
        <td data-label="Fecha">2025-12-31</td>
        <td data-label="Estado"><span class="small">Pendiente</span></td>
        <td data-label="Acciones">
          <a class="btn" href="#">Marcar</a>
          <a class="btn" href="#">Editar</a>
          <a class="btn red" href="#">Eliminar</a>
        </td>
      </tr>
      <tr>
        <td data-label="Título">Tarea de ejemplo 2<br><span class="small">Creada: 2025-11-07</span></td>
        <td data-label="Urgencia"><span class="badge media">Media</span></td>
        <td data-label="Descripción">Otra tarea de prueba.</td>
        <td data-label="Fecha">2025-12-15</td>
        <td data-label="Estado"><span class="small">En producción</span></td>
        <td data-label="Acciones">
          <a class="btn" href="#">Editar</a>
          <a class="btn red" href="#">Eliminar</a>
        </td>
      </tr>
      <tr>
        <td data-label="Título">Tarea de ejemplo 3<br><span class="small">Creada: 2025-11-06</span></td>
        <td data-label="Urgencia"><span class="badge baja">Baja</span></td>
        <td data-label="Descripción">Tarea con prioridad baja.</td>
        <td data-label="Fecha">2026-01-15</td>
        <td data-label="Estado"><span class="small">Pendiente</span></td>
        <td data-label="Acciones">
          <a class="btn" href="#">Marcar</a>
          <a class="btn" href="#">Editar</a>
          <a class="btn red" href="#">Eliminar</a>
        </td>
      </tr>
    </tbody>
  </table>

  <h2>Test de Formulario</h2>
  <form onsubmit="return false;">
    <label>Campo de texto</label>
    <input type="text" placeholder="Ingresa algo..." value="Texto de ejemplo">
    
    <label>Área de texto</label>
    <textarea rows="4" placeholder="Descripción...">Esta es una descripción de prueba para ver cómo se comporta el textarea en diferentes dispositivos móviles y de escritorio.</textarea>
    
    <label>Selector</label>
    <select>
      <option>Opción 1</option>
      <option selected>Opción 2</option>
      <option>Opción 3</option>
    </select>
    
    <label>Fecha</label>
    <input type="date" value="2025-12-31">
    
    <label><input type="checkbox" checked> Checkbox de ejemplo</label>
    
    <div style="margin-top:16px; display: flex; gap: 12px; flex-wrap: wrap;">
      <button class="btn" type="submit">Guardar</button>
      <a class="btn red" href="#">Cancelar</a>
    </div>
  </form>

  <h2>Instrucciones de Prueba</h2>
  <div class="test-card">
    <h3 style="margin: 0 0 12px 0; color: var(--accent-blue);">Cómo probar</h3>
    <ol style="color: var(--text-secondary); line-height: 1.8; margin-left: 20px;">
      <li><strong>En navegador de escritorio:</strong> Abre las herramientas de desarrollador (F12) y activa el modo responsive (Ctrl+Shift+M en Chrome/Edge)</li>
      <li><strong>Tamaños a probar:</strong>
        <ul style="margin: 8px 0; line-height: 1.6;">
          <li>📱 Móvil pequeño: 320px - 480px (iPhone SE, iPhone 8)</li>
          <li>📱 Móvil grande: 481px - 768px (iPhone 12/13/14, Samsung Galaxy)</li>
          <li>📱 Tablet: 769px - 1024px (iPad, Android tablets)</li>
          <li>💻 Desktop: 1025px+ (laptops, monitores)</li>
        </ul>
      </li>
      <li><strong>Prueba landscape:</strong> Rota el dispositivo/simulador a modo horizontal</li>
      <li><strong>En dispositivo real:</strong> Accede desde tu móvil/tablet usando la IP local (ej: http://192.168.x.x/App-Tareas/public/test-responsive.php)</li>
    </ol>
  </div>

  <h2>Breakpoints Implementados</h2>
  <div class="test-grid">
    <div class="test-card">
      <h3 style="margin: 0 0 8px 0; color: var(--accent-blue);">📱 Móvil Pequeño</h3>
      <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">0px - 480px</p>
      <ul style="color: var(--text-muted); font-size: 0.85rem; margin: 8px 0 0 20px;">
        <li>Tabla en modo tarjeta</li>
        <li>Botones ancho completo</li>
        <li>Padding reducido</li>
        <li>Font-size: 14px</li>
      </ul>
    </div>
    
    <div class="test-card">
      <h3 style="margin: 0 0 8px 0; color: var(--accent-blue);">📱 Móvil Grande</h3>
      <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">481px - 768px</p>
      <ul style="color: var(--text-muted); font-size: 0.85rem; margin: 8px 0 0 20px;">
        <li>Botones en grilla 2 col</li>
        <li>Tabla scrollable</li>
        <li>Font-size: 15px</li>
      </ul>
    </div>
    
    <div class="test-card">
      <h3 style="margin: 0 0 8px 0; color: var(--accent-blue);">📱 Tablet</h3>
      <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">769px - 1024px</p>
      <ul style="color: var(--text-muted); font-size: 0.85rem; margin: 8px 0 0 20px;">
        <li>Container 95% ancho</li>
        <li>Tabla normal</li>
        <li>Espaciado medio</li>
      </ul>
    </div>
    
    <div class="test-card">
      <h3 style="margin: 0 0 8px 0; color: var(--accent-blue);">💻 Desktop</h3>
      <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">1025px+</p>
      <ul style="color: var(--text-muted); font-size: 0.85rem; margin: 8px 0 0 20px;">
        <li>Container max 1200px</li>
        <li>Espaciado completo</li>
        <li>Hover effects activos</li>
      </ul>
    </div>
  </div>

  <div style="text-align: center; margin-top: 48px; padding: 24px;">
    <a class="btn" href="index.php" style="font-size: 1.1rem; padding: 14px 32px;">
      ← Volver a la App Principal
    </a>
  </div>
</div>

<script>
function updateDeviceInfo() {
  const width = window.innerWidth;
  const height = window.innerHeight;
  const orientation = width > height ? 'Landscape' : 'Portrait';
  
  let device = '';
  if (width <= 480) {
    device = '📱 Móvil Pequeño';
  } else if (width <= 768) {
    device = '📱 Móvil Grande';
  } else if (width <= 1024) {
    device = '📱 Tablet';
  } else if (width <= 1440) {
    device = '💻 Desktop';
  } else {
    device = '🖥️ Desktop XL';
  }
  
  document.getElementById('width').textContent = width;
  document.getElementById('height').textContent = height;
  document.getElementById('device').textContent = device;
  document.getElementById('orientation').textContent = orientation;
}

updateDeviceInfo();
window.addEventListener('resize', updateDeviceInfo);
window.addEventListener('orientationchange', updateDeviceInfo);
</script>

</body>
</html>
