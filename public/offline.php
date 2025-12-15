<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sin Conexión | App-Tareas</title>
  <meta name="theme-color" content="#1e2139">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background: linear-gradient(135deg, #0f1117 0%, #1e2139 100%);
      color: #e0e0e0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .offline-container {
      text-align: center;
      max-width: 500px;
      background: rgba(30, 33, 57, 0.8);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .offline-icon {
      font-size: 80px;
      margin-bottom: 20px;
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.1); opacity: 0.8; }
    }

    h1 {
      color: #00b4d8;
      font-size: 28px;
      margin-bottom: 16px;
      font-weight: 600;
    }

    p {
      color: #b0b0b0;
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .offline-info {
      background: rgba(0, 180, 216, 0.1);
      border: 1px solid rgba(0, 180, 216, 0.3);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 24px;
      color: #00b4d8;
      font-size: 14px;
    }

    .btn-retry {
      background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%);
      color: white;
      border: none;
      padding: 14px 32px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 180, 216, 0.3);
    }

    .btn-retry:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 180, 216, 0.4);
    }

    .btn-retry:active {
      transform: translateY(0);
    }

    .status {
      margin-top: 20px;
      font-size: 14px;
      color: #808080;
    }

    .status.online {
      color: #00c896;
    }

    .status.offline {
      color: #ff6b6b;
    }

    @media (max-width: 480px) {
      .offline-container {
        padding: 30px 20px;
      }

      .offline-icon {
        font-size: 60px;
      }

      h1 {
        font-size: 24px;
      }

      p {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="offline-container">
    <div class="offline-icon">📡</div>
    <h1>Sin Conexión a Internet</h1>
    <p>No se puede conectar al servidor. Por favor, verifica tu conexión a internet e intenta nuevamente.</p>
    
    <div class="offline-info">
      💡 <strong>Consejo:</strong> Esta aplicación funciona mejor con conexión, pero algunas funciones pueden estar disponibles offline.
    </div>

    <button class="btn-retry" onclick="retryConnection()">
      🔄 Intentar Nuevamente
    </button>

    <div class="status" id="connection-status">
      <span id="status-text">🔴 Sin conexión</span>
    </div>
  </div>

  <script>
    // Verificar estado de conexión
    function updateConnectionStatus() {
      const statusText = document.getElementById('status-text');
      const statusElement = document.getElementById('connection-status');
      
      if (navigator.onLine) {
        statusText.textContent = '🟢 Conexión restaurada';
        statusElement.className = 'status online';
        setTimeout(() => {
          window.location.href = 'index.php';
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
      fetch('index.php', { method: 'HEAD', cache: 'no-store' })
        .then(() => {
          button.textContent = '✅ Conectado';
          setTimeout(() => {
            window.location.href = 'index.php';
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
        fetch('index.php', { method: 'HEAD', cache: 'no-store' })
          .then(() => {
            window.location.href = 'index.php';
          })
          .catch(() => {
            // Sigue sin conexión al servidor
          });
      }
    }, 10000);
  </script>
</body>
</html>
