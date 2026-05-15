<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';

$avis = [];
try {
    $stmt = getPDO()->query('
        SELECT a.note, a.commentaire, u.prenom, u.nom, m.titre AS menu
        FROM avis a
        JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id
        JOIN commande   c ON c.commande_id     = a.commande_id
        JOIN menu       m ON m.menu_id         = c.menu_id
        WHERE a.statut = \'valide\'
        ORDER BY a.avis_id DESC
        LIMIT 6
    ');
    $avis = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vite & Gourmand | Traiteur Bordeaux</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- CSS custom -->
  <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

  <a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>

  <header>
    <nav class="navbar navbar-expand-lg">
      <div class="container-fluid">

        <!-- Logo -->
        <a class="navbar-brand" href="index.php">
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

        <!-- Liens + bouton connexion -->
        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav ms-auto align-items-center gap-3">
            <li class="nav-item"><a class="nav-link active" href="index.php">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="menus.php">Menus</a></li>
            <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="a-propos.html">À propos</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
          </ul>
          <?php if (isConnecte()): ?>
            <?php if (isAdmin()):   $espace = 'espace-admin/index.php';
            elseif (isEmploye()): $espace = 'espace-employe/index.php';
            else:                 $espace = 'espace-utilisateur/index.php'; endif; ?>
            <a class="btn btn-connexion ms-4" href="<?= $espace ?>">Mon espace</a>
          <?php else: ?>
            <a class="btn btn-connexion ms-4" href="connexion.php">Connexion</a>
          <?php endif; ?>
        </div>

      </div>
    </nav>
  </header>

  <main id="contenu-principal">

    <!-- Hero -->
    <section id="hero">
      <div class="row g-0">
        <div class="col-lg-6">
          <p class="hero-label">Bordeaux & Gironde</p>
          <h1>Vite livré,<br>Toujours<br><em>Gourmand.</em></h1>
          <div class="hero-separator"><span class="hero-separator-dot"></span></div>
          <p>Plateaux repas, cocktails prestige et événements sur-mesure pour tous. Une seule adresse pour l'excellence culinaire.</p>
          <div class="hero-btns">
            <a class="btn-devis" href="contact.php">Demander un devis</a>
            <a class="btn-menus" href="menus.php">Nos menus &rarr;</a>
          </div>
        </div>
        <div class="col-lg-6">
          <img src="../assets/img/Image1.PNG" alt="Chef Vite & Gourmand">
          <div class="hero-badge">
            <p>+130</p>
            <span>Événements / an</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Bande de chiffres clés -->
    <section id="stats">
      <div class="row g-0">
        <div class="col-6 col-lg-3 stat-item">
          <p class="stat-name">Julie & José</p>
          <span class="stat-label">Fondateurs</span>
        </div>
        <div class="col-6 col-lg-3 stat-item">
          <p class="stat-name">Bordeaux</p>
          <span class="stat-label">& Gironde</span>
        </div>
        <div class="col-6 col-lg-3 stat-item">
          <p class="stat-number">+130</p>
          <span class="stat-label">Événements / an</span>
        </div>
        <div class="col-6 col-lg-3 stat-item">
          <p class="stat-number">+25</p>
          <span class="stat-label">Ans d'expérience</span>
        </div>
      </div>
    </section>

    <!-- Services -->
    <section id="services">
      <div class="services-header">
        <p class="services-label">Ce que nous proposons</p>
        <h2>Nos Services</h2>
        <span class="services-underline"></span>
      </div>

      <article class="service-item">
        <div class="row g-0">
          <div class="col-lg-5">
            <div class="service-img-wrapper">
              <img src="../assets/img/Image2.PNG" alt="Plateaux & Buffets">
            </div>
          </div>
          <div class="col-lg-7 service-text">
            <h3>Plateaux & Buffets</h3>
            <p>Buffets raffinés et plateaux repas pour tous vos moments — particuliers, collectivités, équipes ou événements familiaux.</p>
            <a href="services.html" class="service-link">Découvrir &rarr;</a>
          </div>
        </div>
        <hr class="service-separator">
      </article>

      <article class="service-item">
        <div class="row g-0">
          <div class="col-lg-7 service-text order-lg-1">
            <h3>Cocktails & Réceptions</h3>
            <p>Mise en place complète, service à table et personnel qualifié pour vos réceptions privées ou professionnelles.</p>
            <a href="services.html" class="service-link">Découvrir &rarr;</a>
          </div>
          <div class="col-lg-5 order-lg-2">
            <div class="service-img-wrapper service-img-right">
              <img src="../assets/img/Image3.PNG" alt="Cocktails & Réceptions">
            </div>
          </div>
        </div>
        <hr class="service-separator">
      </article>

      <article class="service-item">
        <div class="row g-0">
          <div class="col-lg-5">
            <div class="service-img-wrapper">
              <img src="../assets/img/Image5.PNG" alt="Événements sur-mesure">
            </div>
          </div>
          <div class="col-lg-7 service-text">
            <h3>Événements sur-mesure</h3>
            <p>Galas, séminaires, mariages, anniversaires — conception et logistique complètes pour que vous profitiez pleinement.</p>
            <a href="services.html" class="service-link">Découvrir &rarr;</a>
          </div>
        </div>
      </article>
    </section>

    <!-- À propos -->
    <section id="a-propos">
      <div class="row g-0">
        <div class="col-lg-5">
          <div class="apropos-img-wrapper">
            <img src="../assets/img/Image4.PNG" alt="Julie et José en cuisine">
          </div>
        </div>
        <div class="col-lg-7 apropos-text">
          <p class="apropos-label">Notre histoire</p>
          <h2>Julie & José,<br>aux fourneaux<br>depuis <span class="apropos-year">1995</span></h2>
          <span class="apropos-underline"></span>
          <p>Tout a commencé avec une passion commune pour la gastronomie bordelaise. Aujourd'hui, Julie et José mettent leur savoir-faire à votre service pour chaque prestation.</p>
          <p>Produits locaux, traçabilité garantie, cuisines certifiées HACCP. Ponctualité garantie en Gironde.</p>
          <div class="apropos-stats">
            <div class="apropos-stat"><strong>Julie</strong><span>Cuisinière</span></div>
            <div class="apropos-stat"><strong>José</strong><span>Cuisinier</span></div>
            <div class="apropos-stat"><strong>+25</strong><span>Ans d'exp.</span></div>
          </div>
          <a href="a-propos.html" class="btn-apropos">Notre histoire &rarr;</a>
        </div>
      </div>
    </section>

    <!-- Témoignages -->
    <section id="temoignages">
      <div class="temoignages-header">
        <p class="temoignages-label">Ils nous font confiance</p>
        <h2>Témoignages</h2>
        <span class="temoignages-underline"></span>
      </div>

      <?php if (!empty($avis)): ?>

        <div id="carouselAvis" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
          <div class="carousel-inner">
            <?php foreach ($avis as $i => $a): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
              <blockquote class="temoignage-card temoignage-card-carousel">
                <span class="temoignage-quote">&ldquo;</span>
                <div class="temoignage-stars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="bi bi-star<?= $s <= (int)$a['note'] ? '-fill' : '' ?>"></i>
                  <?php endfor; ?>
                </div>
                <p><?= htmlspecialchars($a['commentaire']) ?></p>
                <cite>
                  <strong><?= htmlspecialchars($a['prenom'] . ' ' . mb_strtoupper(mb_substr($a['nom'], 0, 1)) . '.') ?></strong>
                  <span><?= htmlspecialchars($a['menu']) ?></span>
                </cite>
              </blockquote>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if (count($avis) > 1): ?>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselAvis" data-bs-slide="prev" aria-label="Précédent">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselAvis" data-bs-slide="next" aria-label="Suivant">
            <span class="carousel-control-next-icon"></span>
          </button>
          <?php endif; ?>
        </div>

      <?php else: ?>

        <div class="row g-4">
          <div class="col-lg-6">
            <blockquote class="temoignage-card">
              <span class="temoignage-quote">&ldquo;</span>
              <p>Prestation irréprochable pour notre gala annuel de 800 personnes. Ponctualité, raffinement et générosité — exactement ce qu'on attendait.</p>
              <cite>
                <strong>Sophie M.</strong>
                <span>Resp. événements · Mairie de Bordeaux</span>
              </cite>
            </blockquote>
          </div>
          <div class="col-lg-6">
            <blockquote class="temoignage-card">
              <span class="temoignage-quote">&ldquo;</span>
              <p>Nos équipes plébiscitent les menus chaque semaine. La qualité est constante, le service impeccable. Un vrai partenaire de confiance.</p>
              <cite>
                <strong>Julien P.</strong>
                <span>Directeur Général · Tech Startup</span>
              </cite>
            </blockquote>
          </div>
        </div>

      <?php endif; ?>
    </section>

    <!-- Appel à l'action -->
    <section id="cta">
      <div class="cta-inner">
        <img src="../assets/img/logo.svg" alt="Logo Vite & Gourmand" class="cta-logo">
        <h2>Prêt à commander ?</h2>
        <p>Obtenez votre devis personnalisé en moins de 24 heures.</p>
        <div class="cta-btns">
          <a href="contact.php" class="btn-devis">Demander un devis</a>
          <a href="menus.php" class="btn-menus">Voir les menus &rarr;</a>
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
            <li><a href="index.php">Accueil</a></li>
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
  <!-- JS custom -->
  <script src="../JS/main.js"></script>

</body>
</html>
