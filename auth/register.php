<?php
require_once '../includes/header.php';
require_once '../config/db.php';

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email  = trim($_POST['email']);
    $mdp    = $_POST['mot_de_passe'];
    $mdp2   = $_POST['confirm_mdp'];

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($mdp)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($mdp !== $mdp2) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $erreur = "Cet email est déjà utilisé.";
        } else {
            // Insérer le nouvel utilisateur
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $hash]);
            $succes = "Inscription réussie ! Vous pouvez vous connecter.";
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Inscription</h3>

                    <?php if ($erreur): ?>
                        <div class="alert alert-danger"><?= $erreur ?></div>
                    <?php endif; ?>
                    <?php if ($succes): ?>
                        <div class="alert alert-success"><?= $succes ?>
                            <a href="login.php">Se connecter</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Prénom</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Mot de passe</label>
                            <input type="password" name="mot_de_passe" id="mdp" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="confirm_mdp" id="mdp2" class="form-control" required>
                            <small id="mdp-error" class="text-danger"></small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                    </form>
                    <p class="text-center mt-3">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation JS en temps réel -->
<script>
document.getElementById('mdp2').addEventListener('input', function () {
    const mdp = document.getElementById('mdp').value;
    const mdp2 = this.value;
    const error = document.getElementById('mdp-error');
    if (mdp !== mdp2) {
        error.textContent = "Les mots de passe ne correspondent pas.";
    } else {
        error.textContent = "";
    }
});
</script>

</body>
</html>