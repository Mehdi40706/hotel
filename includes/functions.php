<?php
// ─── Protéger une page client ───
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /hotel/auth/login.php');
        exit;
    }
}

// ─── Protéger une page admin ───
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: /hotel/auth/login.php');
        exit;
    }
}

// ─── Nettoyer les entrées utilisateur ───
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ─── Formater une date en français ───
function dateFr($date) {
    $mois = [
        '01'=>'Janvier','02'=>'Février','03'=>'Mars',
        '04'=>'Avril','05'=>'Mai','06'=>'Juin',
        '07'=>'Juillet','08'=>'Août','09'=>'Septembre',
        '10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'
    ];
    [$y, $m, $d] = explode('-', $date);
    return "$d {$mois[$m]} $y";
}

// ─── Calculer le nombre de nuits ───
function nombreNuits($arrivee, $depart) {
    return (strtotime($depart) - strtotime($arrivee)) / 86400;
}

// ─── Calculer le prix total ───
function prixTotal($arrivee, $depart, $prix_nuit) {
    return nombreNuits($arrivee, $depart) * $prix_nuit;
}

// ─── Badge HTML selon statut ───
function badgeStatut($statut) {
    $couleurs = [
        'en attente' => 'warning',
        'confirmée'  => 'success',
        'annulée'    => 'danger',
        'payé'       => 'success',
        'remboursé'  => 'info',
    ];
    $c = $couleurs[$statut] ?? 'secondary';
    return "<span class='badge bg-$c'>$statut</span>";
}

// ─── Récupérer les services d'une réservation ───
function getServicesReservation($pdo, $reservation_id) {
    $stmt = $pdo->prepare("
        SELECT s.* FROM services s
        INNER JOIN reservation_services rs ON s.id = rs.service_id
        WHERE rs.reservation_id = ?
    ");
    $stmt->execute([$reservation_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ─── Afficher les services en HTML ───
function afficherServices($services) {
    if (empty($services)) {
        return '<span class="text-muted">Aucun service</span>';
    }
    $html = '';
    foreach ($services as $service) {
        $html .= '<div class="badge bg-info me-1 mb-1">' . htmlspecialchars($service['nom']) . ' (+' . $service['prix'] . ' DT)</div>';
    }
    return $html;
}
?>