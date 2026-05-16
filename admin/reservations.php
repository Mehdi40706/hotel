<?php
require_once '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /hotel/auth/login.php');
    exit;
}

// Changer le statut d'une réservation
if (isset($_GET['statut']) && isset($_GET['id'])) {
    $id     = $_GET['id'];
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
$where  = $filtre !== 'tous' ? "WHERE r.statut = '$filtre'" : '';

$reservations = $pdo->query("
    SELECT r.*, u.nom, u.prenom, u.email, c.numero, c.type, c.prix_nuit
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    $where
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>📋 Gestion des Réservations</h2>

    <!-- Filtres -->
    <div class="mb-3">
        <a href="?filtre=tous" class="btn btn-outline-dark btn-sm <?= $filtre==='tous'?'active':'' ?>">Toutes</a>
        <a href="?filtre=en attente" class="btn btn-outline-warning btn-sm <?= $filtre==='en attente'?'active':'' ?>">En attente</a>
        <a href="?filtre=confirmée" class="btn btn-outline-success btn-sm <?= $filtre==='confirmée'?'active':'' ?>">Confirmées</a>
        <a href="?filtre=annulée" class="btn btn-outline-danger btn-sm <?= $filtre==='annulée'?'active':'' ?>">Annulées</a>
    </div>

    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Chambre</th>
                        <th>Arrivée</th>
                        <th>Départ</th>
                        <th>Nuits</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r):
                        $nuits = (strtotime($r['date_depart']) - strtotime($r['date_arrivee'])) / 86400;
                        $total = $nuits * $r['prix_nuit'];
                        $badge = match($r['statut']) {
                            'confirmée' => 'success',
                            'annulée'   => 'danger',
                            default     => 'warning'
                        };
                    ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?><br>
                            <small class="text-muted"><?= $r['email'] ?></small>
                        </td>
                        <td>N° <?= $r['numero'] ?> (<?= $r['type'] ?>)</td>
                        <td><?= $r['date_arrivee'] ?></td>
                        <td><?= $r['date_depart'] ?></td>
                        <td><?= $nuits ?></td>
                        <td><strong><?= number_format($total, 2) ?> DT</strong></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= $r['statut'] ?></span></td>
                        <td>
                            <?php if ($r['statut'] === 'en attente'): ?>
                                <a href="?id=<?= $r['id'] ?>&statut=confirmée"
                                   class="btn btn-success btn-sm">✅ Confirmer</a>
                                <a href="?id=<?= $r['id'] ?>&statut=annulée"
                                   class="btn btn-danger btn-sm">❌ Annuler</a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>