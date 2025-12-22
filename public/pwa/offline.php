<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sin Conexión | App-Tareas</title>
  <meta name="theme-color" content="#1e2139">
  <link rel="stylesheet" href="../../assets/css/pages/offline.css">
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

  <script src="../../assets/js/pages/offline.js"></script>
</body>
</html>
