<?php
require_once 'session_check.php';
checkRole('eleve');
require_once 'config.php';

// Fetch the student's ID from the `eleves` table by matching the `utilisateur` details.
$stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user = $stmt_user->fetch();

$stmt_eleve = $pdo->prepare("SELECT id, id_classe, statut FROM eleves WHERE nom = ? AND prenom = ?");
$stmt_eleve->execute([$user['nom'], $user['prenom']]);
$eleve = $stmt_eleve->fetch();

include 'header.php';
?>
<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="fw-bold">Espace Élève</h2>
        <p class="text-muted">Consultez vos informations scolaires.</p>
    </div>
</div>

<div class="row">
    <?php if ($eleve): ?>
        <div class="col-md-8 mx-auto">
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-primary text-white py-3 border-bottom-0">
                    <h4 class="mb-0 fw-bold">Bienvenue, <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <span class="badge bg-secondary p-2 me-2" style="font-size: 0.9em;">Statut: <?= htmlspecialchars(ucfirst($eleve['statut'])) ?></span>
                    </div>
                    
                    <div class="d-grid gap-3">
                        <?php if ($eleve['id_classe']): ?>
                            <a href="/umllast/edt/voir.php?id_classe=<?= $eleve['id_classe'] ?>" class="btn btn-outline-primary btn-lg d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calendar3 me-2"></i> Mon Emploi du Temps</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="/umllast/eleves/liste.php?id_classe=<?= $eleve['id_classe'] ?>" class="btn btn-outline-success btn-lg d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-people me-2"></i> Ma Liste de Classe</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <div class="alert alert-warning">Vous n'êtes assigné à aucune classe pour le moment.</div>
                        <?php endif; ?>

                        <a href="/umllast/notes/liste.php?id_eleve=<?= $eleve['id'] ?>" class="btn btn-outline-info btn-lg d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-journal-text me-2"></i> Mes Notes</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/umllast/bulletins/liste.php?id_eleve=<?= $eleve['id'] ?>" class="btn btn-outline-dark btn-lg d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-bar-graph me-2"></i> Mes Bulletins</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/umllast/absences/liste.php?id_eleve=<?= $eleve['id'] ?>" class="btn btn-outline-warning text-dark btn-lg d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock-history me-2"></i> Mon Dossier d'Absence</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col">
            <div class="alert alert-danger py-4 text-center border-0 shadow-sm">
                <strong>Erreur :</strong> Votre compte n'est pas encore lié à un dossier élève.<br>
                Veuillez contacter l'administration pour régulariser votre situation.
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
