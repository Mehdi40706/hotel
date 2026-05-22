-- ═══════════════════════════════════════════════════════════════
-- Schéma de Base de Données - Système de Gestion d'Hôtel
-- 5 entités principales: users, chambres, reservations, paiements, services
-- ═══════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- TABLE 1: USERS (Clients et Administrateurs)
-- ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ─────────────────────────────────────────────────────────────
-- TABLE 2: CHAMBRES (Inventaire des chambres)
-- ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS chambres;
CREATE TABLE chambres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero INT UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    prix_nuit DECIMAL(8,2) NOT NULL,
    capacite INT NOT NULL,
    description TEXT,
    photo VARCHAR(255) NOT NULL,
    disponible BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disponible (disponible),
    INDEX idx_type (type)
);

-- ─────────────────────────────────────────────────────────────
-- TABLE 3: RESERVATIONS (Réservations de chambres)
-- ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS reservations;
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    chambre_id INT NOT NULL,
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL,
    statut ENUM('en attente','confirmée','annulée') DEFAULT 'en attente',
    montant_total DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chambre_id) REFERENCES chambres(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_chambre_id (chambre_id),
    INDEX idx_statut (statut),
    INDEX idx_dates (date_arrivee, date_depart)
);

-- ─────────────────────────────────────────────────────────────
-- TABLE 4: PAIEMENTS (Suivi des paiements)
-- ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS paiements;
CREATE TABLE paiements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    methode VARCHAR(50) NOT NULL,
    statut ENUM('en attente','payé','remboursé') DEFAULT 'en attente',
    date_paiement DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_statut (statut),
    INDEX idx_date_paiement (date_paiement)
);

-- ─────────────────────────────────────────────────────────────
-- TABLE 5: SERVICES (Services supplémentaires: spa, restaurant, etc.)
-- ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS services;
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(8,2) NOT NULL,
    photo VARCHAR(255) NOT NULL,
    disponible BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disponible (disponible)
);

-- ─────────────────────────────────────────────────────────────
-- TABLE 6: RESERVATION_SERVICES (Lier services aux réservations)
-- ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS reservation_services;
CREATE TABLE reservation_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    service_id INT NOT NULL,
    quantite INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_reservation_service (reservation_id, service_id),
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_service_id (service_id)
);

-- ═══════════════════════════════════════════════════════════════
-- DONNÉES D'EXEMPLE
-- ═══════════════════════════════════════════════════════════════

-- Ajouter un compte admin de test
INSERT INTO users (nom, prenom, email, mot_de_passe, role)
VALUES ('Admin', 'Test', 'admin@hotel.test', '$2y$10$8K1p/weTIy0PlqVrQc8qde52EHzyMZWQbUfkXSKU6ioMqnnvnHl/e', 'admin');
-- Mot de passe: admin123

-- Ajouter des exemples de chambres
INSERT INTO chambres (numero, type, prix_nuit, capacite, description, photo)
VALUES 
(101, 'Chambre Simple', 80.00, 1, 'Chambre confortable pour 1 personne', 'chambre_simple.jpg'),
(102, 'Chambre Double', 120.00, 2, 'Chambre spacieuse pour 2 personnes', 'chambre_double.jpg'),
(201, 'Suite Deluxe', 200.00, 4, 'Suite luxueuse avec vue sur la ville', 'suite_deluxe.jpg');

-- Ajouter des exemples de services
INSERT INTO services (nom, description, prix, photo)
VALUES 
('Spa', 'Accès au spa complet avec massages', 50.00, 'spa.jpg'),
('Restaurant', 'Menu du restaurant 3 étoiles', 35.00, 'restaurant.jpg'),
('Parking', 'Parking privé sécurisé', 15.00, 'parking.jpg');

-- ═══════════════════════════════════════════════════════════════
-- FIN DU SCHÉMA
-- ═══════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 1;
