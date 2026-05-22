<?php
require_once 'includes/header.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$erreur  = '';

// ─── Traitement du formulaire de réservation ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $chambre_id   = intval($_POST['chambre_id']);
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart  = $_POST['date_depart'];
    $services_ids = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];

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
            // Récupérer les prix pour calculer le montant total
            $stmt_chambre = $pdo->prepare("SELECT prix_nuit FROM chambres WHERE id = ?");
            $stmt_chambre->execute([$chambre_id]);
            $chambre = $stmt_chambre->fetch(PDO::FETCH_ASSOC);
            
            $nuits = (strtotime($date_depart) - strtotime($date_arrivee)) / 86400;
            $montant_chambre = $nuits * $chambre['prix_nuit'];
            
            // Calculer le coût des services
            $montant_services = 0;
            if (!empty($services_ids)) {
                $placeholders = implode(',', $services_ids);
                $stmt_services = $pdo->query("SELECT SUM(prix) as total FROM services WHERE id IN ($placeholders)");
                $service_result = $stmt_services->fetch(PDO::FETCH_ASSOC);
                $montant_services = $service_result['total'] ?? 0;
            }
            
            $montant_total = $montant_chambre + $montant_services;
            
            // Insérer la réservation
            $stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, chambre_id, date_arrivee, date_depart, montant_total)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $chambre_id, $date_arrivee, $date_depart, $montant_total]);
            
            // Récupérer l'ID de la réservation créée
            $reservation_id = $pdo->lastInsertId();
            
            // Ajouter les services sélectionnés
            if (!empty($services_ids)) {
                $stmt_insert = $pdo->prepare("INSERT INTO reservation_services (reservation_id, service_id) VALUES (?, ?)");
                foreach ($services_ids as $service_id) {
                    $stmt_insert->execute([$reservation_id, $service_id]);
                }
            }
            
            $message = "✅ Réservation effectuée avec succès ! Montant total: $montant_total DT";
        }
    }
}

// ─── Récupérer toutes les chambres disponibles avec filtres ───
$query = "SELECT * FROM chambres WHERE disponible = 1";
$params = [];

// Filtre par type de chambre
if (!empty($_GET['type'])) {
    $query .= " AND type = ?";
    $params[] = $_GET['type'];
}

// Filtre par capacité minimale
if (!empty($_GET['capacite']) && is_numeric($_GET['capacite'])) {
    $query .= " AND capacite >= ?";
    $params[] = (int)$_GET['capacite'];
}

// Filtre par prix maximal
if (!empty($_GET['prix_max']) && is_numeric($_GET['prix_max'])) {
    $query .= " AND prix_nuit <= ?";
    $params[] = (float)$_GET['prix_max'];
}

// Filtre par prix minimal
if (!empty($_GET['prix_min']) && is_numeric($_GET['prix_min'])) {
    $query .= " AND prix_nuit >= ?";
    $params[] = (float)$_GET['prix_min'];
}

