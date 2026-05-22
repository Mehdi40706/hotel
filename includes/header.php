<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hôtel Royal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/hotel/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/hotel/index.php"><i class="fas fa-building"></i> HÔTEL ROYAL</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto gap-2">
                <a class="nav-link" href="/hotel/chambres.php">Chambres</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a class="nav-link" href="/hotel/admin/index.php">Dashboard Admin</a>
                    <?php else: ?>
                        <a class="nav-link" href="/hotel/user/dashboard.php">Mon Compte</a>
                    <?php endif; ?>
                    <a class="nav-link" href="/hotel/auth/logout.php" style="color: var(--primary) !important;">Déconnexion</a>
                <?php else: ?>
                    <a class="nav-link" href="/hotel/auth/login.php">Connexion</a>
                    <a class="nav-link" href="/hotel/auth/register.php" style="color: var(--primary) !important;">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>