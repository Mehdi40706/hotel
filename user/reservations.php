<?php
require_once '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /hotel/auth/login.php');
    exit;
}

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
    SELECT r.*, c.numero, c.type, c.prix_nuit, c.photo
    FROM reservations r
    JOIN chambres c ON r.chambre_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2>📋 Mes Réservations</h2>
    <a href="/hotel/chambres.php" class="btn btn-primary mb-4">+ Nouvelle réservation</a>

    <?php if (empty($reservations)): ?>
        <div class="alert alert-info">Vous n'avez aucune réservation pour le moment.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Chambre</th>
                        <th>Type</th>
                        <th>Arrivée</th>
                        <th>Départ</th>
                        <th>Nuits</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r):
                        $nuits = (strtotime($r['date_depart']) - strtotime($r['date_arrivee'])) / 86400;
                        $total = $nuits * $r['prix_nuit'];

                        // Couleur selon statut
                        $badge = match($r['statut']) {
                            'confirmée'  => 'success',
                            'annulée'    => 'danger',
                            default      => 'warning'
                        };
                    ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td>N° <?= htmlspecialchars($r['numero']) ?></td>
                        <td><?= $r['type'] ?></td>
                        <td><?= $r['date_arrivee'] ?></td>
                        <td><?= $r['date_depart'] ?></td>
                        <td><?= $nuits ?> nuit(s)</td>
                        <td><strong><?= number_format($total, 2) ?> DT</strong></td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $r['statut'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['statut'] === 'en attente'): ?>
                                <a href="?annuler=<?= $r['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Annuler cette réservation ?')">
                                    Annuler
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>