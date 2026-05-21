<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant', 'eleve']);
require_once '../config.php';

$id_classe = $_GET['id_classe'] ?? null;
$where_clause = "";
$params = [];

// Enforce eleve can only see their own class
if ($_SESSION['role'] === 'eleve') {
    $stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();
    
    $stmt_e = $pdo->prepare("SELECT id_classe FROM eleves WHERE nom = ? AND prenom = ?");
    $stmt_e->execute([$u['nom'], $u['prenom']]);
    $e_info = $stmt_e->fetch();
    
    if (!$e_info || ($id_classe && $e_info['id_classe'] != $id_classe) || !$e_info['id_classe']) {
        die("Accès non autorisé.");
    }
    // Force their class id
    $id_classe = $e_info['id_classe'];
}

// If an ID is passed, filter.
if ($id_classe) {
    $where_clause = "WHERE e.id_classe = ?";
    $params[] = $id_classe;
}

$query = "SELECT e.*, c.niveau, c.nom as classe_nom FROM eleves e LEFT JOIN classes c ON e.id_classe = c.id $where_clause ORDER BY e.nom, e.prenom";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$eleves = $stmt->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Liste des Élèves <?= $id_classe ? 'de la classe sélectionnée' : '' ?></h2>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nom</th>
                        <th>Prénom</th>
                        <th>Classe</th>
                        <th>Date de Naissance</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($eleves as $e): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($e['nom']) ?></td>
                            <td><?= htmlspecialchars($e['prenom']) ?></td>
                            <td><?= $e['classe_nom'] ? htmlspecialchars($e['niveau'] . ' ' . $e['classe_nom']) : '<span class="text-muted">Non assignée</span>' ?></td>
                            <td><?= htmlspecialchars($e['date_naissance']) ?></td>
                            <td>
                                <?php
                                $badge_class = 'bg-secondary';
                                if($e['statut'] == 'inscrit') $badge_class = 'bg-success';
                                elseif($e['statut'] == 'suspendu') $badge_class = 'bg-warning text-dark';
                                elseif($e['statut'] == 'radie') $badge_class = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars(ucfirst($e['statut'])) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="fiche.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info text-white">Fiche</a>
                                <?php if($_SESSION['role'] === 'directeur'): ?>
                                    <a href="affecter_classe.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-warning">Changer Classe</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($eleves) === 0): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Aucun élève trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
