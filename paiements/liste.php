<?php
require_once '../session_check.php';
checkRole('directeur'); // Le directeur peut consulter tous les dossiers financiers
require_once '../config.php';

$stmt = $pdo->query("
    SELECT d.*, e.nom, e.prenom, c.niveau, c.nom as classe_nom 
    FROM dossiers_financiers d 
    JOIN eleves e ON d.id_eleve = e.id 
    LEFT JOIN classes c ON e.id_classe = c.id
    ORDER BY d.solde ASC
");
$dossiers = $stmt->fetchAll();

include '../header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">État Financier Global</h2>
        <p class="text-muted">Vue globale des soldes et scolarités (Directeur).</p>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Élève</th>
                        <th>Classe</th>
                        <th>Statut Scolaire</th>
                        <th>Solde Actualisé</th>
                        <th class="text-end pe-4">Dossier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dossiers as $d): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($d['nom'] . ' ' . $d['prenom']) ?></td>
                            <td><?= $d['classe_nom'] ? htmlspecialchars($d['niveau'] . ' ' . $d['classe_nom']) : '<span class="text-muted">N/A</span>' ?></td>
                            <td>
                                <?php
                                $badge = 'bg-success';
                                if($d['statut'] == 'suspendu') $badge = 'bg-danger';
                                // Note: we should join eleves statut really or use dossier statut, using dossier's saved statut here
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($d['statut'])) ?></span>
                            </td>
                            <td><span class="fs-5 fw-bold <?= $d['solde'] < 1000 ? 'text-warning' : 'text-success' ?>"><?= number_format($d['solde'], 2, ',', ' ') ?> €</span></td>
                            <td class="text-end pe-4">
                                <a href="dossier.php?id_eleve=<?= $d['id_eleve'] ?>" class="btn btn-sm btn-outline-primary">Ouvrir le dossier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($dossiers)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Aucun dossier financier trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
