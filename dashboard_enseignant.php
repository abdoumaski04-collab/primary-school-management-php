<?php
require_once 'session_check.php';
checkRole('enseignant');
require_once 'config.php';

// Get classes assigned to the teacher
$stmt_classes = $pdo->prepare("SELECT c.* FROM classes c JOIN enseignant_classe ec ON c.id = ec.id_classe WHERE ec.id_enseignant = ?");
$stmt_classes->execute([$_SESSION['user_id']]);
$classes = $stmt_classes->fetchAll();

include 'header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Tableau de bord - Enseignant</h2>
        <p class="text-muted">Suivez vos classes et vos élèves.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Mes Classes</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($classes) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($classes as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <span class="fw-semibold" style="font-size: 1.1rem;"><?= htmlspecialchars($c['niveau'] . ' - ' . $c['nom']) ?></span>
                                <div>
                                    <a href="/umllast/notes/liste.php?id_classe=<?= $c['id'] ?>" class="btn btn-sm btn-info text-white me-2 px-3">Notes</a>
                                    <a href="/umllast/absences/saisir.php?id_classe=<?= $c['id'] ?>" class="btn btn-sm btn-warning text-dark px-3">Saisir Absences</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-4">
                        <p class="text-muted mb-0">Aucune classe assignée pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Actions Rapides</h5>
            </div>
            <div class="card-body d-flex flex-column gap-3 p-4">
                <a href="/umllast/notes/saisir.php" class="btn btn-primary btn-lg w-100">Saisir des notes</a>
                <a href="/umllast/absences/saisir.php" class="btn btn-danger btn-lg w-100">Signaler une absence</a>
                <a href="/umllast/absences/liste.php" class="btn btn-warning text-dark btn-lg w-100">Registre des absences</a>
                <a href="/umllast/messages/boite.php" class="btn btn-outline-secondary btn-lg w-100">Messages</a>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
