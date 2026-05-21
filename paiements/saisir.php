<?php
require_once '../session_check.php';
// Parents, Surveillants, Directeurs
require_once '../config.php';

$id_dossier = $_GET['id_dossier'] ?? ($_POST['id_dossier'] ?? null);
if (!$id_dossier) die("Dossier Financier invalide.");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = floatval($_POST['montant'] ?? 0);
    $mode = $_POST['mode'] ?? 'carte';
    
    if ($montant > 0 && in_array($mode, ['carte', 'cash'])) {
        $statut = ($mode === 'carte') ? 'valide' : 'en_attente';
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO paiements (id_dossier, montant, mode, date_transaction, statut) VALUES (?, ?, ?, CURDATE(), ?)");
            $stmt->execute([$id_dossier, $montant, $mode, $statut]);
            
            if ($statut === 'valide') {
                $pdo->prepare("UPDATE dossiers_financiers SET solde = solde + ? WHERE id = ?")->execute([$montant, $id_dossier]);
                
                // Get parent info and notify
                $stmt_i = $pdo->prepare("SELECT e.id_parent, e.prenom FROM dossiers_financiers d JOIN eleves e ON d.id_eleve = e.id WHERE d.id = ?");
                $stmt_i->execute([$id_dossier]);
                $info = $stmt_i->fetch();
                if ($info && $info['id_parent']) {
                    $msg = "Nous avons bien reçu votre paiement par carte de {$montant}€ pour {$info['prenom']}.";
                    $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Paiement', ?, NOW())")->execute([$info['id_parent'], $msg]);
                }
            }
            $pdo->commit();
            $success = ($statut === 'valide') ? "Paiement par carte validé automatiquement." : "Paiement en espèces enregistré. Attente de confirmation du surveillant.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur SQL.";
        }
    } else {
        $error = "Montant ou mode de paiement invalide.";
    }
}

include '../header.php';
?>
<div class="row mb-3">
    <div class="col">
        <a href="dossier.php?id_dossier=<?= $id_dossier ?>&id_eleve=<?= $_GET['id_eleve'] ?? '' ?>" class="btn btn-sm btn-outline-secondary">&larr; Revenir au dossier</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Saisir un Paiement</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="id_dossier" value="<?= htmlspecialchars($id_dossier) ?>">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.95rem;">Montant du paiement (€) :</label>
                        <input type="number" step="0.01" min="1" name="montant" class="form-control form-control-lg" placeholder="ex: 150.00" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label d-block" style="font-size: 0.95rem;">Mode de règlement :</label>
                        <div class="form-check form-check-inline border p-3 rounded me-2">
                            <input class="form-check-input" type="radio" name="mode" id="carte" value="carte" checked>
                            <label class="form-check-label fw-bold" for="carte">Carte Bleue (Auto)</label>
                        </div>
                        <div class="form-check form-check-inline border p-3 rounded">
                            <input class="form-check-input" type="radio" name="mode" id="cash" value="cash">
                            <label class="form-check-label fw-bold" for="cash">Espèces / Chèque</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm py-2">Procéder au paiement</button>
                    <div class="form-text text-center mt-3">Les paiements en espèces nécessitent une validation physique avant d'être ajoutés au solde.</div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
