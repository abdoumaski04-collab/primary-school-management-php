<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE utilisateurs SET est_connecte = 0 WHERE id = ?")->execute([$_SESSION['user_id']]);
    $pdo->prepare("DELETE FROM sessions WHERE id_session = ?")->execute([session_id()]);
}

session_destroy();
header("Location: /umllast/index.php");
exit;
