<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/db.php';
requireLogin();

// Récupérer les infos du client connecté
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter ses réservations
$stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ?");
$stmt2->execute([$_SESSION['user_id']]);
$stats = $stmt2->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-5 mb-5">
    <!-- Header -->
    <div class="mb-5">
        <h1 class="fw-bold mb-2">Bonjour, <span style="background: linear-gradient(135deg, var(--primary), var(--gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?= htmlspecialchars($user['prenom']) ?></span> <i class="fas fa-hand-peace"></i></h1>
        <p class="text-muted">Bienvenue dans votre espace personnel</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card stat-card h-100 p-4 text-center">
                <div class="mb-3 icon-box"><i class="fas fa-user"></i></div>
                <h5 class="fw-bold mb-3"><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></h5>
                <p class="text-muted mb-0 small"><?= htmlspecialchars($user['email']) ?></p>
                <hr class="my-3" style="border-color: rgba(99, 102, 241, 0.2);">
                <small class="text-muted">Membre depuis</small>
            </div>
        </div>

        <!-- Reservations Card -->
        <div class="col-md-4">
            <div class="card stat-card h-100 p-4 text-center" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(129, 140, 248, 0.08)); border-color: rgba(99, 102, 241, 0.2);">
                <div class="mb-3 icon-box" style="color: var(--primary);"><i class="fas fa-calendar-alt"></i></div>
                <h2 class="fw-bold mb-2" style="color: var(--primary);"><?= $stats['total'] ?></h2>
                <p class="mb-3">Réservation(s) effectuée(s)</p>
                <a href="reservations.php" class="btn btn-primary btn-sm">Voir mes réservations</a>
            </div>
        </div>

        <!-- Rooms Card -->
        <div class="col-md-4">
            <div class="card stat-card h-100 p-4 text-center" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(134, 239, 172, 0.08)); border-color: rgba(34, 197, 94, 0.2);">
                <div class="mb-3 icon-box" style="color: #22c55e; font-size: 2.5rem;"><i class="fas fa-bed"></i></div>
                <h5 class="fw-bold mb-3">Réserver une chambre</h5>
                <p class="mb-3 text-muted small">Découvrez nos offres disponibles</p>
                <a href="/hotel/chambres.php" class="btn btn-primary btn-sm">Voir les chambres</a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card stat-card p-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-cog" style="color: var(--primary);"></i>
                    Paramètres du compte
                </h6>
                <p class="text-muted small mb-3">Mettez à jour votre profil et vos préférences</p>
                <a href="edit-profile.php" class="btn btn-outline-light btn-sm">Modifier le profil</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card p-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-comments" style="color: var(--primary);"></i>
                    Support & Aide
                </h6>
                <p class="text-muted small mb-3">Besoin d'aide ? Contactez notre équipe</p>
                <a href="#" class="btn btn-outline-light btn-sm">Contacter le support</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>