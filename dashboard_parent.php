<?php
require_once 'session_check.php';
checkRole('parent');
require_once 'config.php';

// Get children
$stmt_children = $pdo->prepare("SELECT * FROM eleves WHERE id_parent = ?");
$stmt_children->execute([$_SESSION['user_id']]);
$children = $stmt_children->fetchAll();

// Get unread notifications
$stmt_notif = $pdo->prepare("SELECT COUNT(*) as unread FROM notifications WHERE id_utilisateur = ? AND est_lue = 0");
$stmt_notif->execute([$_SESSION['user_id']]);
$unread_notifs = $stmt_notif->fetch()['unread'];

include 'header.php';
?>
<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="fw-bold">Espace Parent</h2>
        <p class="text-muted">Suivez la scolarité de vos enfants.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex flex-wrap gap-2">
            <a href="/umllast/inscriptions/formulaire.php" class="btn btn-success shadow-sm px-4">Nouvelle Inscription</a>
            <a href="/umllast/messages/boite.php" class="btn btn-primary shadow-sm px-4">Messagerie</a>
            <a href="/umllast/notifications/liste.php" class="btn btn-info text-white shadow-sm px-4">
                Notifications
                <?php if($unread_notifs > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $unread_notifs ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <?php if (count($children) > 0): ?>
        <?php foreach($children as $child): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h4 class="mb-0 fw-bold text-primary"><?= htmlspecialchars($child['prenom'] . ' ' . $child['nom']) ?></h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <span class="badge bg-secondary p-2 me-2 mb-2" style="font-size: 0.9em;">Statut: <?= htmlspecialchars(ucfirst($child['statut'])) ?></span>
                            <span class="badge bg-light text-dark border p-2 mb-2" style="font-size: 0.9em;">Né(e) le: <?= htmlspecialchars($child['date_naissance']) ?></span>
                        </div>
                        <div class="mt-auto d-grid gap-2">
                            <?php if ($child['id_classe']): ?>
                            <a href="/umllast/edt/voir.php?id_classe=<?= $child['id_classe'] ?>" class="btn btn-outline-info text-dark">Emploi du Temps</a>
                            <?php endif; ?>
                            <a href="/umllast/bulletins/liste.php?id_eleve=<?= $child['id'] ?>" class="btn btn-outline-primary">Bulletins</a>
                            <a href="/umllast/absences/liste.php?id_eleve=<?= $child['id'] ?>" class="btn btn-outline-warning text-dark">Absences</a>
                            <a href="/umllast/paiements/dossier.php?id_eleve=<?= $child['id'] ?>" class="btn btn-outline-success">Scolarité & Paiements</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col">
            <div class="alert alert-info py-4 text-center border-0 shadow-sm">
                Vous n'avez pas encore d'enfant inscrit.<br>
                <a href="/umllast/inscriptions/formulaire.php" class="alert-link">Soumettre un premier dossier d'inscription</a>.
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
