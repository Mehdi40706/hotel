<?php
require_once '../includes/header.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';

// Ajouter un service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix        = $_POST['prix'];
    $photo       = '';

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $ext   = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = uniqid('service_') . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], '../assets/uploads/' . $photo);
    }

    $pdo->prepare("INSERT INTO services (nom, description, prix, photo) VALUES (?, ?, ?, ?)")
        ->execute([$nom, $description, $prix, $photo]);
    $message = "✅ Service ajouté !";
}

// Supprimer un service
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$_GET['supprimer']]);
    header('Location: services.php');
    exit;
}

$services = $pdo->query("SELECT * FROM services ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>🍽️ Gestion des Services</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!-- Formulaire ajout -->
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">➕ Ajouter un service</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Nom du service</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Prix (DT)</label>
                        <input type="number" name="prix" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Liste des services -->
    <div class="row">
        <?php foreach ($services as $s): ?>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <?php if ($s['photo']): ?>
                    <img src="/hotel/assets/uploads/<?= $s['photo'] ?>"
                         class="card-img-top" style="height:150px;object-fit:cover">
                <?php endif; ?>
                <div class="card-body">
                    <h5><?= htmlspecialchars($s['nom']) ?></h5>
                    <p><?= htmlspecialchars($s['description']) ?></p>
                    <strong><?= $s['prix'] ?> DT</strong>
                </div>
                <div class="card-footer">
                    <a href="?supprimer=<?= $s['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ?')">🗑️ Supprimer</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>