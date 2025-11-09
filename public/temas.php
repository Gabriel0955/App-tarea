<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Vista Previa de Temas | App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="theme-color" content="#1e2139">
  <style>
    .theme-switcher {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--bg-card);
      padding: 16px;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-lg);
      z-index: 1000;
      max-width: 200px;
    }
    
    /* Responsive para el selector de temas */
    @media (max-width: 768px) {
      .theme-switcher {
        position: static;
        max-width: 100%;
        margin-bottom: 20px;
      }
      
      .theme-btn {
        font-size: 0.9rem !important;
        padding: 10px 14px !important;
      }
    }
    
    @media (max-width: 480px) {
      .theme-switcher {
        padding: 12px;
      }
      
      .theme-switcher h3 {
        font-size: 0.85rem !important;
      }
    }
    
    .theme-switcher h3 {
      margin: 0 0 12px 0;
      font-size: 0.9rem;
      color: var(--accent-blue);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .theme-btn {
      display: block;
      width: 100%;
      padding: 8px 12px;
      margin: 4px 0;
      background: var(--bg-input);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.3s ease;
    }
    .theme-btn:hover {
      background: var(--bg-input-focus);
      border-color: var(--accent-blue);
      transform: translateX(4px);
    }
    .demo-section {
      margin: 32px 0;
      padding: 24px;
      background: var(--bg-input);
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
    }
    .demo-badges {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin: 16px 0;
    }
    .demo-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin: 16px 0;
    }
  </style>
</head>
<body>

<div class="theme-switcher">
  <h3>🎨 Cambiar Tema</h3>
  <button class="theme-btn" onclick="loadTheme('default')">🌊 Océano (Default)</button>
  <button class="theme-btn" onclick="loadTheme('fire')">🔥 Fuego</button>
  <button class="theme-btn" onclick="loadTheme('nature')">🌿 Naturaleza</button>
  <button class="theme-btn" onclick="loadTheme('cyberpunk')">💜 Cyberpunk</button>
  <button class="theme-btn" onclick="loadTheme('sunset')">🌅 Sunset</button>
  <button class="theme-btn" onclick="loadTheme('galaxy')">🌌 Galaxia</button>
  <button class="theme-btn" onclick="loadTheme('light')">⚪ Claro</button>
  <button class="theme-btn" onclick="loadTheme('amoled')">⚫ Negro Absoluto</button>
</div>

