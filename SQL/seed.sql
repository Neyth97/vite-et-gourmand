-- ============================================================
-- Vite & Gourmand — seed.sql
-- Données de test
-- Mot de passe de tous les comptes de test : password
-- Peut être rejoué sur une base existante
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM avis;
DELETE FROM commande_historique;
DELETE FROM commande;
DELETE FROM plat_allergene;
DELETE FROM menu_plat;
DELETE FROM plat;
DELETE FROM menu_image;
DELETE FROM menu;
DELETE FROM utilisateur;
DELETE FROM horaire;
DELETE FROM allergene;
DELETE FROM theme;
DELETE FROM regime;
DELETE FROM role;
ALTER TABLE avis               AUTO_INCREMENT = 1;
ALTER TABLE commande_historique AUTO_INCREMENT = 1;
ALTER TABLE commande           AUTO_INCREMENT = 1;
ALTER TABLE plat               AUTO_INCREMENT = 1;
ALTER TABLE menu_image         AUTO_INCREMENT = 1;
ALTER TABLE menu               AUTO_INCREMENT = 1;
ALTER TABLE utilisateur        AUTO_INCREMENT = 1;
ALTER TABLE horaire            AUTO_INCREMENT = 1;
ALTER TABLE allergene          AUTO_INCREMENT = 1;
ALTER TABLE theme              AUTO_INCREMENT = 1;
ALTER TABLE regime             AUTO_INCREMENT = 1;
ALTER TABLE role               AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

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
-- theme_id : 1=classique  2=noel  3=paques  4=evenement
-- regime_id : 1=classique  2=vegetarien  3=vegan  4=sans gluten
-- ============================================================

INSERT INTO menu (titre, description, nombre_personne_minimum, prix_par_personne, conditions, quantite_restante, theme_id, regime_id) VALUES

('Classique Prestige',
 'Un repas raffiné autour des produits du terroir bordelais. Ce menu met à l\'honneur les savoir-faire de Julie et José : entrées délicates, plat généreux et dessert maison. Idéal pour vos repas d\'affaires ou réunions de famille.',
 10, 12.50,
 'À commander minimum 48h avant la prestation. Conserver entre 0 °C et 4 °C jusqu\'au service. Minimum 10 personnes obligatoire.',
 8, 1, 1),

('Noël Gourmand',
 'Célébrez les fêtes avec élégance et générosité. Foie gras maison, chapon rôti aux marrons et bûche artisanale : un repas festif pour un réveillon inoubliable.',
 8, 35.00,
 'À commander minimum 2 semaines avant la prestation. Conserver entre 0 °C et 4 °C jusqu\'au service. Stock limité à 3 commandes pour cette saison.',
 3, 2, 1),

('Pâques en Famille',
 'Un déjeuner de Pâques généreux et ensoleillé pour rassembler toute la famille autour d\'une belle table printanière, avec agneau de lait et légumes primeurs de saison.',
 6, 26.67,
 'À commander minimum 1 semaine avant la prestation. Conserver entre 0 °C et 4 °C jusqu\'au service.',
 12, 3, 1),

('Cocktail Événement',
 'Cocktail dînatoire haut de gamme en verrines et pièces cocktail pour vos réceptions professionnelles, galas ou événements privés. Présentation soignée, saveurs raffinées.',
 20, 18.00,
 'À commander minimum 3 semaines avant la prestation. Conserver entre 0 °C et 4 °C jusqu\'au service. Un supplément livraison s\'applique hors Bordeaux (5 € + 0,59 €/km).',
 5, 4, 1),

('Végétarien Festif',
 'Une cuisine végétarienne créative et savoureuse : légumes rôtis, risotto aux champignons et fondant au chocolat. Idéal pour tout événement souhaitant une option inclusive et raffinée.',
 10, 19.00,
 'À commander minimum 72h avant la prestation. Conserver entre 0 °C et 4 °C jusqu\'au service. Stock très limité.',
 2, 4, 2),

('Vegan Équilibré',
 '100 % végétal, 100 % savoureux. Des produits frais, des associations de saveurs soignées et une présentation élégante. Ce menu entièrement vegan prouve que la cuisine végétale peut être festive et gastronomique.',
 8, 18.75,
 'À commander minimum 48h avant la prestation. Conserver entre 0 °C et 4 °C jusqu\'au service.',
 10, 1, 3);

-- ============================================================
-- Images des menus
-- ordre 1 = image de la carte (menus.php), 2-4 = galerie (menu-detail.php)
-- ============================================================

