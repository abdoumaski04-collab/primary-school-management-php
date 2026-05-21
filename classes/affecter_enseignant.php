<?php
require_once '../session_check.php';
checkRole('directeur');
require_once '../config.php';

$id_classe = $_GET['id'] ?? null;
if (!$id_classe) {
    header("Location: liste.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $id_enseignant = $_POST['id_enseignant'] ?? null;
        if ($id_enseignant) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO enseignant_classe (id_enseignant, id_classe) VALUES (?, ?)");
            if ($stmt->execute([$id_enseignant, $id_classe])) {
                $success = "Enseignant affecté avec succès.";
            } else {
                $error = "Erreur lors de l'affectation.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $id_enseignant = $_POST['id_enseignant'] ?? null;
        if ($id_enseignant) {
            $stmt = $pdo->prepare("DELETE FROM enseignant_classe WHERE id_enseignant = ? AND id_classe = ?");
            if ($stmt->execute([$id_enseignant, $id_classe])) {
                $success = "Enseignant retiré de la classe.";
            }
        }
    }
}

$stmt_c = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt_c->execute([$id_classe]);
$classe = $stmt_c->fetch();

$profs = $pdo->query("SELECT id, nom, prenom FROM utilisateurs WHERE role='enseignant'")->fetchAll();

$stmt_aff = $pdo->prepare("SELECT u.id, u.nom, u.prenom FROM enseignant_classe ec JOIN utilisateurs u ON ec.id_enseignant = u.id WHERE ec.id_classe = ?");
$stmt_aff->execute([$id_classe]);
$affectations = $stmt_aff->fetchAll();

include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-3">
        <a href="liste.php" class="btn btn-sm btn-outline-secondary">&larr; Retour à la liste</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">Affecter un enseignant à la classe : <?= htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']) ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Sélectionner un enseignant</label>
                        <select name="id_enseignant" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach($profs as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning fw-bold">Affecter l'enseignant</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">Enseignants actuellement affectés</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($affectations as $aff): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                            <span><?= htmlspecialchars($aff['prenom'] . ' ' . $aff['nom']) ?></span>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id_enseignant" value="<?= $aff['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Retirer cet enseignant ?');">Retirer</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($affectations)): ?>
                        <li class="list-group-item text-muted p-3">Aucun enseignant affecté.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
