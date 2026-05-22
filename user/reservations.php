<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/db.php';
requireLogin();

// Annuler une réservation
if (isset($_GET['annuler'])) {
    $id = $_GET['annuler'];
    $stmt = $pdo->prepare("
        UPDATE reservations SET statut = 'annulée'
        WHERE id = ? AND user_id = ? AND statut = 'en attente'
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header('Location: reservations.php');
    exit;
}

// Récupérer toutes les réservations du client
$stmt = $pdo->prepare("
    SELECT r.*, c.numero, c.type, c.prix_nuit, c.photo, c.description
    FROM reservations r
    JOIN chambres c ON r.chambre_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.date_arrivee DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Mes Réservations</h2>
        <a href="/hotel/chambres.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle réservation
        </a>
    </div>

    <?php if (empty($reservations)): ?>
        <div class="alert alert-info text-center p-5">
            <i class="fas fa-calendar" style="font-size: 2rem;"></i>
            <h5 class="mt-3">Vous n'avez aucune réservation</h5>
            <p class="mb-0">Parcourez nos chambres et réservez dès maintenant !</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($reservations as $r):
                $nuits = nombreNuits($r['date_arrivee'], $r['date_depart']);
                $services = getServicesReservation($pdo, $r['id']);
                $montantServices = array_sum(array_column($services, 'prix'));
                $montantTotal = $r['montant_total'] ?? ($nuits * $r['prix_nuit'] + $montantServices);
                
                $badge = match($r['statut']) {
                    'confirmée'  => 'success',
                    'annulée'    => 'danger',
                    default      => 'warning'
                };
            ?>
            <div class="col-lg-6">
                <div class="card stat-card h-100">
                    <!-- Header -->
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Réservation #<?= $r['id'] ?></h6>
                        <span class="badge bg-<?= $badge ?>"><?= $r['statut'] ?></span>
                    </div>

                    <!-- Room Info -->
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <?php if ($r['photo']): ?>
                                    <img src="/hotel/assets/uploads/<?= htmlspecialchars($r['photo']) ?>" 
                                         alt="Chambre" class="img-fluid rounded" style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                        <i class="fas fa-image" style="font-size: 2rem; color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <h6 class="fw-bold mb-2">Chambre <?= htmlspecialchars($r['numero']) ?></h6>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($r['type']) ?></p>
                                <p class="text-muted small"><?= htmlspecialchars($r['description']) ?></p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="row g-3 mb-3 p-3" style="background: var(--card-bg); border-radius: 8px;">
                            <div class="col-6">
                                <small class="text-muted">Arrivée</small>
                                <div class="fw-bold"><?= dateFr($r['date_arrivee']) ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Départ</small>
                                <div class="fw-bold"><?= dateFr($r['date_depart']) ?></div>
                            </div>
                        </div>

                        <!-- Services -->
                        <?php if (!empty($services)): ?>
                        <div class="mb-3">
                            <small class="text-muted">Services sélectionnés:</small>
                            <div class="mt-2">
                                <?= afficherServices($services) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tarification -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Chambre (<?= $nuits ?> nuit<?= $nuits > 1 ? 's' : '' ?>)</span>
                                <strong><?= number_format($nuits * $r['prix_nuit'], 2) ?> DT</strong>
                            </div>
                            <?php if (!empty($services)): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Services</span>
                                <strong><?= number_format($montantServices, 2) ?> DT</strong>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between pt-2 border-top">
                                <strong>Total</strong>
                                <h6 class="mb-0" style="color: var(--primary);"><?= number_format($montantTotal, 2) ?> DT</h6>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-footer bg-transparent">
                        <?php if ($r['statut'] === 'en attente'): ?>
                            <a href="?annuler=<?= $r['id'] ?>"
                               class="btn btn-danger btn-sm w-100"
                               onclick="return confirm('Êtes-vous sûr d\'annuler cette réservation ?')">
                                <i class="fas fa-trash"></i> Annuler la réservation
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                Aucune action disponible
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>