INSERT INTO menu_image (menu_id, chemin, ordre) VALUES
-- Classique Prestige
(1, 'assets/menus/Menu1.PNG',                                          1),
(1, 'assets/menus details/classique prestige/velouté potimarron.PNG',  2),
(1, 'assets/menus details/classique prestige/magret de canard.PNG',    3),
(1, 'assets/menus details/classique prestige/tarte fines.PNG',         4),
-- Noël Gourmand
(2, 'assets/menus/Menu2.PNG',                                          1),
(2, 'assets/menus details/noel gourmand/foie gras.PNG',                2),
(2, 'assets/menus details/noel gourmand/chapon roti.PNG',              3),
(2, 'assets/menus details/noel gourmand/buche.PNG',                    4),
-- Pâques en Famille
(3, 'assets/menus/Menu3.PNG',                                          1),
(3, 'assets/menus details/paques en famille/asperge.PNG',              2),
(3, 'assets/menus details/paques en famille/gigot.PNG',                3),
(3, 'assets/menus details/paques en famille/charlotte.PNG',            4),
-- Cocktail Événement
(4, 'assets/menus/Menu4.PNG',                                          1),
(4, 'assets/menus details/cocktails evenements/verrines.PNG',          2),
(4, 'assets/menus details/cocktails evenements/pièces salées.PNG',     3),
(4, 'assets/menus details/cocktails evenements/mignardises.PNG',       4),
-- Végétarien Festif
(5, 'assets/menus/Menu5.PNG',                                          1),
(5, 'assets/menus details/vegetarien festif/tartare betterave.PNG',    2),
(5, 'assets/menus details/vegetarien festif/risotto.PNG',              3),
(5, 'assets/menus details/vegetarien festif/fondant.PNG',              4),
-- Vegan Équilibré
(6, 'assets/menus/Menu6.PNG',                                          1),
(6, 'assets/menus details/vegan équilibré/gaspacho.PNG',               2),
(6, 'assets/menus details/vegan équilibré/curry.PNG',                  3),
(6, 'assets/menus details/vegan équilibré/pannacotta.PNG',             4);

-- ============================================================
-- Plats
-- ============================================================

INSERT INTO plat (nom, type, description, image) VALUES

-- Entrées (plat_id 1 à 6)
('Velouté de potimarron & noisettes torréfiées',
 'entree',
 'Velouté onctueux de potimarron du Médoc, agrémenté de noisettes torréfiées et d\'un filet d\'huile de truffe blanche. Servi chaud ou froid selon la saison.',
 'assets/menus details/classique prestige/velouté potimarron.PNG'),

('Foie gras mi-cuit & chutney de figues au porto',
 'entree',
 'Foie gras de canard mi-cuit maison, servi sur toast brioché légèrement grillé, accompagné d\'un chutney de figues au porto et d\'une fleur de sel de Guérande.',
 'assets/menus details/noel gourmand/foie gras.PNG'),

('Asperges blanches & sauce mousseline aux herbes',
 'entree',
 'Asperges blanches de la région, cuites à la vapeur et servies tièdes, accompagnées d\'une sauce mousseline légère aux herbes fraîches — estragon, ciboulette et cerfeuil.',
 'assets/menus details/paques en famille/asperge.PNG'),

('Assortiment de verrines gastronomiques',
 'entree',
 'Sélection de verrines raffinées : verrine avocat-crevette au citron vert, mousse de saumon fumé & fromage frais aux herbes, et gaspacho de tomates anciennes au basilic.',
 'assets/menus details/cocktails evenements/verrines.PNG'),

('Tartare de betterave & chèvre frais aux noix',
 'entree',
 'Tartare de betteraves marinées à l\'huile d\'olive et au vinaigre balsamique, surmonté d\'une quenelle de chèvre frais aux herbes et parsemé de cerneaux de noix torréfiés.',
 'assets/menus details/vegetarien festif/tartare betterave.PNG'),

('Gaspacho de tomates anciennes & basilic frais',
 'entree',
 'Gaspacho onctueux de tomates anciennes mixé avec concombre, poivron rouge et basilic frais. Servi frais avec une brunoise de légumes croquants et un filet d\'huile d\'olive.',
 'assets/menus details/vegan équilibré/gaspacho.PNG'),

