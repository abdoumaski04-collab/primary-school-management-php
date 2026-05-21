<?php
require_once '../session_check.php';
checkRole('directeur');
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $niveau = $_POST['niveau'] ?? '';
    $nom = $_POST['nom'] ?? '';
    $effectif = $_POST['effectif_max'] ?? 30;
    $annee = $_POST['annee_scolaire'] ?? '';

    if (empty($niveau) || empty($nom) || empty($annee)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO classes (niveau, nom, effectif_max, annee_scolaire) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$niveau, $nom, $effectif, $annee])) {
            $success = "La classe a été créée avec succès.";
        } else {
            $error = "Une erreur est survenue lors de la création de la classe.";
        }
    }
}
include '../header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0 rounded-lg">
            <div class="card-header bg-white py-3">
                <h4 class="mb-0 fw-bold">Créer une classe</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success p-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.95rem;">Niveau (ex: CP, CE1...)</label>
                        <input type="text" name="niveau" class="form-control" required placeholder="CP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.95rem;">Nom (ex: A, B...)</label>
                        <input type="text" name="nom" class="form-control" required placeholder="A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.95rem;">Effectif Max</label>
                        <input type="number" name="effectif_max" class="form-control" value="30" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:0.95rem;">Année Scolaire</label>
                        <input type="text" name="annee_scolaire" class="form-control" required placeholder="2023-2024">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1">Créer la classe</button>
                        <a href="liste.php" class="btn btn-secondary fw-bold flex-grow-1">Retour</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
