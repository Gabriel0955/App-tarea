<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';
$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

// Si es POST, crear el proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $projectService->createProject($userId, $_POST);
    
    if ($result['success']) {
        header('Location: project_view.php?id=' . $result['id']);
        exit;
    } else {
        $error = $result['error'] ?? 'Error al crear el proyecto';
    }
}

$pageTitle = "Nuevo Proyecto";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            justify-content: flex-end;
        }
        
        .btn-primary {
            background: #1976d2;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .form-help {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div style="margin-bottom: 24px;">
            <h1>📂 Crear Nuevo Proyecto</h1>
            <p style="color: #666;">Crea un proyecto para organizar tus tareas y hacer seguimiento del progreso</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nombre del Proyecto *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        required 
                        placeholder="Ej: Sistema de Ventas, App Móvil, Migración BD..."
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Describe el objetivo y alcance del proyecto..."
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div class="form-help">Opcional: Agrega detalles sobre qué planeas lograr</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="En Desarrollo" selected>En Desarrollo</option>
                            <option value="En Pruebas">En Pruebas</option>
                            <option value="En Producción">En Producción</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Prioridad</label>
                        <select id="priority" name="priority">
                            <option value="Alta">🔴 Alta</option>
                            <option value="Media" selected>🟡 Media</option>
                            <option value="Baja">🟢 Baja</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Fecha de Inicio</label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            value="<?php echo $_POST['start_date'] ?? date('Y-m-d'); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="target_date">Fecha Objetivo</label>
                        <input 
                            type="date" 
                            id="target_date" 
                            name="target_date"
                            value="<?php echo $_POST['target_date'] ?? ''; ?>"
                        >
                        <div class="form-help">Opcional: Fecha límite para completar el proyecto</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Categoría</label>
                        <input 
                            type="text" 
                            id="category" 
                            name="category" 
                            placeholder="Ej: Backend, Frontend, DevOps..."
                            value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="repository_url">URL del Repositorio</label>
                        <input 
                            type="url" 
                            id="repository_url" 
                            name="repository_url" 
                            placeholder="https://github.com/..."
                            value="<?php echo htmlspecialchars($_POST['repository_url'] ?? ''); ?>"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notas Adicionales</label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        placeholder="Tecnologías, dependencias, consideraciones especiales..."
                    ><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="projects.php" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">✨ Crear Proyecto</button>
                </div>
            </form>
        </div>
        
        <div style="margin-top: 24px; padding: 16px; background: #e3f2fd; border-radius: 8px; color: #1976d2;">
            <strong>💡 Tip:</strong> Después de crear el proyecto, podrás agregar tareas específicas y hacer seguimiento del progreso diario.
        </div>
    </div>
</body>
</html>