-- Plats (plat_id 7 à 12)
('Magret de canard rôti, jus au merlot & pommes sarladaises',
 'plat',
 'Magret de canard fermier des Landes, rôti à la perfection, accompagné d\'un jus réduit au merlot bordelais et de pommes sarladaises fondantes à l\'ail et au persil.',
 'assets/menus details/classique prestige/magret de canard.PNG'),

('Chapon rôti aux marrons & jus à la truffe noire',
 'plat',
 'Chapon fermier rôti à basse température, farci aux marrons et aux herbes fraîches, accompagné d\'un jus corsé à la truffe noire du Périgord et d\'une purée de céleri rave.',
 'assets/menus details/noel gourmand/chapon roti.PNG'),

('Gigot d\'agneau de lait rôti & légumes de saison',
 'plat',
 'Gigot d\'agneau de lait du Quercy rôti à l\'ail et au romarin, accompagné de légumes printaniers rôtis — carottes fanes, navets nouveaux, petits pois — et d\'un jus court lié au fond d\'agneau.',
 'assets/menus details/paques en famille/gigot.PNG'),

('Assortiment de pièces cocktail salées',
 'plat',
 'Mini-brochettes de bœuf au poivre vert, feuilletés au chèvre & miel, blinis au saumon & crème fraîche, et mini-burgers de canard confit. Préparés et dressés le jour même.',
 'assets/menus details/cocktails evenements/pièces salées.PNG'),

('Risotto aux champignons des bois & parmesan affiné',
 'plat',
 'Risotto crémeux aux champignons des bois — cèpes, girolles et pleurotes — mantecato au beurre et au parmesan 24 mois, avec des copeaux de truffe noire en finition.',
 'assets/menus details/vegetarien festif/risotto.PNG'),

('Curry de légumes & pois chiches au lait de coco',
 'plat',
 'Curry parfumé de légumes de saison et pois chiches mijotés dans une sauce au lait de coco et aux épices douces — curcuma, gingembre, coriandre. Servi avec un riz basmati à la citronnelle.',
 'assets/menus details/vegan équilibré/curry.PNG'),

-- Desserts (plat_id 13 à 18)
('Tarte fine aux pommes & caramel beurre salé',
 'dessert',
 'Tarte fine maison aux pommes Golden caramélisées, sur pâte feuilletée pur beurre, nappée d\'un caramel beurre salé artisanal et accompagnée d\'une quenelle de glace vanille.',
 'assets/menus details/classique prestige/tarte fines.PNG'),

('Bûche artisanale chocolat & framboise',
 'dessert',
 'Bûche de Noël entièrement faite maison : biscuit joconde au chocolat noir, mousse framboise légère et glaçage miroir. Décorée à la main pour une touche festive.',
 'assets/menus details/noel gourmand/buche.PNG'),

('Charlotte aux fraises & coulis de fruits rouges',
 'dessert',
 'Charlotte légère aux fraises de saison, sur biscuits à la cuillère imbibés de sirop de framboise, avec une mousse bavaroise vanille. Servie avec un coulis de fruits rouges frais.',
 'assets/menus details/paques en famille/charlotte.PNG'),

('Plateau de mignardises sucrées',
 'dessert',
 'Macarons assortis (framboise, chocolat, pistache), mini-tartelettes aux fruits de saison, truffes au chocolat noir et guimauves artisanales. Un plateau généreux pour clôturer le cocktail.',
 'assets/menus details/cocktails evenements/mignardises.PNG'),

('Fondant au chocolat noir & crème anglaise vanille',
 'dessert',
 'Fondant au chocolat noir 70 % au cœur coulant, servi tiède avec une crème anglaise à la vanille de Madagascar et une quenelle de glace vanille.',
 'assets/menus details/vegetarien festif/fondant.PNG'),

('Panna cotta coco & fruits de la passion',
 'dessert',
 'Panna cotta végétale au lait de coco et à l\'agar-agar, servie avec un coulis de fruits de la passion frais et quelques suprêmes de mangue. Un dessert léger et exotique.',
 'assets/menus details/vegan équilibré/pannacotta.PNG');

-- ============================================================
-- Associations menu <-> plat
-- ============================================================

INSERT INTO menu_plat (menu_id, plat_id) VALUES
(1,  1), (1,  7), (1, 13),   -- Classique Prestige  : velouté, magret, tarte fine
(2,  2), (2,  8), (2, 14),   -- Noël Gourmand       : foie gras, chapon, bûche
(3,  3), (3,  9), (3, 15),   -- Pâques en Famille   : asperges, gigot, charlotte
(4,  4), (4, 10), (4, 16),   -- Cocktail Événement  : verrines, pièces salées, mignardises
(5,  5), (5, 11), (5, 17),   -- Végétarien Festif   : tartare betterave, risotto, fondant
(6,  6), (6, 12), (6, 18);   -- Vegan Équilibré     : gaspacho, curry, panna cotta

