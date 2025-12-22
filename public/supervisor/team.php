<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/theme.php';
require_once __DIR__ . '/../../services/SupervisorService.php';
require_once __DIR__ . '/../../services/RoleService.php';
require_once __DIR__ . '/../../src/db.php';

$pdo = get_pdo();
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Verificar que el usuario sea supervisor
$supervisorService = new SupervisorService($pdo);
if (!$supervisorService->isSupervisor($userId)) {
    header('Location: ../index.php?error=not_supervisor');
    exit;
}

// Obtener datos del equipo
$teamMembers = $supervisorService->getTeamMembers($userId);
$teamSummary = $supervisorService->getTeamSummary($userId);
$availableUsers = $supervisorService->getAvailableUsers($userId);

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <?php echo getThemeStyles(); ?>
  <title>Mi Equipo | Supervisor</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/supervisor.css">
</head>
<body>
<div class="container">
  <div class="header-section">
    <div>
      <h1>👥 Mi Equipo</h1>
      <p class="subtitle">Supervisor: <strong><?= esc($username) ?></strong></p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a class="btn" href="../index.php">← Mis Tareas</a>
      <a class="btn red" href="../auth/logout.php">🚪 Salir</a>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
      <?php if ($_GET['success'] === 'member_added'): ?>
        ✅ Miembro agregado al equipo correctamente
      <?php elseif ($_GET['success'] === 'member_removed'): ?>
        ✅ Miembro removido del equipo
      <?php elseif ($_GET['success'] === 'notes_updated'): ?>
        ✅ Notas actualizadas correctamente
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
      ⚠️ <?= esc($_GET['error']) ?>
    </div>
  <?php endif; ?>

  <!-- Resumen del equipo -->
  <div class="team-summary-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-value"><?= $teamSummary['total_members'] ?></div>
      <div class="stat-label">Miembros del Equipo</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">📋</div>
      <div class="stat-value"><?= $teamSummary['total_pending'] ?></div>
      <div class="stat-label">Tareas Pendientes</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">⚠️</div>
      <div class="stat-value"><?= $teamSummary['total_overdue'] ?></div>
      <div class="stat-label">Tareas Vencidas</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-value"><?= $teamSummary['total_completed'] ?></div>
      <div class="stat-label">Completadas</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🎯</div>
      <div class="stat-value"><?= number_format($teamSummary['avg_level'], 1) ?></div>
      <div class="stat-label">Nivel Promedio</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">⭐</div>
      <div class="stat-value"><?= number_format($teamSummary['total_points']) ?></div>
      <div class="stat-label">Puntos Totales</div>
    </div>
  </div>

  <!-- Botón para agregar miembros -->
  <div class="action-bar">
    <button class="btn" onclick="openAddMemberModal()">
      ➕ Agregar Miembro al Equipo
    </button>
  </div>

  <!-- Lista de miembros del equipo -->
  <?php if (empty($teamMembers)): ?>
    <div class="empty-state">
      <div class="empty-icon">👥</div>
      <h3>No tienes miembros en tu equipo</h3>
      <p>Agrega usuarios para empezar a supervisar su progreso</p>
      <button class="btn" onclick="openAddMemberModal()">➕ Agregar Primer Miembro</button>
    </div>
  <?php else: ?>
    <div class="team-members-grid">
      <?php foreach ($teamMembers as $member): ?>
        <div class="member-card">
          <div class="member-header">
            <div class="member-avatar">
              <?= substr($member['username'], 0, 1) ?>
            </div>
            <div class="member-info">
              <h3><?= esc($member['username']) ?></h3>
              <span class="member-level">Nivel <?= $member['current_level'] ?> • <?= number_format($member['total_points']) ?> pts</span>
            </div>
            <div class="member-actions">
              <button class="btn-icon" onclick="removeMember(<?= $member['team_member_id'] ?>, '<?= esc($member['username']) ?>')" title="Remover del equipo">
                🗑️
              </button>
            </div>
          </div>

          <div class="member-stats-grid">
            <div class="mini-stat">
              <span class="mini-stat-value"><?= $member['pending_tasks'] ?></span>
              <span class="mini-stat-label">Pendientes</span>
            </div>
            <div class="mini-stat">
              <span class="mini-stat-value overdue"><?= $member['overdue_tasks'] ?></span>
              <span class="mini-stat-label">Vencidas</span>
            </div>
            <div class="mini-stat">
              <span class="mini-stat-value upcoming"><?= $member['upcoming_tasks'] ?></span>
              <span class="mini-stat-label">Próximas</span>
            </div>
            <div class="mini-stat">
              <span class="mini-stat-value completed"><?= $member['completed_tasks'] ?></span>
              <span class="mini-stat-label">Completadas</span>
            </div>
          </div>

          <div class="member-progress">
            <div class="progress-info">
              <span>Progreso</span>
              <span><?= $member['total_tasks'] > 0 ? round(($member['completed_tasks'] / $member['total_tasks']) * 100) : 0 ?>%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?= $member['total_tasks'] > 0 ? round(($member['completed_tasks'] / $member['total_tasks']) * 100) : 0 ?>%"></div>
            </div>
          </div>

          <div class="member-meta">
            <span>🔥 Racha: <?= $member['current_streak'] ?> días</span>
            <span>🍅 Pomodoros: <?= $member['total_pomodoros'] ?></span>
          </div>

          <div class="member-footer">
            <button class="btn btn-sm" onclick="viewMemberTasks(<?= $member['team_member_id'] ?>, '<?= esc($member['username']) ?>')">
              📋 Ver Tareas
            </button>
            <button class="btn btn-sm" onclick="openChatWithMember(<?= $member['team_member_id'] ?>, '<?= esc($member['username']) ?>')">
              💬 Chat
            </button>
            <button class="btn btn-sm" onclick="openNotesModal(<?= $member['team_member_id'] ?>, '<?= esc($member['username']) ?>', '<?= esc($member['notes']) ?>')">
              📝 Notas
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: Agregar Miembro -->
<div id="addMemberModal" class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2>➕ Agregar Miembro al Equipo</h2>
        <button class="modal-close" onclick="closeAddMemberModal()">&times;</button>
      </div>
      <form action="api/supervisor_api.php" method="post">
        <input type="hidden" name="action" value="add_member">
        
        <label>Seleccionar Usuario</label>
        <select name="member_id" required>
          <option value="">-- Seleccionar --</option>
          <?php foreach ($availableUsers as $user): ?>
            <option value="<?= $user['id'] ?>"><?= esc($user['username']) ?> (<?= esc($user['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
        
        <label>Notas (opcional)</label>
        <textarea name="notes" rows="3" placeholder="Información adicional sobre este miembro..."></textarea>
        
        <div class="modal-actions">
          <button class="btn" type="submit">✅ Agregar</button>
          <button class="btn red" type="button" onclick="closeAddMemberModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Notas del Miembro -->
<div id="notesModal" class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📝 Notas: <span id="notesMemberName"></span></h2>
        <button class="modal-close" onclick="closeNotesModal()">&times;</button>
      </div>
      <form action="api/supervisor_api.php" method="post">
        <input type="hidden" name="action" value="update_notes">
        <input type="hidden" name="member_id" id="notesMemberId">
        
        <label>Notas sobre este miembro</label>
        <textarea name="notes" id="notesTextarea" rows="6" placeholder="Escribe notas sobre el desempeño, objetivos, etc..."></textarea>
        
        <div class="modal-actions">
          <button class="btn" type="submit">💾 Guardar</button>
          <button class="btn red" type="button" onclick="closeNotesModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Chat Widget Container -->
<div id="chat-widget-container"></div>

<script src="../../assets/js/pages/supervisor.js"></script>
<link rel="stylesheet" href="../../assets/css/chat.css">
<script src="../../assets/js/chat-client.js"></script>
<script src="../../assets/js/chat-widget.js"></script>

<script>
// Configurar datos del usuario para el chat
document.body.dataset.userId = '<?= $userId ?>';
document.body.dataset.username = '<?= esc($username) ?>';
document.body.dataset.sessionToken = '<?= session_id() ?>';

// Función para abrir chat con un miembro
function openChatWithMember(memberId, memberName) {
  if (window.chatWidget) {
    window.chatWidget.openChat({
      id: memberId,
      username: memberName,
      isOnline: false // Se actualizará con el estado real
    });
    window.chatWidget.toggleWidget();
  } else {
    alert('El chat no está disponible en este momento');
  }
}
</script>
</body>
</html>
