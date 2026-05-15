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
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/hotel/index.php">🏨 Hôtel Royal</a>
        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a class="nav-link" href="/hotel/admin/index.php">Dashboard Admin</a>
                <?php else: ?>
                    <a class="nav-link" href="/hotel/user/dashboard.php">Mon Compte</a>
                <?php endif; ?>
                <a class="nav-link text-danger" href="/hotel/auth/logout.php">Déconnexion</a>
            <?php else: ?>
                <a class="nav-link" href="/hotel/auth/login.php">Connexion</a>
                <a class="nav-link" href="/hotel/auth/register.php">Inscription</a>
            <?php endif; ?>
        </div>
    </div>
</nav>