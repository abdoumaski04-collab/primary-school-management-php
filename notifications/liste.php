<?php
require_once '../session_check.php';
// All logged in users can see their notifications
require_once '../config.php';

// Mark as read if id passed
if (isset($_GET['lire'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET est_lue = 1 WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$_GET['lire'], $_SESSION['user_id']]);
    header("Location: liste.php");
    exit;
}

if (isset($_GET['tout_lire'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET est_lue = 1 WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header("Location: liste.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id_utilisateur = ? ORDER BY date_creation DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Vos Notifications</h2>
    <?php if(count($notifications) > 0): ?>
        <a href="liste.php?tout_lire=1" class="btn btn-outline-secondary btn-sm">Tout marquer comme lu</a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-12">
        <?php if(count($notifications) > 0): ?>
            <div class="list-group shadow-sm border-0">
                <?php foreach($notifications as $n): ?>
                    <div class="list-group-item list-group-item-action p-4 <?= $n['est_lue'] ? 'bg-light' : '' ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold <?= $n['est_lue'] ? 'text-muted' : 'text-primary' ?>">
                                <?= !$n['est_lue'] ? '<span class="badge bg-danger p-1 me-2 rounded-circle" style="width: 10px; height: 10px; display:inline-block;"></span>' : '' ?>
                                <?= htmlspecialchars($n['type']) ?>
                            </h6>
                            <small class="text-muted"><?= htmlspecialchars($n['date_creation']) ?></small>
                        </div>
                        <p class="mb-1 mt-2" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($n['contenu'])) ?></p>
                        <?php if(!$n['est_lue']): ?>
                            <a href="liste.php?lire=<?= $n['id'] ?>" class="btn btn-sm btn-link text-decoration-none p-0 mt-2">Marquer comme lu</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="card-body py-5 text-center text-muted">
                    <i class="bi bi-bell-slash fd-1 display-1"></i>
                    <p class="mt-3 fs-5">Vous n'avez aucune notification.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../footer.php'; ?>
