<?php
require_once '../includes/header.php';
require_once '../config/db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp   = $_POST['mot_de_passe'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['role']    = $user['role'];

        // Redirection selon le rôle
        if ($user['role'] === 'admin') {
            header('Location: /hotel/admin/index.php');
        } else {
            header('Location: /hotel/user/dashboard.php');
        }
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Connexion</h3>

                    <?php if ($erreur): ?>
                        <div class="alert alert-danger"><?= $erreur ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Mot de passe</label>
                            <input type="password" name="mot_de_passe" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                    </form>
                    <p class="text-center mt-3">Pas encore inscrit ? <a href="register.php">S'inscrire</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>