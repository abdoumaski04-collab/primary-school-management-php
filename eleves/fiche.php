<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant', 'parent']);
require_once '../config.php';

$id_eleve = $_GET['id'] ?? null;
if (!$id_eleve) {
    header("Location: liste.php");
    exit;
}

// Fetch student details
$stmt = $pdo->prepare("SELECT e.*, c.niveau, c.nom as classe_nom, u.nom as parent_nom, u.prenom as parent_prenom, u.email as parent_email 
                       FROM eleves e 
                       LEFT JOIN classes c ON e.id_classe = c.id 
                       LEFT JOIN utilisateurs u ON e.id_parent = u.id 
                       WHERE e.id = ?");
$stmt->execute([$id_eleve]);
$eleve = $stmt->fetch();

if (!$eleve) {
    die("�?lève introuvable.");
}

// Security for parents: can only view their own child's fiche
if ($_SESSION['role'] === 'parent' && $eleve['id_parent'] != $_SESSION['user_id']) {
    die("Accès non autorisé.");
}

include '../header.php';
?>
<div class="row">
    <div class="col-md-12 mb-3">
        <?php if($_SESSION['role'] !== 'parent'): ?>
        <a href="liste.php" class="btn btn-sm btn-outline-secondary">&larr; Retour à la liste</a>
        <?php else: ?>
        <a href="/umllast/dashboard_parent.php" class="btn btn-sm btn-outline-secondary">&larr; Retour au tableau de bord</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold">Fiche �?lève</h4>
        <span class="badge bg-primary px-3 py-2"><?= htmlspecialchars(ucfirst($eleve['statut'])) ?></span>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5 class="fw-bold text-muted border-bottom pb-2">Informations Personnelles</h5>
                <p><strong>Nom :</strong> <?= htmlspecialchars($eleve['nom']) ?></p>
                <p><strong>Prénom :</strong> <?= htmlspecialchars($eleve['prenom']) ?></p>
                <p><strong>Date de naissance :</strong> <?= htmlspecialchars($eleve['date_naissance']) ?></p>
            </div>
            <div class="col-md-6 mb-4">
                <h5 class="fw-bold text-muted border-bottom pb-2">Scolarité</h5>
                <p><strong>Classe actuelle :</strong> <?= $eleve['classe_nom'] ? htmlspecialchars($eleve['niveau'] . ' ' . $eleve['classe_nom']) : '<span class="text-danger">Non assignée</span>' ?></p>
                <p><strong>Matricule :</strong> <?= htmlspecialchars($eleve['id']) ?></p>
            </div>
        </div>
        
        <?php if($_SESSION['role'] !== 'parent'): ?>
        <div class="row mt-2">
            <div class="col-md-12">
                <h5 class="fw-bold text-muted border-bottom pb-2">Responsable Légal (Parent)</h5>
                <?php if($eleve['parent_nom']): ?>
                    <p><strong>Nom & Prénom :</strong> <?= htmlspecialchars($eleve['parent_nom'] . ' ' . $eleve['parent_prenom']) ?></p>
                    <p><strong>Email :</strong> <a href="mailto:<?= htmlspecialchars($eleve['parent_email']) ?>"><?= htmlspecialchars($eleve['parent_email']) ?></a></p>
                <?php else: ?>
                    <p class="text-muted">Aucun parent rattaché.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-12 d-flex gap-2">
                <a href="/umllast/bulletins/liste.php?id_eleve=<?= $eleve['id'] ?>" class="btn btn-outline-primary">Voir les bulletins</a>
                <a href="/umllast/absences/liste.php?id_eleve=<?= $eleve['id'] ?>" class="btn btn-outline-warning text-dark">Voir les absences</a>
                <?php if(in_array($_SESSION['role'], ['directeur', 'surveillant', 'parent'])): ?>
                    <a href="/umllast/paiements/dossier.php?id_eleve=<?= $eleve['id'] ?>" class="btn btn-outline-success">Scolarité et Paiements</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
