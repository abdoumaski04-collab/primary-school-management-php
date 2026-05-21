<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant', 'parent', 'eleve']);
require_once '../config.php';

$id_bulletin = $_GET['id'] ?? null;
if (!$id_bulletin) {
    header("Location: liste.php");
    exit;
}

$stmt_b = $pdo->prepare("SELECT b.*, e.nom, e.prenom, e.id_parent, c.niveau as classe_niveau, c.nom as classe_nom, c.annee_scolaire 
                         FROM bulletins b JOIN eleves e ON b.id_eleve = e.id 
                         LEFT JOIN classes c ON e.id_classe = c.id 
                         WHERE b.id = ?");
$stmt_b->execute([$id_bulletin]);
$bulletin = $stmt_b->fetch();

if (!$bulletin) die("Bulletin introuvable.");

if ($_SESSION['role'] === 'parent' && $bulletin['id_parent'] != $_SESSION['user_id']) {
    die("Accès non autorisé.");
}

if ($_SESSION['role'] === 'eleve') {
    $stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
    $stmt_u->execute([$_SESSION['user_id']]);
    $u = $stmt_u->fetch();
    if ($bulletin['nom'] !== $u['nom'] || $bulletin['prenom'] !== $u['prenom']) {
        die("Accès non autorisé.");
    }
    if (!in_array($bulletin['statut'], ['publie', 'consulte'])) {
        die("Bulletin en attente de publication.");
    }
}

// Update status to 'consulte' if parent or eleve views a published bulletin
if (in_array($_SESSION['role'], ['parent', 'eleve']) && $bulletin['statut'] === 'publie') {
    $pdo->prepare("UPDATE bulletins SET statut = 'consulte' WHERE id = ?")->execute([$id_bulletin]);
    $bulletin['statut'] = 'consulte';
}

$stmt_n = $pdo->prepare("SELECT n.valeur, n.date_evaluation, m.nom as matiere, m.coefficient 
                         FROM notes n JOIN matieres m ON n.id_matiere = m.id 
                         WHERE n.id_eleve = ? AND n.trimestre = ?");
$stmt_n->execute([$bulletin['id_eleve'], $bulletin['trimestre']]);
$notes = $stmt_n->fetchAll();

include '../header.php';
?>
<div class="row mb-3 d-print-none">
    <div class="col d-flex justify-content-between">
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">&larr; Retour</a>
        <button onclick="window.print()" class="btn btn-sm btn-dark"><i class="bi bi-printer"></i> Imprimer</button>
    </div>
</div>

<div class="card shadow-sm border border-secondary print-container">
    <div class="card-body p-5">
        <div class="text-center border-bottom pb-4 mb-4">
            <h2 class="fw-bold mb-0 text-uppercase">Bulletin Scolaire</h2>
            <p class="text-muted mb-0 fs-5">Trimestre <?= $bulletin['trimestre'] ?> | Année Scolaire <?= htmlspecialchars($bulletin['annee_scolaire'] ?? 'N/A') ?></p>
        </div>
        
        <div class="row mb-5">
            <div class="col-md-6 border-end">
                <p class="mb-1"><span class="text-muted">Nom de l'élève :</span> <strong class="fs-5"><?= htmlspecialchars($bulletin['nom']) ?></strong></p>
                <p class="mb-0"><span class="text-muted">Prénom :</span> <strong class="fs-5"><?= htmlspecialchars($bulletin['prenom']) ?></strong></p>
            </div>
            <div class="col-md-6 ps-md-4">
                <p class="mb-1"><span class="text-muted">Classe :</span> <strong class="fs-5"><?= htmlspecialchars(($bulletin['classe_niveau'] ?? '').' '.($bulletin['classe_nom'] ?? '')) ?></strong></p>
                <p class="mb-0"><span class="text-muted">Statut :</span> <span class="badge bg-light text-dark border p-1 rounded-0"><?= htmlspecialchars(ucfirst($bulletin['statut'])) ?></span></p>
            </div>
        </div>

        <table class="table table-bordered mb-4 border-dark">
            <thead class="table-light border-dark text-center">
                <tr>
                    <th class="text-start">Matières</th>
                    <th style="width: 15%">Coefficient</th>
                    <th style="width: 15%">Notes</th>
                    <th style="width: 30%">Dernière Évaluation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($notes as $n): ?>
                    <tr>
                        <td class="fw-semibold px-3"><?= htmlspecialchars($n['matiere']) ?></td>
                        <td class="text-center"><?= $n['coefficient'] ?></td>
                        <td class="text-center fw-bold fs-5 <?= $n['valeur'] < 10 ? 'text-danger' : '' ?>"><?= htmlspecialchars($n['valeur']) ?></td>
                        <td class="text-center text-muted"><?= htmlspecialchars($n['date_evaluation']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(count($notes) === 0): ?>
                    <tr><td colspan="4" class="text-center py-3">Aucune note enregistrée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row border p-4 bg-light align-items-center">
            <div class="col-md-6 border-end">
                <h5 class="mb-2 text-muted fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.9rem;">Moyenne Générale</h5>
                <h1 class="display-3 mb-0 fw-bold <?= $bulletin['moyenne_generale'] < 10 ? 'text-danger' : 'text-success' ?>"><?= htmlspecialchars($bulletin['moyenne_generale']) ?><span class="fs-5 text-muted">/20</span></h1>
            </div>
            <div class="col-md-6 ps-md-4">
                <h5 class="mb-2 text-muted fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.9rem;">Appréciation du Conseil</h5>
                <p class="fs-4 fst-italic mb-0">"<?= htmlspecialchars($bulletin['appreciation']) ?>"</p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body { background-color: white; }
    .d-print-none { display: none !important; }
    .shadow-sm, .shadow { box-shadow: none !important; border-color: #000 !important; }
    .card-body { padding: 0 !important; }
    .print-container { border: 0 !important; }
    .navbar { display: none !important; }
}
</style>
<?php include '../footer.php'; ?>