// Tri
$sort = $_GET['sort'] ?? 'numero';
$order = 'ASC';
if ($sort === 'prix_desc') {
    $query .= " ORDER BY prix_nuit DESC";
} elseif ($sort === 'prix_asc') {
    $query .= " ORDER BY prix_nuit ASC";
} else {
    $query .= " ORDER BY " . $sort;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les types de chambres uniques
$types = $pdo->query("SELECT DISTINCT type FROM chambres WHERE disponible = 1")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les services disponibles
$services = $pdo->query("SELECT * FROM services WHERE disponible = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bed"></i> Nos Chambres</h2>
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
            <i class="fas fa-sliders-h"></i> Filtres
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= $erreur ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Panel -->
    <div class="collapse mb-4" id="filterPanel">
        <div class="card stat-card p-4">
            <form method="GET" class="row g-3">
                <!-- Search by room number -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Rechercher par numéro</label>
                    <input type="text" class="form-control" placeholder="Ex: Chambre 101" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>

                <!-- Room Type Filter -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Type de chambre</label>
                    <select class="form-select" name="type">
                        <option value="">Tous les types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= htmlspecialchars($t['type']) ?>" 
                                <?= (isset($_GET['type']) && $_GET['type'] === $t['type']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Capacity Filter -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Capacité minimale</label>
                    <select class="form-select" name="capacite">
                        <option value="">Toute capacité</option>
                        <option value="1" <?= ($_GET['capacite'] ?? '') === '1' ? 'selected' : '' ?>>1 personne</option>
                        <option value="2" <?= ($_GET['capacite'] ?? '') === '2' ? 'selected' : '' ?>>2 personnes</option>
                        <option value="4" <?= ($_GET['capacite'] ?? '') === '4' ? 'selected' : '' ?>>4 personnes</option>
                        <option value="6" <?= ($_GET['capacite'] ?? '') === '6' ? 'selected' : '' ?>>6+ personnes</option>
                    </select>
                </div>

                <!-- Price Range Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Prix min</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="prix_min" placeholder="Min" value="<?= htmlspecialchars($_GET['prix_min'] ?? '') ?>">
                        <span class="input-group-text">DT</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Prix max</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="prix_max" placeholder="Max" value="<?= htmlspecialchars($_GET['prix_max'] ?? '') ?>">
                        <span class="input-group-text">DT</span>
                    </div>
                </div>

                <!-- Sort Filter -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Trier par</label>
                    <select class="form-select" name="sort">
                        <option value="numero" <?= ($_GET['sort'] ?? '') === 'numero' ? 'selected' : '' ?>>Numéro</option>
                        <option value="prix_asc" <?= ($_GET['sort'] ?? '') === 'prix_asc' ? 'selected' : '' ?>>Prix (croissant)</option>
                        <option value="prix_desc" <?= ($_GET['sort'] ?? '') === 'prix_desc' ? 'selected' : '' ?>>Prix (décroissant)</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <a href="chambres.php" class="btn btn-outline-primary">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Count -->
    <div class="mb-3">
        <p class="text-muted">
            <i class="fas fa-info-circle"></i> 
            <?= count($chambres) ?> chambre(s) trouvée(s)
        </p>
    </div>

    <!-- Rooms Grid -->
    <div class="row g-4">
        <?php if (empty($chambres)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center p-5">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--primary);"></i>
                    <h5 class="mt-3">Aucune chambre ne correspond à vos critères</h5>
                    <p>Essayez de modifier vos filtres ou <a href="chambres.php">voir toutes les chambres</a></p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($chambres as $chambre): ?>
            <div class="col-md-4">
                <div class="card stat-card h-100">
                    <!-- Room Image -->
                    <?php if ($chambre['photo']): ?>
                        <img src="/hotel/assets/uploads/<?= htmlspecialchars($chambre['photo']) ?>"
                             class="card-img-top" style="height:250px; object-fit:cover;">
                    <?php else: ?>
                        <div class="bg-light text-center py-5">
                            <i class="fas fa-image" style="font-size: 3rem; color: var(--border);"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <!-- Room Header -->
                        <div class="mb-3">
                            <h5 class="card-title fw-bold mb-2">
                                Chambre <?= htmlspecialchars($chambre['numero']) ?>
                            </h5>
                            <div>
                                <span class="badge" style="background: var(--primary); color: white;">
                                    <?= htmlspecialchars($chambre['type']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Room Description -->
                        <p class="card-text text-muted small flex-grow-1">
                            <?= htmlspecialchars($chambre['description']) ?>
                        </p>

                        <!-- Room Details -->
                        <div class="mb-3 p-3" style="background: var(--card-bg); border-radius: 12px;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">
                                    <i class="fas fa-users"></i> Capacité
                                </span>
                                <strong><?= $chambre['capacite'] ?> personne(s)</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">
                                    <i class="fas fa-tag"></i> Prix/nuit
                                </span>
                                <strong style="color: var(--primary); font-size: 1.1rem;">
                                    <?= $chambre['prix_nuit'] ?> DT
                                </strong>
                            </div>
                        </div>

                        <!-- Book Button -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-primary w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalReserver"
                                    data-chambre-id="<?= $chambre['id'] ?>"
                                    data-chambre-num="<?= $chambre['numero'] ?>">
                                <i class="fas fa-calendar-check"></i> Réserver
                            </button>
                        <?php else: ?>
                            <a href="/hotel/auth/login.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

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

                    <!-- Services supplémentaires -->
                    <?php if (!empty($services)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Services supplémentaires</label>
                        <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                            <?php foreach ($services as $service): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input service-checkbox" 
                                       type="checkbox" 
                                       name="services[]" 
                                       value="<?= $service['id'] ?>"
                                       data-prix="<?= $service['prix'] ?>"
                                       id="service_<?= $service['id'] ?>">
                                <label class="form-check-label" for="service_<?= $service['id'] ?>">
                                    <strong><?= htmlspecialchars($service['nom']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($service['description']) ?></small>
                                    <span style="float: right; color: var(--primary); font-weight: bold;">
                                        +<?= $service['prix'] ?> DT
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Calcul automatique du prix total -->
                    <div class="alert alert-info d-none" id="prixTotal">
                        💰 Chambre: <strong id="montantChambre">0</strong> DT
                        <br>
                        🎁 Services: <strong id="montantServices">0</strong> DT
                        <br>
                        <strong style="font-size: 1.2em;">Total: <span id="montantTotal">0</span> DT</strong>
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
    
    // Réinitialiser le calcul
    document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = false);
    calculerPrix();
});

// Synchroniser la date minimale de départ avec la date d'arrivée
document.getElementById('dateArrivee').addEventListener('change', function() {
    const dateArrivee = this.value;
    const dateDepart = document.getElementById('dateDepart');
    
    if (dateArrivee) {
        dateDepart.min = dateArrivee;
        
        // Si la date de départ est antérieure ou égale à la date d'arrivée, la réinitialiser
        if (dateDepart.value && dateDepart.value <= dateArrivee) {
            dateDepart.value = '';
            calculerPrix();
        }
    }
});

// Valider la date de départ quand elle change
document.getElementById('dateDepart').addEventListener('change', function() {
    const dateArrivee = document.getElementById('dateArrivee').value;
    
    if (dateArrivee && this.value && this.value <= dateArrivee) {
        alert('❌ La date de départ doit être après la date d\'arrivée!');
        this.value = '';
        calculerPrix();
    }
});

// Calcul automatique du prix total
function calculerPrix() {
    const chambreId = document.getElementById('chambreId').value;
    const arrivee   = new Date(document.getElementById('dateArrivee').value);
    const depart    = new Date(document.getElementById('dateDepart').value);

    let prixChambre = 0;
    let prixServices = 0;

    if (arrivee && depart && depart > arrivee) {
        const nuits = (depart - arrivee) / (1000 * 60 * 60 * 24);
        const prix  = prixChambres[chambreId];
        prixChambre = nuits * prix;

        // Calculer le prix des services sélectionnés
        document.querySelectorAll('.service-checkbox:checked').forEach(checkbox => {
            prixServices += parseFloat(checkbox.getAttribute('data-prix'));
        });

        const prixTotal = prixChambre + prixServices;

        document.getElementById('prixTotal').classList.remove('d-none');
        document.getElementById('montantChambre').textContent = prixChambre.toFixed(2);
        document.getElementById('montantServices').textContent = prixServices.toFixed(2);
        document.getElementById('montantTotal').textContent = prixTotal.toFixed(2);
    } else {
        document.getElementById('prixTotal').classList.add('d-none');
    }
}

document.getElementById('dateArrivee').addEventListener('change', calculerPrix);
document.getElementById('dateDepart').addEventListener('change', calculerPrix);
document.querySelectorAll('.service-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', calculerPrix);
});
</script>

</body>
</html>