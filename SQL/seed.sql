-- ============================================================
-- Vite & Gourmand — seed.sql
-- Données de test
-- Mot de passe de tous les comptes de test : password
-- ============================================================

-- ============================================================
-- Rôles
-- ============================================================

INSERT INTO role (libelle) VALUES
('admin'),
('employe'),
('utilisateur');

-- ============================================================
-- Régimes
-- ============================================================

INSERT INTO regime (libelle) VALUES
('classique'),
('vegetarien'),
('vegan'),
('sans gluten');

-- ============================================================
-- Thèmes
-- ============================================================

INSERT INTO theme (libelle) VALUES
('classique'),
('noel'),
('paques'),
('evenement');

-- ============================================================
-- Allergènes (14 allergènes réglementaires EU)
-- ============================================================

INSERT INTO allergene (libelle) VALUES
('Gluten'),
('Crustacés'),
('Œufs'),
('Poisson'),
('Arachides'),
('Soja'),
('Lait'),
('Fruits à coque'),
('Céleri'),
('Moutarde'),
('Sésame'),
('Sulfites'),
('Lupin'),
('Mollusques');

-- ============================================================
-- Horaires
-- ============================================================

INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, ferme) VALUES
('Lundi',    '09:00', '19:00', 0),
('Mardi',    '09:00', '19:00', 0),
('Mercredi', '09:00', '19:00', 0),
('Jeudi',    '09:00', '19:00', 0),
('Vendredi', '09:00', '19:00', 0),
('Samedi',   '10:00', '17:00', 0),
('Dimanche', NULL,    NULL,    1);

-- ============================================================
-- Utilisateurs
-- hash bcrypt pour le mot de passe : password
-- ============================================================

