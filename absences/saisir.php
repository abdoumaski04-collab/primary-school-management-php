<?php
require_once '../session_check.php';
checkRole(['enseignant', 'surveillant']);
require_once '../config.php';

$error = '';
$success = '';

// Get classes
$classes = [];
if ($_SESSION['role'] === 'enseignant') {
    $stmt_c = $pdo->prepare("SELECT c.* FROM classes c JOIN enseignant_classe ec ON c.id = ec.id_classe WHERE ec.id_enseignant = ?");
    $stmt_c->execute([$_SESSION['user_id']]);
} else {
    $stmt_c = $pdo->query("SELECT * FROM classes");
}
$classes = $stmt_c->fetchAll();

$id_classe = $_GET['id_classe'] ?? ($_POST['id_classe'] ?? null);
$eleves = [];

if ($id_classe) {
    $stmt_e = $pdo->prepare("SELECT id, nom, prenom, id_parent FROM eleves WHERE id_classe = ? AND statut IN ('inscrit', 'preInscrit')");
    $stmt_e->execute([$id_classe]);
    $eleves = $stmt_e->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absences'])) {
    $date = $_POST['date'] ?? date('Y-m-d');
    $demi_journee = $_POST['demi_journee'] ?? 'matin';
    
    $pdo->beginTransaction();
    try {
        $stmt_ins = $pdo->prepare("INSERT INTO absences (id_eleve, date, demi_journee, motif, statut, date_signalement) VALUES (?, ?, ?, '', 'signalee', CURDATE())");
        $stmt_notif = $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Absence', ?, NOW())");
        
        $stmt_count_nj = $pdo->prepare("SELECT COUNT(*) as nb FROM absences WHERE id_eleve = ? AND statut IN ('signalee', 'non_justifiee')");
        $stmt_alert = $pdo->prepare("INSERT INTO alertes (id_eleve, type, date_generation, nb_absences_nj) VALUES (?, 'Absences multiples', CURDATE(), ?)");
        
        // Notification to surveillant
        $stmt_survs = $pdo->query("SELECT id FROM utilisateurs WHERE role = 'surveillant'");
        $surveillants = $stmt_survs->fetchAll();
        
        $count = 0;
        foreach ($_POST['absences'] as $id_eleve) {
            // Check if already absent
            $check = $pdo->prepare("SELECT id FROM absences WHERE id_eleve = ? AND date = ? AND demi_journee = ?");
            $check->execute([$id_eleve, $date, $demi_journee]);
            if ($check->fetch()) continue;
            
            $stmt_ins->execute([$id_eleve, $date, $demi_journee]);
            $count++;
            
            // Get student info for notification
            $stmt_info = $pdo->prepare("SELECT nom, prenom, id_parent FROM eleves WHERE id = ?");
            $stmt_info->execute([$id_eleve]);
            $info = $stmt_info->fetch();
            
            // Notify parent
            if ($info['id_parent']) {
                $msg = "Votre enfant {$info['prenom']} a été marqué absent le $date ($demi_journee).";
                $stmt_notif->execute([$info['id_parent'], $msg]);
            }
            
            // Check non-justified threshold
            $stmt_count_nj->execute([$id_eleve]);
            $nb_nj = $stmt_count_nj->fetch()['nb'];
            
            if ($nb_nj > 3) {
                $stmt_alert->execute([$id_eleve, $nb_nj]);
                // Notify surveillants
                foreach($surveillants as $s) {
                    $alert_msg = "Alerte : L'élève {$info['nom']} {$info['prenom']} a accumulé $nb_nj absences non justifiées.";
                    $stmt_notif->execute([$s['id'], $alert_msg]);
                }
            }
        }
        $pdo->commit();
        $success = "$count absence(s) enregistrée(s) et notifiée(s).";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

include '../header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Saisir les Absences</h2>
        <p class="text-muted">L'enregistrement notifie immédiatement les parents correspondants.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row gx-3 gy-2 align-items-center">
                    <div class="col-sm-4">
                        <select name="id_classe" class="form-select" required onchange="this.form.submit()">
                            <option value="">Sélectionner une classe</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $id_classe == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['niveau'] . ' ' . $c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($id_classe): ?>
            <?php if ($error): ?><div class="alert alert-danger px-4 py-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success px-4 py-3"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="id_classe" value="<?= htmlspecialchars($id_classe) ?>">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Liste d'appel</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            <select name="demi_journee" class="form-select form-select-sm" required style="width: auto;">
                                <option value="matin">Matin</option>
                                <option value="apres_midi">Après-midi</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 50px;">Absent</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($eleves as $e): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <input class="form-check-input" type="checkbox" name="absences[]" value="<?= $e['id'] ?>" style="transform: scale(1.5);">
                                        </td>
                                        <td class="fw-semibold text-danger"><?= htmlspecialchars($e['nom']) ?></td>
                                        <td><?= htmlspecialchars($e['prenom']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($eleves)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Aucun élève trouvé.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light text-end">
                        <button type="submit" class="btn btn-danger fw-bold px-4" onclick="return confirm('Confirmer l\'enregistrement de ces absences et l\'envoi des notifications aux parents ?');">Enregistrer les absences</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include '../footer.php'; ?>
