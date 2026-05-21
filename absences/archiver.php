<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant']);
require_once '../config.php';

$id_absence = $_GET['id'] ?? null;
if ($id_absence) {
    try {
        $stmt = $pdo->prepare("UPDATE absences SET statut = 'archivee' WHERE id = ?");
        $stmt->execute([$id_absence]);
    } catch(Exception $e) {}
}

header("Location: liste.php");
exit;
