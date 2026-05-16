<?php
require_once '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /hotel/auth/login.php');
    exit;
}

$message = '';
$erreur  = '';

// ─── AJOUTER une chambre ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $numero      = trim($_POST['numero']);
    $type        = $_POST['type'];
    $prix        = $_POST['prix_nuit'];
    $capacite    = $_POST['capacite'];
    $description = trim($_POST['description']);
    $photo       = '';

    // Upload de la photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array(strtolower($ext), $allowed)) {
            $erreur = "Format de photo non autorisé (jpg, jpeg, png, webp).";
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $erreur = "La photo ne doit pas dépasser 2 Mo.";
        } else {
            $photo = uniqid('chambre_') . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../assets/uploads/' . $photo);
        }
    }

    if (!$erreur) {
        $stmt = $pdo->prepare("
            INSERT INTO chambres (numero, type, prix_nuit, capacite, description, photo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$numero, $type, $prix, $capacite, $description, $photo]);
        $message = "✅ Chambre ajoutée avec succès !";
    }
}

// ─── SUPPRIMER une chambre ───
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'];
    // Supprimer la photo aussi
    $stmt = $pdo->prepare("SELECT photo FROM chambres WHERE id = ?");
    $stmt->execute([$id]);
    $ch = $stmt->fetch();
    if ($ch['photo'] && file_exists('../assets/uploads/' . $ch['photo'])) {
        unlink('../assets/uploads/' . $ch['photo']);
    }
    $pdo->prepare("DELETE FROM chambres WHERE id = ?")->execute([$id]);
    header('Location: chambres.php');
    exit;
}

// ─── MODIFIER disponibilité ───
if (isset($_GET['toggle'])) {
    $id   = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE chambres SET disponible = NOT disponible WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: chambres.php');
    exit;
}

// Récupérer toutes les chambres
$chambres = $pdo->query("SELECT * FROM chambres ORDER BY numero")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>🛏️ Gestion des Chambres</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= $erreur ?></div>
    <?php endif; ?>

    <!-- ─── Formulaire ajout ─── -->
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">➕ Ajouter une chambre</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ajouter">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>Numéro</label>
                        <input type="text" name="numero" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Type</label>
                        <select name="type" class="form-select" required>
                            <option value="simple">Simple</option>
                            <option value="double">Double</option>
                            <option value="suite">Suite</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Prix/nuit (DT)</label>
                        <input type="number" name="prix_nuit" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Capacité</label>
                        <input type="number" name="capacite" class="form-control" required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Photo ⭐</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Max 2Mo — jpg, png, webp</small>
                        <!-- Prévisualisation avant upload -->
                        <img id="preview" src="#" class="mt-2 d-none rounded" style="max-height:100px">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Ajouter la chambre</button>
            </form>
        </div>
    </div>

    <!-- ─── Tableau des chambres ─── -->
    <div class="card shadow">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Photo</th>
                        <th>N°</th>
                        <th>Type</th>
                        <th>Prix/nuit</th>
                        <th>Capacité</th>
                        <th>Disponible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chambres as $c): ?>
                    <tr>
                        <td>
                            <?php if ($c['photo']): ?>
                                <img src="/hotel/assets/uploads/<?= $c['photo'] ?>"
                                     style="width:60px;height:40px;object-fit:cover" class="rounded">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $c['numero'] ?></td>
                        <td><?= $c['type'] ?></td>
                        <td><?= $c['prix_nuit'] ?> DT</td>
                        <td><?= $c['capacite'] ?> pers.</td>
                        <td>
                            <a href="?toggle=<?= $c['id'] ?>">
                                <?php if ($c['disponible']): ?>
                                    <span class="badge bg-success">✅ Disponible</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">❌ Indisponible</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <a href="?supprimer=<?= $c['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Supprimer cette chambre ?')">
                                🗑️ Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Prévisualisation photo avant upload -->
<script>
document.querySelector('input[name="photo"]').addEventListener('change', function () {
    const preview = document.getElementById('preview');
    const file    = this.files[0];
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
});
</script>

</body>
</html>