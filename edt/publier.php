<?php
require_once '../session_check.php';
checkRole(['surveillant']);
require_once '../config.php';

$id = $_GET['id'] ?? null;
if ($id) {
    try {
        $pdo->prepare("UPDATE emplois_du_temps SET statut = 'publie' WHERE id = ?")->execute([$id]);
        
        $stmt_c = $pdo->prepare("SELECT id_classe FROM emplois_du_temps WHERE id = ?");
        $stmt_c->execute([$id]);
        $id_classe = $stmt_c->fetch()['id_classe'];
        
        header("Location: voir.php?id_classe=" . $id_classe);
        exit;
    } catch(Exception $e) {}
}

header("Location: voir.php");
exit;
