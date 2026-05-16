<?php
require_once '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /hotel/auth/login.php');
    exit;
}

// Supprimer un client
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'client'")
        ->execute([$_GET['supprimer']]);
    header('Location: clients.php');
    exit;
}

// Tous les clients avec leur nb de réservations
$clients = $pdo->query("
    SELECT u.*, COUNT(r.id) as nb_reservations
    FROM users u
    LEFT JOIN reservations r ON u.id = r.user_id
    WHERE u.role = 'client'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>👤 Gestion des Clients</h2>

    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Inscrit le</th>
                        <th>Réservations</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <span class="badge bg-primary"><?= $c['nb_reservations'] ?></span>
                        </td>
                        <td>
                            <a href="?supprimer=<?= $c['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Supprimer ce client ?')">
                                🗑️
                            </a>
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