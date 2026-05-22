<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/db.php';
requireLogin();

$message = '';
$error = '';

// Récupérer l'utilisateur courant
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $nouveau_mdp = trim($_POST['nouveau_mdp'] ?? '');
    $confirmer_mdp = trim($_POST['confirmer_mdp'] ?? '');
    $mdp_actuel = trim($_POST['mdp_actuel'] ?? '');

    // Validations
    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = '❌ Les champs nom, prénom et email sont obligatoires.';
    } elseif (strlen($nom) < 2 || strlen($prenom) < 2) {
        $error = '❌ Le nom et prénom doivent contenir au moins 2 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Veuillez entrer une adresse email valide.';
    } else {
        // Vérifier que l'email est unique (si changé)
        if ($email !== $user['email']) {
            $check = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $_SESSION['user_id']]);
            if ($check->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                $error = '❌ Cet email est déjà utilisé.';
            }
        }

        // Si pas d'erreur et pas de changement de mot de passe
        if (empty($error) && empty($nouveau_mdp)) {
            // Mettre à jour les infos sans mot de passe
            $update = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?");
            $update->execute([$nom, $prenom, $email, $telephone, $adresse, $_SESSION['user_id']]);
            
            // Mettre à jour la session si l'email a changé
            if ($email !== $user['email']) {
                $_SESSION['email'] = $email;
            }
            
            $message = '✅ Profil mis à jour avec succès !';
            
            // Rafraîchir les données
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        // Si changement de mot de passe
        elseif (!empty($nouveau_mdp)) {
            // Vérifier le mot de passe actuel
            if (!password_verify($mdp_actuel, $user['mot_de_passe'])) {
                $error = '❌ Le mot de passe actuel est incorrect.';
            } elseif ($nouveau_mdp !== $confirmer_mdp) {
                $error = '❌ Les mots de passe ne correspondent pas.';
            } elseif (strlen($nouveau_mdp) < 6) {
                $error = '❌ Le mot de passe doit contenir au moins 6 caractères.';
            } else {
                // Mettre à jour les infos et le mot de passe
                $mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, mot_de_passe = ? WHERE id = ?");
                $update->execute([$nom, $prenom, $email, $telephone, $adresse, $mdp_hash, $_SESSION['user_id']]);
                
                $message = '✅ Profil et mot de passe mis à jour avec succès !';
                
                // Rafraîchir les données
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-5">
                <h1 class="fw-bold">✏️ Modifier mon profil</h1>
                <p class="text-muted">Mettez à jour vos informations personnelles</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="POST" class="card stat-card p-5">
                <!-- Section Infos Personnelles -->
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-user-circle" style="color: var(--primary);"></i>
                    Informations personnelles
                </h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nom *</label>
                        <input type="text" name="nom" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($user['nom']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Prénom *</label>
                        <input type="text" name="prenom" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($user['prenom']) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Email *</label>
                    <input type="email" name="email" class="form-control form-control-lg" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control form-control-lg" 
                           value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Adresse</label>
                    <textarea name="adresse" class="form-control form-control-lg" rows="3"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea>
                </div>

                <hr class="my-4" style="border-color: rgba(99, 102, 241, 0.2);">

                <!-- Section Sécurité -->
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-lock" style="color: var(--primary);"></i>
                    Sécurité (optionnel)
                </h5>

                <p class="text-muted small mb-3">Laissez vide pour ne pas modifier le mot de passe</p>

                <div class="mb-4">
                    <label class="form-label fw-bold">Mot de passe actuel</label>
                    <input type="password" name="mdp_actuel" class="form-control form-control-lg">
                    <small class="text-muted">Requis si vous changez le mot de passe</small>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mdp" class="form-control form-control-lg" 
                               placeholder="Minimum 6 caractères">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Confirmer mot de passe</label>
                        <input type="password" name="confirmer_mdp" class="form-control form-control-lg">
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-3 mt-5">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
