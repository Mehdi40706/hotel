<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$message = '';
$erreur  = '';

// ─── Traitement du formulaire de réservation ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $chambre_id   = $_POST['chambre_id'];
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart  = $_POST['date_depart'];

    // Validation des dates
    if (empty($date_arrivee) || empty($date_depart)) {
        $erreur = "Veuillez remplir les deux dates.";
    } elseif ($date_arrivee >= $date_depart) {
        $erreur = "La date de départ doit être après la date d'arrivée.";
    } elseif ($date_arrivee < date('Y-m-d')) {
        $erreur = "La date d'arrivée ne peut pas être dans le passé.";
    } else {
        // Vérifier si la chambre est déjà réservée sur ces dates
        $stmt = $pdo->prepare("
            SELECT id FROM reservations
            WHERE chambre_id = ?
            AND statut != 'annulée'
            AND (date_arrivee < ? AND date_depart > ?)
        ");
        $stmt->execute([$chambre_id, $date_depart, $date_arrivee]);

        if ($stmt->fetch()) {
            $erreur = "Cette chambre est déjà réservée sur ces dates.";
        } else {
            // Insérer la réservation
            $stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, chambre_id, date_arrivee, date_depart)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $chambre_id, $date_arrivee, $date_depart]);
            $message = "✅ Réservation effectuée avec succès !";
        }
    }
}

// ─── Récupérer toutes les chambres disponibles ───
$chambres = $pdo->query("SELECT * FROM chambres WHERE disponible = 1")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2 class="mb-4">🛏️ Nos Chambres</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= $erreur ?></div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($chambres as $chambre): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <!-- Photo de la chambre -->
                <?php if ($chambre['photo']): ?>
                    <img src="/hotel/assets/uploads/<?= htmlspecialchars($chambre['photo']) ?>"
                         class="card-img-top" style="height:200px; object-fit:cover;">
                <?php else: ?>
                    <div class="bg-secondary text-white text-center py-5">Pas de photo</div>
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title">
                        Chambre <?= htmlspecialchars($chambre['numero']) ?>
                        <span class="badge bg-info"><?= $chambre['type'] ?></span>
                    </h5>
                    <p class="card-text"><?= htmlspecialchars($chambre['description']) ?></p>
                    <p>
                        👥 Capacité : <?= $chambre['capacite'] ?> personnes<br>
                        💰 Prix : <strong><?= $chambre['prix_nuit'] ?> DT/nuit</strong>
                    </p>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Bouton qui ouvre le modal de réservation -->
                        <button class="btn btn-primary w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#modalReserver"
                                data-chambre-id="<?= $chambre['id'] ?>"
                                data-chambre-num="<?= $chambre['numero'] ?>">
                            Réserver
                        </button>
                    <?php else: ?>
                        <a href="/hotel/auth/login.php" class="btn btn-outline-primary w-100">
                            Connectez-vous pour réserver
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($chambres)): ?>
            <div class="col-12">
                <div class="alert alert-info">Aucune chambre disponible pour le moment.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Modal Réservation ─── -->
<div class="modal fade" id="modalReserver" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Réserver la chambre <span id="numChambre"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="chambre_id" id="chambreId">

                    <div class="mb-3">
                        <label>Date d'arrivée</label>
                        <input type="date" name="date_arrivee" id="dateArrivee"
                               class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Date de départ</label>
                        <input type="date" name="date_depart" id="dateDepart"
                               class="form-control" required>
                    </div>

                    <!-- Calcul automatique du prix total -->
                    <div class="alert alert-info d-none" id="prixTotal">
                        💰 Total estimé : <strong id="montantTotal"></strong> DT
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Confirmer la réservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Récupérer les prix des chambres depuis PHP
const prixChambres = {};
<?php foreach ($chambres as $c): ?>
    prixChambres[<?= $c['id'] ?>] = <?= $c['prix_nuit'] ?>;
<?php endforeach; ?>

// Quand on clique sur "Réserver" → remplir le modal
const modal = document.getElementById('modalReserver');
modal.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    const id  = btn.getAttribute('data-chambre-id');
    const num = btn.getAttribute('data-chambre-num');

    document.getElementById('chambreId').value = id;
    document.getElementById('numChambre').textContent = num;

    // Date minimum = aujourd'hui
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateArrivee').min = today;
    document.getElementById('dateDepart').min  = today;
});

// Calcul automatique du prix total
function calculerPrix() {
    const chambreId = document.getElementById('chambreId').value;
    const arrivee   = new Date(document.getElementById('dateArrivee').value);
    const depart    = new Date(document.getElementById('dateDepart').value);

    if (arrivee && depart && depart > arrivee) {
        const nuits  = (depart - arrivee) / (1000 * 60 * 60 * 24);
        const prix   = prixChambres[chambreId];
        const total  = nuits * prix;

        document.getElementById('prixTotal').classList.remove('d-none');
        document.getElementById('montantTotal').textContent = total.toFixed(2);
    }
}

document.getElementById('dateArrivee').addEventListener('change', calculerPrix);
document.getElementById('dateDepart').addEventListener('change',  calculerPrix);
</script>

</body>
</html>