// Index Page JavaScript - Main Dashboard Logic

// Funciones globales para los modales
window.openModal = function() {
  const modal = document.getElementById('taskModal');
  if (modal) {
    modal.style.display = 'flex';
  } else {
    console.error('Modal taskModal no encontrado');
  }
}

window.closeModal = function() {
  const modal = document.getElementById('taskModal');
  const form = document.getElementById('taskForm');
  const docsSection = document.getElementById('documentsSection');
  
  if (modal) modal.style.display = 'none';
  if (form) form.reset();
  if (docsSection) docsSection.style.display = 'none';
}

window.openDeployModal = function(taskId, requiresDocs) {
  document.getElementById('deployTaskId').value = taskId;
  document.getElementById('deployRequiresDocs').value = requiresDocs ? '1' : '0';
  
  // Obtener los checkboxes
  const checklistBackup = document.getElementById('checklistBackup');
  const checklistTests = document.getElementById('checklistTests');
  const checklistDocs = document.getElementById('checklistDocs');
  const checklistTeam = document.getElementById('checklistTeam');
  
  // Si la tarea requiere documentos, hacer los checkboxes obligatorios
  if (requiresDocs) {
    checklistBackup.setAttribute('required', 'required');
    checklistTests.setAttribute('required', 'required');
    checklistDocs.setAttribute('required', 'required');
    checklistTeam.setAttribute('required', 'required');
  } else {
    // Si no requiere documentos, quitar el required
    checklistBackup.removeAttribute('required');
    checklistTests.removeAttribute('required');
    checklistDocs.removeAttribute('required');
    checklistTeam.removeAttribute('required');
  }
  
  document.getElementById('deployModal').style.display = 'flex';
}

window.closeDeployModal = function() {
  const modal = document.getElementById('deployModal');
  const form = document.getElementById('deployForm');
  
  if (modal) modal.style.display = 'none';
  if (form) form.reset();
}

// Función para mostrar información de logro
window.showAchievementInfo = function(name, description, icon, date, points) {
  document.getElementById('achievementIcon').textContent = icon;
  document.getElementById('achievementName').textContent = name;
  document.getElementById('achievementDescription').textContent = description;
  document.getElementById('achievementPoints').textContent = points;
  document.getElementById('achievementDate').textContent = date;
  document.getElementById('achievementModal').style.display = 'flex';
}

window.closeAchievementModal = function() {
  const modal = document.getElementById('achievementModal');
  if (modal) modal.style.display = 'none';
}

// Variables globales para el modal de eliminar
let deleteTaskId = null;

window.openDeleteModal = function(taskId, isCompleted, taskTitle) {
  deleteTaskId = taskId;
  document.getElementById('deleteTaskTitle').textContent = taskTitle;
  
  const warningDiv = document.getElementById('deleteWarningCompleted');
  if (isCompleted) {
    warningDiv.style.display = 'block';
  } else {
    warningDiv.style.display = 'none';
  }
  
  document.getElementById('deleteModal').style.display = 'flex';
}

window.closeDeleteModal = function() {
  const modal = document.getElementById('deleteModal');
  if (modal) modal.style.display = 'none';
  deleteTaskId = null;
}

window.confirmDeleteTask = function() {
  if (deleteTaskId) {
    window.location.href = 'tasks/actions/delete.php?id=' + deleteTaskId;
  }
}

window.toggleFilters = function() {
  const filtersForm = document.getElementById('filtersForm');
  const filterIcon = document.getElementById('filterIcon');
  
  if (filtersForm.style.display === 'none') {
    filtersForm.style.display = 'flex';
    filterIcon.textContent = '▲';
  } else {
    filtersForm.style.display = 'none';
    filterIcon.textContent = '▼';
  }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
  const taskModal = document.getElementById('taskModal');
  const deployModal = document.getElementById('deployModal');
  const achievementModal = document.getElementById('achievementModal');
  const deleteModal = document.getElementById('deleteModal');
  const themeModal = document.getElementById('themeModal');
  if (event.target === taskModal) {
    closeModal();
  }
  if (event.target === deployModal) {
    closeDeployModal();
  }
  if (event.target === achievementModal) {
    closeAchievementModal();
  }
  if (event.target === deleteModal) {
    closeDeleteModal();
  }
  if (event.target === themeModal) {
    closeThemeModal();
  }
}

