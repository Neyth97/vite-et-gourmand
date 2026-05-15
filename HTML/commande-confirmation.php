<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';

requireConnexion();

$pdo = getPDO();

$commande_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$commande_id) {
    header('Location: menus.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT c.*, m.titre AS menu_titre, m.conditions AS menu_conditions
     FROM commande c
     JOIN menu m ON c.menu_id = m.menu_id
     WHERE c.commande_id = ? AND c.utilisateur_id = ?'
);
$stmt->execute([$commande_id, $_SESSION['utilisateur_id']]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: menus.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Commande confirmée | Vite & Gourmand</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style.css">
  <link rel="stylesheet" href="../CSS/commande.css">
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

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                aria-controls="navMenu" aria-expanded="false" aria-label="Ouvrir le menu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav ms-auto align-items-center gap-3">
            <li class="nav-item"><a class="nav-link" href="index.html">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="menus.php">Menus</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            <li class="nav-item">
              <a class="nav-link" href="espace-utilisateur/index.php">
                <i class="bi bi-person-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($_SESSION['prenom'] ?? 'Mon espace') ?>
              </a>
            </li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <main id="contenu-principal">

    <section id="confirmation-header">
      <div class="confirmation-icon" aria-hidden="true">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <h1>Commande confirmée !</h1>
      <p>Merci <?= htmlspecialchars($_SESSION['prenom'] ?? '') ?>, votre réservation a bien été enregistrée.</p>
      <p class="confirmation-numero">
        N° de commande : <strong><?= htmlspecialchars($commande['numero_commande']) ?></strong>
      </p>
    </section>

    <section id="confirmation-content">

      <!-- Récapitulatif -->
      <div class="commande-section">
        <div class="commande-section-header">
          <span class="commande-step" aria-hidden="true"><i class="bi bi-receipt"></i></span>
          <h2>Récapitulatif</h2>
        </div>

        <div class="recap-ligne recap-menu">
          <span class="recap-label">Menu</span>
          <span class="recap-value"><?= htmlspecialchars($commande['menu_titre']) ?></span>
        </div>
        <div class="recap-ligne">
          <span class="recap-label">Personnes</span>
          <span class="recap-value"><?= (int)$commande['nombre_personne'] ?></span>
        </div>
        <div class="recap-ligne">
          <span class="recap-label">Date de la prestation</span>
          <span class="recap-value">
            <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
            à <?= htmlspecialchars($commande['heure_livraison']) ?>
          </span>
        </div>
        <div class="recap-ligne">
          <span class="recap-label">Lieu</span>
          <span class="recap-value">
            <?= htmlspecialchars($commande['adresse_prestation']) ?>,
            <?= htmlspecialchars($commande['code_postal_prestation']) ?>
            <?= htmlspecialchars($commande['ville_prestation']) ?>
          </span>
        </div>

        <div class="recap-separateur"></div>

        <div class="recap-ligne">
          <span class="recap-label">Sous-total menu</span>
          <span class="recap-value"><?= number_format((float)$commande['prix_menu'], 2, ',', ' ') ?> €</span>
        </div>
        <div class="recap-ligne">
          <span class="recap-label">Livraison</span>
          <span class="recap-value">
            <?= (float)$commande['prix_livraison'] > 0
                ? number_format((float)$commande['prix_livraison'], 2, ',', ' ') . ' €'
                : 'Incluse (Bordeaux)' ?>
          </span>
        </div>

        <div class="recap-separateur"></div>

        <div class="recap-ligne recap-total">
          <span class="recap-label">Total TTC</span>
          <span class="recap-value"><?= number_format((float)$commande['prix_total'], 2, ',', ' ') ?> €</span>
        </div>
      </div>

      <!-- Conditions si présentes -->
      <?php if ($commande['menu_conditions']): ?>
      <div class="cmd-conditions-bloc">
        <p class="cmd-conditions-titre">
          <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
          Conditions de ce menu — à conserver
        </p>
        <p><?= htmlspecialchars($commande['menu_conditions']) ?></p>
      </div>
      <?php endif; ?>

      <!-- Actions -->
      <div class="confirmation-actions">
        <a href="espace-utilisateur/index.php" class="btn-confirmation-primary">
          <i class="bi bi-person-circle" aria-hidden="true"></i>
          Voir mes commandes
        </a>
        <a href="menus.php" class="btn-confirmation-secondary">
          <i class="bi bi-grid" aria-hidden="true"></i>
          Retour aux menus
        </a>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>

</body>
</html>
