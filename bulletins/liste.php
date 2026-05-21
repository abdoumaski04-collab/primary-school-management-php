<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant', 'parent', 'eleve']);
require_once '../config.php';

$id_eleve = $_GET['id_eleve'] ?? null;

// Enforce eleve role limits
if ($_SESSION['role'] === 'eleve') {
    $stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $u = $stmt_user->fetch();
    
    $stmt_e = $pdo->prepare("SELECT id FROM eleves WHERE nom = ? AND prenom = ?");
    $stmt_e->execute([$u['nom'], $u['prenom']]);
    $e_info = $stmt_e->fetch();
    
    if (!$e_info || ($id_eleve && $e_info['id'] != $id_eleve)) {
        die("Accès non autorisé.");
    }
    $id_eleve = $e_info['id'];
}

$where = "WHERE 1=1";
$params = [];

if ($id_eleve) {
    if ($_SESSION['role'] === 'parent') {
        $check = $pdo->prepare("SELECT 1 FROM eleves WHERE id = ? AND id_parent = ?");
        $check->execute([$id_eleve, $_SESSION['user_id']]);
        if (!$check->fetch()) die("Accès non autorisé.");
    }
    $where .= " AND b.id_eleve = ?";
    $params[] = $id_eleve;
    
    if ($_SESSION['role'] === 'eleve') {
        $where .= " AND b.statut IN ('publie', 'consulte')";
    }
} elseif ($_SESSION['role'] === 'parent') {
    // Parent sees only their children's bulletins
    $where .= " AND e.id_parent = ?";
    $params[] = $_SESSION['user_id'];
    
    // Parent can only see published or consulted bulletins (not 'en_cours' or 'genere' unless published by school)
    $where .= " AND b.statut IN ('publie', 'consulte')";
} else {
    // Teachers and Admins don't need restriction on view, except maybe class relations, but left simple for admin.
}

$query = "SELECT b.*, e.nom, e.prenom, c.niveau as classe_niveau 
          FROM bulletins b 
          JOIN eleves e ON b.id_eleve = e.id 
          LEFT JOIN classes c ON e.id_classe = c.id 
          $where 
          ORDER BY b.date_generation DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bulletins = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publier']) && $_SESSION['role'] === 'directeur') {
    $id_b = $_POST['publier'];
    $pdo->prepare("UPDATE bulletins SET statut = 'publie' WHERE id = ?")->execute([$id_b]);
    header("Location: liste.php");
    exit;
}

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Bulletins Scolaires</h2>
    <?php if(in_array($_SESSION['role'], ['directeur', 'surveillant'])): ?>
        <a href="generer.php" class="btn btn-primary fw-bold shadow-sm">Générer Bulletins</a>
    <?php endif; ?>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Élève</th>
                        <th>Classe</th>
                        <th>Trimestre</th>
                        <th>Moyenne</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bulletins as $b): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($b['nom'] . ' ' . $b['prenom']) ?></td>
                            <td><?= htmlspecialchars($b['classe_niveau'] ?? 'N/A') ?></td>
                            <td><?= $b['trimestre'] ?></td>
                            <td><span class="badge <?= $b['moyenne_generale'] >= 10 ? 'bg-success' : 'bg-danger' ?> fs-6"><?= htmlspecialchars($b['moyenne_generale']) ?></span></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if($b['statut'] == 'genere') $badge = 'bg-info text-dark';
                                if($b['statut'] == 'publie') $badge = 'bg-primary';
                                if($b['statut'] == 'consulte') $badge = 'bg-success';
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($b['statut'])) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="voir.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">Voir Détails</a>
                                <?php if($_SESSION['role'] === 'directeur' && $b['statut'] === 'genere'): ?>
                                    <form method="POST" class="d-inline">
                                        <button type="submit" name="publier" value="<?= $b['id'] ?>" class="btn btn-sm btn-success">Publier</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($bulletins) === 0): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Aucun bulletin trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
