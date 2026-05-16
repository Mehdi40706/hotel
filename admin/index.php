<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Protection admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /hotel/auth/login.php');
    exit;
}

// ─── Statistiques ───
$stats = [];

// Total clients
$stats['clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();

// Total chambres
$stats['chambres'] = $pdo->query("SELECT COUNT(*) FROM chambres")->fetchColumn();

// Total réservations
$stats['reservations'] = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

// Réservations en attente
$stats['en_attente'] = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en attente'")->fetchColumn();

// Revenu total (réservations confirmées)
$stats['revenu'] = $pdo->query("
    SELECT SUM(DATEDIFF(r.date_depart, r.date_arrivee) * c.prix_nuit)
    FROM reservations r
    JOIN chambres c ON r.chambre_id = c.id
    WHERE r.statut = 'confirmée'
")->fetchColumn() ?? 0;

// Réservations par mois (pour le graphique)
$parMois = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%M') as mois,
           COUNT(*) as total
    FROM reservations
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

// Chambres les plus réservées
$topChambres = $pdo->query("
    SELECT c.numero, c.type, COUNT(r.id) as nb_reservations
    FROM chambres c
    LEFT JOIN reservations r ON c.id = r.chambre_id
    GROUP BY c.id
    ORDER BY nb_reservations DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Dernières réservations
$dernieres = $pdo->query("
    SELECT r.*, u.nom, u.prenom, c.numero
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4">
    <h2>📊 Dashboard Administration</h2>

    <!-- ─── Cartes statistiques ─── -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <h3><?= $stats['clients'] ?></h3>
                    <p>👤 Clients inscrits</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h3><?= $stats['chambres'] ?></h3>
                    <p>🛏️ Chambres</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white shadow">
                <div class="card-body">
                    <h3><?= $stats['reservations'] ?></h3>
                    <p>📋 Réservations totales</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow">
                <div class="card-body">
                    <h3><?= number_format($stats['revenu'], 2) ?> DT</h3>
                    <p>💰 Revenu total</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- ─── Graphique réservations par mois ─── -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <h5>📈 Réservations par mois</h5>
                    <canvas id="graphMois"></canvas>
                </div>
            </div>
        </div>

        <!-- ─── Top chambres ─── -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5>🏆 Top Chambres</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topChambres as $ch): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>N° <?= $ch['numero'] ?> (<?= $ch['type'] ?>)</span>
                            <span class="badge bg-primary"><?= $ch['nb_reservations'] ?> rés.</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Dernières réservations ─── -->
    <div class="card shadow mt-4">
        <div class="card-body">
            <h5>🕐 Dernières réservations</h5>
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Client</th>
                        <th>Chambre</th>
                        <th>Arrivée</th>
                        <th>Départ</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dernieres as $r):
                        $badge = match($r['statut']) {
                            'confirmée' => 'success',
                            'annulée'   => 'danger',
                            default     => 'warning'
                        };
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?></td>
                        <td>N° <?= $r['numero'] ?></td>
                        <td><?= $r['date_arrivee'] ?></td>
                        <td><?= $r['date_depart'] ?></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= $r['statut'] ?></span></td>
                        <td>
                            <a href="reservations.php" class="btn btn-sm btn-outline-primary">
                                Gérer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ─── Liens rapides ─── -->
    <div class="row mt-4 mb-5">
        <div class="col-md-3">
            <a href="chambres.php" class="btn btn-outline-success w-100 py-3">🛏️ Gérer les Chambres</a>
        </div>
        <div class="col-md-3">
            <a href="reservations.php" class="btn btn-outline-warning w-100 py-3">📋 Gérer les Réservations</a>
        </div>
        <div class="col-md-3">
            <a href="services.php" class="btn btn-outline-info w-100 py-3">🍽️ Gérer les Services</a>
        </div>
        <div class="col-md-3">
            <a href="clients.php" class="btn btn-outline-primary w-100 py-3">👤 Gérer les Clients</a>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode(array_column($parMois, 'mois')) ?>;
const data   = <?= json_encode(array_column($parMois, 'total')) ?>;

new Chart(document.getElementById('graphMois'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Réservations',
            data: data,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>