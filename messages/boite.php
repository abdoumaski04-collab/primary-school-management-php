<?php
require_once '../session_check.php';
// Everyone
require_once '../config.php';

$stmt_in = $pdo->prepare("SELECT m.*, u.nom as exp_nom, u.prenom as exp_prenom, u.role as exp_role 
                          FROM messages m 
                          JOIN utilisateurs u ON m.id_expediteur = u.id 
                          WHERE m.destinataire_role = ? OR m.destinataire_role = 'tous' 
                          ORDER BY m.date_envoi DESC");
$stmt_in->execute([$_SESSION['role']]);
$received = $stmt_in->fetchAll();

$stmt_out = $pdo->prepare("SELECT * FROM messages WHERE id_expediteur = ? ORDER BY date_envoi DESC");
$stmt_out->execute([$_SESSION['user_id']]);
$sent = $stmt_out->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Messagerie Principale</h2>
    <?php if(in_array($_SESSION['role'], ['directeur', 'surveillant', 'enseignant'])): ?>
        <a href="envoyer.php" class="btn btn-primary fw-bold shadow-sm">Nouveau Message</a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="mb-0 fw-bold text-success"><i class="bi bi-inbox"></i> Boîte de réception</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush rounded-bottom">
                    <?php foreach($received as $r): ?>
                        <div class="list-group-item p-3 border-start border-4 border-success">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($r['exp_nom'] . ' ' . $r['exp_prenom']) ?> <span class="badge bg-light text-dark fw-normal border ms-1"><?= ucfirst($r['exp_role']) ?></span></h6>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['date_envoi'])) ?></small>
                            </div>
                            <p class="mb-1 mt-2 text-dark" style="white-space: pre-line;"><?= htmlspecialchars($r['contenu']) ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($received)): ?>
                        <div class="p-4 text-center text-muted">Boîte de réception vide.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-send"></i> Messages envoyés</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($sent as $s): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold text-muted">À : <?= ucfirst($s['destinataire_role']) ?></h6>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($s['date_envoi'])) ?></small>
                            </div>
                            <p class="mb-1 mt-1 text-muted" style="white-space: pre-line; font-size: 0.95rem;"><?= htmlspecialchars($s['contenu']) ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($sent)): ?>
                        <div class="p-4 text-center text-muted">Aucun message envoyé.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
