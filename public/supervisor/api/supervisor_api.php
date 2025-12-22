<?php
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../services/SupervisorService.php';
require_once __DIR__ . '/../../../src/db.php';

$pdo = get_pdo();
$supervisorService = new SupervisorService($pdo);
$supervisorId = $_SESSION['user_id'];

// Verificar que el usuario sea supervisor
if (!$supervisorService->isSupervisor($supervisorId)) {
    header('Location: ../team.php?error=not_supervisor');
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_member':
        $memberId = intval($_POST['member_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($memberId <= 0) {
            header('Location: ../team.php?error=invalid_member');
            exit;
        }
        
        $result = $supervisorService->addTeamMember($supervisorId, $memberId, $notes);
        
        if ($result['success']) {
            header('Location: ../team.php?success=member_added');
        } else {
            header('Location: ../team.php?error=' . urlencode($result['error']));
        }
        exit;
        break;
        
    case 'remove_member':
        $memberId = intval($_POST['member_id'] ?? 0);
        
        if ($memberId <= 0) {
            header('Location: ../team.php?error=invalid_member');
            exit;
        }
        
        $result = $supervisorService->removeTeamMember($supervisorId, $memberId);
        
        if ($result['success']) {
            header('Location: ../team.php?success=member_removed');
        } else {
            header('Location: ../team.php?error=' . urlencode($result['error']));
        }
        exit;
        break;
        
    case 'update_notes':
        $memberId = intval($_POST['member_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($memberId <= 0) {
            header('Location: ../team.php?error=invalid_member');
            exit;
        }
        
        if (!$supervisorService->hasAccessToMember($supervisorId, $memberId)) {
            header('Location: ../team.php?error=no_access');
            exit;
        }
        
        $result = $supervisorService->updateMemberNotes($supervisorId, $memberId, $notes);
        
        if ($result['success']) {
            header('Location: ../team.php?success=notes_updated');
        } else {
            header('Location: ../team.php?error=' . urlencode($result['error']));
        }
        exit;
        break;
        
    default:
        header('Location: ../team.php?error=invalid_action');
        exit;
}