<div class="container" style="margin-top: 20px;">
  <h1>🎨 Vista Previa de Temas</h1>
  <p class="subtitle" style="color: var(--text-secondary); font-size: 1.1rem; margin-top: -12px; margin-bottom: 24px; font-weight: 400;">
    Prueba diferentes temas y encuentra tu favorito
  </p>

  <div class="demo-section">
    <h2>Badges de Urgencia</h2>
    <div class="demo-badges">
      <span class="badge baja">Baja</span>
      <span class="badge media">Media</span>
      <span class="badge alta">Alta</span>
    </div>
  </div>

  <div class="demo-section">
    <h2>Botones</h2>
    <div class="demo-buttons">
      <a class="btn" href="#" onclick="return false;">Botón Normal</a>
      <a class="btn red" href="#" onclick="return false;">Botón Peligro</a>
      <button class="btn">Botón Submit</button>
    </div>
  </div>

  <div class="demo-section">
    <h2>Tabla de Ejemplo</h2>
    <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Urgencia</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Implementar nueva funcionalidad<br><span class="small">Creada: 2025-11-08</span></td>
          <td><span class="badge alta">Alta</span></td>
          <td><span class="small">Pendiente</span></td>
          <td>
            <a class="btn" href="#" onclick="return false;">Editar</a>
            <a class="btn red" href="#" onclick="return false;">Eliminar</a>
          </td>
        </tr>
        <tr>
          <td>Revisar código legacy<br><span class="small">Creada: 2025-11-07</span></td>
          <td><span class="badge media">Media</span></td>
          <td><span class="small">En producción</span></td>
          <td>
            <a class="btn" href="#" onclick="return false;">Editar</a>
            <a class="btn red" href="#" onclick="return false;">Eliminar</a>
          </td>
        </tr>
        <tr>
          <td>Actualizar documentación<br><span class="small">Creada: 2025-11-06</span></td>
          <td><span class="badge baja">Baja</span></td>
          <td><span class="small">Pendiente</span></td>
          <td>
            <a class="btn" href="#" onclick="return false;">Editar</a>
            <a class="btn red" href="#" onclick="return false;">Eliminar</a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="demo-section">
    <h2>Formulario</h2>
    <form onsubmit="return false;">
      <label>Título de la tarea</label>
      <input type="text" placeholder="Ingresa el título..." value="Ejemplo de tarea">
      
      <label>Descripción</label>
      <textarea rows="4" placeholder="Detalles de la tarea...">Esta es una descripción de ejemplo para mostrar cómo se ve el textarea con el tema seleccionado.</textarea>
      
      <label>Urgencia</label>
      <select>
        <option>Baja</option>
        <option selected>Media</option>
        <option>Alta</option>
      </select>
      
      <label>Fecha límite</label>
      <input type="date" value="2025-12-31">
      
      <label><input type="checkbox" checked> Ya en producción</label>
      
      <div style="margin-top:16px">
        <button class="btn" type="submit">Guardar Cambios</button>
        <a class="btn red" href="#" onclick="return false;">Cancelar</a>
      </div>
    </form>
  </div>


  <div style="text-align: center; margin-top: 48px; padding: 24px;">
    <a class="btn" href="index.php" style="font-size: 1.1rem; padding: 14px 32px;">
      ← Volver a la App Principal
    </a>
  </div>
</div>

