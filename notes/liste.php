<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant', 'parent', 'eleve']);
require_once '../config.php';

$id_classe = $_GET['id_classe'] ?? null;
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

if ($id_classe) {
    // Basic verification for teacher
    if ($_SESSION['role'] === 'enseignant') {
        $check = $pdo->prepare("SELECT 1 FROM enseignant_classe WHERE id_enseignant = ? AND id_classe = ?");
        $check->execute([$_SESSION['user_id'], $id_classe]);
        if (!$check->fetch()) die("Accès non autorisé.");
    }
    $where .= " AND e.id_classe = ?";
    $params[] = $id_classe;
}

if ($id_eleve) {
    if ($_SESSION['role'] === 'parent') {
        $check = $pdo->prepare("SELECT 1 FROM eleves WHERE id = ? AND id_parent = ?");
        $check->execute([$id_eleve, $_SESSION['user_id']]);
        if (!$check->fetch()) die("Accès non autorisé à cet élève.");
    }
    $where .= " AND n.id_eleve = ?";
    $params[] = $id_eleve;
}

$query = "SELECT n.*, e.nom as eleve_nom, e.prenom as eleve_prenom, m.nom as matiere_nom 
          FROM notes n 
          JOIN eleves e ON n.id_eleve = e.id 
          JOIN matieres m ON n.id_matiere = m.id 
          $where 
          ORDER BY n.trimestre ASC, n.date_evaluation DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notes = $stmt->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Liste des Notes</h2>
    <?php if($_SESSION['role'] === 'enseignant'): ?>
        <a href="saisir.php" class="btn btn-primary fw-bold shadow-sm">Saisir des notes</a>
    <?php endif; ?>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Élève</th>
                        <th>Matière</th>
                        <th>Trimestre</th>
                        <th>Note</th>
                        <th>Date d'évaluation</th>
                        <?php if($_SESSION['role'] === 'enseignant'): ?>
                        <th class="text-end pe-4">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($notes as $n): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($n['eleve_nom'] . ' ' . $n['eleve_prenom']) ?></td>
                            <td><?= htmlspecialchars($n['matiere_nom']) ?></td>
                            <td><?= htmlspecialchars($n['trimestre']) ?></td>
                            <td><span class="badge <?= $n['valeur'] >= 10 ? 'bg-success' : 'bg-danger' ?> fs-6"><?= htmlspecialchars($n['valeur']) ?> / 20</span></td>
                            <td><?= htmlspecialchars($n['date_evaluation']) ?></td>
                            <?php if($_SESSION['role'] === 'enseignant'): ?>
                            <td class="text-end pe-4">
                                <a href="modifier.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($notes) === 0): ?>
                        <tr><td colspan="<?= $_SESSION['role'] === 'enseignant' ? '6' : '5' ?>" class="text-center py-4 text-muted">Aucune note trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
