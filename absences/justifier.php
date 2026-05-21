<?php
require_once '../session_check.php';
checkRole(['surveillant']);
require_once '../config.php';

$id_absence = $_GET['id'] ?? null;
if (!$id_absence) {
    header("Location: liste.php");
    exit;
}

$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT a.*, e.nom, e.prenom FROM absences a JOIN eleves e ON a.id_eleve = e.id WHERE a.id = ?");
$stmt->execute([$id_absence]);
$absence = $stmt->fetch();

if (!$absence) die("Absence introuvable.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_presence = $_POST['date_presence'] ?? date('Y-m-d');
    $motif = trim($_POST['motif'] ?? '');
    
    // Simplification: We combine step 1 & 2 here directly as validation
    if (!empty($motif)) {
        $pdo->beginTransaction();
        try {
            $stmt_justif = $pdo->prepare("INSERT INTO justifications (id_absence, date_presence_parent, motif, document_joint) VALUES (?, ?, ?, NULL)");
            $stmt_justif->execute([$id_absence, $date_presence, $motif]);
            
            $stmt_upd = $pdo->prepare("UPDATE absences SET statut = 'justifiee', motif = ? WHERE id = ?");
            $stmt_upd->execute([$motif, $id_absence]);
            
            $pdo->commit();
            $success = "L'absence a été justifiée avec succès.";
            $absence['statut'] = 'justifiee';
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la justification.";
        }
    } else {
        $error = "Le motif est obligatoire.";
    }
}

include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-3">
        <a href="liste.php" class="btn btn-sm btn-outline-secondary">&larr; Retour aux absences</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Justifier une absence</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <div class="p-3 bg-light rounded mb-4 border">
                    <p class="mb-1"><strong>Élève :</strong> <?= htmlspecialchars($absence['prenom'] . ' ' . $absence['nom']) ?></p>
                    <p class="mb-1"><strong>Date :</strong> <?= htmlspecialchars($absence['date']) ?> (<?= str_replace('_', '-', $absence['demi_journee']) ?>)</p>
                    <p class="mb-0"><strong>Statut :</strong> <?= htmlspecialchars(ucfirst($absence['statut'])) ?></p>
                </div>

                <?php if($absence['statut'] !== 'justifiee'): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.95rem;">Date de présence du parent (au bureau)</label>
                        <input type="date" name="date_presence" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 0.95rem;">Motif de l'absence / Explications</label>
                        <textarea name="motif" class="form-control" rows="3" required placeholder="Saisir le motif donné par le parent..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">Valider la justification</button>
                </form>
                <?php else: ?>
                    <div class="alert alert-info">Cette absence a déjà été justifiée.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
