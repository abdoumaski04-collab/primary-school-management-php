<?php
require_once 'session_check.php';
checkRole('directeur');
require_once 'config.php';

// Fetch quick stats for directeur
$stmt_eleves = $pdo->query("SELECT COUNT(*) as nb FROM eleves WHERE statut IN ('inscrit', 'preInscrit')");
$nb_eleves = $stmt_eleves->fetch()['nb'];

$stmt_classes = $pdo->query("SELECT COUNT(*) as nb FROM classes");
$nb_classes = $stmt_classes->fetch()['nb'];

$stmt_profs = $pdo->query("SELECT COUNT(*) as nb FROM utilisateurs WHERE role='enseignant'");
$nb_profs = $stmt_profs->fetch()['nb'];

include 'header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Tableau de bord - Directeur</h2>
        <p class="text-muted">Gérez l'établissement scolaire.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white shadow border-0 rounded-lg">
            <div class="card-body">
                <h5 class="card-title text-light opacity-75">Élèves Actifs</h5>
                <p class="card-text display-4 fw-bold"><?= $nb_eleves ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white shadow border-0 rounded-lg">
            <div class="card-body">
                <h5 class="card-title text-light opacity-75">Classes</h5>
                <p class="card-text display-4 fw-bold"><?= $nb_classes ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white shadow border-0 rounded-lg">
            <div class="card-body">
                <h5 class="card-title text-light opacity-75">Enseignants</h5>
                <p class="card-text display-4 fw-bold"><?= $nb_profs ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Raccourcis de Gestion</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2">
                    <a href="/umllast/classes/liste.php" class="btn btn-outline-primary px-4 py-2">Gérer les classes</a>
                    <a href="/umllast/eleves/liste.php" class="btn btn-outline-primary px-4 py-2">Gérer les élèves</a>
                    <a href="/umllast/inscriptions/liste.php" class="btn btn-outline-primary px-4 py-2">Dossiers d'inscription</a>
                    <a href="/umllast/paiements/liste.php" class="btn btn-outline-primary px-4 py-2">Vue Financière</a>
                    <a href="/umllast/absences/liste.php" class="btn btn-outline-warning text-dark px-4 py-2">Archiver les absences</a>
                    <a href="/umllast/messages/boite.php" class="btn btn-outline-primary px-4 py-2">Messagerie</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
