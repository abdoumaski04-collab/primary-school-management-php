<?php
require_once '../session_check.php';
// All roles can view
require_once '../config.php';

$classes = $pdo->query("SELECT * FROM classes ORDER BY annee_scolaire DESC, niveau ASC")->fetchAll();
$id_classe = $_GET['id_classe'] ?? null;

$edt = null;
$creneaux = [];

if ($id_classe) {
    $stmt = $pdo->prepare("SELECT * FROM emplois_du_temps WHERE id_classe = ?");
    $stmt->execute([$id_classe]);
    $edt = $stmt->fetch();
    
    if ($edt && (in_array($_SESSION['role'], ['surveillant', 'directeur']) || $edt['statut'] === 'publie')) {
        $stmt_c = $pdo->prepare("SELECT c.*, m.nom as mat_nom, u.nom as prof_nom, u.prenom as prof_prenom 
                                 FROM creneaux c 
                                 JOIN matieres m ON c.id_matiere = m.id 
                                 JOIN utilisateurs u ON c.id_enseignant = u.id 
                                 WHERE c.id_edt = ? ORDER BY c.heure_debut ASC");
        $stmt_c->execute([$edt['id']]);
        $slots = $stmt_c->fetchAll();
        
        // Group by day for simple representation
        foreach ($slots as $s) {
            $creneaux[$s['jour']][] = $s;
        }
    }
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

include '../header.php';
?>
<div class="row mb-4">
    <div class="col d-flex justify-content-between align-items-center">
        <h2 class="fw-bold">Emploi du Temps</h2>
        <?php if($_SESSION['role'] === 'surveillant'): ?>
            <a href="creer.php" class="btn btn-primary fw-bold shadow-sm">Créer / Modifier l'EDT</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row gx-3 align-items-center">
            <div class="col-sm-6">
                <select name="id_classe" class="form-select" required onchange="this.form.submit()">
                    <option value="">Sélectionner une classe</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $id_classe == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['niveau'] . ' ' . $c['nom'] . ' (' . $c['annee_scolaire'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if($id_classe): ?>
    <?php if($edt): ?>
        <?php if(in_array($_SESSION['role'], ['surveillant', 'directeur']) || $edt['statut'] === 'publie'): ?>
            <div class="alert <?= $edt['statut'] == 'publie' ? 'alert-success' : 'alert-warning' ?> d-flex justify-content-between align-items-center">
                <span><strong>Statut : </strong> <?= htmlspecialchars(ucfirst($edt['statut'])) ?></span>
                <?php if($_SESSION['role'] === 'surveillant' && $edt['statut'] === 'brouillon'): ?>
                    <a href="publier.php?id=<?= $edt['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Confirmer la publication ? Les parents et élèves pourront le voir.');">Publier cet EDT</a>
                <?php endif; ?>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach($jours as $jour): ?>
                    <div class="col">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-dark text-white fw-bold text-center py-3">
                                <?= $jour ?>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php if(isset($creneaux[$jour])): ?>
                                        <?php foreach($creneaux[$jour] as $slot): ?>
                                            <li class="list-group-item p-3">
                                                <div class="fw-bold text-primary mb-1"><?= substr($slot['heure_debut'], 0, 5) ?> - <?= substr($slot['heure_fin'], 0, 5) ?></div>
                                                <div class="mb-1"><span class="badge bg-secondary"><?= htmlspecialchars($slot['mat_nom']) ?></span></div>
                                                <div class="text-muted small mb-1"><i class="bi bi-person"></i> Prof. <?= htmlspecialchars($slot['prof_nom'] . ' ' . $slot['prof_prenom']) ?></div>
                                                <div class="text-muted small"><i class="bi bi-door-open"></i> Salle : <?= htmlspecialchars($slot['salle']) ?></div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center text-muted p-4 border-bottom-0">Aucun cours</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Cet emploi du temps est en cours de création et n'a pas encore été publié.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-warning">Aucun emploi du temps n'a été créé pour cette classe.</div>
    <?php endif; ?>
<?php endif; ?>
<?php include '../footer.php'; ?>
