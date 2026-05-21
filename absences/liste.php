<?php
require_once '../session_check.php';
// Everyone needs access based on their roles
require_once '../config.php';

$where = "WHERE 1=1";
$params = [];

if ($_SESSION['role'] === 'parent') {
    $where .= " AND e.id_parent = ?";
    $params[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'enseignant') {
    // Teachers see absences of their classes
    $where .= " AND e.id_classe IN (SELECT id_classe FROM enseignant_classe WHERE id_enseignant = ?)";
    $params[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'eleve') {
    $stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
    $stmt_u->execute([$_SESSION['user_id']]);
    $u = $stmt_u->fetch();
    
    $where .= " AND e.nom = ? AND e.prenom = ?";
    $params[] = $u['nom'];
    $params[] = $u['prenom'];
}

if (isset($_GET['id_eleve'])) {
    $where .= " AND a.id_eleve = ?";
    $params[] = $_GET['id_eleve'];
}

$query = "SELECT a.*, e.nom, e.prenom, c.niveau, c.nom as nom_classe 
          FROM absences a 
          JOIN eleves e ON a.id_eleve = e.id 
          LEFT JOIN classes c ON e.id_classe = c.id 
          $where 
          ORDER BY a.date_signalement DESC, a.date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$absences = $stmt->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Registre des Absences</h2>
    <?php if(in_array($_SESSION['role'], ['enseignant', 'surveillant'])): ?>
        <a href="saisir.php" class="btn btn-danger fw-bold shadow-sm">Saisir Absences</a>
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
                        <th>Date & Moment</th>
                        <th>Signalée le</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($absences as $a): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($a['nom'] . ' ' . $a['prenom']) ?></td>
                            <td><?= htmlspecialchars($a['niveau'] . ' ' . $a['nom_classe']) ?></td>
                            <td><strong><?= htmlspecialchars($a['date']) ?></strong> <span class="text-muted">(<?= str_replace('_', '-', $a['demi_journee']) ?>)</span></td>
                            <td class="text-muted"><small><?= htmlspecialchars($a['date_signalement']) ?></small></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if($a['statut'] == 'signalee') $badge = 'bg-warning text-dark';
                                if($a['statut'] == 'justifiee') $badge = 'bg-success';
                                if($a['statut'] == 'non_justifiee') $badge = 'bg-danger';
                                if($a['statut'] == 'archivee') $badge = 'bg-dark text-white opacity-50';
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $a['statut']))) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if(in_array($_SESSION['role'], ['surveillant']) && $a['statut'] === 'signalee'): ?>
                                    <a href="justifier.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-success">Justifier</a>
                                <?php endif; ?>
                                <?php if(in_array($_SESSION['role'], ['directeur', 'surveillant']) && $a['statut'] !== 'archivee'): ?>
                                    <a href="archiver.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary">Archiver</a>
                                <?php endif; ?>
                                <?php if($a['statut'] === 'justifiee'): ?>
                                    <button class="btn btn-sm btn-light border" disabled>Justifiée</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($absences) === 0): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Aucune absence enregistrée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
