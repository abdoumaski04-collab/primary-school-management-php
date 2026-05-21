<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /umllast/dashboard_' . $_SESSION['role'] . '.php');
    exit;
}
require_once 'config.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $roles_autorises = ['parent', 'eleve','enseignant'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($role)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!in_array($role, $roles_autorises)) {
        $error = "Rôle invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nom, $prenom, $email, $hash, $role])) {
                $success = "Compte créé avec succès. Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de la création du compte. Veuillez réessayer.";
            }
        }
    }
}
include 'header.php';
?>
<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5">
        <div class="card shadow border-0 rounded-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Créer un compte</h3>
                    <p class="text-muted">Rejoignez l'École Primaire Connectée</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger p-2 text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success p-2 text-center">
                        <?= htmlspecialchars($success) ?> <br>
                        <a href="/umllast/index.php" class="alert-link">Se connecter</a>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" style="font-size: 0.9rem;">Nom</label>
                            <input type="text" name="nom" class="form-control" required placeholder="Votre nom"
                                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" style="font-size: 0.9rem;">Prénom</label>
                            <input type="text" name="prenom" class="form-control" required placeholder="Votre prénom"
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.9rem;">Adresse Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="email@exemple.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.9rem;">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 0.9rem;">Je m'inscris en tant que</label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>-- Choisir un rôle --</option>
                            <option value="parent" <?= ($_POST['role'] ?? '') === 'parent' ? 'selected' : '' ?>>👨‍👧 Parent</option>
                            <option value="eleve"  <?= ($_POST['role'] ?? '') === 'eleve'  ? 'selected' : '' ?>>🎒 Élève</option>
                            <option value="enseignant" <?= ($_POST['role'] ?? '') === 'enseignant' ? 'selected' : '' ?>>👨‍🏫 Enseignant</option>

                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold mb-3">S'inscrire</button>
                    <div class="text-center" style="font-size: 0.9rem;">
                        <a href="/umllast/index.php" class="text-decoration-none">Déjà un compte ? Se connecter</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>