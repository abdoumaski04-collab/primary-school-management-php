<?php
require_once '../session_check.php';
checkRole(['parent']);
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_enfant = trim($_POST['nom_enfant'] ?? '');
    $prenom_enfant = trim($_POST['prenom_enfant'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $pieces_jointes = trim($_POST['pieces_jointes'] ?? '');
    
    // estComplet verification (simulated by checking if pieces_jointes is not empty)
    if (empty($nom_enfant) || empty($prenom_enfant) || empty($date_naissance)) {
        $error = "Veuillez remplir toutes les informations de l'enfant.";
    } elseif (empty($pieces_jointes)) {
        $error = "Le dossier est incomplet. Veuillez fournir les pièces jointes nécessaires (ex: Liens vers documents).";
    } else {
        $pdo->beginTransaction();
        try {
            // Create Dossier
            $stmt_d = $pdo->prepare("INSERT INTO dossiers_inscription (id_parent, statut, pieces_jointes, date_soumission) VALUES (?, 'soumis', ?, CURDATE())");
            $stmt_d->execute([$_SESSION['user_id'], $pieces_jointes]);
            $id_dossier = $pdo->lastInsertId();
            
            // Save Form data (JSON for simplicity)
            $donnees = json_encode([
                'nom' => $nom_enfant,
                'prenom' => $prenom_enfant,
                'date_naissance' => $date_naissance
            ]);
            
            $stmt_f = $pdo->prepare("INSERT INTO formulaires (id_dossier, donnees_parent, est_valide) VALUES (?, ?, 0)");
            $stmt_f->execute([$id_dossier, $donnees]);
            
            // Notify Surveillance
            $msg_surv = "Un nouveau dossier d'inscription a été soumis par " . $_SESSION['nom_complet'];
            $stmt_s = $pdo->query("SELECT id FROM utilisateurs WHERE role='surveillant'");
            foreach($stmt_s->fetchAll() as $s) {
                $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Inscription', ?, NOW())")->execute([$s['id'], $msg_surv]);
            }
            
            $pdo->commit();
            $success = "Votre dossier a été soumis avec succès et est en attente de vérification.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la soumission du dossier.";
        }
    }
}

// Fetch existing submissions for this parent
$stmt_list = $pdo->prepare("SELECT d.*, f.donnees_parent FROM dossiers_inscription d JOIN formulaires f ON d.id = f.id_dossier WHERE d.id_parent = ? ORDER BY d.date_soumission DESC");
$stmt_list->execute([$_SESSION['user_id']]);
$dossiers = $stmt_list->fetchAll();

include '../header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Procédure d'Inscription</h2>
        <p class="text-muted">Soumettez un nouveau dossier pour inscrire votre enfant ou suivez vos demandes en cours.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Nouveau Dossier</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nom de l'enfant</label>
                        <input type="text" name="nom_enfant" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prénom de l'enfant</label>
                        <input type="text" name="prenom_enfant" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date de Naissance</label>
                        <input type="date" name="date_naissance" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Pièces Jointes (Copie livret, photos, etc.)</label>
                        <textarea name="pieces_jointes" class="form-control" rows="3" placeholder="Insérez vos liens Google Drive, ou le nom des fichiers déposés au bureau" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm py-2">Soumettre le dossier</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Mes Dossiers Soumis</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($dossiers as $d): ?>
                        <?php $donnees = json_decode($d['donnees_parent'], true); ?>
                        <li class="list-group-item p-4">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($donnees['prenom'].' '.$donnees['nom']) ?></h5>
                                <?php
                                $badge = 'bg-secondary';
                                if($d['statut'] == 'soumis') $badge = 'bg-warning text-dark';
                                if($d['statut'] == 'verifie') $badge = 'bg-info text-dark';
                                if($d['statut'] == 'accepte') $badge = 'bg-success';
                                if($d['statut'] == 'refuse') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge ?> fs-6"><?= ucfirst($d['statut']) ?></span>
                            </div>
                            <p class="mb-1 text-muted">Né(e) le : <?= htmlspecialchars($donnees['date_naissance']) ?></p>
                            <small class="text-muted">Soumis le : <?= htmlspecialchars($d['date_soumission']) ?></small>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($dossiers)): ?>
                        <li class="list-group-item p-5 text-center text-muted">Vous n'avez soumis aucun dossier.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
