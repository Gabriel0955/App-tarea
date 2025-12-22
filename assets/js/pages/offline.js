// Offline Page JavaScript

// Verificar estado de conexión
function updateConnectionStatus() {
  const statusText = document.getElementById('status-text');
  const statusElement = document.getElementById('connection-status');
  
  if (navigator.onLine) {
    statusText.textContent = '🟢 Conexión restaurada';
    statusElement.className = 'status online';
    setTimeout(() => {
      window.location.href = '../index.php';
    }, 1000);
  } else {
    statusText.textContent = '🔴 Sin conexión';
    statusElement.className = 'status offline';
  }
}

// Reintentar conexión
function retryConnection() {
  const button = document.querySelector('.btn-retry');
  button.textContent = '⏳ Verificando...';
  button.disabled = true;
  
  // Intentar cargar la página principal
  fetch('../index.php', { method: 'HEAD', cache: 'no-store' })
    .then(() => {
      button.textContent = '✅ Conectado';
      setTimeout(() => {
        window.location.href = '../index.php';
      }, 500);
    })
    .catch(() => {
      button.textContent = '❌ Sin conexión';
      setTimeout(() => {
        button.textContent = '🔄 Intentar Nuevamente';
        button.disabled = false;
      }, 1500);
    });
}

// Escuchar cambios en la conexión
window.addEventListener('online', updateConnectionStatus);
window.addEventListener('offline', updateConnectionStatus);

// Verificar al cargar
updateConnectionStatus();

// Auto-reintentar cada 10 segundos
setInterval(() => {
  if (navigator.onLine) {
    fetch('../index.php', { method: 'HEAD', cache: 'no-store' })
      .then(() => {
        window.location.href = '../index.php';
      })
      .catch(() => {
        // Sigue sin conexión al servidor
      });
  }
}, 10000);
