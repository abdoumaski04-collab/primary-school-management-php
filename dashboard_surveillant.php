<?php
require_once 'session_check.php';
checkRole('surveillant');
require_once 'config.php';

// Fetch pending inscriptions
$stmt_insc = $pdo->query("SELECT COUNT(*) as nb FROM dossiers_inscription WHERE statut = 'soumis'");
$pending_inscriptions = $stmt_insc->fetch()['nb'];

// Fetch unarchived absences
$stmt_abs = $pdo->query("SELECT COUNT(*) as nb FROM absences WHERE statut != 'archivee'");
$pending_absences = $stmt_abs->fetch()['nb'];

include 'header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Tableau de bord - Surveillant</h2>
        <p class="text-muted">Vérifications et gestion de la vie scolaire.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card bg-warning text-dark shadow border-0 rounded-lg">
            <div class="card-body">
                <h5 class="card-title opacity-75">Inscriptions en attente</h5>
                <p class="card-text display-4 fw-bold"><?= $pending_inscriptions ?></p>
                <a href="/umllast/inscriptions/liste.php" class="btn btn-dark mt-2 px-4">Examiner</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card bg-danger text-white shadow border-0 rounded-lg">
            <div class="card-body">
                <h5 class="card-title text-light opacity-75">Absences non archivées</h5>
                <p class="card-text display-4 fw-bold"><?= $pending_absences ?></p>
                <a href="/umllast/absences/liste.php" class="btn btn-light mt-2 px-4 shadow-sm">Gérer</a>
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
                    <a href="/umllast/edt/voir.php" class="btn btn-outline-info px-4 py-2">Emplois du Temps</a>
                    <a href="/umllast/paiements/valider.php" class="btn btn-outline-success px-4 py-2">Valider Paiements Cash</a>
                    <a href="/umllast/messages/boite.php" class="btn btn-outline-primary px-4 py-2">Messagerie</a>
                    <a href="/umllast/eleves/liste.php" class="btn btn-outline-secondary px-4 py-2">Consulter les élèves</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
