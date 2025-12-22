// Quick Tasks Page JavaScript

// Agregar tarea rápida
const quickAddForm = document.getElementById('quickAddForm');
if (quickAddForm) {
  quickAddForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'create');
    const selectedDate = document.querySelector('input[name="date"]')?.value || new Date().toISOString().split('T')[0];
    formData.append('date', selectedDate);
    
    try {
      const response = await fetch('api/quick_tasks_api.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        location.reload();
      } else {
        alert('Error: ' + (result.error || 'No se pudo crear la tarea'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error al crear la tarea');
    }
  });
}

// Marcar/desmarcar tarea como completada
async function toggleTask(taskId, completed) {
  const formData = new FormData();
  formData.append('action', completed ? 'complete' : 'uncomplete');
  formData.append('task_id', taskId);
  
  try {
    const response = await fetch('api/quick_tasks_api.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      if (completed && result.points > 0) {
        showNotification(`¡Tarea completada! +${result.points} puntos 🎉`);
      }
      setTimeout(() => location.reload(), 1000);
    } else {
      alert('Error: ' + (result.error || result.message));
      location.reload();
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al actualizar la tarea');
    location.reload();
  }
}

// Eliminar tarea
async function deleteTask(taskId) {
  if (!confirm('¿Seguro que deseas eliminar esta tarea?')) {
    return;
  }
  
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('task_id', taskId);
  
  try {
    const response = await fetch('api/quick_tasks_api.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      location.reload();
    } else {
      alert('Error: ' + (result.error || 'No se pudo eliminar'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al eliminar la tarea');
  }
}

// Mostrar notificación
function showNotification(message) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #4caf50;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    z-index: 1000;
    animation: slideIn 0.3s ease;
  `;
  notification.textContent = message;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}
