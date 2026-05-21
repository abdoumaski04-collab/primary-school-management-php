<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant']);
require_once '../config.php';

$stmt = $pdo->query("SELECT d.*, u.nom as parent_nom, u.prenom as parent_prenom, f.donnees_parent 
                     FROM dossiers_inscription d 
                     JOIN utilisateurs u ON d.id_parent = u.id 
                     JOIN formulaires f ON d.id = f.id_dossier 
                     ORDER BY d.date_soumission DESC");
$dossiers = $stmt->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Gestion des Inscriptions</h2>
    <?php if($_SESSION['role'] === 'surveillant'): ?>
        <span class="badge bg-warning text-dark fs-6 py-2 px-3 shadow-sm">Étape 1 : Vérification</span>
    <?php else: ?>
        <span class="badge bg-success fs-6 py-2 px-3 shadow-sm">Étape 2 : Décision (Directeur)</span>
    <?php endif; ?>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Parent Responsable</th>
                        <th>Enfant (Nom, Prénom)</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dossiers as $d): ?>
                        <?php $donnees = json_decode($d['donnees_parent'], true); ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted"><?= htmlspecialchars($d['date_soumission']) ?></td>
                            <td><?= htmlspecialchars($d['parent_prenom'] . ' ' . $d['parent_nom']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($donnees['nom'] . ' ' . $donnees['prenom']) ?></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if($d['statut'] == 'soumis') $badge = 'bg-warning text-dark';
                                if($d['statut'] == 'verifie') $badge = 'bg-info text-dark';
                                if($d['statut'] == 'accepte') $badge = 'bg-success';
                                if($d['statut'] == 'refuse') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge ?> rounded-pill px-3"><?= ucfirst($d['statut']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="examiner.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-primary px-3 fw-bold shadow-sm">Ouvrir & Évaluer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($dossiers)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Aucun dossier d'inscription trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
