<?php
require_once '../session_check.php';
checkRole('surveillant');
require_once '../config.php';

$error = '';
$success = '';

$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$id_classe = $_GET['id_classe'] ?? ($_POST['id_classe'] ?? null);

$edt = null;
if ($id_classe) {
    $stmt = $pdo->prepare("SELECT * FROM emplois_du_temps WHERE id_classe = ?");
    $stmt->execute([$id_classe]);
    $edt = $stmt->fetch();
    
    // Auto create empty EDT if not exists
    if (!$edt) {
        $stmt_c = $pdo->prepare("SELECT annee_scolaire FROM classes WHERE id = ?");
        $stmt_c->execute([$id_classe]);
        $annee = $stmt_c->fetch()['annee_scolaire'];
        
        $pdo->prepare("INSERT INTO emplois_du_temps (id_classe, annee_scolaire, statut) VALUES (?, ?, 'brouillon')")->execute([$id_classe, $annee]);
        $edt = ['id' => $pdo->lastInsertId()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_creneau'])) {
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $id_matiere = $_POST['id_matiere'];
    $salle = trim($_POST['salle']);
    
    // Get enseignant from matiere
    $stmt_m = $pdo->prepare("SELECT id_enseignant FROM matieres WHERE id = ?");
    $stmt_m->execute([$id_matiere]);
    $id_enseignant = $stmt_m->fetch()['id_enseignant'];
    
    if (!$id_enseignant) {
        $error = "Cette matière n'a pas d'enseignant affecté.";
    } else {
        // Conflits check -> Time overlap logic: (NewStart < OldEnd) AND (NewEnd > OldStart)
        // AND (Same Teacher OR Same Room) AND Same Day
        // Plus constraint on the current class EDT
        
        $stmt_conflict = $pdo->prepare("
            SELECT c.*, e.id_classe 
            FROM creneaux c 
            JOIN emplois_du_temps e ON c.id_edt = e.id 
            WHERE c.jour = ? AND (c.heure_debut < ? AND c.heure_fin > ?) 
            AND (c.id_enseignant = ? OR c.salle = ? OR c.id_edt = ?)
        ");
        $stmt_conflict->execute([$jour, $heure_fin, $heure_debut, $id_enseignant, $salle, $edt['id']]);
        
        if ($stmt_conflict->fetch()) {
            $error = "Conflit détecté ! Cet enseignant, cette salle, ou cette classe a déjà un cours sur ce créneau.";
        } else {
            $stmt_ins = $pdo->prepare("INSERT INTO creneaux (id_edt, jour, heure_debut, heure_fin, id_matiere, id_enseignant, salle) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_ins->execute([$edt['id'], $jour, $heure_debut, $heure_fin, $id_matiere, $id_enseignant, $salle])) {
                $success = "Créneau ajouté avec succès.";
                // Reset statut to brouillon if modified
                $pdo->prepare("UPDATE emplois_du_temps SET statut = 'brouillon' WHERE id = ?")->execute([$edt['id']]);
            }
        }
    }
}

if (isset($_GET['supprimer_creneau'])) {
    $pdo->prepare("DELETE FROM creneaux WHERE id = ?")->execute([$_GET['supprimer_creneau']]);
    header("Location: creer.php?id_classe=$id_classe");
    exit;
}

$matieres = $pdo->query("SELECT m.*, u.nom as prof_nom FROM matieres m JOIN utilisateurs u ON m.id_enseignant = u.id")->fetchAll();
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-4">
        <h2 class="fw-bold">Gestion Emploi du Temps</h2>
    </div>
</div>

<div class="card shadow border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row gx-3 align-items-center">
            <div class="col-sm-6">
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

<?php if ($id_classe && $edt): ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Ajouter un créneau</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="ajouter_creneau" value="1">
                        <input type="hidden" name="id_classe" value="<?= htmlspecialchars($id_classe) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Jour</label>
                            <select name="jour" class="form-select" required>
                                <?php foreach($jours as $j): ?><option value="<?= $j ?>"><?= $j ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Début</label>
                                <input type="time" name="heure_debut" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Fin</label>
                                <input type="time" name="heure_fin" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Matière & Enseignant</label>
                            <select name="id_matiere" class="form-select" required>
                                <?php foreach($matieres as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?> (Prof: <?= htmlspecialchars($m['prof_nom']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Salle</label>
                            <input type="text" name="salle" class="form-control" required placeholder="ex: Salle 12">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Ajouter Créneau</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Aperçu rapide</h5>
                    <a href="voir.php?id_classe=<?= $id_classe ?>" class="btn btn-sm btn-outline-secondary">Voir la grille complète</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    $stmt_slots = $pdo->prepare("SELECT c.*, m.nom as mat_nom FROM creneaux c JOIN matieres m ON c.id_matiere = m.id WHERE c.id_edt = ? ORDER BY c.jour, c.heure_debut");
                    $stmt_slots->execute([$edt['id']]);
                    $all_slots = $stmt_slots->fetchAll();
                    ?>
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Jour</th>
                                <th>Horaire</th>
                                <th>Matière</th>
                                <th>Salle</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_slots as $slot): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($slot['jour']) ?></td>
                                    <td><?= substr($slot['heure_debut'],0,5) ?> - <?= substr($slot['heure_fin'],0,5) ?></td>
                                    <td><?= htmlspecialchars($slot['mat_nom']) ?></td>
                                    <td><?= htmlspecialchars($slot['salle']) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="creer.php?id_classe=<?= $id_classe ?>&supprimer_creneau=<?= $slot['id'] ?>" class="btn btn-sm btn-danger py-0 px-2" onclick="return confirm('Supprimer ce créneau ?');"><i class="bi bi-trash"></i> X</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(!$all_slots): ?><tr><td colspan="5" class="text-center py-4 text-muted">EDT vide.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php include '../footer.php'; ?>
