<?php
require_once '../includes/header.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

// ─── Statistiques ───
$stats = [];

// Total clients
$stats['clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

// Total clients admin
$stats['admins'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

// Total chambres
$stats['chambres'] = $pdo->query("SELECT COUNT(*) FROM chambres")->fetchColumn();

// Chambres disponibles
$stats['chambres_dispo'] = $pdo->query("SELECT COUNT(*) FROM chambres WHERE disponible=1")->fetchColumn();

// Total réservations
$stats['reservations'] = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

// Réservations en attente
$stats['en_attente'] = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en attente'")->fetchColumn();

// Réservations confirmées
$stats['confirmees'] = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='confirmée'")->fetchColumn();

// Paiements effectués
$stats['paiements'] = $pdo->query("SELECT COUNT(*) FROM paiements WHERE statut='payé'")->fetchColumn();

// Revenu total (somme des montants_total des réservations confirmées)
$stats['revenu'] = $pdo->query("
    SELECT SUM(COALESCE(montant_total, DATEDIFF(r.date_depart, r.date_arrivee) * c.prix_nuit))
    FROM reservations r
    JOIN chambres c ON r.chambre_id = c.id
    WHERE r.statut = 'confirmée'
")->fetchColumn() ?? 0;

// Montant des paiements enregistrés
$stats['montant_paiements'] = $pdo->query("SELECT SUM(montant) FROM paiements WHERE statut='payé'")->fetchColumn() ?? 0;

// Taux de remplissage moyen
$total_nuits = $pdo->query("
    SELECT SUM(DATEDIFF(date_depart, date_arrivee)) 
    FROM reservations 
    WHERE statut != 'annulée'
")->fetchColumn() ?? 0;

$capacite_total = $pdo->query("SELECT SUM(capacite) FROM chambres")->fetchColumn() ?? 1;
$stats['taux_remplissage'] = $capacite_total > 0 ? round(($total_nuits / $capacite_total) * 100, 1) : 0;

// Services les plus demandés
$services_popular = $pdo->query("
    SELECT s.nom, COUNT(rs.id) as utilisations, s.prix
    FROM services s
    LEFT JOIN reservation_services rs ON s.id = rs.service_id
    GROUP BY s.id
    ORDER BY utilisations DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Réservations par mois
$parMois = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as mois,
           DATE_FORMAT(created_at, '%M %Y') as mois_label,
           COUNT(*) as total
    FROM reservations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at
")->fetchAll(PDO::FETCH_ASSOC);

// Chambres les plus réservées
$topChambres = $pdo->query("
    SELECT c.id, c.numero, c.type, COUNT(r.id) as nb_reservations, c.prix_nuit
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
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Revenus par mois
$revenus_mois = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as mois,
           DATE_FORMAT(created_at, '%M %Y') as mois_label,
           SUM(COALESCE(montant_total, 0)) as total
    FROM reservations
    WHERE statut = 'confirmée'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">📊 Dashboard Administration</h2>
            <p class="text-muted">Bienvenue dans votre espace de gestion</p>
        </div>
        <div class="text-end">
            <small class="text-muted">Dernière mise à jour: <?= date('d/m/Y H:i') ?></small>
        </div>
    </div>

    <!-- ─── KPI Cards ─── -->
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card stat-card border-start border-5 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">👤 Clients inscrits</h6>
                            <h2 class="fw-bold mb-0"><?= $stats['clients'] ?></h2>
                        </div>
                        <i class="fas fa-users" style="font-size: 2rem; color: rgba(99, 102, 241, 0.2);"></i>
                    </div>
                    <small class="text-muted">+ <?= $stats['admins'] ?> admin(s)</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card border-start border-5 border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">🛏️ Chambres</h6>
                            <h2 class="fw-bold mb-0"><?= $stats['chambres'] ?></h2>
                        </div>
                        <i class="fas fa-door-open" style="font-size: 2rem; color: rgba(34, 197, 94, 0.2);"></i>
                    </div>
                    <small class="text-muted"><?= $stats['chambres_dispo'] ?> disponible(s)</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card border-start border-5 border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">📋 Réservations</h6>
                            <h2 class="fw-bold mb-0"><?= $stats['reservations'] ?></h2>
                        </div>
                        <i class="fas fa-calendar-alt" style="font-size: 2rem; color: rgba(234, 179, 8, 0.2);"></i>
                    </div>
                    <small class="text-muted"><?= $stats['confirmees'] ?> confirmées</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card border-start border-5 border-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">💰 Revenu total</h6>
                            <h2 class="fw-bold mb-0"><?= number_format($stats['revenu'], 0) ?> DT</h2>
                        </div>
                        <i class="fas fa-chart-line" style="font-size: 2rem; color: rgba(239, 68, 68, 0.2);"></i>
                    </div>
                    <small class="text-muted">Réservations confirmées</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Secondary KPI ─── -->
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">⏳ En attente de confirmation</h6>
                    <h3 class="fw-bold mb-0" style="color: #eab308;"><?= $stats['en_attente'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">💳 Paiements enregistrés</h6>
                    <h3 class="fw-bold mb-0" style="color: #10b981;"><?= $stats['paiements'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">📈 Taux de remplissage</h6>
                    <h3 class="fw-bold mb-0" style="color: #8b5cf6;"><?= $stats['taux_remplissage'] ?>%</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">🎁 Services demandés</h6>
                    <h3 class="fw-bold mb-0" style="color: #3b82f6;"><?= count($services_popular) ?></h3>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-4 mb-5">
        <!-- Top 5 Chambres -->
        <div class="col-lg-6">
            <div class="card stat-card">
                <div class="card-header">
                    <h6 class="mb-0">🏆 Top 5 Chambres les plus réservées</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($topChambres)): ?>
                        <p class="text-muted text-center py-4">Aucune réservation</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-borderless small">
                                <tbody>
                                    <?php foreach ($topChambres as $idx => $ch): ?>
                                    <tr>
                                        <td><strong style="font-size: 1.5em; color: var(--primary);">#<?= $idx + 1 ?></strong></td>
                                        <td>
                                            <strong>Chambre <?= htmlspecialchars($ch['numero']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($ch['type']) ?></small>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= $ch['nb_reservations'] ?></strong> réservations
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Services -->
        <div class="col-lg-6">
            <div class="card stat-card">
                <div class="card-header">
                    <h6 class="mb-0">🎁 Services les plus demandés</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($services_popular)): ?>
                        <p class="text-muted text-center py-4">Aucun service sélectionné</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-borderless small">
                                <tbody>
                                    <?php foreach ($services_popular as $idx => $svc): ?>
                                    <tr>
                                        <td><strong style="font-size: 1.5em; color: var(--primary);">#<?= $idx + 1 ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($svc['nom']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= $svc['prix'] ?> DT</small>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= $svc['utilisations'] ?></strong> fois
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Dernières réservations ─── -->
    <div class="card stat-card mb-5">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">🕐 Dernières réservations</h6>
                <a href="reservations.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($dernieres)): ?>
                <p class="text-muted text-center py-4">Aucune réservation</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Chambre</th>
                                <th>Dates</th>
                                <th>Statut</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dernieres as $r):
                                $nuits = nombreNuits($r['date_arrivee'], $r['date_depart']);
                                $badge = match($r['statut']) {
                                    'confirmée' => 'success',
                                    'annulée'   => 'danger',
                                    default     => 'warning'
                                };
                            ?>
                            <tr>
                                <td><strong>#<?= $r['id'] ?></strong></td>
                                <td><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></td>
                                <td>Chambre <?= htmlspecialchars($r['numero']) ?></td>
                                <td>
                                    <small><?= dateFr($r['date_arrivee']) ?> → <?= dateFr($r['date_depart']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $badge ?>"><?= $r['statut'] ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="reservations.php?filtre=<?= urlencode($r['statut']) ?>" class="btn btn-xs btn-outline-primary">
                                        Gérer
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ─── Quick Links ─── -->
    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
            <a href="chambres.php" class="btn btn-outline-success w-100 py-3">
                <i class="fas fa-bed"></i><br>Gérer les Chambres
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="reservations.php" class="btn btn-outline-warning w-100 py-3">
                <i class="fas fa-calendar"></i><br>Gérer les Réservations
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="paiements.php" class="btn btn-outline-info w-100 py-3">
                <i class="fas fa-credit-card"></i><br>Gérer les Paiements
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="services.php" class="btn btn-outline-danger w-100 py-3">
                <i class="fas fa-gift"></i><br>Gérer les Services
            </a>
        </div>
    </div>
</div>



</body>
</html>