<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';
$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

// Obtener proyectos con estadísticas
$projects = $projectService->getProjectsWithStats($userId, 'all');

$pageTitle = "Proyectos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .projects-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .project-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid var(--project-color);
            cursor: pointer;
        }
        
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .project-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .project-icon {
            font-size: 32px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--project-color);
            border-radius: 10px;
            opacity: 0.9;
        }
        
        .project-info {
            flex: 1;
        }
        
        .project-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 4px 0;
        }
        
        .project-description {
            font-size: 13px;
            color: #666;
            margin: 0;
        }
        
        .project-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1976d2;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--project-color);
            transition: width 0.3s;
        }
        
        .project-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        
        .btn-project {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: #1976d2;
            color: white;
        }
        
        .btn-view:hover {
            background: #1565c0;
        }
        
        .btn-edit {
            background: #f5f5f5;
            color: #333;
        }
        
        .btn-edit:hover {
            background: #e0e0e0;
        }
        
        .project-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-completed {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-archived {
            background: #f5f5f5;
            color: #757575;
        }
        
        .add-project-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            cursor: pointer;
            border: 2px dashed rgba(255,255,255,0.3);
        }
        
        .add-project-card:hover {
            border-color: white;
        }
        
        .add-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .color-picker {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
            margin: 10px 0;
        }
        
        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .color-option:hover {
            transform: scale(1.1);
        }
        
        .color-option.selected {
            border-color: #333;
            transform: scale(1.15);
        }
        
        .icon-picker {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
            margin: 10px 0;
        }
        
        .icon-option {
            width: 40px;
            height: 40px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .icon-option:hover {
            background: #f5f5f5;
        }
        
        .icon-option.selected {
            background: #e3f2fd;
            border-color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="projects-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1>📁 Mis Proyectos</h1>
                <p style="color: #666;">Organiza tus tareas por proyectos</p>
            </div>
            <a href="../index.php" class="btn-project btn-view">← Volver a Tareas</a>
        </div>
        
        <div class="projects-grid">
            <!-- Tarjeta para agregar proyecto -->
            <div class="project-card add-project-card" onclick="openCreateModal()">
                <div class="add-icon">➕</div>
                <h3 style="margin: 0;">Nuevo Proyecto</h3>
                <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">Click para crear</p>
            </div>
            
            <!-- Lista de proyectos -->
            <?php foreach ($projects as $project): ?>
                <div class="project-card" style="--project-color: <?php echo htmlspecialchars($project['color']); ?>;">
                    <div class="project-header">
                        <div class="project-icon"><?php echo htmlspecialchars($project['icon']); ?></div>
                        <div class="project-info">
                            <h3 class="project-name"><?php echo htmlspecialchars($project['name']); ?></h3>
                            <span class="project-status status-<?php echo $project['status']; ?>">
                                <?php echo $project['status'] === 'active' ? 'Activo' : ($project['status'] === 'completed' ? 'Completado' : 'Archivado'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($project['description']): ?>
                        <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="project-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $project['stats']['total_tasks']; ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color: #4caf50;"><?php echo $project['stats']['completed_tasks']; ?></div>
                            <div class="stat-label">Completadas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color: #ff9800;"><?php echo $project['stats']['pending_tasks']; ?></div>
                            <div class="stat-label">Pendientes</div>
                        </div>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $project['stats']['completion_percentage']; ?>%;"></div>
                    </div>
                    <p style="text-align: center; font-size: 12px; color: #666; margin: 5px 0 0 0;">
                        <?php echo number_format($project['stats']['completion_percentage'], 1); ?>% completado
                    </p>
                    
                    <div class="project-actions">
                        <button class="btn-project btn-view" onclick="viewProject(<?php echo $project['id']; ?>)">Ver Tareas</button>
                        <button class="btn-project btn-edit" onclick="editProject(<?php echo $project['id']; ?>)">✏️</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modal para crear/editar proyecto -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Crear Proyecto</h2>
            <form id="projectForm" action="project_api.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="project_id" id="projectId">
                <input type="hidden" name="color" id="selectedColor" value="#1976d2">
                <input type="hidden" name="icon" id="selectedIcon" value="📁">
                
                <label>Nombre del Proyecto *</label>
                <input type="text" name="name" id="projectName" required placeholder="Ej: Desarrollo Web, Marketing 2024">
                
                <label>Descripción (opcional)</label>
                <textarea name="description" id="projectDescription" rows="3" placeholder="Describe el objetivo de este proyecto..."></textarea>
                
                <label>Color</label>
                <div class="color-picker">
                    <div class="color-option selected" style="background: #1976d2;" onclick="selectColor('#1976d2')"></div>
                    <div class="color-option" style="background: #d32f2f;" onclick="selectColor('#d32f2f')"></div>
                    <div class="color-option" style="background: #388e3c;" onclick="selectColor('#388e3c')"></div>
                    <div class="color-option" style="background: #f57c00;" onclick="selectColor('#f57c00')"></div>
                    <div class="color-option" style="background: #7b1fa2;" onclick="selectColor('#7b1fa2')"></div>
                    <div class="color-option" style="background: #0288d1;" onclick="selectColor('#0288d1')"></div>
                    <div class="color-option" style="background: #c2185b;" onclick="selectColor('#c2185b')"></div>
                    <div class="color-option" style="background: #5d4037;" onclick="selectColor('#5d4037')"></div>
                </div>
                
                <label>Icono</label>
                <div class="icon-picker">
                    <div class="icon-option selected" onclick="selectIcon('📁')">📁</div>
                    <div class="icon-option" onclick="selectIcon('💼')">💼</div>
                    <div class="icon-option" onclick="selectIcon('🎯')">🎯</div>
                    <div class="icon-option" onclick="selectIcon('🚀')">🚀</div>
                    <div class="icon-option" onclick="selectIcon('💻')">💻</div>
                    <div class="icon-option" onclick="selectIcon('📱')">📱</div>
                    <div class="icon-option" onclick="selectIcon('🎨')">🎨</div>
                    <div class="icon-option" onclick="selectIcon('📊')">📊</div>
                    <div class="icon-option" onclick="selectIcon('🏠')">🏠</div>
                    <div class="icon-option" onclick="selectIcon('🎓')">🎓</div>
                    <div class="icon-option" onclick="selectIcon('⚡')">⚡</div>
                    <div class="icon-option" onclick="selectIcon('🔥')">🔥</div>
                    <div class="icon-option" onclick="selectIcon('💡')">💡</div>
                    <div class="icon-option" onclick="selectIcon('🎮')">🎮</div>
                    <div class="icon-option" onclick="selectIcon('📚')">📚</div>
                    <div class="icon-option" onclick="selectIcon('🎬')">🎬</div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-project btn-view" style="flex: 1;">Guardar</button>
                    <button type="button" class="btn-project btn-edit" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Crear Proyecto';
            document.getElementById('formAction').value = 'create';
            document.getElementById('projectForm').reset();
            document.getElementById('selectedColor').value = '#1976d2';
            document.getElementById('selectedIcon').value = '📁';
            document.getElementById('projectModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('projectModal').style.display = 'none';
        }
        
        function selectColor(color) {
            document.getElementById('selectedColor').value = color;
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
            event.target.classList.add('selected');
        }
        
        function selectIcon(icon) {
            document.getElementById('selectedIcon').value = icon;
            document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
            event.target.classList.add('selected');
        }
        
        function viewProject(projectId) {
            window.location.href = 'project_view.php?id=' + projectId;
        }
        
        function editProject(projectId) {
            // Aquí podrías cargar los datos del proyecto y abrir el modal
            alert('Funcionalidad de edición en desarrollo');
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('projectModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
