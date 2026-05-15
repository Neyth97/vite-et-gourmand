<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';
require_once '../PHP/includes/mailer.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message      = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message      = 'Requête invalide. Veuillez réessayer.';
        $message_type = 'danger';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ? AND actif = 1');
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            if ($u) {
                $token  = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $pdo->prepare('UPDATE utilisateur SET token_reset = ?, token_reset_expire = ? WHERE utilisateur_id = ?')
                    ->execute([$token, $expiry, $u['utilisateur_id']]);

                $su = $pdo->prepare('SELECT prenom, nom FROM utilisateur WHERE utilisateur_id = ?');
                $su->execute([$u['utilisateur_id']]);
                $info = $su->fetch();
                mailResetPassword($email, $info['prenom'] ?? '', $info['nom'] ?? '', $token);
            }
        }

        // Message neutre : ne pas révéler si l'email existe en BDD
        $message      = 'Si cette adresse email est associée à un compte, vous recevrez un lien de réinitialisation dans quelques minutes.';
        $message_type = 'success';
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié | Vite & Gourmand</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- CSS global (navbar, footer) -->
  <link rel="stylesheet" href="../CSS/style.css">
  <!-- CSS page mot de passe oublié -->
  <link rel="stylesheet" href="../CSS/mot-de-passe-oublie.css">
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

        <!-- Liens -->
        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav ms-auto align-items-center gap-3">
            <li class="nav-item"><a class="nav-link" href="index.html">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="menus.php">Menus</a></li>
            <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="a-propos.html">À propos</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            <li class="nav-item"><a class="nav-link active" href="connexion.php">Connexion</a></li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <main id="contenu-principal">
    <div class="mdp-wrapper">

      <!-- Colonne gauche : image -->
      <div class="mdp-img-col">
        <img src="../assets/img/Image6.PNG" alt="Cuisine Vite & Gourmand" class="mdp-img">
        <div class="mdp-img-overlay">
          <h1>Un oubli ?<br>Ça <em>arrive.</em></h1>
        </div>
      </div>

      <!-- Colonne droite : formulaire -->
      <div class="mdp-form-col">
        <div class="mdp-form-inner">

          <span class="mdp-badge">Espace client</span>
          <h2 class="mdp-title">Mot de passe oublié</h2>
          <p class="mdp-subtitle">Renseignez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>

          <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>" role="alert"><?= $message ?></div>
          <?php endif; ?>

          <form action="mot-de-passe-oublie.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group-custom">
              <label for="email">Adresse email</label>
              <input type="email" id="email" name="email" placeholder="exemple@email.com" class="input-custom" required>
            </div>

            <button type="submit" class="btn-submit">Envoyer le lien</button>

          </form>

          <p class="mdp-back">
            <a href="connexion.php" class="link-gold"><i class="bi bi-arrow-left"></i> Retour à la connexion</a>
          </p>

        </div>
      </div>

    </div>
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
  <!-- JS custom -->
  <script src="../JS/main.js"></script>

</body>
</html>
