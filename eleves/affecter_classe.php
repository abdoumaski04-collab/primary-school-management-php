<?php
require_once '../session_check.php';
checkRole('directeur');
require_once '../config.php';

$id_eleve = $_GET['id'] ?? null;
if (!$id_eleve) {
    header("Location: liste.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_classe = $_POST['id_classe'] ?? null;
    $id_classe = $id_classe === '' ? null : $id_classe; // Handle unassign
    
    // Check constraints if assigning a non-null class
    $can_assign = true;
    if ($id_classe !== null) {
        $stmt_check = $pdo->prepare("SELECT effectif_max FROM classes WHERE id = ?");
        $stmt_check->execute([$id_classe]);
        $classe_info = $stmt_check->fetch();
        
        if ($classe_info) {
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as current_count FROM eleves WHERE id_classe = ?");
            $stmt_count->execute([$id_classe]);
            $current_count = $stmt_count->fetch()['current_count'];
            
            if ($current_count >= $classe_info['effectif_max']) {
                $error = "Impossible d'affecter : l'effectif maximum de cette classe est atteint (".$classe_info['effectif_max']." élèves).";
                $can_assign = false;
            }
        }
    }

    if ($can_assign) {
        $stmt_upd = $pdo->prepare("UPDATE eleves SET id_classe = ? WHERE id = ?");
        if ($stmt_upd->execute([$id_classe, $id_eleve])) {
            $success = "La classe de l'élève a été mise à jour.";
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}

// Fetch eleve info
$stmt_e = $pdo->prepare("SELECT nom, prenom, id_classe FROM eleves WHERE id = ?");
$stmt_e->execute([$id_eleve]);
$eleve = $stmt_e->fetch();

// Fetch all classes
$stmt_classes = $pdo->query("SELECT * FROM classes ORDER BY annee_scolaire DESC, niveau ASC");
$classes = $stmt_classes->fetchAll();

include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-3">
        <a href="liste.php" class="btn btn-sm btn-outline-secondary">&larr; Retour à la liste</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Affectation de Classe : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 0.95rem;">Choisir la classe</label>
                        <select name="id_classe" class="form-select">
                            <option value="">-- Sans classe (Désaffecter) --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($eleve['id_classe'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['niveau'] . ' ' . $c['nom'] . ' (' . $c['annee_scolaire'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Enregistrer l'affectation</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
