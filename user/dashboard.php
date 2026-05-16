<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Protection : si pas connecté → redirige vers login
if (!isset($_SESSION['user_id'])) {
    header('Location: /hotel/auth/login.php');
    exit;
}

// Récupérer les infos du client connecté
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter ses réservations
$stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ?");
$stmt2->execute([$_SESSION['user_id']]);
$stats = $stmt2->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2>Bonjour, <?= htmlspecialchars($user['prenom']) ?> 👋</h2>
    <p class="text-muted">Bienvenue dans votre espace personnel</p>

    <div class="row mt-4">
        <!-- Carte infos -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h1>👤</h1>
                    <h5><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
        </div>

        <!-- Carte réservations -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body text-center">
                    <h1><?= $stats['total'] ?></h1>
                    <p>Réservation(s) effectuée(s)</p>
                    <a href="reservations.php" class="btn btn-light btn-sm">Voir mes réservations</a>
                </div>
            </div>
        </div>

        <!-- Carte chambres -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body text-center">
                    <h1>🛏️</h1>
                    <p>Réserver une chambre</p>
                    <a href="/hotel/chambres.php" class="btn btn-light btn-sm">Voir les chambres</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>