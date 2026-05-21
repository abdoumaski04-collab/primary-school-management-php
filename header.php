<?php
// header.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>√?cole Priv√©e - Gestion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; }
    </style>
</head>
<body>
<?php if (isset($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow">
  <div class="container">
    <a class="navbar-brand" href="/umllast/dashboard_<?php echo htmlspecialchars($_SESSION['role']); ?>.php">Mon √?cole</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="/umllast/dashboard_<?php echo htmlspecialchars($_SESSION['role']); ?>.php">Tableau de bord</a>
        </li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item">
            <span class="nav-link text-light">Bonjour, <?php echo htmlspecialchars($_SESSION['nom_complet'] ?? ''); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/umllast/logout.php">D√©connexion</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container">
