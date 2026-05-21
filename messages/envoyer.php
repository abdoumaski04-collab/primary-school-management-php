<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant']);
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinataire = $_POST['destinataire'] ?? '';
    $contenu = trim($_POST['contenu'] ?? '');
    
    if (empty($destinataire) || empty($contenu)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (id_expediteur, destinataire_role, contenu, date_envoi, statut) VALUES (?, ?, ?, NOW(), 'envoye')");
            $stmt->execute([$_SESSION['user_id'], $destinataire, $contenu]);
            
            // Notify target audiences immediately
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Nouveau Message', ?, NOW())");
            
            $query = "SELECT id FROM utilisateurs WHERE role = ?";
            $usersTarget = [];
            if ($destinataire !== 'tous') {
                $stmt_u = $pdo->prepare($query);
                $stmt_u->execute([$destinataire]);
                $usersTarget = $stmt_u->fetchAll();
            } else {
                $stmt_u = $pdo->query("SELECT id FROM utilisateurs");
                $usersTarget = $stmt_u->fetchAll();
            }
            
            $notif_msg = "Un nouveau message vous a été envoyé par {$_SESSION['nom_complet']}.";
            foreach($usersTarget as $u) {
                if ($u['id'] != $_SESSION['user_id']) {
                    $stmt_notif->execute([$u['id'], $notif_msg]);
                }
            }
            
            $pdo->commit();
            $success = "Le message a été envoyé avec succès et les notifications ont été distribuées.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de l'envoi.";
        }
    }
}
include '../header.php';
?>
<div class="row mb-3">
    <div class="col">
        <a href="boite.php" class="btn btn-sm btn-outline-secondary">&larr; Retour à la messagerie</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Envoyer un message de diffusion</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.95rem;">Destinataires cibles :</label>
                        <select name="destinataire" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            <option value="parent">Tous les parents</option>
                            <option value="enseignant">Tous les enseignants</option>
                            <?php if($_SESSION['role'] === 'directeur'): ?>
                                <option value="surveillant">Les surveillants</option>
                                <option value="tous">Toute l'école</option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Le message sera visible dans leur boîte de réception et une notification leur sera envoyée.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 0.95rem;">Contenu du message</label>
                        <textarea name="contenu" class="form-control" rows="6" required placeholder="Tapez votre message ici..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-send"></i> Envoyer et Notifier</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