<script>
const themes = {
  default: {
    '--primary-gradient-start': '#2d3561',
    '--primary-gradient-end': '#1a1d29',
    '--accent-blue': '#00d4ff',
    '--accent-purple': '#a855f7',
    '--bg-body': '#0f1117',
    '--bg-card': '#1e2139',
    '--bg-card-hover': '#252a42',
    '--bg-input': '#2a2f48',
    '--bg-input-focus': '#353b5a',
    '--text-primary': '#e4e4e7',
    '--text-secondary': '#a1a1aa',
    '--text-muted': '#71717a',
    '--border-color': '#2d3250',
    '--shadow-glow': '0 0 20px rgba(0, 212, 255, 0.3)'
  },
  fire: {
    '--primary-gradient-start': '#612d2d',
    '--primary-gradient-end': '#1a0f0f',
    '--accent-blue': '#ff4757',
    '--accent-purple': '#ff6348',
    '--bg-body': '#1a0f0f',
    '--bg-card': '#2d1a1a',
    '--bg-card-hover': '#3d2424',
    '--bg-input': '#3a1f1f',
    '--bg-input-focus': '#4a2929',
    '--text-primary': '#ffe4e4',
    '--text-secondary': '#ffb8b8',
    '--text-muted': '#cc8888',
    '--border-color': '#5d3030',
    '--shadow-glow': '0 0 20px rgba(255, 71, 87, 0.4)'
  },
  nature: {
    '--primary-gradient-start': '#2d6138',
    '--primary-gradient-end': '#0f1711',
    '--accent-blue': '#10b981',
    '--accent-purple': '#34d399',
    '--bg-body': '#0f1711',
    '--bg-card': '#1a2d22',
    '--bg-card-hover': '#24392d',
    '--bg-input': '#1f3a28',
    '--bg-input-focus': '#2a4a35',
    '--text-primary': '#e4ffe4',
    '--text-secondary': '#b8ffb8',
    '--text-muted': '#88cc88',
    '--border-color': '#305d38',
    '--shadow-glow': '0 0 20px rgba(16, 185, 129, 0.4)'
  },
  cyberpunk: {
    '--primary-gradient-start': '#4c2d61',
    '--primary-gradient-end': '#13111a',
    '--accent-blue': '#a855f7',
    '--accent-purple': '#ec4899',
    '--bg-body': '#13111a',
    '--bg-card': '#1e1829',
    '--bg-card-hover': '#2a2038',
    '--bg-input': '#2a1f3a',
    '--bg-input-focus': '#3a2a4a',
    '--text-primary': '#f5e4ff',
    '--text-secondary': '#d8b8ff',
    '--text-muted': '#b088cc',
    '--border-color': '#5d3070',
    '--shadow-glow': '0 0 20px rgba(168, 85, 247, 0.5)'
  },
  sunset: {
    '--primary-gradient-start': '#61452d',
    '--primary-gradient-end': '#1a1410',
    '--accent-blue': '#ff6b35',
    '--accent-purple': '#f7931a',
    '--bg-body': '#1a1410',
    '--bg-card': '#2d1f1a',
    '--bg-card-hover': '#3d2924',
    '--bg-input': '#3a251f',
    '--bg-input-focus': '#4a352a',
    '--text-primary': '#ffe8e4',
    '--text-secondary': '#ffc8b8',
    '--text-muted': '#cc9888',
    '--border-color': '#5d4030',
    '--shadow-glow': '0 0 20px rgba(255, 107, 53, 0.4)'
  },
  galaxy: {
    '--primary-gradient-start': '#2d3d61',
    '--primary-gradient-end': '#0f0f1a',
    '--accent-blue': '#4f46e5',
    '--accent-purple': '#7c3aed',
    '--bg-body': '#0f0f1a',
    '--bg-card': '#1a1a2d',
    '--bg-card-hover': '#24243d',
    '--bg-input': '#1f1f3a',
    '--bg-input-focus': '#2a2a4a',
    '--text-primary': '#e4e4ff',
    '--text-secondary': '#b8b8ff',
    '--text-muted': '#8888cc',
    '--border-color': '#30305d',
    '--shadow-glow': '0 0 20px rgba(79, 70, 229, 0.4)'
  },
  light: {
    '--primary-gradient-start': '#3b82f6',
    '--primary-gradient-end': '#8b5cf6',
    '--accent-blue': '#3b82f6',
    '--accent-purple': '#8b5cf6',
    '--bg-body': '#f5f5f5',
    '--bg-card': '#ffffff',
    '--bg-card-hover': '#f9fafb',
    '--bg-input': '#f3f4f6',
    '--bg-input-focus': '#e5e7eb',
    '--text-primary': '#1f2937',
    '--text-secondary': '#4b5563',
    '--text-muted': '#6b7280',
    '--border-color': '#d1d5db',
    '--shadow-glow': '0 0 20px rgba(59, 130, 246, 0.2)'
  },
  amoled: {
    '--primary-gradient-start': '#1a1a1a',
    '--primary-gradient-end': '#000000',
    '--accent-blue': '#00d4ff',
    '--accent-purple': '#a855f7',
    '--bg-body': '#000000',
    '--bg-card': '#0a0a0a',
    '--bg-card-hover': '#141414',
    '--bg-input': '#1a1a1a',
    '--bg-input-focus': '#242424',
    '--text-primary': '#ffffff',
    '--text-secondary': '#d1d5db',
    '--text-muted': '#9ca3af',
    '--border-color': '#1f1f1f',
    '--shadow-glow': '0 0 30px rgba(0, 212, 255, 0.5)'
  }
};

function loadTheme(themeName) {
  const theme = themes[themeName];
  const root = document.documentElement;
  
  for (const [property, value] of Object.entries(theme)) {
    root.style.setProperty(property, value);
  }
  
  // Guardar en localStorage
  localStorage.setItem('selectedTheme', themeName);
}

// Cargar tema guardado al inicio
window.addEventListener('DOMContentLoaded', () => {
  const savedTheme = localStorage.getItem('selectedTheme');
  if (savedTheme && themes[savedTheme]) {
    loadTheme(savedTheme);
  }
});
</script>

</body>
</html>