INSERT INTO utilisateur (email, password, nom, prenom, telephone, adresse, code_postal, ville, pays, role_id) VALUES
('jose@vitegourmand.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martin',  'José',   '0612345678', '12 rue des Saveurs',   '33000', 'Bordeaux', 'France', 1),
('julie@vitegourmand.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dupont',  'Julie',  '0623456789', '8 avenue du Traiteur', '33000', 'Bordeaux', 'France', 2),
('thomas@example.fr',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bernard', 'Thomas', '0634567890', '5 rue de la Paix',     '33100', 'Bordeaux', 'France', 3),
('marie@example.fr',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Leroy',   'Marie',  '0645678901', '22 allée des Fleurs',  '33200', 'Bordeaux', 'France', 3),
('lucas@example.fr',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Moreau',  'Lucas',  '0656789012', '3 chemin du Moulin',   '64000', 'Pau',      'France', 3);

-- ============================================================
-- Menus
-- ============================================================

INSERT INTO menu (titre, description, nombre_personne_minimum, prix_par_personne, conditions, quantite_restante, theme_id, regime_id) VALUES
('Noël Tradition',       'Un menu raffiné pour célébrer Noël en famille, autour de saveurs festives et généreuses.',                  8,  35.00, 'À commander 7 jours minimum avant la prestation. Conservation au réfrigérateur obligatoire.',  5, 2, 1),
('Pâques Familial',      'Un repas convivial pour réunir toute la famille autour de spécialités de saison.',                          6,  28.00, 'À commander 5 jours minimum avant la prestation.',                                             8, 3, 1),
('Végétarien Printemps', 'Une sélection de plats végétariens frais et colorés, idéale pour les beaux jours.',                         4,  22.00, 'À commander 3 jours minimum avant la prestation. Produits frais de saison.',                   10, 1, 2),
('Prestige Événement',   'Un menu d\'exception pour sublimer vos événements professionnels ou personnels les plus importants.',        10, 55.00, 'À commander 14 jours minimum avant la prestation. Devis personnalisé possible sur demande.',    3, 4, 1),
('Vegan Été',            'Des saveurs estivales 100% végétales pour un repas léger et respectueux de l\'environnement.',               4,  25.00, 'À commander 3 jours minimum avant la prestation.',                                             7, 1, 3),
('Bordeaux Classique',   'Le meilleur de la gastronomie bordelaise : des produits locaux sélectionnés avec soin par José et Julie.',  6,  32.00, 'À commander 5 jours minimum avant la prestation. Accord mets-vins disponible sur demande.',    6, 1, 1);

-- ============================================================
-- Images des menus
-- ============================================================

INSERT INTO menu_image (menu_id, chemin, ordre) VALUES
(1, 'assets/menus/Menu1.PNG', 1),
(2, 'assets/menus/Menu2.PNG', 1),
(3, 'assets/menus/Menu3.PNG', 1),
(4, 'assets/menus/Menu4.PNG', 1),
(5, 'assets/menus/Menu5.PNG', 1),
(6, 'assets/menus/Menu6.PNG', 1);

-- ============================================================
-- Plats
-- ============================================================

INSERT INTO plat (nom, type, description) VALUES
('Foie gras maison',           'entree',  'Foie gras de canard mi-cuit, chutney de figues et toast brioché.'),
('Velouté de potiron',         'entree',  'Velouté onctueux de potiron, crème fraîche et noisettes torréfiées.'),
('Salade de chèvre chaud',     'entree',  'Mesclun, crottin de chèvre chaud, noix et vinaigrette au miel.'),
('Tartare de saumon',          'entree',  'Saumon frais mariné, avocat, citron vert et aneth.'),
('Assiette de crudités vegan', 'entree',  'Assortiment de légumes croquants de saison avec houmous maison.'),
('Magret de canard aux cèpes', 'plat',    'Magret de canard rôti, sauce aux cèpes et gratin dauphinois.'),
('Filet de bœuf Wellington',   'plat',    'Filet de bœuf en croûte, duxelles de champignons et sauce Périgueux.'),
('Risotto aux champignons',    'plat',    'Risotto crémeux aux champignons sauvages, parmesan et truffe.'),
('Pavé de saumon en croûte',   'plat',    'Pavé de saumon en croûte d\'herbes, beurre blanc et légumes vapeur.'),
('Tajine de légumes vegan',    'plat',    'Tajine de légumes du soleil aux épices douces, couscous et raisins secs.'),
('Bûche de Noël au chocolat',  'dessert', 'Bûche maison ganache chocolat noir, éclats de noisettes caramélisées.'),
('Tarte Tatin',                'dessert', 'Tarte Tatin aux pommes caramélisées, crème fraîche épaisse.'),
('Mousse au chocolat vegan',   'dessert', 'Mousse aérienne au chocolat noir 70%, sans produit d\'origine animale.'),
('Crème brûlée à la vanille',  'dessert', 'Crème brûlée traditionnelle à la vanille de Madagascar.'),
('Salade de fruits frais',     'dessert', 'Assortiment de fruits de saison, menthe fraîche et sirop de fleur d\'oranger.');

-- ============================================================
-- Associations menu <-> plat
-- ============================================================

INSERT INTO menu_plat (menu_id, plat_id) VALUES
(1, 1), (1, 6),  (1, 11),
(2, 2), (2, 7),  (2, 12),
(3, 3), (3, 8),  (3, 14),
(4, 1), (4, 7),  (4, 14),
(5, 5), (5, 10), (5, 13),
(6, 4), (6, 6),  (6, 12);

-- ============================================================
-- Associations plat <-> allergène
-- ============================================================

-- Foie gras (1) : gluten, sulfites
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (1,1), (1,12);
-- Velouté de potiron (2) : lait, fruits à coque
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (2,7), (2,8);
-- Salade de chèvre chaud (3) : lait, fruits à coque
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (3,7), (3,8);
-- Tartare de saumon (4) : poisson
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (4,4);
-- Assiette crudités vegan (5) : sésame
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (5,11);
-- Magret de canard (6) : gluten, lait
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (6,1), (6,7);
-- Filet de bœuf Wellington (7) : gluten, œufs, lait
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (7,1), (7,3), (7,7);
-- Risotto aux champignons (8) : lait
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (8,7);
-- Pavé de saumon (9) : poisson, lait, gluten
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (9,4), (9,7), (9,1);
-- Tajine de légumes vegan (10) : aucun allergène majeur
-- Bûche de Noël (11) : gluten, œufs, lait, fruits à coque
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (11,1), (11,3), (11,7), (11,8);
-- Tarte Tatin (12) : gluten, œufs, lait
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (12,1), (12,3), (12,7);
-- Mousse chocolat vegan (13) : aucun allergène majeur
-- Crème brûlée (14) : œufs, lait
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (14,3), (14,7);
-- Salade de fruits frais (15) : aucun allergène majeur

-- ============================================================
-- Commandes
-- Livraison hors Bordeaux : 5€ + 0,59€/km (Pau ≈ 100km)
-- Réduction 10% si nombre_personne >= minimum + 5
-- ============================================================

INSERT INTO commande (numero_commande, utilisateur_id, menu_id, date_prestation, heure_livraison, adresse_prestation, ville_prestation, code_postal_prestation, nombre_personne, prix_menu, prix_livraison, prix_total, statut, pret_materiel) VALUES
('VG-2026-001', 3, 1, '2026-12-24', '12:00', '5 rue de la Paix',    'Bordeaux', '33100', 10, 350.00,  0.00,  350.00, 'terminee',   0),
('VG-2026-002', 4, 2, '2026-04-20', '13:00', '22 allée des Fleurs', 'Bordeaux', '33200',  6, 168.00,  0.00,  168.00, 'en_attente', 0),
('VG-2026-003', 5, 3, '2026-06-15', '12:30', '3 chemin du Moulin',  'Pau',      '64000',  4,  88.00, 64.00,  152.00, 'accepte',    0);

-- ============================================================
-- Historique des statuts de commande
-- ============================================================

INSERT INTO commande_historique (commande_id, statut, created_at) VALUES
(1, 'en_attente',         '2026-11-01 10:00:00'),
(1, 'accepte',            '2026-11-02 09:30:00'),
(1, 'en_preparation',     '2026-12-23 08:00:00'),
(1, 'en_cours_livraison', '2026-12-24 10:00:00'),
(1, 'livre',              '2026-12-24 12:15:00'),
(1, 'terminee',           '2026-12-24 12:15:00'),
(2, 'en_attente',         '2026-03-15 14:00:00'),
(3, 'en_attente',         '2026-05-01 11:00:00'),
(3, 'accepte',            '2026-05-02 09:00:00');

-- ============================================================
-- Avis
-- ============================================================

INSERT INTO avis (utilisateur_id, commande_id, note, commentaire, statut) VALUES
(3, 1, 5, 'Excellent repas de Noël ! Le foie gras était sublime et le magret parfaitement cuit. Je recommande vivement.', 'valide');
