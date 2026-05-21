<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /umllast/index.php');
    exit;
}

// Session validation in DB
require_once __DIR__ . '/config.php';
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE id_session = ?");
$stmt->execute([session_id()]);
$session_db = $stmt->fetch();

if (!$session_db) {
    // Session exists in PHP space but not in DB -> destroy and redirect
    session_destroy();
    header('Location: /umllast/index.php');
    exit;
}

// Update last activity in DB
$stmt = $pdo->prepare("UPDATE sessions SET derniere_activite = NOW() WHERE id_session = ?");
$stmt->execute([session_id()]);

function checkRole($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect to their respective dashboard if not allowed
        header("Location: /umllast/dashboard_" . $_SESSION['role'] . ".php");
        exit;
    }
}
?>
