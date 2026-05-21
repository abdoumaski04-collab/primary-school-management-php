<?php
require_once '../session_check.php';
// Parents see their children's, everyone else sees all if they have the eleve id
require_once '../config.php';

$id_eleve = $_GET['id_eleve'] ?? null;
if (!$id_eleve) die("Dossier introuvable.");

$stmt_e = $pdo->prepare("SELECT * FROM eleves WHERE id = ?");
$stmt_e->execute([$id_eleve]);
$eleve = $stmt_e->fetch();

if (!$eleve) die("Élève non trouvé.");
if ($_SESSION['role'] === 'parent' && $eleve['id_parent'] != $_SESSION['user_id']) die("Accès refusé.");

// Get or auto-created dossier
$stmt_d = $pdo->prepare("SELECT * FROM dossiers_financiers WHERE id_eleve = ?");
$stmt_d->execute([$id_eleve]);
$dossier = $stmt_d->fetch();

if (!$dossier) {
    if (in_array($_SESSION['role'], ['directeur', 'surveillant'])) {
        $pdo->prepare("INSERT INTO dossiers_financiers (id_eleve, solde, statut, date_creation) VALUES (?, 0, 'actif', CURDATE())")->execute([$id_eleve]);
        $dossier = ['id' => $pdo->lastInsertId(), 'solde' => 0, 'statut' => 'actif'];
    } else {
        die("Dossier en cours de création par l'administration.");
    }
}

// Retard check button purely for demonstration of the feature
if (isset($_POST['retard']) && in_array($_SESSION['role'], ['directeur', 'surveillant'])) {
    $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Retard Paiement', 'Retard de paiement détecté. Veuillez régulariser sous 5 jours pour éviter la suspension de votre enfant.', NOW())")->execute([$eleve['id_parent']]);
    $msg_retard = "Notification de retard envoyée au parent.";
}
if (isset($_POST['suspendre']) && in_array($_SESSION['role'], ['directeur', 'surveillant'])) {
    $pdo->prepare("UPDATE eleves SET statut = 'suspendu' WHERE id = ?")->execute([$id_eleve]);
    $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Suspension', 'Le statut de votre enfant a été mis à \"suspendu\" suite à un défaut de paiement.', NOW())")->execute([$eleve['id_parent']]);
    $msg_retard = "L'élève a été suspendu.";
    $eleve['statut'] = 'suspendu'; // update ui
}

$stmt_p = $pdo->prepare("SELECT * FROM paiements WHERE id_dossier = ? ORDER BY date_transaction DESC");
$stmt_p->execute([$dossier['id']]);
$paiements = $stmt_p->fetchAll();

include '../header.php';
?>
<div class="row mb-4">
    <div class="col d-flex justify-content-between align-items-center">
        <h2 class="fw-bold">Dossier Financier : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h2>
        <?php if(in_array($_SESSION['role'], ['parent', 'surveillant', 'directeur'])): ?>
            <a href="saisir.php?id_dossier=<?= $dossier['id'] ?>" class="btn btn-success fw-bold shadow-sm">Effectuer un Paiement</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($msg_retard)): ?>
    <div class="alert alert-info px-4 py-3 border-0 shadow-sm"><?= $msg_retard ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center border-bottom border-primary border-4 rounded-bottom">
                <h5 class="text-muted fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Solde Actuel Cumulé</h5>
                <h1 class="display-4 fw-bold text-primary mb-3"><?= number_format($dossier['solde'], 2, ',', ' ') ?> €</h1>
                <div>
                    <span class="badge <?= $eleve['statut'] == 'suspendu' ? 'bg-danger' : 'bg-success' ?> fs-6 py-2 px-3">Statut Scolaire : <?= ucfirst($eleve['statut']) ?></span>
                </div>
                
                <?php if(in_array($_SESSION['role'], ['directeur', 'surveillant'])): ?>
                    <form method="POST" class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" name="retard" class="btn btn-sm btn-outline-warning text-dark flex-grow-1" onclick="return confirm('Notifier le parent d\'un retard ?');">Signaler Retard (5j)</button>
                        <button type="submit" name="suspendre" class="btn btn-sm btn-outline-danger flex-grow-1" onclick="return confirm('Suspendre l\'élève (plus de 5j de retard) ?');">Suspendre Élève</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Historique des Paiements</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($paiements as $p): ?>
                        <li class="list-group-item p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1">+ <?= number_format($p['montant'], 2, ',', ' ') ?> €</h5>
                                <div class="text-muted" style="font-size: 0.95rem;">
                                    Ref: #P<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?> | Mode : <span class="fw-semibold text-dark"><?= ucfirst($p['mode']) ?></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="mb-1">
                                    <?php
                                    $p_badge = 'bg-secondary';
                                    if($p['statut'] == 'valide') $p_badge = 'bg-success';
                                    if($p['statut'] == 'en_attente') $p_badge = 'bg-warning text-dark';
                                    if($p['statut'] == 'refuse') $p_badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $p_badge ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['statut']))) ?></span>
                                </div>
                                <small class="text-muted"><?= htmlspecialchars($p['date_transaction']) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($paiements)): ?>
                        <li class="list-group-item p-5 text-center text-muted">Aucun paiement effectué pour le moment.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
