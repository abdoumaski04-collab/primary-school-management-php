<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant']);
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_paiement']) && isset($_POST['action'])) {
    $id_paiement = $_POST['id_paiement'];
    $action = $_POST['action']; // 'valide' ou 'refuse'
    
    if (in_array($action, ['valide', 'refuse'])) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM paiements WHERE id = ? AND statut = 'en_attente'");
            $stmt->execute([$id_paiement]);
            $p = $stmt->fetch();
            
            if ($p) {
                // Update payment status
                $pdo->prepare("UPDATE paiements SET statut = ? WHERE id = ?")->execute([$action, $id_paiement]);
                
                // Get Parent
                $stmt_parent = $pdo->prepare("SELECT e.id_parent, e.prenom FROM dossiers_financiers d JOIN eleves e ON d.id_eleve = e.id WHERE d.id = ?");
                $stmt_parent->execute([$p['id_dossier']]);
                $info = $stmt_parent->fetch();
                
                if ($action === 'valide') {
                    // Update dossier solde
                    $pdo->prepare("UPDATE dossiers_financiers SET solde = solde + ? WHERE id = ?")->execute([$p['montant'], $p['id_dossier']]);
                    // Notif parent
                    if ($info['id_parent']) {
                        $msg = "Le paiement en espèces/chèque de {$p['montant']}€ pour {$info['prenom']} a été physiquement validé par l'administration.";
                        $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Paiement', ?, NOW())")->execute([$info['id_parent'], $msg]);
                    }
                } else {
                    if ($info['id_parent']) {
                        $msg = "Un ou plusieurs de vos paiements récents (${p['montant']}€) ont été refusés ou annulés. Veuillez contacter la scolarité.";
                        $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Paiement Refusé', ?, NOW())")->execute([$info['id_parent'], $msg]);
                    }
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: valider.php");
    exit;
}

$stmt_att = $pdo->query("
    SELECT p.*, e.nom, e.prenom, d.id_eleve 
    FROM paiements p 
    JOIN dossiers_financiers d ON p.id_dossier = d.id 
    JOIN eleves e ON d.id_eleve = e.id 
    WHERE p.statut = 'en_attente' 
    ORDER BY p.date_transaction ASC
");
$en_attente = $stmt_att->fetchAll();

include '../header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Paiements en attente de caisse</h2>
        <p class="text-muted">Confirmez la bonne réception de l'argent (Espèces ou Chèques) déposés physiquement.</p>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Réf#</th>
                        <th>Date</th>
                        <th>Élève</th>
                        <th>Mode</th>
                        <th>Montant</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($en_attente as $p): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted">P<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($p['date_transaction']) ?></td>
                            <td><a href="dossier.php?id_eleve=<?= $p['id_eleve'] ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></a></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($p['mode']) ?></span></td>
                            <td class="fw-bold text-primary"><?= number_format($p['montant'], 2, ',', ' ') ?> €</td>
                            <td class="text-end pe-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id_paiement" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="valide" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Valider définitivement ce solde ?');"><i class="bi bi-check-circle"></i> Valider</button>
                                    <button type="submit" name="action" value="refuse" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Refuser ce paiement ?');">Refuser</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($en_attente)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aun paiement en attente.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
