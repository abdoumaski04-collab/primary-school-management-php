<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant', 'enseignant']);
require_once '../config.php';

$stmt = $pdo->query("SELECT * FROM classes ORDER BY annee_scolaire DESC, niveau ASC");
$classes = $stmt->fetchAll();

include '../header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Liste des Classes</h2>
    <?php if($_SESSION['role'] === 'directeur'): ?>
        <a href="creer.php" class="btn btn-primary fw-bold shadow-sm">+ Créer une classe</a>
    <?php endif; ?>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Niveau</th>
                        <th>Nom</th>
                        <th>Année Scolaire</th>
                        <th>Effectif Max</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($classes as $c): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($c['niveau']) ?></td>
                            <td><?= htmlspecialchars($c['nom']) ?></td>
                            <td><?= htmlspecialchars($c['annee_scolaire']) ?></td>
                            <td><?= htmlspecialchars($c['effectif_max']) ?></td>
                            <td class="text-end pe-4">
                                <a href="../eleves/liste.php?id_classe=<?= $c['id'] ?>" class="btn btn-sm btn-info text-white">Voir Élèves</a>
                                <?php if($_SESSION['role'] === 'directeur'): ?>
                                    <a href="affecter_enseignant.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Affecter Enseignant</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($classes) === 0): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Aucune classe n'a été trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
