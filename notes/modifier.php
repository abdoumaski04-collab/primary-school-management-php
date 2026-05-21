<?php
require_once '../session_check.php';
checkRole(['enseignant']);
require_once '../config.php';

$id_note = $_GET['id'] ?? null;
if (!$id_note) {
    header("Location: liste.php");
    exit;
}

$error = '';
$success = '';

// Fetch the note and ensure the teacher owns the subject
$stmt = $pdo->prepare("
    SELECT n.*, e.nom as eleve_nom, e.prenom as eleve_prenom, m.nom as matiere_nom, m.id_enseignant 
    FROM notes n 
    JOIN eleves e ON n.id_eleve = e.id 
    JOIN matieres m ON n.id_matiere = m.id 
    WHERE n.id = ?
");
$stmt->execute([$id_note]);
$note = $stmt->fetch();

if (!$note || $note['id_enseignant'] != $_SESSION['user_id']) {
    die("Accès non autorisé ou note inexistante.");
}

// Handle modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouvelle_valeur = $_POST['valeur'] ?? '';
    if ($nouvelle_valeur !== '' && $nouvelle_valeur >= 0 && $nouvelle_valeur <= 20) {
        if ($nouvelle_valeur != $note['valeur']) {
            $pdo->beginTransaction();
            try {
                // Update note
                $stmt_upd = $pdo->prepare("UPDATE notes SET valeur = ? WHERE id = ?");
                $stmt_upd->execute([$nouvelle_valeur, $id_note]);
                
                // Audit log
                $stmt_audit = $pdo->prepare("INSERT INTO journal_audit (id_note, ancienne_valeur, nouvelle_valeur, auteur, date_modification) VALUES (?, ?, ?, ?, NOW())");
                $stmt_audit->execute([$id_note, $note['valeur'], $nouvelle_valeur, $_SESSION['nom_complet']]);
                
                $pdo->commit();
                $success = "La note a été modifiée avec succès. L'historique d'audit a été mis à jour.";
                $note['valeur'] = $nouvelle_valeur; // Update for display
            } catch(Exception $e) {
                $pdo->rollBack();
                $error = "Erreur lors de la modification.";
            }
        }
    } else {
        $error = "La note doit être comprise entre 0 et 20.";
    }
}

// Fetch audit trail
$stmt_hist = $pdo->prepare("SELECT * FROM journal_audit WHERE id_note = ? ORDER BY date_modification DESC");
$stmt_hist->execute([$id_note]);
$historique = $stmt_hist->fetchAll();

include '../header.php';
?>
<div class="row mb-3">
    <div class="col">
        <a href="liste.php" class="btn btn-sm btn-outline-secondary">&larr; Retour aux notes</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Modifier une note</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <p><strong>Élève :</strong> <?= htmlspecialchars($note['eleve_nom'] . ' ' . $note['eleve_prenom']) ?></p>
                <p><strong>Matière :</strong> <?= htmlspecialchars($note['matiere_nom']) ?> (Trimestre <?= $note['trimestre'] ?>)</p>
                <p><strong>Date d'évaluation :</strong> <?= htmlspecialchars($note['date_evaluation']) ?></p>
                
                <form method="POST" class="mt-4">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.95rem;">Note Actuelle (sur 20)</label>
                        <input type="number" step="0.25" min="0" max="20" name="valeur" class="form-control form-control-lg" value="<?= htmlspecialchars($note['valeur']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Mettre à jour & Auditer</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-muted">Journal d'Audit (Modifications)</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($historique as $h): ?>
                        <li class="list-group-item p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <small class="text-muted"><?= htmlspecialchars($h['date_modification']) ?></small>
                                <small class="fw-bold">Par: <?= htmlspecialchars($h['auteur']) ?></small>
                            </div>
                            <p class="mb-0 mt-1">
                                Changement de <strong><?= htmlspecialchars($h['ancienne_valeur']) ?></strong> à <strong class="text-primary"><?= htmlspecialchars($h['nouvelle_valeur']) ?></strong>
                            </p>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($historique)): ?>
                        <li class="list-group-item p-4 text-center text-muted border-bottom-0">Aucune modification n'a été enregistrée pour cette note.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
