<?php
require_once '../session_check.php';
checkRole('enseignant');
require_once '../config.php';

$error = '';
$success = '';

// Get matieres taught by this teacher
$stmt_m = $pdo->prepare("SELECT * FROM matieres WHERE id_enseignant = ?");
$stmt_m->execute([$_SESSION['user_id']]);
$matieres = $stmt_m->fetchAll();

// Get classes assigned to this teacher
$stmt_c = $pdo->prepare("SELECT c.* FROM classes c JOIN enseignant_classe ec ON c.id = ec.id_classe WHERE ec.id_enseignant = ?");
$stmt_c->execute([$_SESSION['user_id']]);
$classes = $stmt_c->fetchAll();

$id_classe = $_GET['id_classe'] ?? ($_POST['id_classe'] ?? null);
$id_matiere = $_GET['id_matiere'] ?? ($_POST['id_matiere'] ?? null);
$trimestre = $_GET['trimestre'] ?? ($_POST['trimestre'] ?? 1);

$eleves = [];
if ($id_classe && $id_matiere) {
    $stmt_e = $pdo->prepare("SELECT * FROM eleves WHERE id_classe = ? AND statut IN ('inscrit', 'preInscrit') ORDER BY nom, prenom");
    $stmt_e->execute([$id_classe]);
    $eleves = $stmt_e->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notes'])) {
    $date_evaluation = $_POST['date_evaluation'] ?? date('Y-m-d');
    
    $pdo->beginTransaction();
    try {
        foreach ($_POST['notes'] as $id_eleve => $valeur) {
            if ($valeur === '') continue; // Skip empty inputs
            if ($valeur < 0 || $valeur > 20) {
                throw new Exception("La note doit être comprise entre 0 et 20.");
            }
            
            // Check if a note already exists for this exact evaluation context
            $stmt_check = $pdo->prepare("SELECT id, valeur FROM notes WHERE id_eleve = ? AND id_matiere = ? AND trimestre = ? AND date_evaluation = ?");
            $stmt_check->execute([$id_eleve, $id_matiere, $trimestre, $date_evaluation]);
            $existing = $stmt_check->fetch();
            
            if ($existing) {
                // Same date evaluation exists, just update it (and audit)
                if ($existing['valeur'] != $valeur) {
                    $stmt_upd = $pdo->prepare("UPDATE notes SET valeur = ? WHERE id = ?");
                    $stmt_upd->execute([$valeur, $existing['id']]);
                    
                    // Audit trailing
                    $stmt_audit = $pdo->prepare("INSERT INTO journal_audit (id_note, ancienne_valeur, nouvelle_valeur, auteur, date_modification) VALUES (?, ?, ?, ?, NOW())");
                    $stmt_audit->execute([$existing['id'], $existing['valeur'], $valeur, $_SESSION['nom_complet']]);
                }
            } else {
                // Insert new note
                $stmt_ins = $pdo->prepare("INSERT INTO notes (id_eleve, id_matiere, trimestre, valeur, date_evaluation) VALUES (?, ?, ?, ?, ?)");
                $stmt_ins->execute([$id_eleve, $id_matiere, $trimestre, $valeur, $date_evaluation]);
            }
        }
        $pdo->commit();
        $success = "Notes enregistrées avec succès.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-4">
        <h2 class="fw-bold">Saisie des Notes</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row gx-3 gy-2 align-items-center">
                    <div class="col-sm-3">
                        <label class="visually-hidden">Classe</label>
                        <select name="id_classe" class="form-select" required>
                            <option value="">Sélectionner une classe</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $id_classe == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['niveau'] . ' ' . $c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="visually-hidden">Matière</label>
                        <select name="id_matiere" class="form-select" required>
                            <option value="">Sélectionner une matière</option>
                            <?php foreach($matieres as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $id_matiere == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="visually-hidden">Trimestre</label>
                        <select name="trimestre" class="form-select" required>
                            <option value="1" <?= $trimestre == 1 ? 'selected' : '' ?>>Trimestre 1</option>
                            <option value="2" <?= $trimestre == 2 ? 'selected' : '' ?>>Trimestre 2</option>
                            <option value="3" <?= $trimestre == 3 ? 'selected' : '' ?>>Trimestre 3</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Afficher les élèves</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($id_classe && $id_matiere): ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="id_classe" value="<?= htmlspecialchars($id_classe) ?>">
                <input type="hidden" name="id_matiere" value="<?= htmlspecialchars($id_matiere) ?>">
                <input type="hidden" name="trimestre" value="<?= htmlspecialchars($trimestre) ?>">
                
                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Liste des élèves</h5>
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 nowrap">Date d'évaluation:</label>
                            <input type="date" name="date_evaluation" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nom</th>
                                    <th>Prénom</th>
                                    <th style="width: 200px;" class="pe-4">Note (/20)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($eleves as $e): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= htmlspecialchars($e['nom']) ?></td>
                                        <td><?= htmlspecialchars($e['prenom']) ?></td>
                                        <td class="pe-4">
                                            <input type="number" step="0.25" min="0" max="20" name="notes[<?= $e['id'] ?>]" class="form-control" placeholder="--">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light text-end">
                        <button type="submit" class="btn btn-success fw-bold px-4">Enregistrer les notes</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include '../footer.php'; ?>