// Sistema de temas
const themes = {
  dark: {
    '--bg-body': '#0f1117',
    '--bg-card': '#1e2139',
    '--bg-card-hover': '#252a42',
    '--primary-gradient-start': '#2d3561',
    '--primary-gradient-end': '#1a1d29'
  },
  blue: {
    '--bg-body': '#0a0e1a',
    '--bg-card': '#0f172a',
    '--bg-card-hover': '#1e293b',
    '--primary-gradient-start': '#1e3a8a',
    '--primary-gradient-end': '#0f172a'
  },
  purple: {
    '--bg-body': '#0d0a1a',
    '--bg-card': '#1e1b4b',
    '--bg-card-hover': '#2e1c5d',
    '--primary-gradient-start': '#5b21b6',
    '--primary-gradient-end': '#1e1b4b'
  },
  green: {
    '--bg-body': '#061412',
    '--bg-card': '#022c22',
    '--bg-card-hover': '#064e3b',
    '--primary-gradient-start': '#065f46',
    '--primary-gradient-end': '#022c22'
  },
  red: {
    '--bg-body': '#120b0e',
    '--bg-card': '#1f1418',
    '--bg-card-hover': '#3f1f27',
    '--primary-gradient-start': '#7f1d1d',
    '--primary-gradient-end': '#1f1418'
  },
  gray: {
    '--bg-body': '#0a0c10',
    '--bg-card': '#111827',
    '--bg-card-hover': '#1f2937',
    '--primary-gradient-start': '#374151',
    '--primary-gradient-end': '#111827'
  }
};

window.openThemeModal = function() {
  const modal = document.getElementById('themeModal');
  if (modal) {
    modal.style.display = 'flex';
    // Marcar el tema actual
    const currentTheme = localStorage.getItem('appTheme') || 'dark';
    document.querySelectorAll('.theme-option').forEach(option => {
      const themeDiv = option.querySelector('div');
      if (option.dataset.theme === currentTheme) {
        themeDiv.style.borderColor = 'var(--accent-blue)';
        themeDiv.style.boxShadow = '0 0 20px rgba(0, 212, 255, 0.5)';
      } else {
        themeDiv.style.borderColor = 'transparent';
        themeDiv.style.boxShadow = 'none';
      }
    });
  }
}

window.closeThemeModal = function() {
  const modal = document.getElementById('themeModal');
  if (modal) modal.style.display = 'none';
}

window.changeTheme = function(themeName) {
  const theme = themes[themeName];
  if (!theme) return;
  
  // Aplicar colores del tema
  Object.keys(theme).forEach(property => {
    document.documentElement.style.setProperty(property, theme[property]);
  });
  
  // Guardar en localStorage
  localStorage.setItem('appTheme', themeName);
  
  // Cerrar dropdown
  const dropdown = document.getElementById('themeDropdown');
  if (dropdown) dropdown.style.display = 'none';
  
  // Cerrar modal si está abierto
  closeThemeModal();
}

// Toggle dropdown de temas
window.toggleThemeDropdown = function() {
  const dropdown = document.getElementById('themeDropdown');
  if (!dropdown) return;
  
  if (dropdown.style.display === 'none' || dropdown.style.display === '') {
    dropdown.style.display = 'block';
  } else {
    dropdown.style.display = 'none';
  }
}