-- ============================================================
-- Associations plat <-> allergène
-- ============================================================

-- plat 1 — Velouté de potimarron : Lait(7), Fruits à coque(8)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (1,7), (1,8);
-- plat 2 — Foie gras mi-cuit : Gluten(1), Sulfites(12)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (2,1), (2,12);
-- plat 3 — Asperges mousseline : Œufs(3), Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (3,3), (3,7);
-- plat 4 — Verrines gastronomiques : Crustacés(2), Poisson(4), Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (4,2), (4,4), (4,7);
-- plat 5 — Tartare de betterave : Gluten(1), Lait(7), Fruits à coque(8)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (5,1), (5,7), (5,8);
-- plat 6 — Gaspacho : aucun allergène majeur
-- plat 7 — Magret de canard : Sulfites(12)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (7,12);
-- plat 8 — Chapon rôti : Céleri(9)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (8,9);
-- plat 9 — Gigot d'agneau : aucun allergène majeur
-- plat 10 — Pièces cocktail salées : Gluten(1), Œufs(3), Poisson(4), Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (10,1), (10,3), (10,4), (10,7);
-- plat 11 — Risotto : Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (11,7);
-- plat 12 — Curry de légumes : aucun allergène majeur
-- plat 13 — Tarte fine : Gluten(1), Œufs(3), Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (13,1), (13,3), (13,7);
-- plat 14 — Bûche chocolat framboise : Gluten(1), Œufs(3), Lait(7), Fruits à coque(8)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (14,1), (14,3), (14,7), (14,8);
-- plat 15 — Charlotte aux fraises : Gluten(1), Œufs(3), Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (15,1), (15,3), (15,7);
-- plat 16 — Mignardises sucrées : Gluten(1), Œufs(3), Lait(7), Fruits à coque(8)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (16,1), (16,3), (16,7), (16,8);
-- plat 17 — Fondant chocolat : Gluten(1), Œufs(3), Lait(7)
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (17,1), (17,3), (17,7);
-- plat 18 — Panna cotta coco : aucun allergène majeur

-- ============================================================
-- Commandes
-- Réduction 10% si nombre_personne >= minimum + 5
-- Livraison hors Bordeaux : 5 € + 0,59 €/km
-- ============================================================

INSERT INTO commande (numero_commande, utilisateur_id, menu_id, date_prestation, heure_livraison, adresse_prestation, ville_prestation, code_postal_prestation, nombre_personne, prix_menu, prix_livraison, prix_total, statut, pret_materiel) VALUES
('VG-2026-001', 3, 1, '2026-06-14', '12:00', '5 rue de la Paix',    'Bordeaux', '33100', 10, 125.00,  0.00, 125.00, 'terminee',   0),
('VG-2026-002', 4, 2, '2026-12-24', '12:00', '22 allée des Fleurs', 'Bordeaux', '33200',  8, 280.00,  0.00, 280.00, 'en_attente', 0),
('VG-2026-003', 5, 3, '2026-04-05', '13:00', '3 chemin du Moulin',  'Pau',      '64000',  6, 160.00, 64.00, 224.00, 'accepte',    0);

-- ============================================================
-- Historique des statuts de commande
-- ============================================================

INSERT INTO commande_historique (commande_id, statut, created_at) VALUES
(1, 'en_attente',         '2026-05-01 10:00:00'),
(1, 'accepte',            '2026-05-02 09:30:00'),
(1, 'en_preparation',     '2026-06-13 08:00:00'),
(1, 'en_cours_livraison', '2026-06-14 10:00:00'),
(1, 'livre',              '2026-06-14 12:10:00'),
(1, 'terminee',           '2026-06-14 12:10:00'),
(2, 'en_attente',         '2026-11-15 14:00:00'),
(3, 'en_attente',         '2026-03-01 11:00:00'),
(3, 'accepte',            '2026-03-02 09:00:00');

-- ============================================================
-- Avis
-- ============================================================

INSERT INTO avis (utilisateur_id, commande_id, note, commentaire, statut) VALUES
(3, 1, 5, 'Excellent repas ! Le velouté de potimarron était onctueux à souhait et le magret de canard parfaitement cuit. Une table d\'exception, je recommande vivement Vite & Gourmand.', 'valide');
