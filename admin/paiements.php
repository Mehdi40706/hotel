<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/db.php';

requireAdmin();

$message = '';

// ─── Ajouter un paiement ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'];
    $montant        = $_POST['montant'];
    $methode        = $_POST['methode'];

    $pdo->prepare("
        INSERT INTO paiements (reservation_id, montant, methode, statut)
        VALUES (?, ?, ?, 'payé')
    ")->execute([$reservation_id, $montant, $methode]);

    // Mettre à jour le statut de la réservation → confirmée
    $pdo->prepare("UPDATE reservations SET statut = 'confirmée' WHERE id = ?")
        ->execute([$reservation_id]);

    $message = "✅ Paiement enregistré et réservation confirmée !";
}

// ─── Changer statut paiement ───
if (isset($_GET['rembourser'])) {
    $pdo->prepare("UPDATE paiements SET statut = 'remboursé' WHERE id = ?")
        ->execute([$_GET['rembourser']]);
    header('Location: paiements.php');
    exit;
}

// ─── Récupérer tous les paiements ───
$paiements = $pdo->query("
    SELECT p.*, r.date_arrivee, r.date_depart,
           u.nom, u.prenom, c.numero
    FROM paiements p
    JOIN reservations r ON p.reservation_id = r.id
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    ORDER BY p.date_paiement DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Réservations sans paiement (en attente) ───
$sansP = $pdo->query("
    SELECT r.*, u.nom, u.prenom, c.numero, c.prix_nuit
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    WHERE r.id NOT IN (SELECT reservation_id FROM paiements)
    AND r.statut != 'annulée'
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Stats paiements ───
$totalPaye = $pdo->query("
    SELECT SUM(montant) FROM paiements WHERE statut = 'payé'
")->fetchColumn() ?? 0;
?>

<div class="container mt-4">
    <h2>💰 Gestion des Paiements</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!-- Stat rapide -->
    <div class="alert alert-info">
        💵 Total encaissé : <strong><?= number_format($totalPaye, 2) ?> DT</strong>
    </div>

    <!-- ─── Réservations à payer ─── -->
    <?php if (!empty($sansP)): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-dark">
            ⚠️ Réservations sans paiement (<?= count($sansP) ?>)
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Réservation</label>
                        <select name="reservation_id" id="selectRes" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($sansP as $r):
                                $nuits = nombreNuits($r['date_arrivee'], $r['date_depart']);
                                $total = $nuits * $r['prix_nuit'];
                            ?>
                            <option value="<?= $r['id'] ?>"
                                    data-montant="<?= $total ?>">
                                #<?= $r['id'] ?> — <?= $r['nom'].' '.$r['prenom'] ?>
                                (N°<?= $r['numero'] ?>) — <?= $total ?> DT
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Montant (DT)</label>
                        <input type="number" name="montant" id="montantInput"
                               step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Méthode</label>
                        <select name="methode" class="form-select" required>
                            <option value="espèces">💵 Espèces</option>
                            <option value="carte">💳 Carte</option>
                            <option value="virement">🏦 Virement</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            Enregistrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ─── Tableau des paiements ─── -->
    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Chambre</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paiements as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></td>
                        <td>N° <?= $p['numero'] ?></td>
                        <td><strong><?= number_format($p['montant'], 2) ?> DT</strong></td>
                        <td><?= $p['methode'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['date_paiement'])) ?></td>
                        <td><?= badgeStatut($p['statut']) ?></td>
                        <td>
                            <?php if ($p['statut'] === 'payé'): ?>
                                <a href="?rembourser=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-warning"
                                   onclick="return confirm('Rembourser ce paiement ?')">
                                    ↩️ Rembourser
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
    </div>
</div>

<!-- Auto-remplir le montant quand on choisit une réservation -->
<script>
document.getElementById('selectRes').addEventListener('change', function () {
    const opt     = this.options[this.selectedIndex];
    const montant = opt.getAttribute('data-montant');
    if (montant) {
        document.getElementById('montantInput').value = montant;
    }
});
</script>

</body>
</html>