// Cargar tema guardado al iniciar
window.addEventListener('DOMContentLoaded', function() {
  const savedTheme = localStorage.getItem('appTheme') || 'dark';
  if (savedTheme !== 'dark') {
    changeTheme(savedTheme);
  }
});

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
  const dropdown = document.getElementById('themeDropdown');
  const appTitle = event.target.closest('h1');
  
  if (dropdown && !appTitle && dropdown.style.display === 'block') {
    dropdown.style.display = 'none';
  }
});

window.toggleDocuments = function() {
  const requiresDocs = document.getElementById('requiresDocs');
  const docsSection = document.getElementById('documentsSection');
  const docInputs = docsSection ? docsSection.querySelectorAll('input[type="checkbox"]') : [];
  
  if (requiresDocs && requiresDocs.checked) {
    if (docsSection) docsSection.style.display = 'block';
  } else {
    if (docsSection) docsSection.style.display = 'none';
    docInputs.forEach(input => input.checked = false);
  }
}

// Sistema de notificaciones del navegador
function requestNotificationPermission() {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
}

function showNotification(title, body, icon = '⚠️') {
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification(title, {
      body: body,
      icon: '../assets/icon.png',
      badge: '../assets/badge.png',
      tag: 'app-tareas-alert',
      requireInteraction: false,
      vibrate: [200, 100, 200]
    });
  }
}

function checkPendingTasks() {
  // Obtener las estadísticas desde el DOM o variables PHP embebidas
  const pendingElem = document.querySelector('[data-stat="pendientes"]');
  const overdueElem = document.querySelector('[data-stat="vencidos"]');
  const urgentElem = document.querySelector('[data-stat="urgentes"]');
  const upcomingElem = document.querySelector('[data-stat="proximos"]');
  
  const pendingCount = pendingElem ? parseInt(pendingElem.textContent) : 0;
  const overdueCount = overdueElem ? parseInt(overdueElem.textContent) : 0;
  const urgentCount = urgentElem ? parseInt(urgentElem.textContent) : 0;
  const upcomingCount = upcomingElem ? parseInt(upcomingElem.textContent) : 0;
  
  // Notificar tareas vencidas (prioridad alta)
  if (overdueCount > 0) {
    showNotification(
      '⚠️ Tareas Vencidas!',
      `Tienes ${overdueCount} tarea(s) que ya pasaron su fecha límite y necesitan atención urgente.`
    );
  }
  // Notificar tareas urgentes pendientes
  else if (urgentCount > 0) {
    showNotification(
      '🔥 Tareas Urgentes',
      `Tienes ${urgentCount} tarea(s) urgentes pendientes de desplegar.`
    );
  }
  // Notificar tareas próximas a vencer
  else if (upcomingCount > 0) {
    showNotification(
      '📅 Tareas Próximas',
      `Tienes ${upcomingCount} tarea(s) que vencen en los próximos 7 días.`
    );
  }
  // Notificación general de pendientes
  else if (pendingCount > 0) {
    showNotification(
      '⏳ Tareas Pendientes',
      `Tienes ${pendingCount} tarea(s) pendientes de desplegar.`
    );
  }
}

// Pedir permiso al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  requestNotificationPermission();
  
  // Mostrar notificación después de 3 segundos (dar tiempo a que se cargue)
  setTimeout(checkPendingTasks, 3000);
  
  // Verificar cada 30 minutos si hay tareas pendientes
  setInterval(checkPendingTasks, 30 * 60 * 1000);
  
  // Si viene desde un proyecto, abrir el modal y preseleccionar el proyecto
  const urlParams = new URLSearchParams(window.location.search);
  const projectId = urlParams.get('project');
  if (projectId) {
    openModal();
    const projectSelect = document.querySelector('select[name="project_id"]');
    if (projectSelect) {
      projectSelect.value = projectId;
    }
  }
});

// Registrar Service Worker para PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('pwa/sw.js')
      .then(function(registration) {
        console.log('✅ Service Worker registrado:', registration.scope);
      })
      .catch(function(error) {
        console.log('❌ Error al registrar Service Worker:', error);
      });
  });
}
