<?php
require_once '../../PHP/includes/session.php';
require_once '../../PHP/config/db.php';
require_once '../../PHP/includes/mailer.php';

function uploadMenuImage(): ?string {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES['image'];
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'], true)) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) return null;
    $filename = uniqid('menu_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../../assets/menus/' . $filename)) return null;
    return 'assets/menus/' . $filename;
}

function uploadPlatImage(): ?string {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES['image'];
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'], true)) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) return null;
    $dir = __DIR__ . '/../../assets/plats';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = uniqid('plat_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) return null;
    return 'assets/plats/' . $filename;
}

requireConnexion();
if (!isAdmin()) {
    header('Location: /vite-et-gourmand/HTML/connexion.php');
    exit;
}

$pdo = getPDO();

$flash_ok  = $_SESSION['flash_ok']  ?? null; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? null; unset($_SESSION['flash_err']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// POST handlers
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_err'] = 'Requête invalide.';
        header('Location: index.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // --- Changer statut ---
    if ($action === 'changer_statut') {
        $cid    = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
        $statut = trim($_POST['statut'] ?? '');
        $allowed = ['accepte','en_preparation','en_cours_livraison','livre','attente_retour_materiel','terminee'];

        if ($cid && in_array($statut, $allowed, true)) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE commande SET statut = ? WHERE commande_id = ?')->execute([$statut, $cid]);
            $pdo->prepare('INSERT INTO commande_historique (commande_id, statut) VALUES (?, ?)')->execute([$cid, $statut]);
            $pdo->commit();

            if (in_array($statut, ['terminee', 'attente_retour_materiel'], true)) {
                $s = $pdo->prepare(
                    'SELECT c.numero_commande, u.email, u.prenom, u.nom, c.commande_id
                     FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
                     WHERE c.commande_id = ?'
                );
                $s->execute([$cid]);
                $row = $s->fetch();
                if ($row) {
                    if ($statut === 'terminee') {
                        mailCommandeTerminee($row['email'], $row['prenom'], $row['nom'], $row['numero_commande'], (int)$row['commande_id']);
                    } else {
                        mailRetourMateriel($row['email'], $row['prenom'], $row['nom'], $row['numero_commande']);
                    }
                }
            }

            $_SESSION['flash_ok'] = 'Statut mis à jour.';
        } else {
            $_SESSION['flash_err'] = 'Données invalides.';
        }
        header('Location: index.php?section=commandes&statut=' . urlencode($_POST['filtre_statut'] ?? '') . '&client=' . urlencode($_POST['filtre_client'] ?? ''));
        exit;
    }

    // --- Annuler commande ---
    if ($action === 'annuler_commande') {
        $cid   = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
        $motif = trim($_POST['motif'] ?? '');
        $mode  = trim($_POST['mode_contact'] ?? '');

        $err = [];
        if (!$cid)                                  $err[] = 'Commande invalide.';
        if (empty($motif))                           $err[] = 'Le motif est requis.';
        if (!in_array($mode, ['gsm','mail'], true))  $err[] = 'Mode de contact requis.';

        if (!$err) {
            $stmt = $pdo->prepare('SELECT commande_id FROM commande WHERE commande_id = ? AND statut NOT IN ("terminee","annulee")');
            $stmt->execute([$cid]);
            if (!$stmt->fetch()) $err[] = 'Cette commande ne peut pas être annulée.';
        }

        if ($err) {
            $_SESSION['flash_err'] = implode(' ', $err);
            header('Location: index.php?section=commandes');
            exit;
        }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE commande SET statut="annulee", motif_annulation=?, mode_contact_annulation=? WHERE commande_id=?')
            ->execute([$motif, $mode, $cid]);
        $pdo->prepare('INSERT INTO commande_historique (commande_id, statut, commentaire) VALUES (?, "annulee", ?)')
            ->execute([$cid, $motif]);
        $pdo->commit();

        $_SESSION['flash_ok'] = 'Commande annulée.';
        header('Location: index.php?section=commandes');
        exit;
    }

    // --- Ajouter menu ---
    if ($action === 'ajouter_menu') {
        $titre      = trim($_POST['titre'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $theme_id   = filter_input(INPUT_POST, 'theme_id', FILTER_VALIDATE_INT);
        $regime_id  = filter_input(INPUT_POST, 'regime_id', FILTER_VALIDATE_INT);
        $nb_min     = filter_input(INPUT_POST, 'nombre_personne_minimum', FILTER_VALIDATE_INT);
        $prix       = filter_input(INPUT_POST, 'prix_par_personne', FILTER_VALIDATE_FLOAT);
        $conditions = trim($_POST['conditions'] ?? '');
        $stock      = filter_input(INPUT_POST, 'quantite_restante', FILTER_VALIDATE_INT) ?: 0;

        $err = [];
        if (empty($titre))                $err[] = 'Titre requis.';
        if (!$theme_id)                   $err[] = 'Thème requis.';
        if (!$regime_id)                  $err[] = 'Régime requis.';
        if (!$nb_min || $nb_min < 1)      $err[] = 'Nombre de personnes minimum invalide.';
        if ($prix === false || $prix < 0) $err[] = 'Prix invalide.';

        if ($err) { $_SESSION['flash_err'] = implode(' ', $err); header('Location: index.php?section=menus'); exit; }

        $pdo->prepare(
            'INSERT INTO menu (titre, description, theme_id, regime_id, nombre_personne_minimum, prix_par_personne, conditions, quantite_restante)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$titre, $desc ?: null, $theme_id, $regime_id, $nb_min, $prix, $conditions ?: null, $stock]);

        $new_menu_id = (int)$pdo->lastInsertId();
        $img_menu = uploadMenuImage();
        if ($img_menu) {
            $pdo->prepare('INSERT INTO menu_image (menu_id, chemin, ordre) VALUES (?, ?, 1)')->execute([$new_menu_id, $img_menu]);
        }

        $_SESSION['flash_ok'] = 'Menu ajouté.';
        header('Location: index.php?section=menus');
        exit;
    }

    // --- Modifier menu ---
    if ($action === 'modifier_menu') {
        $mid        = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
        $titre      = trim($_POST['titre'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $theme_id   = filter_input(INPUT_POST, 'theme_id', FILTER_VALIDATE_INT);
        $regime_id  = filter_input(INPUT_POST, 'regime_id', FILTER_VALIDATE_INT);
        $nb_min     = filter_input(INPUT_POST, 'nombre_personne_minimum', FILTER_VALIDATE_INT);
        $prix       = filter_input(INPUT_POST, 'prix_par_personne', FILTER_VALIDATE_FLOAT);
        $conditions = trim($_POST['conditions'] ?? '');
        $stock      = filter_input(INPUT_POST, 'quantite_restante', FILTER_VALIDATE_INT) ?: 0;
        $actif      = isset($_POST['actif']) ? 1 : 0;

        $err = [];
        if (!$mid)                        $err[] = 'Menu invalide.';
        if (empty($titre))                $err[] = 'Titre requis.';
        if (!$theme_id)                   $err[] = 'Thème requis.';
        if (!$regime_id)                  $err[] = 'Régime requis.';
        if (!$nb_min || $nb_min < 1)      $err[] = 'Nombre de personnes minimum invalide.';
        if ($prix === false || $prix < 0) $err[] = 'Prix invalide.';

        if ($err) { $_SESSION['flash_err'] = implode(' ', $err); header('Location: index.php?section=menus'); exit; }

        $pdo->prepare(
            'UPDATE menu SET titre=?, description=?, theme_id=?, regime_id=?,
             nombre_personne_minimum=?, prix_par_personne=?, conditions=?, quantite_restante=?, actif=?
             WHERE menu_id=?'
        )->execute([$titre, $desc ?: null, $theme_id, $regime_id, $nb_min, $prix, $conditions ?: null, $stock, $actif, $mid]);

        $img_menu = uploadMenuImage();
        if ($img_menu) {
            $existing = $pdo->prepare('SELECT image_id FROM menu_image WHERE menu_id = ? ORDER BY ordre ASC LIMIT 1');
            $existing->execute([$mid]);
            $existing_row = $existing->fetch();
            if ($existing_row) {
                $pdo->prepare('UPDATE menu_image SET chemin = ? WHERE image_id = ?')->execute([$img_menu, $existing_row['image_id']]);
            } else {
                $pdo->prepare('INSERT INTO menu_image (menu_id, chemin, ordre) VALUES (?, ?, 1)')->execute([$mid, $img_menu]);
            }
        }

        $_SESSION['flash_ok'] = 'Menu modifié.';
        header('Location: index.php?section=menus');
        exit;
    }

    // --- Supprimer menu ---
    if ($action === 'supprimer_menu') {
        $mid = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
        if ($mid) {
            $pdo->prepare('UPDATE menu SET actif = 0 WHERE menu_id = ?')->execute([$mid]);
            $_SESSION['flash_ok'] = 'Menu désactivé.';
        }
        header('Location: index.php?section=menus');
        exit;
    }

    // --- Ajouter plat ---
    if ($action === 'ajouter_plat') {
        $nom  = trim($_POST['nom'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $aids = array_filter(array_map('intval', (array)($_POST['allergene_ids'] ?? [])));

        $err = [];
        if (empty($nom))                                   $err[] = 'Nom requis.';
        if (!in_array($type, ['entree','plat','dessert'])) $err[] = 'Type invalide.';

        if ($err) { $_SESSION['flash_err'] = implode(' ', $err); header('Location: index.php?section=menus'); exit; }

        $pdo->prepare('INSERT INTO plat (nom, type, description) VALUES (?, ?, ?)')->execute([$nom, $type, $desc ?: null]);
        $pid = (int)$pdo->lastInsertId();
        $img_plat = uploadPlatImage();
        if ($img_plat) {
            $pdo->prepare('UPDATE plat SET image = ? WHERE plat_id = ?')->execute([$img_plat, $pid]);
        }
        foreach ($aids as $aid) {
            $pdo->prepare('INSERT IGNORE INTO plat_allergene (plat_id, allergene_id) VALUES (?, ?)')->execute([$pid, $aid]);
        }

        $_SESSION['flash_ok'] = 'Plat ajouté.';
        header('Location: index.php?section=menus');
        exit;
    }

    // --- Modifier plat ---
    if ($action === 'modifier_plat') {
        $pid  = filter_input(INPUT_POST, 'plat_id', FILTER_VALIDATE_INT);
        $nom  = trim($_POST['nom'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $aids = array_filter(array_map('intval', (array)($_POST['allergene_ids'] ?? [])));

        $err = [];
        if (!$pid)                                         $err[] = 'Plat invalide.';
        if (empty($nom))                                   $err[] = 'Nom requis.';
        if (!in_array($type, ['entree','plat','dessert'])) $err[] = 'Type invalide.';

        if ($err) { $_SESSION['flash_err'] = implode(' ', $err); header('Location: index.php?section=menus'); exit; }

        $pdo->prepare('UPDATE plat SET nom=?, type=?, description=? WHERE plat_id=?')->execute([$nom, $type, $desc ?: null, $pid]);
        $img_plat = uploadPlatImage();
        if ($img_plat) {
            $pdo->prepare('UPDATE plat SET image = ? WHERE plat_id = ?')->execute([$img_plat, $pid]);
        }
        $pdo->prepare('DELETE FROM plat_allergene WHERE plat_id=?')->execute([$pid]);
        foreach ($aids as $aid) {
            $pdo->prepare('INSERT IGNORE INTO plat_allergene (plat_id, allergene_id) VALUES (?, ?)')->execute([$pid, $aid]);
        }

        $_SESSION['flash_ok'] = 'Plat modifié.';
        header('Location: index.php?section=menus');
        exit;
    }

    // --- Supprimer plat ---
    if ($action === 'supprimer_plat') {
        $pid = filter_input(INPUT_POST, 'plat_id', FILTER_VALIDATE_INT);
        if ($pid) {
            $pdo->prepare('DELETE FROM plat WHERE plat_id = ?')->execute([$pid]);
            $_SESSION['flash_ok'] = 'Plat supprimé.';
        }
        header('Location: index.php?section=menus');
        exit;
    }

    // --- Modifier horaire ---
    if ($action === 'modifier_horaire') {
        $hid       = filter_input(INPUT_POST, 'horaire_id', FILTER_VALIDATE_INT);
        $ouverture = trim($_POST['heure_ouverture'] ?? '');
        $fermeture = trim($_POST['heure_fermeture'] ?? '');
        $ferme     = isset($_POST['ferme']) ? 1 : 0;

        if ($hid) {
            if ($ferme) {
                $pdo->prepare('UPDATE horaire SET ferme=1, heure_ouverture=NULL, heure_fermeture=NULL WHERE horaire_id=?')
                    ->execute([$hid]);
            } else {
                $pdo->prepare('UPDATE horaire SET ferme=0, heure_ouverture=?, heure_fermeture=? WHERE horaire_id=?')
                    ->execute([$ouverture ?: null, $fermeture ?: null, $hid]);
            }
            $_SESSION['flash_ok'] = 'Horaire mis à jour.';
        }
        header('Location: index.php?section=horaires');
        exit;
    }

    // --- Valider / refuser avis ---
    if ($action === 'valider_avis' || $action === 'refuser_avis') {
        $aid    = filter_input(INPUT_POST, 'avis_id', FILTER_VALIDATE_INT);
        $statut = ($action === 'valider_avis') ? 'valide' : 'refuse';
        if ($aid) {
            $pdo->prepare('UPDATE avis SET statut = ? WHERE avis_id = ?')->execute([$statut, $aid]);
            $_SESSION['flash_ok'] = ($statut === 'valide') ? 'Avis validé et publié.' : 'Avis refusé.';
        }
        header('Location: index.php?section=avis');
        exit;
    }

    // --- Créer employé ---
    if ($action === 'creer_employe') {
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $nom      = trim($_POST['nom']      ?? '');
        $prenom   = trim($_POST['prenom']   ?? '');

        $err = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Adresse email invalide.';
        if (strlen($password) < 8)                      $err[] = 'Mot de passe trop court (8 caractères minimum).';

        if (!$err) {
            $stmt = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) $err[] = 'Cette adresse email est déjà utilisée.';
        }

        if ($err) {
            $_SESSION['flash_err'] = implode(' ', $err);
            header('Location: index.php?section=employes');
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO utilisateur (email, password, nom, prenom, role_id, actif) VALUES (?, ?, ?, ?, 2, 1)')
            ->execute([$email, $hash, $nom ?: null, $prenom ?: null]);

        mailCreationCompteEmploye($email);

        $_SESSION['flash_ok'] = 'Compte employé créé. Un email de notification a été envoyé (sans le mot de passe).';
        header('Location: index.php?section=employes');
        exit;
    }

    // --- Activer / désactiver employé ---
    if ($action === 'toggle_employe') {
        $uid = filter_input(INPUT_POST, 'employe_id', FILTER_VALIDATE_INT);
        if ($uid) {
            $stmt = $pdo->prepare('SELECT actif FROM utilisateur WHERE utilisateur_id = ? AND role_id = 2');
            $stmt->execute([$uid]);
            $emp = $stmt->fetch();
            if ($emp) {
                $newActif = $emp['actif'] ? 0 : 1;
                $pdo->prepare('UPDATE utilisateur SET actif = ? WHERE utilisateur_id = ? AND role_id = 2')
                    ->execute([$newActif, $uid]);
                $_SESSION['flash_ok'] = $newActif ? 'Compte réactivé.' : 'Compte désactivé.';
            }
        }
        header('Location: index.php?section=employes');
        exit;
    }

    header('Location: index.php');
    exit;
}

// ============================================================
// GET — fetch data
// ============================================================

$allowed_sections = ['commandes','menus','horaires','avis','employes','statistiques'];
$section_param    = $_GET['section'] ?? 'commandes';
$active_section   = in_array($section_param, $allowed_sections, true) ? $section_param : 'commandes';

$filtre_statut = trim($_GET['statut'] ?? '');
$filtre_client = trim($_GET['client'] ?? '');

$valid_statuts = ['en_attente','accepte','en_preparation','en_cours_livraison','livre','attente_retour_materiel','terminee','annulee'];

// Commandes
$where  = [];
$params = [];
if ($filtre_statut && in_array($filtre_statut, $valid_statuts, true)) {
    $where[]  = 'c.statut = ?';
    $params[] = $filtre_statut;
}
if ($filtre_client !== '') {
    $where[]  = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
    $like     = '%' . $filtre_client . '%';
    array_push($params, $like, $like, $like);
}
$wclause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$s = $pdo->prepare(
    "SELECT c.commande_id, c.numero_commande, c.date_commande, c.date_prestation,
            c.heure_livraison, c.nombre_personne, c.prix_total, c.statut,
            c.motif_annulation, c.mode_contact_annulation,
            m.titre AS menu_titre,
            u.nom AS client_nom, u.prenom AS client_prenom,
            u.email AS client_email, u.telephone AS client_tel
     FROM commande c
     JOIN menu m ON c.menu_id = m.menu_id
     JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
     $wclause
     ORDER BY c.date_commande DESC"
);
$s->execute($params);
$commandes = $s->fetchAll();

// Menus
$menus = $pdo->query(
    'SELECT m.menu_id, m.titre, m.description, m.nombre_personne_minimum, m.prix_par_personne,
            m.conditions, m.quantite_restante, m.actif, m.theme_id, m.regime_id,
            t.libelle AS theme_libelle, r.libelle AS regime_libelle,
            (SELECT chemin FROM menu_image WHERE menu_id = m.menu_id ORDER BY ordre ASC LIMIT 1) AS image
     FROM menu m
     JOIN theme t ON m.theme_id = t.theme_id
     JOIN regime r ON m.regime_id = r.regime_id
     ORDER BY m.actif DESC, m.titre ASC'
)->fetchAll();

// Plats
$plats = $pdo->query(
    "SELECT p.plat_id, p.nom, p.type, p.description, p.image,
            GROUP_CONCAT(a.libelle ORDER BY a.libelle SEPARATOR ', ') AS allergenes,
            GROUP_CONCAT(a.allergene_id ORDER BY a.libelle SEPARATOR ',') AS allergene_ids_str
     FROM plat p
     LEFT JOIN plat_allergene pa ON p.plat_id = pa.plat_id
     LEFT JOIN allergene a ON pa.allergene_id = a.allergene_id
     GROUP BY p.plat_id
     ORDER BY FIELD(p.type,'entree','plat','dessert'), p.nom"
)->fetchAll();

// Horaires
$horaires = $pdo->query('SELECT * FROM horaire ORDER BY horaire_id')->fetchAll();

// Avis en attente
$avis_liste = $pdo->query(
    'SELECT a.avis_id, a.note, a.commentaire, a.created_at,
            u.nom AS client_nom, u.prenom AS client_prenom,
            c.numero_commande, m.titre AS menu_titre
     FROM avis a
     JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
     JOIN commande c ON a.commande_id = c.commande_id
     JOIN menu m ON c.menu_id = m.menu_id
     WHERE a.statut = "en_attente"
     ORDER BY a.created_at ASC'
)->fetchAll();

// Employés
$employes = $pdo->query(
    'SELECT utilisateur_id, nom, prenom, email, actif
     FROM utilisateur WHERE role_id = 2 ORDER BY nom, prenom'
)->fetchAll();

// Listes pour formulaires
$themes    = $pdo->query('SELECT theme_id, libelle FROM theme ORDER BY libelle')->fetchAll();
$regimes   = $pdo->query('SELECT regime_id, libelle FROM regime ORDER BY libelle')->fetchAll();
$allergenes = $pdo->query('SELECT allergene_id, libelle FROM allergene ORDER BY libelle')->fetchAll();

// Statistiques MongoDB
$ca_menu_id    = filter_input(INPUT_GET, 'ca_menu', FILTER_VALIDATE_INT) ?: 0;
$ca_date_debut = trim($_GET['date_debut'] ?? '');
$ca_date_fin   = trim($_GET['date_fin']   ?? '');

$mongo_commandes_par_menu = [];
$mongo_ca_par_menu        = [];
$mongo_error              = false;

try {
    require_once '../../PHP/config/mongodb.php';
    $col = getMongoDB()->commandes;

    $result = $col->aggregate([
        ['$group' => ['_id' => '$menu_titre', 'count' => ['$sum' => 1]]],
        ['$sort'  => ['count' => -1]],
    ]);
    foreach ($result as $doc) {
        $mongo_commandes_par_menu[] = ['menu' => (string)$doc['_id'], 'count' => (int)$doc['count']];
    }

    $match = [];
    if ($ca_date_debut && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ca_date_debut)) {
        $match['date_commande']['$gte'] = new \MongoDB\BSON\UTCDateTime(strtotime($ca_date_debut) * 1000);
    }
    if ($ca_date_fin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ca_date_fin)) {
        $match['date_commande']['$lte'] = new \MongoDB\BSON\UTCDateTime((strtotime($ca_date_fin) + 86399) * 1000);
    }
    if ($ca_menu_id) {
        $match['menu_id'] = $ca_menu_id;
    }

    $pipeline_ca = [];
    if ($match) $pipeline_ca[] = ['$match' => $match];
    $pipeline_ca[] = ['$group' => ['_id' => '$menu_titre', 'ca' => ['$sum' => '$prix_total'], 'count' => ['$sum' => 1]]];
    $pipeline_ca[] = ['$sort'  => ['ca' => -1]];

    foreach ($col->aggregate($pipeline_ca) as $doc) {
        $mongo_ca_par_menu[] = ['menu' => (string)$doc['_id'], 'ca' => (float)$doc['ca'], 'count' => (int)$doc['count']];
    }
} catch (\Throwable $e) {
    $mongo_error = true;
}

$statut_labels = [
    'en_attente'              => 'En attente',
    'accepte'                 => 'Acceptée',
    'en_preparation'          => 'En préparation',
    'en_cours_livraison'      => 'En cours de livraison',
    'livre'                   => 'Livrée',
    'attente_retour_materiel' => 'En attente retour matériel',
    'terminee'                => 'Terminée',
    'annulee'                 => 'Annulée',
];

$statut_badges = [
    'en_attente'              => 'badge-attente',
    'accepte'                 => 'badge-accepte',
    'en_preparation'          => 'badge-preparation',
    'en_cours_livraison'      => 'badge-livraison',
    'livre'                   => 'badge-livre',
    'attente_retour_materiel' => 'badge-materiel',
    'terminee'                => 'badge-terminee',
    'annulee'                 => 'badge-annulee',
];

$transitions = [
    'en_attente'              => ['accepte'],
    'accepte'                 => ['en_preparation'],
    'en_preparation'          => ['en_cours_livraison'],
    'en_cours_livraison'      => ['livre'],
    'livre'                   => ['terminee', 'attente_retour_materiel'],
    'attente_retour_materiel' => ['terminee'],
    'terminee'                => [],
    'annulee'                 => [],
];

$type_labels = ['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Espace administrateur | Vite &amp; Gourmand</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/espace-employe.css">
  <link rel="stylesheet" href="../../CSS/espace-admin.css">
</head>
<body>

  <header class="ee-header">
    <div class="ee-header-inner">
      <a href="../index.html" class="ee-logo">Vite &amp; Gourmand</a>
      <span class="ea-role-badge">Administrateur</span>
      <span class="ee-user-name">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
      <a href="../../PHP/deconnexion.php" class="ee-logout">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Déconnexion
      </a>
    </div>
  </header>

  <div class="ee-layout">

    <aside class="ee-sidebar">
      <nav aria-label="Navigation espace administrateur">
        <ul class="ee-nav">
          <li>
            <a href="?section=commandes" class="ee-nav-link <?= $active_section === 'commandes' ? 'active' : '' ?>">
              <i class="bi bi-bag" aria-hidden="true"></i> Commandes
            </a>
          </li>
          <li>
            <a href="?section=menus" class="ee-nav-link <?= $active_section === 'menus' ? 'active' : '' ?>">
              <i class="bi bi-journal-text" aria-hidden="true"></i> Menus &amp; plats
            </a>
          </li>
          <li>
            <a href="?section=horaires" class="ee-nav-link <?= $active_section === 'horaires' ? 'active' : '' ?>">
              <i class="bi bi-clock" aria-hidden="true"></i> Horaires
            </a>
          </li>
          <li>
            <a href="?section=avis" class="ee-nav-link <?= $active_section === 'avis' ? 'active' : '' ?>">
              <i class="bi bi-star" aria-hidden="true"></i> Avis clients
              <?php if (count($avis_liste) > 0): ?>
                <span class="ee-badge-count"><?= count($avis_liste) ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li>
            <a href="?section=employes" class="ee-nav-link <?= $active_section === 'employes' ? 'active' : '' ?>">
              <i class="bi bi-people" aria-hidden="true"></i> Employés
            </a>
          </li>
          <li>
            <a href="?section=statistiques" class="ee-nav-link <?= $active_section === 'statistiques' ? 'active' : '' ?>">
              <i class="bi bi-bar-chart" aria-hidden="true"></i> Statistiques
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <main class="ee-main" id="contenu-principal">

      <?php if ($flash_ok): ?>
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
          <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
          <?= htmlspecialchars($flash_ok) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
      <?php endif; ?>
      <?php if ($flash_err): ?>
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
          <?= htmlspecialchars($flash_err) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
      <?php endif; ?>

      <!-- ===== SECTION : COMMANDES ===== -->
      <section id="section-commandes" class="ee-section"
               <?= $active_section !== 'commandes' ? 'style="display:none"' : '' ?>>

        <h1 class="ee-page-title">Commandes</h1>

        <div class="ee-filters">
          <form action="index.php" method="get" class="ee-filter-form">
            <input type="hidden" name="section" value="commandes">
            <div class="ee-filter-group">
              <label for="filtre-statut">Statut</label>
              <select id="filtre-statut" name="statut">
                <option value="">Tous</option>
                <?php foreach ($statut_labels as $val => $lbl): ?>
                  <option value="<?= $val ?>" <?= $filtre_statut === $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($lbl) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ee-filter-group">
              <label for="filtre-client">Client</label>
              <input type="text" id="filtre-client" name="client" placeholder="Nom ou e-mail…"
                     value="<?= htmlspecialchars($filtre_client) ?>">
            </div>
            <button type="submit" class="ee-btn ee-btn-primary">Filtrer</button>
            <?php if ($filtre_statut || $filtre_client): ?>
              <a href="?section=commandes" class="ee-btn ee-btn-secondary">Réinitialiser</a>
            <?php endif; ?>
          </form>
        </div>

        <div class="ee-table-wrapper">
          <table class="ee-table">
            <thead>
              <tr>
                <th>N°</th><th>Client</th><th>Menu</th><th>Date prestation</th>
                <th>Pers.</th><th>Total</th><th>Statut</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($commandes)): ?>
                <tr><td colspan="8" style="text-align:center;color:#888;padding:2rem;">Aucune commande trouvée.</td></tr>
              <?php else: ?>
                <?php foreach ($commandes as $cmd):
                  $cid    = (int)$cmd['commande_id'];
                  $statut = $cmd['statut'];
                  $badge  = $statut_badges[$statut] ?? 'badge-attente';
                  $label  = $statut_labels[$statut]  ?? $statut;
                  $nexts  = $transitions[$statut] ?? [];
                  $can_cancel = !in_array($statut, ['terminee','annulee'], true);
                ?>
                <tr>
                  <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                  <td>
                    <p class="ee-client-name"><?= htmlspecialchars($cmd['client_prenom'] . ' ' . $cmd['client_nom']) ?></p>
                    <p class="ee-client-contact">
                      <?= htmlspecialchars($cmd['client_email']) ?>
                      <?php if ($cmd['client_tel']): ?> — <?= htmlspecialchars($cmd['client_tel']) ?><?php endif; ?>
                    </p>
                  </td>
                  <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                  <td><?= date('d/m/Y', strtotime($cmd['date_prestation'])) ?> — <?= htmlspecialchars(substr($cmd['heure_livraison'], 0, 5)) ?></td>
                  <td><?= (int)$cmd['nombre_personne'] ?></td>
                  <td><?= number_format((float)$cmd['prix_total'], 2, ',', ' ') ?> €</td>
                  <td><span class="ee-badge <?= $badge ?>"><?= $label ?></span></td>
                  <td class="ee-actions">
                    <?php if ($nexts): ?>
                      <button class="ee-btn ee-btn-sm ee-btn-primary" data-modal="modal-statut-<?= $cid ?>">Avancer</button>
                    <?php endif; ?>
                    <?php if ($can_cancel): ?>
                      <button class="ee-btn ee-btn-sm ee-btn-danger" data-modal="modal-annuler-<?= $cid ?>">Annuler</button>
                    <?php endif; ?>
                    <?php if ($statut === 'annulee' && $cmd['motif_annulation']): ?>
                      <span class="ee-motif-link" data-modal="modal-motif-<?= $cid ?>" style="cursor:pointer;color:#888;font-size:.78rem;">
                        <i class="bi bi-info-circle"></i> Motif
                      </span>
                    <?php endif; ?>
                    <?php if (empty($nexts) && !$can_cancel): ?>—<?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===== SECTION : MENUS & PLATS ===== -->
      <section id="section-menus" class="ee-section"
               <?= $active_section !== 'menus' ? 'style="display:none"' : '' ?>>

        <h1 class="ee-page-title">Menus &amp; plats</h1>

        <div class="ee-subsection">
          <div class="ee-subsection-header">
            <h2 class="ee-subsection-title">Menus</h2>
            <button class="ee-btn ee-btn-primary" data-modal="modal-ajouter-menu">
              <i class="bi bi-plus" aria-hidden="true"></i> Ajouter un menu
            </button>
          </div>
          <div class="ee-table-wrapper">
            <table class="ee-table">
              <thead>
                <tr>
                  <th>Titre</th><th>Thème</th><th>Régime</th><th>Prix/pers.</th>
                  <th>Pers. min.</th><th>Stock</th><th>État</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($menus)): ?>
                  <tr><td colspan="8" style="text-align:center;color:#888;padding:2rem;">Aucun menu.</td></tr>
                <?php else: ?>
                  <?php foreach ($menus as $m): ?>
                  <tr <?= !$m['actif'] ? 'style="opacity:.5"' : '' ?>>
                    <td><?= htmlspecialchars($m['titre']) ?></td>
                    <td><?= htmlspecialchars($m['theme_libelle']) ?></td>
                    <td><?= htmlspecialchars($m['regime_libelle']) ?></td>
                    <td><?= number_format((float)$m['prix_par_personne'], 2, ',', ' ') ?> €</td>
                    <td><?= (int)$m['nombre_personne_minimum'] ?></td>
                    <td><?= (int)$m['quantite_restante'] ?></td>
                    <td>
                      <?php if ($m['actif']): ?>
                        <span class="ee-badge badge-accepte">Actif</span>
                      <?php else: ?>
                        <span class="ee-badge badge-annulee">Inactif</span>
                      <?php endif; ?>
                    </td>
                    <td class="ee-actions">
                      <button class="ee-btn ee-btn-sm ee-btn-secondary"
                              data-modal="modal-modifier-menu-<?= $m['menu_id'] ?>">Modifier</button>
                      <?php if ($m['actif']): ?>
                        <button class="ee-btn ee-btn-sm ee-btn-danger"
                                data-modal="modal-supprimer-menu-<?= $m['menu_id'] ?>">Désactiver</button>
                      <?php else: ?>
                        <form action="index.php" method="post" style="display:inline">
                          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                          <input type="hidden" name="action" value="modifier_menu">
                          <input type="hidden" name="menu_id" value="<?= $m['menu_id'] ?>">
                          <input type="hidden" name="titre" value="<?= htmlspecialchars($m['titre']) ?>">
                          <input type="hidden" name="theme_id" value="<?= $m['theme_id'] ?>">
                          <input type="hidden" name="regime_id" value="<?= $m['regime_id'] ?>">
                          <input type="hidden" name="nombre_personne_minimum" value="<?= $m['nombre_personne_minimum'] ?>">
                          <input type="hidden" name="prix_par_personne" value="<?= $m['prix_par_personne'] ?>">
                          <input type="hidden" name="quantite_restante" value="<?= $m['quantite_restante'] ?>">
                          <input type="hidden" name="actif" value="1">
                          <button type="submit" class="ee-btn ee-btn-sm ee-btn-secondary">Réactiver</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="ee-subsection">
          <div class="ee-subsection-header">
            <h2 class="ee-subsection-title">Plats</h2>
            <button class="ee-btn ee-btn-primary" data-modal="modal-ajouter-plat">
              <i class="bi bi-plus" aria-hidden="true"></i> Ajouter un plat
            </button>
          </div>
          <div class="ee-table-wrapper">
            <table class="ee-table">
              <thead>
                <tr><th>Nom</th><th>Type</th><th>Description</th><th>Allergènes</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (empty($plats)): ?>
                  <tr><td colspan="5" style="text-align:center;color:#888;padding:2rem;">Aucun plat.</td></tr>
                <?php else: ?>
                  <?php foreach ($plats as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= htmlspecialchars($type_labels[$p['type']] ?? $p['type']) ?></td>
                    <td style="max-width:200px;font-size:.82rem;color:#555;">
                      <?= $p['description'] ? htmlspecialchars(mb_strimwidth($p['description'], 0, 60, '…')) : '—' ?>
                    </td>
                    <td style="font-size:.82rem;"><?= $p['allergenes'] ? htmlspecialchars($p['allergenes']) : '—' ?></td>
                    <td class="ee-actions">
                      <button class="ee-btn ee-btn-sm ee-btn-secondary"
                              data-modal="modal-modifier-plat-<?= $p['plat_id'] ?>">Modifier</button>
                      <button class="ee-btn ee-btn-sm ee-btn-danger"
                              data-modal="modal-supprimer-plat-<?= $p['plat_id'] ?>">Supprimer</button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ===== SECTION : HORAIRES ===== -->
      <section id="section-horaires" class="ee-section"
               <?= $active_section !== 'horaires' ? 'style="display:none"' : '' ?>>

        <h1 class="ee-page-title">Horaires</h1>

        <div class="ee-table-wrapper">
          <table class="ee-table">
            <thead>
              <tr><th>Jour</th><th>Ouverture</th><th>Fermeture</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($horaires as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['jour']) ?></td>
                <?php if ($h['ferme']): ?>
                  <td colspan="2" class="ee-closed">Fermé</td>
                <?php else: ?>
                  <td><?= $h['heure_ouverture'] ? htmlspecialchars(substr($h['heure_ouverture'], 0, 5)) : '—' ?></td>
                  <td><?= $h['heure_fermeture'] ? htmlspecialchars(substr($h['heure_fermeture'], 0, 5)) : '—' ?></td>
                <?php endif; ?>
                <td>
                  <button class="ee-btn ee-btn-sm ee-btn-secondary"
                          data-modal="modal-horaire-<?= $h['horaire_id'] ?>">Modifier</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===== SECTION : AVIS ===== -->
      <section id="section-avis" class="ee-section"
               <?= $active_section !== 'avis' ? 'style="display:none"' : '' ?>>

        <h1 class="ee-page-title">Avis clients en attente</h1>

        <?php if (empty($avis_liste)): ?>
          <div class="ee-table-wrapper" style="padding:2rem;text-align:center;color:#888;">
            <i class="bi bi-check-all" style="font-size:2rem;display:block;margin-bottom:.75rem;"></i>
            Aucun avis en attente de modération.
          </div>
        <?php else: ?>
          <div class="ee-table-wrapper">
            <table class="ee-table">
              <thead>
                <tr><th>Client</th><th>Commande</th><th>Menu</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($avis_liste as $av): ?>
                <tr>
                  <td><?= htmlspecialchars($av['client_prenom'] . ' ' . $av['client_nom']) ?></td>
                  <td><?= htmlspecialchars($av['numero_commande']) ?></td>
                  <td><?= htmlspecialchars($av['menu_titre']) ?></td>
                  <td>
                    <span class="ee-stars" aria-label="<?= (int)$av['note'] ?>/5">
                      <?php for ($i = 1; $i <= 5; $i++): ?><?= $i <= $av['note'] ? '★' : '☆' ?><?php endfor; ?>
                    </span>
                  </td>
                  <td style="max-width:250px;font-size:.85rem;"><?= htmlspecialchars($av['commentaire']) ?></td>
                  <td style="font-size:.8rem;color:#888;"><?= date('d/m/Y', strtotime($av['created_at'])) ?></td>
                  <td class="ee-actions">
                    <form action="index.php" method="post" style="display:inline">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <input type="hidden" name="action" value="valider_avis">
                      <input type="hidden" name="avis_id" value="<?= (int)$av['avis_id'] ?>">
                      <button type="submit" class="ee-btn ee-btn-sm ee-btn-primary">
                        <i class="bi bi-check" aria-hidden="true"></i> Valider
                      </button>
                    </form>
                    <form action="index.php" method="post" style="display:inline">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <input type="hidden" name="action" value="refuser_avis">
                      <input type="hidden" name="avis_id" value="<?= (int)$av['avis_id'] ?>">
                      <button type="submit" class="ee-btn ee-btn-sm ee-btn-danger">
                        <i class="bi bi-x" aria-hidden="true"></i> Refuser
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <!-- ===== SECTION : EMPLOYÉS ===== -->
      <section id="section-employes" class="ee-section"
               <?= $active_section !== 'employes' ? 'style="display:none"' : '' ?>>

        <h1 class="ee-page-title">Employés</h1>

        <div class="ee-subsection">
          <div class="ee-subsection-header">
            <h2 class="ee-subsection-title">Comptes employés</h2>
            <button class="ee-btn ee-btn-primary" data-modal="modal-ajouter-employe">
              <i class="bi bi-person-plus" aria-hidden="true"></i> Ajouter un employé
            </button>
          </div>

          <div class="ee-table-wrapper">
            <table class="ee-table">
              <thead>
                <tr><th>Prénom</th><th>Nom</th><th>Adresse email</th><th>Statut</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (empty($employes)): ?>
                  <tr><td colspan="5" style="text-align:center;color:#888;padding:2rem;">Aucun employé.</td></tr>
                <?php else: ?>
                  <?php foreach ($employes as $emp): ?>
                  <tr>
                    <td><?= htmlspecialchars($emp['prenom'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($emp['nom'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($emp['email']) ?></td>
                    <td>
                      <?php if ($emp['actif']): ?>
                        <span class="ee-badge badge-accepte">Actif</span>
                      <?php else: ?>
                        <span class="ee-badge badge-annulee">Inactif</span>
                      <?php endif; ?>
                    </td>
                    <td class="ee-actions">
                      <form action="index.php" method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="toggle_employe">
                        <input type="hidden" name="employe_id" value="<?= (int)$emp['utilisateur_id'] ?>">
                        <?php if ($emp['actif']): ?>
                          <button type="submit" class="ee-btn ee-btn-sm ee-btn-danger"
                                  onclick="return confirm('Désactiver ce compte ?')">
                            <i class="bi bi-person-x" aria-hidden="true"></i> Désactiver
                          </button>
                        <?php else: ?>
                          <button type="submit" class="ee-btn ee-btn-sm ee-btn-secondary"
                                  onclick="return confirm('Réactiver ce compte ?')">
                            <i class="bi bi-person-check" aria-hidden="true"></i> Réactiver
                          </button>
                        <?php endif; ?>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ===== SECTION : STATISTIQUES ===== -->
      <section id="section-statistiques" class="ee-section"
               <?= $active_section !== 'statistiques' ? 'style="display:none"' : '' ?>>

        <h1 class="ee-page-title">Statistiques</h1>

        <?php if ($mongo_error): ?>
          <div class="alert alert-warning mb-4" role="alert">
            <i class="bi bi-database-exclamation me-2" aria-hidden="true"></i>
            MongoDB non disponible — lancez MongoDB et rechargez la page pour voir les statistiques.
          </div>
        <?php endif; ?>

        <!-- Commandes par menu -->
        <div class="stat-card">
          <h2 class="stat-card-title">Nombre de commandes par menu</h2>
          <?php if (empty($mongo_commandes_par_menu)): ?>
            <p style="color:#888;text-align:center;padding:2rem;">Aucune donnée disponible.</p>
          <?php else: ?>
            <canvas id="chart-commandes" style="max-height:350px"></canvas>
          <?php endif; ?>
        </div>

        <!-- Chiffre d'affaires -->
        <div class="stat-card">
          <h2 class="stat-card-title">Chiffre d'affaires par menu</h2>

          <form action="index.php" method="get" class="ee-filter-form" style="margin-bottom:1.5rem">
            <input type="hidden" name="section" value="statistiques">
            <div class="ee-filter-group">
              <label for="ca-menu">Menu</label>
              <select id="ca-menu" name="ca_menu">
                <option value="">Tous les menus</option>
                <?php foreach ($menus as $m): ?>
                  <option value="<?= $m['menu_id'] ?>" <?= $ca_menu_id == $m['menu_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['titre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ee-filter-group">
              <label for="ca-date-debut">Du</label>
              <input type="date" id="ca-date-debut" name="date_debut"
                     value="<?= htmlspecialchars($ca_date_debut) ?>">
            </div>
            <div class="ee-filter-group">
              <label for="ca-date-fin">Au</label>
              <input type="date" id="ca-date-fin" name="date_fin"
                     value="<?= htmlspecialchars($ca_date_fin) ?>">
            </div>
            <button type="submit" class="ee-btn ee-btn-primary">Filtrer</button>
            <?php if ($ca_menu_id || $ca_date_debut || $ca_date_fin): ?>
              <a href="?section=statistiques" class="ee-btn ee-btn-secondary">Réinitialiser</a>
            <?php endif; ?>
          </form>

          <?php if (empty($mongo_ca_par_menu)): ?>
            <p style="color:#888;text-align:center;padding:2rem;">Aucune donnée disponible.</p>
          <?php else: ?>
            <canvas id="chart-ca" style="max-height:350px"></canvas>
            <div class="ee-table-wrapper" style="margin-top:1.5rem">
              <table class="ee-table">
                <thead>
                  <tr><th>Menu</th><th>Commandes</th><th>Chiffre d'affaires</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($mongo_ca_par_menu as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['menu']) ?></td>
                    <td><?= (int)$row['count'] ?></td>
                    <td><?= number_format($row['ca'], 2, ',', ' ') ?> €</td>
                  </tr>
                  <?php endforeach; ?>
                  <tr style="font-weight:bold;background:#fafafa">
                    <td>Total</td>
                    <td><?= array_sum(array_column($mongo_ca_par_menu, 'count')) ?></td>
                    <td><?= number_format(array_sum(array_column($mongo_ca_par_menu, 'ca')), 2, ',', ' ') ?> €</td>
                  </tr>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </section>

    </main>
  </div>

  <!-- ============================================================
       MODALS — Commandes
  ============================================================ -->
  <?php foreach ($commandes as $cmd):
    $cid    = (int)$cmd['commande_id'];
    $statut = $cmd['statut'];
    $nexts  = $transitions[$statut] ?? [];
    $can_cancel = !in_array($statut, ['terminee','annulee'], true);
  ?>

    <?php if ($nexts): ?>
    <div class="ee-modal-overlay" id="modal-statut-<?= $cid ?>" style="display:none"
         role="dialog" aria-modal="true" aria-labelledby="modal-statut-title-<?= $cid ?>">
      <div class="ee-modal">
        <div class="ee-modal-header">
          <h2 id="modal-statut-title-<?= $cid ?>">Avancer la commande <?= htmlspecialchars($cmd['numero_commande']) ?></h2>
          <button class="ee-modal-close" data-modal="modal-statut-<?= $cid ?>" aria-label="Fermer">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>
        <form action="index.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="action" value="changer_statut">
          <input type="hidden" name="commande_id" value="<?= $cid ?>">
          <input type="hidden" name="filtre_statut" value="<?= htmlspecialchars($filtre_statut) ?>">
          <input type="hidden" name="filtre_client" value="<?= htmlspecialchars($filtre_client) ?>">
          <div class="ee-form-group">
            <label for="statut-<?= $cid ?>">Nouveau statut</label>
            <select id="statut-<?= $cid ?>" name="statut" required>
              <?php foreach ($nexts as $ns): ?>
                <option value="<?= $ns ?>"><?= htmlspecialchars($statut_labels[$ns] ?? $ns) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ee-modal-footer">
            <button type="button" class="ee-btn ee-btn-secondary" data-modal="modal-statut-<?= $cid ?>">Annuler</button>
            <button type="submit" class="ee-btn ee-btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can_cancel): ?>
    <div class="ee-modal-overlay" id="modal-annuler-<?= $cid ?>" style="display:none"
         role="dialog" aria-modal="true" aria-labelledby="modal-annuler-title-<?= $cid ?>">
      <div class="ee-modal">
        <div class="ee-modal-header">
          <h2 id="modal-annuler-title-<?= $cid ?>">Annuler <?= htmlspecialchars($cmd['numero_commande']) ?></h2>
          <button class="ee-modal-close" data-modal="modal-annuler-<?= $cid ?>" aria-label="Fermer">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>
        <p class="ee-modal-info">Vous devez avoir contacté le client avant d'annuler cette commande.</p>
        <form action="index.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="action" value="annuler_commande">
          <input type="hidden" name="commande_id" value="<?= $cid ?>">
          <div class="ee-form-group">
            <label for="mode-contact-<?= $cid ?>">Mode de contact utilisé</label>
            <select id="mode-contact-<?= $cid ?>" name="mode_contact" required>
              <option value="">— Sélectionner —</option>
              <option value="gsm">Appel GSM</option>
              <option value="mail">E-mail</option>
            </select>
          </div>
          <div class="ee-form-group">
            <label for="motif-<?= $cid ?>">Motif d'annulation</label>
            <textarea id="motif-<?= $cid ?>" name="motif" rows="3" placeholder="Décrivez le motif…" required></textarea>
          </div>
          <div class="ee-modal-footer">
            <button type="button" class="ee-btn ee-btn-secondary" data-modal="modal-annuler-<?= $cid ?>">Retour</button>
            <button type="submit" class="ee-btn ee-btn-danger">
              <i class="bi bi-x-circle" aria-hidden="true"></i> Confirmer l'annulation
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($statut === 'annulee' && $cmd['motif_annulation']): ?>
    <div class="ee-modal-overlay" id="modal-motif-<?= $cid ?>" style="display:none" role="dialog" aria-modal="true">
      <div class="ee-modal">
        <div class="ee-modal-header">
          <h2>Motif d'annulation — <?= htmlspecialchars($cmd['numero_commande']) ?></h2>
          <button class="ee-modal-close" data-modal="modal-motif-<?= $cid ?>" aria-label="Fermer">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>
        <p style="font-size:.875rem;margin-bottom:.5rem;">
          <strong>Mode de contact :</strong>
          <?= $cmd['mode_contact_annulation'] === 'gsm' ? 'Appel GSM' : 'E-mail' ?>
        </p>
        <p style="font-size:.875rem;"><?= htmlspecialchars($cmd['motif_annulation']) ?></p>
        <div class="ee-modal-footer">
          <button class="ee-btn ee-btn-secondary" data-modal="modal-motif-<?= $cid ?>">Fermer</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

  <?php endforeach; ?>

  <!-- ============================================================
       MODALS — Menus
  ============================================================ -->

  <div class="ee-modal-overlay" id="modal-ajouter-menu" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal ee-modal-large">
      <div class="ee-modal-header">
        <h2>Ajouter un menu</h2>
        <button class="ee-modal-close" data-modal="modal-ajouter-menu" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <form action="index.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="ajouter_menu">
        <div class="ee-form-group">
          <label for="new-menu-titre">Titre</label>
          <input type="text" id="new-menu-titre" name="titre" required>
        </div>
        <div class="ee-form-row">
          <div class="ee-form-group">
            <label for="new-menu-theme">Thème</label>
            <select id="new-menu-theme" name="theme_id" required>
              <option value="">— Sélectionner —</option>
              <?php foreach ($themes as $t): ?>
                <option value="<?= $t['theme_id'] ?>"><?= htmlspecialchars($t['libelle']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ee-form-group">
            <label for="new-menu-regime">Régime</label>
            <select id="new-menu-regime" name="regime_id" required>
              <option value="">— Sélectionner —</option>
              <?php foreach ($regimes as $r): ?>
                <option value="<?= $r['regime_id'] ?>"><?= htmlspecialchars($r['libelle']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="ee-form-group">
          <label for="new-menu-desc">Description</label>
          <textarea id="new-menu-desc" name="description" rows="3"></textarea>
        </div>
        <div class="ee-form-row">
          <div class="ee-form-group">
            <label for="new-menu-nb">Pers. min.</label>
            <input type="number" id="new-menu-nb" name="nombre_personne_minimum" min="1" required>
          </div>
          <div class="ee-form-group">
            <label for="new-menu-prix">Prix / personne (€)</label>
            <input type="number" id="new-menu-prix" name="prix_par_personne" step="0.01" min="0" required>
          </div>
          <div class="ee-form-group">
            <label for="new-menu-stock">Stock</label>
            <input type="number" id="new-menu-stock" name="quantite_restante" min="0" value="0">
          </div>
        </div>
        <div class="ee-form-group">
          <label for="new-menu-conditions">Conditions</label>
          <textarea id="new-menu-conditions" name="conditions" rows="2"
                    placeholder="Ex : commander 7 jours avant la prestation…"></textarea>
        </div>
        <div class="ee-form-group">
          <label for="new-menu-image">Image principale</label>
          <input type="file" id="new-menu-image" name="image" accept="image/*" class="form-control">
          <small class="text-muted">JPG, PNG, WebP — max 5 Mo</small>
        </div>
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary" data-modal="modal-ajouter-menu">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-primary">Ajouter</button>
        </div>
      </form>
    </div>
  </div>

  <?php foreach ($menus as $m): ?>
  <div class="ee-modal-overlay" id="modal-modifier-menu-<?= $m['menu_id'] ?>" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal ee-modal-large">
      <div class="ee-modal-header">
        <h2>Modifier — <?= htmlspecialchars($m['titre']) ?></h2>
        <button class="ee-modal-close" data-modal="modal-modifier-menu-<?= $m['menu_id'] ?>" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <form action="index.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="modifier_menu">
        <input type="hidden" name="menu_id" value="<?= $m['menu_id'] ?>">
        <div class="ee-form-group">
          <label for="titre-<?= $m['menu_id'] ?>">Titre</label>
          <input type="text" id="titre-<?= $m['menu_id'] ?>" name="titre"
                 value="<?= htmlspecialchars($m['titre']) ?>" required>
        </div>
        <div class="ee-form-row">
          <div class="ee-form-group">
            <label for="theme-<?= $m['menu_id'] ?>">Thème</label>
            <select id="theme-<?= $m['menu_id'] ?>" name="theme_id" required>
              <?php foreach ($themes as $t): ?>
                <option value="<?= $t['theme_id'] ?>" <?= $t['theme_id'] == $m['theme_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['libelle']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ee-form-group">
            <label for="regime-<?= $m['menu_id'] ?>">Régime</label>
            <select id="regime-<?= $m['menu_id'] ?>" name="regime_id" required>
              <?php foreach ($regimes as $r): ?>
                <option value="<?= $r['regime_id'] ?>" <?= $r['regime_id'] == $m['regime_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($r['libelle']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="ee-form-group">
          <label for="desc-<?= $m['menu_id'] ?>">Description</label>
          <textarea id="desc-<?= $m['menu_id'] ?>" name="description" rows="3"><?= htmlspecialchars($m['description'] ?? '') ?></textarea>
        </div>
        <div class="ee-form-row">
          <div class="ee-form-group">
            <label for="nb-<?= $m['menu_id'] ?>">Pers. min.</label>
            <input type="number" id="nb-<?= $m['menu_id'] ?>" name="nombre_personne_minimum"
                   value="<?= (int)$m['nombre_personne_minimum'] ?>" min="1" required>
          </div>
          <div class="ee-form-group">
            <label for="prix-<?= $m['menu_id'] ?>">Prix / personne (€)</label>
            <input type="number" id="prix-<?= $m['menu_id'] ?>" name="prix_par_personne"
                   value="<?= number_format((float)$m['prix_par_personne'], 2, '.', '') ?>"
                   step="0.01" min="0" required>
          </div>
          <div class="ee-form-group">
            <label for="stock-<?= $m['menu_id'] ?>">Stock</label>
            <input type="number" id="stock-<?= $m['menu_id'] ?>" name="quantite_restante"
                   value="<?= (int)$m['quantite_restante'] ?>" min="0">
          </div>
        </div>
        <div class="ee-form-group">
          <label for="cond-<?= $m['menu_id'] ?>">Conditions</label>
          <textarea id="cond-<?= $m['menu_id'] ?>" name="conditions" rows="2"><?= htmlspecialchars($m['conditions'] ?? '') ?></textarea>
        </div>
        <div class="ee-form-group">
          <label for="image-<?= $m['menu_id'] ?>">Image principale</label>
          <?php if (!empty($m['image'])): ?>
            <img src="../../<?= htmlspecialchars($m['image']) ?>" alt="Image actuelle"
                 style="max-height:80px;border-radius:4px;display:block;margin-bottom:.4rem;">
          <?php endif; ?>
          <input type="file" id="image-<?= $m['menu_id'] ?>" name="image" accept="image/*" class="form-control">
          <small class="text-muted">Laisser vide pour conserver l'image existante</small>
        </div>
        <div class="ee-form-group">
          <label>
            <input type="checkbox" name="actif" <?= $m['actif'] ? 'checked' : '' ?>> Menu actif (visible sur le site)
          </label>
        </div>
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary"
                  data-modal="modal-modifier-menu-<?= $m['menu_id'] ?>">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <?php if ($m['actif']): ?>
  <div class="ee-modal-overlay" id="modal-supprimer-menu-<?= $m['menu_id'] ?>" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal">
      <div class="ee-modal-header">
        <h2>Désactiver le menu</h2>
        <button class="ee-modal-close" data-modal="modal-supprimer-menu-<?= $m['menu_id'] ?>" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <p class="ee-modal-info">
        Le menu <strong><?= htmlspecialchars($m['titre']) ?></strong> sera masqué du site.
      </p>
      <form action="index.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="supprimer_menu">
        <input type="hidden" name="menu_id" value="<?= $m['menu_id'] ?>">
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary"
                  data-modal="modal-supprimer-menu-<?= $m['menu_id'] ?>">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-danger">Désactiver</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>

  <!-- Modal : Ajouter plat -->
  <div class="ee-modal-overlay" id="modal-ajouter-plat" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal">
      <div class="ee-modal-header">
        <h2>Ajouter un plat</h2>
        <button class="ee-modal-close" data-modal="modal-ajouter-plat" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <form action="index.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="ajouter_plat">
        <div class="ee-form-group">
          <label for="new-plat-nom">Nom du plat</label>
          <input type="text" id="new-plat-nom" name="nom" required>
        </div>
        <div class="ee-form-group">
          <label for="new-plat-type">Type</label>
          <select id="new-plat-type" name="type" required>
            <option value="entree">Entrée</option>
            <option value="plat">Plat</option>
            <option value="dessert">Dessert</option>
          </select>
        </div>
        <div class="ee-form-group">
          <label for="new-plat-desc">Description</label>
          <textarea id="new-plat-desc" name="description" rows="2"></textarea>
        </div>
        <div class="ee-form-group">
          <label>Allergènes</label>
          <div class="ee-checkboxes">
            <?php foreach ($allergenes as $al): ?>
              <label class="ee-checkbox-label">
                <input type="checkbox" name="allergene_ids[]" value="<?= $al['allergene_id'] ?>">
                <?= htmlspecialchars($al['libelle']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="ee-form-group">
          <label for="new-plat-image">Photo du plat</label>
          <input type="file" id="new-plat-image" name="image" accept="image/*" class="form-control">
          <small class="text-muted">JPG, PNG, WebP — max 5 Mo</small>
        </div>
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary" data-modal="modal-ajouter-plat">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-primary">Ajouter</button>
        </div>
      </form>
    </div>
  </div>

  <?php foreach ($plats as $p):
    $plat_aid_arr = $p['allergene_ids_str'] ? explode(',', $p['allergene_ids_str']) : [];
  ?>
  <div class="ee-modal-overlay" id="modal-modifier-plat-<?= $p['plat_id'] ?>" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal">
      <div class="ee-modal-header">
        <h2>Modifier — <?= htmlspecialchars($p['nom']) ?></h2>
        <button class="ee-modal-close" data-modal="modal-modifier-plat-<?= $p['plat_id'] ?>" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <form action="index.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="modifier_plat">
        <input type="hidden" name="plat_id" value="<?= $p['plat_id'] ?>">
        <div class="ee-form-group">
          <label for="plat-nom-<?= $p['plat_id'] ?>">Nom</label>
          <input type="text" id="plat-nom-<?= $p['plat_id'] ?>" name="nom"
                 value="<?= htmlspecialchars($p['nom']) ?>" required>
        </div>
        <div class="ee-form-group">
          <label for="plat-type-<?= $p['plat_id'] ?>">Type</label>
          <select id="plat-type-<?= $p['plat_id'] ?>" name="type" required>
            <option value="entree" <?= $p['type'] === 'entree'  ? 'selected' : '' ?>>Entrée</option>
            <option value="plat"   <?= $p['type'] === 'plat'    ? 'selected' : '' ?>>Plat</option>
            <option value="dessert"<?= $p['type'] === 'dessert' ? 'selected' : '' ?>>Dessert</option>
          </select>
        </div>
        <div class="ee-form-group">
          <label for="plat-desc-<?= $p['plat_id'] ?>">Description</label>
          <textarea id="plat-desc-<?= $p['plat_id'] ?>" name="description" rows="2"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
        </div>
        <div class="ee-form-group">
          <label>Allergènes</label>
          <div class="ee-checkboxes">
            <?php foreach ($allergenes as $al): ?>
              <label class="ee-checkbox-label">
                <input type="checkbox" name="allergene_ids[]" value="<?= $al['allergene_id'] ?>"
                       <?= in_array((string)$al['allergene_id'], $plat_aid_arr) ? 'checked' : '' ?>>
                <?= htmlspecialchars($al['libelle']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="ee-form-group">
          <label for="plat-image-<?= $p['plat_id'] ?>">Photo du plat</label>
          <?php if (!empty($p['image'])): ?>
            <img src="../../<?= htmlspecialchars($p['image']) ?>" alt="Photo actuelle"
                 style="max-height:80px;border-radius:4px;display:block;margin-bottom:.4rem;">
          <?php endif; ?>
          <input type="file" id="plat-image-<?= $p['plat_id'] ?>" name="image" accept="image/*" class="form-control">
          <small class="text-muted">Laisser vide pour conserver la photo existante</small>
        </div>
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary"
                  data-modal="modal-modifier-plat-<?= $p['plat_id'] ?>">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <div class="ee-modal-overlay" id="modal-supprimer-plat-<?= $p['plat_id'] ?>" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal">
      <div class="ee-modal-header">
        <h2>Supprimer le plat</h2>
        <button class="ee-modal-close" data-modal="modal-supprimer-plat-<?= $p['plat_id'] ?>" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <p class="ee-modal-info">
        Supprimer <strong><?= htmlspecialchars($p['nom']) ?></strong> ? Cette action est irréversible.
      </p>
      <form action="index.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="supprimer_plat">
        <input type="hidden" name="plat_id" value="<?= $p['plat_id'] ?>">
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary"
                  data-modal="modal-supprimer-plat-<?= $p['plat_id'] ?>">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-danger">Supprimer</button>
        </div>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- ============================================================
       MODALS — Horaires
  ============================================================ -->
  <?php foreach ($horaires as $h): ?>
  <div class="ee-modal-overlay" id="modal-horaire-<?= $h['horaire_id'] ?>" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal">
      <div class="ee-modal-header">
        <h2>Modifier — <?= htmlspecialchars($h['jour']) ?></h2>
        <button class="ee-modal-close" data-modal="modal-horaire-<?= $h['horaire_id'] ?>" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <form action="index.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="modifier_horaire">
        <input type="hidden" name="horaire_id" value="<?= $h['horaire_id'] ?>">
        <div class="ee-form-row">
          <div class="ee-form-group">
            <label for="ouv-<?= $h['horaire_id'] ?>">Ouverture</label>
            <input type="time" id="ouv-<?= $h['horaire_id'] ?>" name="heure_ouverture"
                   value="<?= htmlspecialchars($h['heure_ouverture'] ?? '') ?>"
                   <?= $h['ferme'] ? 'disabled' : '' ?>>
          </div>
          <div class="ee-form-group">
            <label for="ferm-<?= $h['horaire_id'] ?>">Fermeture</label>
            <input type="time" id="ferm-<?= $h['horaire_id'] ?>" name="heure_fermeture"
                   value="<?= htmlspecialchars($h['heure_fermeture'] ?? '') ?>"
                   <?= $h['ferme'] ? 'disabled' : '' ?>>
          </div>
        </div>
        <div class="ee-form-group">
          <label class="ee-checkbox-label">
            <input type="checkbox" name="ferme" id="ferme-<?= $h['horaire_id'] ?>"
                   <?= $h['ferme'] ? 'checked' : '' ?>
                   onchange="this.form.querySelectorAll('input[type=time]').forEach(i=>i.disabled=this.checked)">
            Fermé ce jour
          </label>
        </div>
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary"
                  data-modal="modal-horaire-<?= $h['horaire_id'] ?>">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- ============================================================
       MODAL — Ajouter employé
  ============================================================ -->
  <div class="ee-modal-overlay" id="modal-ajouter-employe" style="display:none" role="dialog" aria-modal="true">
    <div class="ee-modal">
      <div class="ee-modal-header">
        <h2>Ajouter un employé</h2>
        <button class="ee-modal-close" data-modal="modal-ajouter-employe" aria-label="Fermer">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
      <p class="ee-modal-info">
        Un email de notification sera envoyé à l'employé. Le mot de passe ne lui sera pas communiqué — il devra se rapprocher de l'administrateur pour l'obtenir.
      </p>
      <form action="index.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="creer_employe">
        <div class="ee-form-row">
          <div class="ee-form-group">
            <label for="employe-prenom">Prénom</label>
            <input type="text" id="employe-prenom" name="prenom" placeholder="Julie">
          </div>
          <div class="ee-form-group">
            <label for="employe-nom">Nom</label>
            <input type="text" id="employe-nom" name="nom" placeholder="Martin">
          </div>
        </div>
        <div class="ee-form-group">
          <label for="employe-email">Adresse email (identifiant)</label>
          <input type="email" id="employe-email" name="email"
                 placeholder="prenom.nom@vite-et-gourmand.fr" required>
        </div>
        <div class="ee-form-group">
          <label for="employe-password">Mot de passe temporaire</label>
          <input type="password" id="employe-password" name="password"
                 placeholder="••••••••" minlength="8" required>
        </div>
        <div class="ee-modal-footer">
          <button type="button" class="ee-btn ee-btn-secondary" data-modal="modal-ajouter-employe">Annuler</button>
          <button type="submit" class="ee-btn ee-btn-primary">Créer le compte</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script>
    document.querySelectorAll('[data-modal]').forEach(el => {
      el.addEventListener('click', () => {
        const modal = document.getElementById(el.dataset.modal);
        if (!modal) return;
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
      });
    });

    document.querySelectorAll('.ee-modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.style.display = 'none';
      });
    });

    <?php if (!empty($mongo_commandes_par_menu)): ?>
    new Chart(document.getElementById('chart-commandes'), {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($mongo_commandes_par_menu, 'menu'), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          label: 'Commandes',
          data: <?= json_encode(array_column($mongo_commandes_par_menu, 'count')) ?>,
          backgroundColor: '#b8860b',
          borderRadius: 6,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
      }
    });
    <?php endif; ?>

    <?php if (!empty($mongo_ca_par_menu)): ?>
    new Chart(document.getElementById('chart-ca'), {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($mongo_ca_par_menu, 'menu'), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          label: 'CA (€)',
          data: <?= json_encode(array_column($mongo_ca_par_menu, 'ca')) ?>,
          backgroundColor: '#2c7a3a',
          borderRadius: 6,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>
