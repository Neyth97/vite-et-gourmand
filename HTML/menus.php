<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';

$stmt = getPDO()->query(
    'SELECT m.menu_id, m.titre, m.description, m.nombre_personne_minimum,
            m.prix_par_personne, m.quantite_restante,
            t.libelle AS theme, r.libelle AS regime,
            (SELECT chemin FROM menu_image WHERE menu_id = m.menu_id ORDER BY ordre ASC LIMIT 1) AS image
     FROM menu m
     JOIN theme t ON m.theme_id = t.theme_id
     JOIN regime r ON m.regime_id = r.regime_id
     WHERE m.actif = 1
     ORDER BY m.menu_id ASC'
);
$menus = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menus | Vite & Gourmand</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- CSS global (navbar, footer) -->
  <link rel="stylesheet" href="../CSS/style.css">
  <!-- CSS page menus -->
  <link rel="stylesheet" href="../CSS/menus.css">
</head>
<body>

  <a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>

  <header>
    <nav class="navbar navbar-expand-lg">
      <div class="container-fluid">

        <!-- Logo -->
        <a class="navbar-brand" href="index.html">
          <img src="../assets/img/logo.svg" alt="Logo Vite & Gourmand" height="45">
          <div class="brand-text">
            Vite & Gourmand
            <span class="brand-subtitle">Traiteur professionnel</span>
          </div>
        </a>

        <!-- Bouton hamburger (mobile) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Ouvrir le menu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Liens + menus active -->
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

    <!-- Bannière -->
    <section id="menus-banner">
      <div class="menus-banner-inner">
        <p class="menus-banner-label">Notre carte</p>
        <h1>Nos <em>Menus</em></h1>
        <span class="menus-banner-underline"></span>
        <p class="menus-banner-desc">Formules classiques, festives ou sur-mesure — pour chaque occasion, une table à votre image.</p>
      </div>
    </section>

    <!-- Filtres -->
    <section id="menus-filtres">
      <form id="filtres-form" novalidate>

        <div class="row g-3 align-items-end">

          <!-- Thème -->
          <div class="col-lg-2 col-md-4 col-sm-6">
            <label class="filtres-label" for="filtre-theme">Thème</label>
            <select class="filtres-select form-select" id="filtre-theme" name="theme">
              <option value="">Tous les thèmes</option>
              <option value="classique">Classique</option>
              <option value="noel">Noël</option>
              <option value="paques">Pâques</option>
              <option value="evenement">Événement</option>
            </select>
          </div>

          <!-- Régime -->
          <div class="col-lg-2 col-md-4 col-sm-6">
            <label class="filtres-label" for="filtre-regime">Régime</label>
            <select class="filtres-select form-select" id="filtre-regime" name="regime">
              <option value="">Tous les régimes</option>
              <option value="classique">Classique</option>
              <option value="vegetarien">Végétarien</option>
              <option value="vegan">Vegan</option>
            </select>
          </div>

          <!-- Prix minimum -->
          <div class="col-lg-2 col-md-4 col-sm-6">
            <label class="filtres-label" for="filtre-prix-min">Prix min. (€)</label>
            <input class="filtres-input form-control" type="number" id="filtre-prix-min" name="prix-min" min="0" placeholder="Ex : 100">
          </div>

          <!-- Prix maximum -->
          <div class="col-lg-2 col-md-4 col-sm-6">
            <label class="filtres-label" for="filtre-prix-max">Prix max. (€)</label>
            <input class="filtres-input form-control" type="number" id="filtre-prix-max" name="prix-max" min="0" placeholder="Ex : 300">
          </div>

          <!-- Nombre de personnes minimum -->
          <div class="col-lg-2 col-md-4 col-sm-6">
            <label class="filtres-label" for="filtre-personnes">Personnes min.</label>
            <input class="filtres-input form-control" type="number" id="filtre-personnes" name="personnes" min="1" placeholder="Ex : 10">
          </div>

          <!-- Boutons -->
          <div class="col-lg-2 col-md-4 col-sm-6 d-flex gap-2">
            <button type="submit" class="filtres-btn-appliquer" id="btn-filtrer">Filtrer</button>
            <button type="reset" class="filtres-btn-reset" id="btn-reset">Réinitialiser</button>
          </div>

        </div>

      </form>
    </section>

    <!-- Grille des menus -->
    <section id="menus-grille">
      <div class="row g-4" id="menus-liste">

        <?php foreach ($menus as $menu):
          $prix_base = (int)($menu['nombre_personne_minimum'] * $menu['prix_par_personne']);
          $stock_low = $menu['quantite_restante'] <= 3;
          $image     = $menu['image'] ?: 'assets/menus/Menu1.PNG';
        ?>
        <div class="col-lg-4 col-md-6 menu-card-wrapper"
             data-theme="<?= htmlspecialchars($menu['theme']) ?>"
             data-regime="<?= htmlspecialchars($menu['regime']) ?>"
             data-prix="<?= $prix_base ?>"
             data-personnes="<?= (int)$menu['nombre_personne_minimum'] ?>"
             data-stock="<?= (int)$menu['quantite_restante'] ?>">
          <article class="menu-card">
            <div class="menu-card-img-wrapper">
              <img src="../<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($menu['titre']) ?>">
              <span class="menu-card-badge"><?= htmlspecialchars(ucfirst($menu['theme'])) ?></span>
            </div>
            <div class="menu-card-body">
              <h3 class="menu-card-title"><?= htmlspecialchars($menu['titre']) ?></h3>
              <p class="menu-card-desc"><?= htmlspecialchars($menu['description']) ?></p>
              <ul class="menu-card-meta">
                <li><i class="bi bi-people" aria-hidden="true"></i> À partir de <strong><?= (int)$menu['nombre_personne_minimum'] ?> personnes</strong></li>
                <li><i class="bi bi-tag" aria-hidden="true"></i> À partir de <strong><?= number_format($prix_base, 0, ',', ' ') ?> €</strong></li>
                <?php if ($stock_low): ?>
                  <li class="menu-card-stock menu-card-stock--low"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Plus que <strong><?= (int)$menu['quantite_restante'] ?></strong> commandes disponibles</li>
                <?php else: ?>
                  <li class="menu-card-stock"><i class="bi bi-box-seam" aria-hidden="true"></i> <strong><?= (int)$menu['quantite_restante'] ?></strong> commandes disponibles</li>
                <?php endif; ?>
                <li class="menu-card-allergenes"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Contient des allergènes</li>
              </ul>
              <a href="menu-detail.php?id=<?= (int)$menu['menu_id'] ?>" class="menu-card-btn">Voir le détail</a>
            </div>
          </article>
        </div>
        <?php endforeach; ?>

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

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>
  <script src="../JS/menus.js"></script>

</body>
</html>
