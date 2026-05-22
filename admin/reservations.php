<?php
require_once '../includes/header.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Changer le statut d'une réservation
if (isset($_GET['statut']) && isset($_GET['id'])) {
    $id     = intval($_GET['id']);
    $statut = $_GET['statut'];
    if (in_array($statut, ['confirmée', 'annulée', 'en attente'])) {
        $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?")
            ->execute([$statut, $id]);
    }
    header('Location: reservations.php');
    exit;
}

// Filtre par statut
$filtre = $_GET['filtre'] ?? 'tous';
$where  = $filtre !== 'tous' ? "WHERE r.statut = ?" : '';
$params = $filtre !== 'tous' ? [$filtre] : [];

$query = "
    SELECT r.*, u.nom, u.prenom, u.email, c.numero, c.type, c.prix_nuit
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    $where
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les services pour chaque réservation
$services_par_reservation = [];
foreach ($reservations as $r) {
    $services_par_reservation[$r['id']] = getServicesReservation($pdo, $r['id']);
}
?>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Gestion des Réservations</h2>
        <div class="badge bg-primary p-2">
            Total: <?= count($reservations) ?> réservations
        </div>
    </div>

    <!-- Filtres -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <a href="?filtre=tous" class="btn btn-outline-dark btn-sm <?= $filtre==='tous'?'btn-dark':'' ?>">
                ✓ Toutes (<?= $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn() ?>)
            </a>
            <a href="?filtre=en attente" class="btn btn-outline-warning btn-sm <?= $filtre==='en attente'?'active':'' ?>">
                ⏳ En attente (<?= $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en attente'")->fetchColumn() ?>)
            </a>
            <a href="?filtre=confirmée" class="btn btn-outline-success btn-sm <?= $filtre==='confirmée'?'active':'' ?>">
                ✅ Confirmées (<?= $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='confirmée'")->fetchColumn() ?>)
            </a>
            <a href="?filtre=annulée" class="btn btn-outline-danger btn-sm <?= $filtre==='annulée'?'active':'' ?>">
                ❌ Annulées (<?= $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='annulée'")->fetchColumn() ?>)
            </a>
        </div>
    </div>

    <!-- Tableau des réservations -->
    <div class="card shadow">
        <?php if (empty($reservations)): ?>
            <div class="card-body text-center p-5">
                <i class="fas fa-calendar" style="font-size: 3rem; color: var(--primary);"></i>
                <h5 class="mt-3">Aucune réservation trouvée</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Chambre</th>
                            <th>Dates</th>
                            <th>Services</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r):
                            $nuits = nombreNuits($r['date_arrivee'], $r['date_depart']);
                            $montant = $r['montant_total'] ?? ($nuits * $r['prix_nuit']);
                            $services = $services_par_reservation[$r['id']] ?? [];
                            $badge = match($r['statut']) {
                                'confirmée' => 'success',
                                'annulée'   => 'danger',
                                default     => 'warning'
                            };
                        ?>
                        <tr>
                            <td><strong>#<?= $r['id'] ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?></strong>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($r['email']) ?></small>
                            </td>
                            <td>
                                <strong>Chambre <?= htmlspecialchars($r['numero']) ?></strong>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($r['type']) ?></small>
                            </td>
                            <td>
                                <small>
                                    <strong><?= dateFr($r['date_arrivee']) ?></strong><br>
                                    à
                                    <strong><?= dateFr($r['date_depart']) ?></strong><br>
                                    <span class="badge bg-secondary"><?= $nuits ?> nuit<?= $nuits > 1 ? 's' : '' ?></span>
                                </small>
                            </td>
                            <td>
                                <?php if (empty($services)): ?>
                                    <small class="text-muted">—</small>
                                <?php else: ?>
                                    <?php foreach ($services as $s): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($s['nom']) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--primary); font-size: 1.1em;">
                                    <?= number_format($montant, 2) ?> DT
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-<?= $badge ?>" style="font-size: 0.85em;">
                                    <?= $r['statut'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($r['statut'] === 'en attente'): ?>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?id=<?= $r['id'] ?>&statut=confirmée"
                                           class="btn btn-success"
                                           title="Confirmer la réservation">
                                            ✅
                                        </a>
                                        <a href="?id=<?= $r['id'] ?>&statut=annulée"
                                           class="btn btn-danger"
                                           title="Annuler la réservation"
                                           onclick="return confirm('Annuler cette réservation ?')">
                                            ❌
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>