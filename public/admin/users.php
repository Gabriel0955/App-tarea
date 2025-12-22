<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/theme.php';
require_once __DIR__ . '/../../services/UserService.php';

// Solo admins pueden acceder
require_role('admin');

$pdo = get_pdo();
$user_id = get_current_user_id();
$username = get_current_username();

// Procesar cambio de rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_user_id = intval($_POST['user_id']);
    $new_role_id = intval($_POST['role_id']);
    
    $result = updateUserRole($pdo, $target_user_id, $new_role_id, $user_id);
    
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

// Obtener todos los usuarios con sus roles usando servicio
$users = getAllUsersWithRoles($pdo);

// Obtener todos los roles disponibles usando servicio
$roles = getRoles($pdo);

function esc($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <title>Administración de Usuarios | App-Tareas</title>
    
    <?php echo getThemeStyles(); ?>
    
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="../../assets/css/pages/users-admin.css">
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin-bottom: 8px;">👥 Administración de Usuarios</h1>
            <p class="subtitle" style="color: var(--text-secondary); margin: 0;">
                Gestiona roles y permisos de los usuarios
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a class="btn" href="../index.php" style="padding: 10px 20px;">
                ← Volver
            </a>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div style="background: var(--accent-green); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px;">
            ✓ <?= esc($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div style="background: var(--accent-red); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px;">
            ⚠️ <?= esc($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Leyenda de Roles -->
    <div style="background: var(--bg-card); padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 2px solid var(--border-color);">
        <h3 style="margin: 0 0 12px 0; color: var(--accent-blue);">📋 Roles Disponibles</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <?php foreach ($roles as $role): ?>
                <div style="padding: 10px; background: rgba(0,0,0,0.2); border-radius: 6px;">
                    <div style="font-weight: 600; margin-bottom: 4px;">
                        <?php
                            $badge_style = '';
                            if ($role['name'] === 'admin') $badge_style = 'background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%); color: white;';
                            elseif ($role['name'] === 'manager') $badge_style = 'background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%); color: white;';
                            elseif ($role['name'] === 'user') $badge_style = 'background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;';
                            else $badge_style = 'background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white;';
                        ?>
                        <span class="badge" style="<?= $badge_style ?> padding: 4px 10px; font-size: 0.8rem;">
                            <?= strtoupper($role['name']) ?>
                        </span>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">
                        <?= esc($role['description']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div style="background: var(--bg-card); border-radius: 8px; overflow: hidden; border: 2px solid var(--border-color);">
        <table style="margin: 0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol Actual</th>
                    <th>Stats</th>
                    <th>Registrado</th>
                    <th>Cambiar Rol</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td data-label="ID"><?= $u['id'] ?></td>
                        <td data-label="Usuario">
                            <strong><?= esc($u['username']) ?></strong>
                            <?php if ($u['id'] == $user_id): ?>
                                <span class="badge" style="background: var(--accent-blue); font-size: 0.7rem; margin-left: 6px;">TÚ</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Email"><?= esc($u['email']) ?></td>
                        <td data-label="Rol">
                            <?php
                                $badge_style = '';
                                if ($u['role_name'] === 'admin') $badge_style = 'background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%); color: white;';
                                elseif ($u['role_name'] === 'manager') $badge_style = 'background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%); color: white;';
                                elseif ($u['role_name'] === 'user') $badge_style = 'background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;';
                                else $badge_style = 'background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white;';
                            ?>
                            <span class="badge" style="<?= $badge_style ?>">
                                <?= strtoupper($u['role_name']) ?>
                            </span>
                        </td>
                        <td data-label="Stats">
                            <div style="font-size: 0.85rem; line-height: 1.6;">
                                <div>⭐ Nivel <?= $u['current_level'] ?></div>
                                <div>🏆 <?= number_format($u['total_points']) ?> pts</div>
                                <div>✅ <?= $u['tasks_completed'] ?> tareas</div>
                            </div>
                        </td>
                        <td data-label="Registrado">
                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                            </span>
                        </td>
                        <td data-label="Cambiar Rol">
                            <?php if ($u['id'] == $user_id): ?>
                                <span style="color: var(--text-muted); font-size: 0.85rem;">No puedes cambiar tu propio rol</span>
                            <?php else: ?>
                                <form method="post" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="update_role" value="1">
                                    <select name="role_id" style="padding: 6px 10px; font-size: 0.9rem; min-width: 120px;">
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role['id'] ?>" <?= $role['id'] == $u['role_id'] ? 'selected' : '' ?>>
                                                <?= ucfirst($role['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 0.85rem;">
                                        💾 Guardar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Resumen -->
    <div style="margin-top: 24px; padding: 16px; background: var(--bg-card); border-radius: 8px; border: 2px solid var(--border-color);">
        <h3 style="margin: 0 0 12px 0; color: var(--accent-blue);">📊 Resumen</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
            <div style="text-align: center; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 6px;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--accent-blue);">
                    <?= count($users) ?>
                </div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Usuarios</div>
            </div>
            <?php foreach ($roles as $role): ?>
                <?php
                    $count = count(array_filter($users, function($u) use ($role) {
                        return $u['role_id'] == $role['id'];
                    }));
                ?>
                <div style="text-align: center; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 6px;">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--accent-green);">
                        <?= $count ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <?= ucfirst($role['name']) ?>s
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="../../assets/js/pages/users-admin.js"></script>
</body>
</html>
