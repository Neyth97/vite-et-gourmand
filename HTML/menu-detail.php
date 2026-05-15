<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: menus.php');
    exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT m.*, t.libelle AS theme, r.libelle AS regime
     FROM menu m
     JOIN theme t ON m.theme_id = t.theme_id
     JOIN regime r ON m.regime_id = r.regime_id
     WHERE m.menu_id = ? AND m.actif = 1'
);
$stmt->execute([$id]);
$menu = $stmt->fetch();

if (!$menu) {
    header('Location: menus.php');
    exit;
}

$stmt_images = $pdo->prepare('SELECT chemin FROM menu_image WHERE menu_id = ? ORDER BY ordre ASC');
$stmt_images->execute([$id]);
$images = $stmt_images->fetchAll();

$stmt_plats = $pdo->prepare(
    'SELECT p.plat_id, p.nom, p.type, p.description, p.image,
            GROUP_CONCAT(a.libelle ORDER BY a.libelle SEPARATOR \'||\') AS allergenes
     FROM plat p
     JOIN menu_plat mp ON p.plat_id = mp.plat_id
     LEFT JOIN plat_allergene pa ON p.plat_id = pa.plat_id
     LEFT JOIN allergene a ON pa.allergene_id = a.allergene_id
     WHERE mp.menu_id = ?
     GROUP BY p.plat_id, p.nom, p.type, p.description, p.image
     ORDER BY FIELD(p.type, \'entree\', \'plat\', \'dessert\'), p.nom'
);
$stmt_plats->execute([$id]);
$plats = $stmt_plats->fetchAll();

$type_labels = ['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'];
$image_hero  = !empty($images) ? '../' . $images[0]['chemin'] : '../assets/menus/Menu1.PNG';
$prix_base   = $menu['nombre_personne_minimum'] * $menu['prix_par_personne'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($menu['titre']) ?> | Vite & Gourmand</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- CSS global (navbar, footer) -->
  <link rel="stylesheet" href="../CSS/style.css">
  <!-- CSS page détail menu -->
  <link rel="stylesheet" href="../CSS/menu-detail.css">
</head>
<body>

  <a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>

  <header>
    <nav class="navbar navbar-expand-lg">
      <div class="container-fluid">

        <a class="navbar-brand" href="index.html">
          <img src="../assets/img/logo.svg" alt="Logo Vite & Gourmand" height="45">
          <div class="brand-text">
            Vite & Gourmand
            <span class="brand-subtitle">Traiteur professionnel</span>
          </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Ouvrir le menu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav ms-auto align-items-center gap-3">
            <li class="nav-item"><a class="nav-link" href="index.html">Accueil</a></li>
            <li class="nav-item"><a class="nav-link active" href="menus.php">Menus</a></li>
            <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="a-propos.html">À propos</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            <li class="nav-item"><a class="nav-link" href="connexion.php">Connexion</a></li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <main id="contenu-principal">

    <!-- Retour aux menus -->
    <section id="detail-breadcrumb">
      <a href="menus.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Retour aux menus</a>
    </section>

    <!-- Image principale -->
    <section id="detail-hero">
      <div id="detail-hero-img">
        <img src="<?= htmlspecialchars($image_hero) ?>" alt="<?= htmlspecialchars($menu['titre']) ?>">
      </div>
      <div id="detail-hero-content">
        <span class="detail-badge-theme"><?= htmlspecialchars(ucfirst($menu['theme'])) ?></span>
        <span class="detail-badge-regime"><?= htmlspecialchars(ucfirst($menu['regime'])) ?></span>
        <h1><?= htmlspecialchars($menu['titre']) ?></h1>
      </div>
    </section>

    <!-- Contenu principal -->
    <section id="detail-content">
      <div class="row">

        <!-- Colonne gauche : description, composition, galerie -->
        <div class="col-lg-8">

          <!-- Description -->
          <div class="detail-bloc">
            <h2>À propos de ce menu</h2>
            <p><?= htmlspecialchars($menu['description']) ?></p>
          </div>

          <!-- Composition du menu -->
          <div class="detail-bloc">
            <h2>Composition du menu</h2>

            <?php foreach ($plats as $plat):
              $label     = $type_labels[$plat['type']] ?? ucfirst($plat['type']);
              $allergenes = $plat['allergenes'] ? explode('||', $plat['allergenes']) : [];
            ?>
            <article class="detail-plat">
              <h3>
                <span class="detail-plat-type"><?= htmlspecialchars($label) ?></span>
                <?= htmlspecialchars($plat['nom']) ?>
              </h3>
              <?php if ($plat['image']): ?>
                <div class="detail-plat-img">
                  <img src="../<?= htmlspecialchars($plat['image']) ?>" alt="<?= htmlspecialchars($plat['nom']) ?>">
                </div>
              <?php endif; ?>
              <?php if ($plat['description']): ?>
                <p><?= htmlspecialchars($plat['description']) ?></p>
              <?php endif; ?>
              <div class="detail-allergenes">
                <p><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> <strong>Allergènes :</strong></p>
                <?php if ($allergenes): ?>
                  <ul>
                    <?php foreach ($allergenes as $allergene): ?>
                      <li><?= htmlspecialchars($allergene) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <ul><li>Aucun allergène majeur</li></ul>
                <?php endif; ?>
              </div>
            </article>
            <?php endforeach; ?>

          </div>

          <!-- Galerie -->
          <?php if (count($images) > 1): ?>
          <div class="detail-bloc">
            <h2>Galerie</h2>
            <div id="detail-galerie">
              <?php foreach (array_slice($images, 1) as $img): ?>
                <div class="detail-galerie-img">
                  <img src="../<?= htmlspecialchars($img['chemin']) ?>" alt="<?= htmlspecialchars($menu['titre']) ?>">
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

        </div>

        <!-- Colonne droite : fiche technique, conditions, bouton commander -->
        <div class="col-lg-4">

          <!-- Fiche technique -->
          <div class="detail-bloc detail-fiche">
            <h2>Informations</h2>
            <ul>
              <li>
                <i class="bi bi-tag" aria-hidden="true"></i>
                <span>Prix</span>
                <strong>À partir de <?= number_format($prix_base, 0, ',', ' ') ?> €</strong>
              </li>
              <li>
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>Personnes minimum</span>
                <strong><?= (int)$menu['nombre_personne_minimum'] ?> personnes</strong>
              </li>
              <li>
                <i class="bi bi-palette" aria-hidden="true"></i>
                <span>Thème</span>
                <strong><?= htmlspecialchars(ucfirst($menu['theme'])) ?></strong>
              </li>
              <li>
                <i class="bi bi-leaf" aria-hidden="true"></i>
                <span>Régime</span>
                <strong><?= htmlspecialchars(ucfirst($menu['regime'])) ?></strong>
              </li>
              <li>
                <i class="bi bi-box-seam" aria-hidden="true"></i>
                <span>Stock disponible</span>
                <strong><?= (int)$menu['quantite_restante'] ?> commandes</strong>
              </li>
            </ul>
          </div>

          <!-- Conditions du menu (mis en évidence) -->
          <?php if ($menu['conditions']): ?>
          <div class="detail-conditions" role="alert">
            <h2><i class="bi bi-info-circle-fill" aria-hidden="true"></i> Conditions de commande</h2>
            <p><?= htmlspecialchars($menu['conditions']) ?></p>
          </div>
          <?php endif; ?>

          <!-- Bouton Commander -->
          <div class="detail-cta">
            <?php if (isConnecte()): ?>
              <a href="commande.php?menu_id=<?= (int)$menu['menu_id'] ?>" class="detail-btn-commander">
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                Commander ce menu
              </a>
            <?php else: ?>
              <a href="connexion.php" class="detail-btn-commander">
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                Commander ce menu
              </a>
              <p class="detail-cta-note">Une connexion est requise pour passer commande.</p>
            <?php endif; ?>
          </div>

        </div>

      </div>
    </section>

  </main>

  <footer>
    <div class="footer-main">
      <div class="row">

        <div class="col-lg-4 footer-col">
          <p class="footer-brand">Vite & Gourmand</p>
          <p class="footer-brand-sub">Traiteur professionnel</p>
          <address class="footer-horaires">
            <p><span>Lun-Ven :</span> 7h00 – 21h00</p>
            <p><span>Samedi :</span> 8h00 – 18h00</p>
            <p><span>Dimanche :</span> Fermé</p>
          </address>
        </div>

        <div class="col-lg-4 footer-col">
          <p class="footer-col-title">Navigation</p>
          <ul class="footer-links">
            <li><a href="index.html">Accueil</a></li>
            <li><a href="menus.php">Menus</a></li>
            <li><a href="services.html">Services</a></li>
            <li><a href="a-propos.html">À propos</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
        </div>

        <div class="col-lg-4 footer-col">
          <p class="footer-col-title">Légal</p>
          <ul class="footer-links">
            <li><a href="mentions-legales.html">Mentions légales</a></li>
            <li><a href="cgv.html">CGV</a></li>
          </ul>
        </div>

      </div>
    </div>

    <div class="footer-bottom">
      <small>&copy; 2026 Vite & Gourmand — Bordeaux · Tous droits réservés.</small>
      <ul class="footer-socials">
        <li><a href="#" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a></li>
        <li><a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a></li>
        <li><a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a></li>
      </ul>
    </div>
  </footer>

  <!-- Lightbox galerie -->
  <div id="lightbox" role="dialog" aria-modal="true" aria-label="Image agrandie" hidden>
    <button id="lightbox-close" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
    <img id="lightbox-img" src="" alt="">
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>

</body>
</html>
