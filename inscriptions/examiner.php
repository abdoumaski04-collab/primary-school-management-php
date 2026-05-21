<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant']);
require_once '../config.php';

$id_dossier = $_GET['id'] ?? null;
if (!$id_dossier) die("Dossier introuvable.");

$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT d.*, u.nom as parent_nom, u.prenom as parent_prenom, u.id as id_parent, f.donnees_parent 
                       FROM dossiers_inscription d 
                       JOIN utilisateurs u ON d.id_parent = u.id 
                       JOIN formulaires f ON d.id = f.id_dossier 
                       WHERE d.id = ?");
$stmt->execute([$id_dossier]);
$dossier = $stmt->fetch();

if (!$dossier) die("Dossier inexistant.");
$donnees = json_decode($dossier['donnees_parent'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $valid_actions = [];
    if ($_SESSION['role'] === 'surveillant') $valid_actions = ['verifie', 'refuse'];
    if ($_SESSION['role'] === 'directeur') $valid_actions = ['accepte', 'refuse'];
    
    if (in_array($action, $valid_actions)) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE dossiers_inscription SET statut = ? WHERE id = ?")->execute([$action, $id_dossier]);
            
            // Si accepté, création de l'élève !
            if ($action === 'accepte') {
                $stmt_eleve = $pdo->prepare("INSERT INTO eleves (id_parent, nom, prenom, date_naissance, statut) VALUES (?, ?, ?, ?, 'preInscrit')");
                $stmt_eleve->execute([$dossier['id_parent'], $donnees['nom'], $donnees['prenom'], $donnees['date_naissance']]);
                
                // create financial dossier
                $id_eleve_new = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO dossiers_financiers (id_eleve, solde, statut, date_creation) VALUES (?, 0, 'actif', CURDATE())")->execute([$id_eleve_new]);
                
                $msg_parent = "Félicitations ! Le dossier d'inscription de {$donnees['prenom']} a été accepté. L'enfant est désormais pré-inscrit.";
            } elseif ($action === 'refuse') {
                $msg_parent = "Le dossier d'inscription de {$donnees['prenom']} a été refusé. Veuillez contacter l'administration.";
            } elseif ($action === 'verifie') {
                $msg_parent = "Le dossier d'inscription de {$donnees['prenom']} a été vérifié et transmis à la direction pour décision finale.";
            }
            
            $pdo->prepare("INSERT INTO notifications (id_utilisateur, type, contenu, date_creation) VALUES (?, 'Inscription', ?, NOW())")->execute([$dossier['id_parent'], $msg_parent]);
            
            $pdo->commit();
            $dossier['statut'] = $action; // update var
            $success = "Statut mis à jour : " . ucfirst($action);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors du traitement.";
        }
    } else {
        $error = "Action non autorisée.";
    }
}

include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-3">
        <a href="liste.php" class="btn btn-sm btn-outline-secondary">&larr; Retour à la liste</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Examen du dossier #<?= str_pad($dossier['id'], 4, '0', STR_PAD_LEFT) ?></h5>
                <?php
                    $badge = 'bg-secondary';
                    if($dossier['statut'] == 'soumis') $badge = 'bg-warning text-dark';
                    if($dossier['statut'] == 'verifie') $badge = 'bg-info text-dark';
                    if($dossier['statut'] == 'accepte') $badge = 'bg-success';
                    if($dossier['statut'] == 'refuse') $badge = 'bg-danger';
                ?>
                <span class="badge <?= $badge ?> fs-6 px-3 py-2"><?= ucfirst($dossier['statut']) ?></span>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <h6 class="text-muted text-uppercase mb-3 fw-bold" style="letter-spacing: 1px;">INFORMATIONS PARENT</h6>
                <p><strong>Responsable :</strong> <?= htmlspecialchars($dossier['parent_prenom'] . ' ' . $dossier['parent_nom']) ?></p>
                <p><strong>Date de soumission :</strong> <?= htmlspecialchars($dossier['date_soumission']) ?></p>
                
                <hr>
                
                <h6 class="text-muted text-uppercase mb-3 mt-4 fw-bold" style="letter-spacing: 1px;">INFORMATIONS ENFANT</h6>
                <p><strong>Nom :</strong> <?= htmlspecialchars($donnees['nom']) ?></p>
                <p><strong>Prénom :</strong> <?= htmlspecialchars($donnees['prenom']) ?></p>
                <p><strong>Date de Naissance :</strong> <?= htmlspecialchars($donnees['date_naissance']) ?></p>
                
                <hr>
                
                <h6 class="text-muted text-uppercase mb-3 mt-4 fw-bold" style="letter-spacing: 1px;">PIÈCES JOINTES</h6>
                <div class="p-3 bg-light rounded border">
                    <?= nl2br(htmlspecialchars($dossier['pieces_jointes'])) ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Décision</h5>
            </div>
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                <?php if ($dossier['statut'] === 'accepte' || $dossier['statut'] === 'refuse'): ?>
                    <div class="alert alert-secondary border-0">Aucune action requise. Dossier clos.</div>
                <?php else: ?>
                    <form method="POST" class="d-grid gap-3">
                        <?php if($_SESSION['role'] === 'surveillant' && $dossier['statut'] === 'soumis'): ?>
                            <button name="action" value="verifie" class="btn btn-info text-white fw-bold btn-lg shadow-sm" onclick="return confirm('Dossier complet et vérifié ?');">Marquer comme Vérifié</button>
                            <button name="action" value="refuse" class="btn btn-outline-danger fw-bold" onclick="return confirm('Refuser ce dossier ?');">Refuser (Incomplet)</button>
                        <?php elseif($_SESSION['role'] === 'directeur' && in_array($dossier['statut'], ['soumis', 'verifie'])): ?>
                            <button name="action" value="accepte" class="btn btn-success fw-bold btn-lg shadow-sm" onclick="return confirm('Accepter et pré-inscrire l\'enfant ?');">Accepter l'inscription</button>
                            <button name="action" value="refuse" class="btn btn-outline-danger fw-bold" onclick="return confirm('Refuser définitivement ?');">Refuser l'inscription</button>
                        <?php else: ?>
                            <div class="alert alert-info">En attente de traitement par la Direction.</div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
