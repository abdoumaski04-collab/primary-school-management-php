<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /umllast/dashboard_' . $_SESSION['role'] . '.php');
    exit;
}
require_once 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom_complet'] = $user['prenom'] . ' ' . $user['nom'];
        
        // Register session in DB
        $session_id = session_id();
        $stmt_sess = $pdo->prepare("INSERT INTO sessions (id_session, id_utilisateur, date_debut, derniere_activite) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE derniere_activite = NOW()");
        $stmt_sess->execute([$session_id, $user['id']]);
        
        // Update connected status
        $pdo->prepare("UPDATE utilisateurs SET est_connecte = 1 WHERE id = ?")->execute([$user['id']]);
        
        header('Location: /umllast/dashboard_' . $user['role'] . '.php');
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}
include 'header.php';
?>
<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-4">
        <div class="card shadow border-0 rounded-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Connexion</h3>
                    <p class="text-muted">Accédez à votre espace école</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger p-2 text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.9rem;">Adresse Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="email@exemple.com">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 0.9rem;">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required placeholder="�?��?��?��?��?��?��?��?�">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Se connecter</button>
                </form>
                <div class="mt-4 text-center" style="font-size: 0.9rem;">
                    Vous n'avez pas de compte ? <br>
                    <a href="inscription.php" class="text-decoration-none fw-bold">Créer un compte</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
