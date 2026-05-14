-- ============================================================
-- Vite & Gourmand — create.sql
-- Base de données : vite_gourmand
-- Interclassement : utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================
-- Tables de référence
-- ============================================================

CREATE TABLE role (
    role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regime (
    regime_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle   VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE theme (
    theme_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle  VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE allergene (
    allergene_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle      VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE horaire (
    horaire_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jour            VARCHAR(20) NOT NULL,
    heure_ouverture VARCHAR(5)  DEFAULT NULL,
    heure_fermeture VARCHAR(5)  DEFAULT NULL,
    ferme           TINYINT(1)  NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Utilisateurs
-- ============================================================

CREATE TABLE utilisateur (
    utilisateur_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email              VARCHAR(180) NOT NULL UNIQUE,
    password           VARCHAR(255) NOT NULL,
    nom                VARCHAR(50)  NOT NULL,
    prenom             VARCHAR(50)  NOT NULL,
    telephone          VARCHAR(20)  DEFAULT NULL,
    adresse            VARCHAR(255) DEFAULT NULL,
    code_postal        VARCHAR(10)  DEFAULT NULL,
    ville              VARCHAR(100) DEFAULT NULL,
    pays               VARCHAR(50)  NOT NULL DEFAULT 'France',
    role_id            INT UNSIGNED NOT NULL,
    actif              TINYINT(1)   NOT NULL DEFAULT 1,
    token_reset        VARCHAR(255) DEFAULT NULL,
    token_reset_expire DATETIME     DEFAULT NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id) REFERENCES role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Menus
-- ============================================================

CREATE TABLE menu (
    menu_id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre                   VARCHAR(100) NOT NULL,
    description             TEXT         DEFAULT NULL,
    nombre_personne_minimum INT UNSIGNED NOT NULL DEFAULT 1,
    prix_par_personne       DECIMAL(8,2) NOT NULL,
    conditions              TEXT         DEFAULT NULL,
    quantite_restante       INT UNSIGNED NOT NULL DEFAULT 0,
    theme_id                INT UNSIGNED NOT NULL,
    regime_id               INT UNSIGNED NOT NULL,
    actif                   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_theme  FOREIGN KEY (theme_id)  REFERENCES theme  (theme_id),
    CONSTRAINT fk_menu_regime FOREIGN KEY (regime_id) REFERENCES regime (regime_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_image (
    image_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id  INT UNSIGNED NOT NULL,
    chemin   VARCHAR(255) NOT NULL,
    ordre    INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_menu_image_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Plats
-- ============================================================

CREATE TABLE plat (
    plat_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    type        ENUM('entree','plat','dessert') NOT NULL,
    description TEXT         DEFAULT NULL,
    image       VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_plat (
    menu_id INT UNSIGNED NOT NULL,
    plat_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    CONSTRAINT fk_menu_plat_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_plat_plat FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat_allergene (
    plat_id      INT UNSIGNED NOT NULL,
    allergene_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    CONSTRAINT fk_plat_allergene_plat      FOREIGN KEY (plat_id)      REFERENCES plat      (plat_id)      ON DELETE CASCADE,
    CONSTRAINT fk_plat_allergene_allergene FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Commandes
-- ============================================================

CREATE TABLE commande (
    commande_id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_commande         VARCHAR(50)  NOT NULL UNIQUE,
    utilisateur_id          INT UNSIGNED NOT NULL,
    menu_id                 INT UNSIGNED NOT NULL,
    date_commande           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_prestation         DATE         NOT NULL,
    heure_livraison         VARCHAR(5)   NOT NULL,
    adresse_prestation      VARCHAR(255) NOT NULL,
    ville_prestation        VARCHAR(100) NOT NULL,
    code_postal_prestation  VARCHAR(10)  NOT NULL,
    nombre_personne         INT UNSIGNED NOT NULL,
    prix_menu               DECIMAL(8,2) NOT NULL,
    prix_livraison          DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    prix_total              DECIMAL(8,2) NOT NULL,
    statut                  ENUM('en_attente','accepte','en_preparation','en_cours_livraison','livre','attente_retour_materiel','terminee','annulee') NOT NULL DEFAULT 'en_attente',
    pret_materiel           TINYINT(1)   NOT NULL DEFAULT 0,
    motif_annulation        TEXT         DEFAULT NULL,
    mode_contact_annulation VARCHAR(10)  DEFAULT NULL,
    CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id),
    CONSTRAINT fk_commande_menu        FOREIGN KEY (menu_id)        REFERENCES menu        (menu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commande_historique (
    historique_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id   INT UNSIGNED NOT NULL,
    statut        VARCHAR(50)  NOT NULL,
    commentaire   TEXT         DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historique_commande FOREIGN KEY (commande_id) REFERENCES commande (commande_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Avis
-- ============================================================

CREATE TABLE avis (
    avis_id        INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED     NOT NULL,
    commande_id    INT UNSIGNED     NOT NULL UNIQUE,
    note           TINYINT UNSIGNED NOT NULL,
    commentaire    TEXT             DEFAULT NULL,
    statut         ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id),
    CONSTRAINT fk_avis_commande    FOREIGN KEY (commande_id)    REFERENCES commande    (commande_id),
    CONSTRAINT chk_avis_note       CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
