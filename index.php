<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="hero">
    <div class="container">
        <h1><i class="fas fa-building"></i> HÔTEL ROYAL</h1>
        <p>Votre séjour de luxe vous attend</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="chambres.php" class="btn btn-gold btn-lg">Voir nos chambres</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="auth/register.php" class="btn btn-outline-light btn-lg">S'inscrire</a>
            <?php else: ?>
                <a href="user/dashboard.php" class="btn btn-outline-light btn-lg">Mon espace</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Why Choose Us -->
<div class="container py-5 my-5">
    <h2 class="text-center mb-5 fw-bold"><i class="fas fa-sparkles"></i> Pourquoi nous choisir ?</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card stat-card text-center p-4 h-100">
                <div class="mb-3 icon-box"><i class="fas fa-bed"></i></div>
                <h5 class="fw-bold mb-3">Chambres de Luxe</h5>
                <p class="text-muted">Simple, double ou suite — confort et élégance garantis pour votre séjour</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card text-center p-4 h-100">
                <div class="mb-3 icon-box"><i class="fas fa-utensils"></i></div>
                <h5 class="fw-bold mb-3">Services Premium</h5>
                <p class="text-muted">Restaurant gastronomique, spa relaxant, piscine et bien plus encore</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card text-center p-4 h-100">
                <div class="mb-3 icon-box"><i class="fas fa-map-marker-alt"></i></div>
                <h5 class="fw-bold mb-3">Emplacement Idéal</h5>
                <p class="text-muted">Au cœur de la ville, proche de tous les sites touristiques majeurs</p>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="container py-5 my-5">
    <div class="card stat-card p-5 text-center border-0">
        <h3 class="fw-bold mb-4">Prêt à réserver votre chambre ?</h3>
        <p class="mb-4" style="font-size: 1.1rem;">Découvrez nos offres spéciales et bénéficiez d'une expérience inoubliable</p>
        <a href="chambres.php" class="btn btn-primary btn-lg">Réserver maintenant</a>
    </div>
</div>

<!-- Footer -->
<footer class="mt-5">
    <p class="mb-0">© 2025 <span>HÔTEL ROYAL</span> — Tous droits réservés</p>
    <p class="small mt-2" style="color: var(--text-muted);">Un service premium pour un séjour mémorable</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>