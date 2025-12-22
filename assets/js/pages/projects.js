// Projects Page JavaScript

let deleteProjectData = { id: null, name: '', tasksCount: 0 };

function openCreateModal() {
  document.getElementById('modalTitle').textContent = '➕ Nuevo Proyecto';
  document.getElementById('formAction').value = 'create';
  document.getElementById('projectId').value = '';
  document.getElementById('projectForm').reset();
  document.getElementById('selectedColor').value = '#00b4d8';
  document.getElementById('selectedIcon').value = '📁';
  document.querySelectorAll('.icon-item').forEach(el => el.classList.remove('selected'));
  document.querySelectorAll('.icon-item')[0].classList.add('selected');
  document.querySelectorAll('.color-item').forEach(el => el.classList.remove('selected'));
  document.querySelectorAll('.color-item')[0].classList.add('selected');
  document.getElementById('projectModal').style.display = 'flex';
}

function openEditModal(id, name, description, color, icon) {
  document.getElementById('modalTitle').textContent = '✏️ Editar Proyecto';
  document.getElementById('formAction').value = 'update';
  document.getElementById('projectId').value = id;
  document.getElementById('projectName').value = name;
  document.getElementById('projectDescription').value = description;
  document.getElementById('selectedColor').value = color;
  document.getElementById('selectedIcon').value = icon;
  
  // Seleccionar el icono actual
  document.querySelectorAll('.icon-item').forEach(el => {
    if (el.textContent.trim() === icon) {
      el.classList.add('selected');
    } else {
      el.classList.remove('selected');
    }
  });
  
  // Seleccionar el color actual
  document.querySelectorAll('.color-item').forEach(el => {
    if (el.style.background.toLowerCase().includes(color.replace('#', ''))) {
      el.classList.add('selected');
    } else {
      el.classList.remove('selected');
    }
  });
  
  document.getElementById('projectModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('projectModal').style.display = 'none';
}

function selectColor(color) {
  document.getElementById('selectedColor').value = color;
  document.querySelectorAll('.color-item').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');
}

function selectIcon(icon) {
  document.getElementById('selectedIcon').value = icon;
  document.querySelectorAll('.icon-item').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');
}

function openDeleteModal(id, name, tasksCount) {
  deleteProjectData = { id, name, tasksCount };
  
  document.getElementById('deleteProjectName').textContent = name;
  document.getElementById('deleteTasksCount').textContent = tasksCount;
  
  const deleteTasksSection = document.getElementById('deleteTasksSection');
  const deleteNoTasksSection = document.getElementById('deleteNoTasksSection');
  const deleteTasksCheckbox = document.getElementById('deleteTasksCheckbox');
  
  if (tasksCount > 0) {
    deleteTasksSection.style.display = 'block';
    deleteNoTasksSection.style.display = 'none';
    deleteTasksCheckbox.checked = false;
  } else {
    deleteTasksSection.style.display = 'none';
    deleteNoTasksSection.style.display = 'block';
  }
  
  document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
  deleteProjectData = { id: null, name: '', tasksCount: 0 };
}

async function confirmDelete() {
  if (!deleteProjectData.id) return;
  
  const deleteTasks = document.getElementById('deleteTasksCheckbox').checked;
  
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('project_id', deleteProjectData.id);
  formData.append('delete_tasks', deleteTasks ? '1' : '0');
  
  try {
    const response = await fetch('api/project_api.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showNotification('✅ Proyecto eliminado correctamente');
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification('❌ Error: ' + (result.error || 'No se pudo eliminar el proyecto'));
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('❌ Error al eliminar el proyecto');
  }
}

function showNotification(message) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #1e2139;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 10000;
    animation: slideIn 0.3s ease;
    border-left: 4px solid #00b4d8;
  `;
  notification.textContent = message;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

window.onclick = function(event) {
  const projectModal = document.getElementById('projectModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target === projectModal) {
    closeModal();
  }
  
  if (event.target === deleteModal) {
    closeDeleteModal();
  }
}

