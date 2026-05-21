<?php
require_once '../session_check.php';
checkRole(['directeur', 'surveillant']);
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trimestre = $_POST['trimestre'] ?? 1;
    $id_classe = $_POST['id_classe'] ?? null;
    
    if ($id_classe && in_array($trimestre, [1,2,3])) {
        // Fetch students in the class
        $stmt_e = $pdo->prepare("SELECT id FROM eleves WHERE id_classe = ? AND statut IN ('inscrit', 'preInscrit')");
        $stmt_e->execute([$id_classe]);
        $eleves = $stmt_e->fetchAll();
        
        $count = 0;
        foreach ($eleves as $e) {
            $id_eleve = $e['id'];
            
            // Check if bulletin already generated
            $stmt_check = $pdo->prepare("SELECT id FROM bulletins WHERE id_eleve = ? AND trimestre = ?");
            $stmt_check->execute([$id_eleve, $trimestre]);
            if ($stmt_check->fetch()) {
                continue; // Skip if already exists
            }
            
            // Calculate weighted average
            // Formula: Sum(Note * Coeff) / Sum(Coeff)
            $stmt_notes = $pdo->prepare("
                SELECT n.valeur, m.coefficient 
                FROM notes n 
                JOIN matieres m ON n.id_matiere = m.id 
                WHERE n.id_eleve = ? AND n.trimestre = ?
            ");
            $stmt_notes->execute([$id_eleve, $trimestre]);
            $notes = $stmt_notes->fetchAll();
            
            if (count($notes) > 0) {
                $sumNoteCoeff = 0;
                $sumCoeff = 0;
                
                foreach ($notes as $n) {
                    $sumNoteCoeff += ($n['valeur'] * $n['coefficient']);
                    $sumCoeff += $n['coefficient'];
                }
                
                $moyenne = $sumCoeff > 0 ? round($sumNoteCoeff / $sumCoeff, 2) : 0;
                $apprecation = "";
                if ($moyenne >= 16) $apprecation = "Très Bien";
                elseif ($moyenne >= 14) $apprecation = "Bien";
                elseif ($moyenne >= 12) $apprecation = "Assez Bien";
                elseif ($moyenne >= 10) $apprecation = "Moyen";
                else $apprecation = "Insuffisant";
                
                // Insert bulletin
                $stmt_ins = $pdo->prepare("INSERT INTO bulletins (id_eleve, trimestre, moyenne_generale, appreciation, statut, date_generation) VALUES (?, ?, ?, ?, 'genere', CURDATE())");
                $stmt_ins->execute([$id_eleve, $trimestre, $moyenne, $apprecation]);
                $count++;
            }
        }
        $success = "$count bulletin(s) généré(s) avec succès pour le trimestre $trimestre.";
    } else {
        $error = "Veuillez sélectionner une classe.";
    }
}

$classes = $pdo->query("SELECT * FROM classes ORDER BY annee_scolaire DESC, niveau ASC")->fetchAll();

include '../header.php';
?>
<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold">Générer les Bulletins</h2>
        <p class="text-muted">Calcul des moyennes pondérées par classe.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Trimestre</label>
                        <select name="trimestre" class="form-select" required>
                            <option value="1">Trimestre 1</option>
                            <option value="2">Trimestre 2</option>
                            <option value="3">Trimestre 3</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Classe</label>
                        <select name="id_classe" class="form-select" required>
                            <option value="">Sélectionner une classe</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['niveau'] . ' ' . $c['nom'] . ' (' . $c['annee_scolaire'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Générer les Bulletins</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
