<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';

if (isConnecte()) {
    if (isAdmin())   { header('Location: espace-admin/index.php');       exit; }
    if (isEmploye()) { header('Location: espace-employe/index.php');     exit; }
                       header('Location: espace-utilisateur/index.php'); exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erreur    = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $erreur = 'Requête invalide. Veuillez réessayer.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $email_val = htmlspecialchars($email);

        if (empty($email) || empty($password)) {
            $erreur = 'Veuillez remplir tous les champs.';
        } else {
            $stmt = getPDO()->prepare('SELECT utilisateur_id, nom, prenom, email, password, role_id, actif FROM utilisateur WHERE email = ?');
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            if (!$u || !password_verify($password, $u['password'])) {
                $erreur = 'Email ou mot de passe incorrect.';
            } elseif (!$u['actif']) {
                $erreur = 'Ce compte a été désactivé. Contactez l\'administrateur.';
            } else {
                session_regenerate_id(true);
                $_SESSION['utilisateur_id'] = $u['utilisateur_id'];
                $_SESSION['nom']            = $u['nom'];
                $_SESSION['prenom']         = $u['prenom'];
                $_SESSION['email']          = $u['email'];
                $_SESSION['role_id']        = $u['role_id'];

                if (isAdmin())   { header('Location: espace-admin/index.php');       exit; }
                if (isEmploye()) { header('Location: espace-employe/index.php');   exit; }
                                   header('Location: espace-utilisateur/index.php'); exit;
            }
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion | Vite & Gourmand</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- CSS global (navbar, footer) -->
  <link rel="stylesheet" href="../CSS/style.css">
  <!-- CSS page connexion -->
  <link rel="stylesheet" href="../CSS/connexion.css">
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

        <!-- Liens + connexion active -->
        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav ms-auto align-items-center gap-3">
            <li class="nav-item"><a class="nav-link" href="index.html">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="menus.php">Menus</a></li>
            <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="a-propos.html">À propos</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.html">Contact</a></li>
            <li class="nav-item"><a class="nav-link active" href="connexion.php">Connexion</a></li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <main id="contenu-principal">
    <div class="connexion-wrapper">

      <!-- Colonne gauche : image -->
      <div class="connexion-img-col">
        <img src="../assets/img/Image6.PNG" alt="Cuisine Vite & Gourmand" class="connexion-img">
        <div class="connexion-img-overlay">
          <h1>Vite livré,<br>Toujours<br><em>Gourmand.</em></h1>
        </div>
      </div>

      <!-- Colonne droite : formulaire -->
      <div class="connexion-form-col">
        <div class="connexion-form-inner">

          <span class="connexion-badge">Espace client</span>
          <h2 class="connexion-title">Connexion</h2>
          <p class="connexion-subtitle">Accédez à votre espace personnel.</p>

          <?php if ($erreur): ?>
            <div class="alert alert-danger" role="alert"><?= $erreur ?></div>
          <?php endif; ?>

          <form action="connexion.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Email -->
            <div class="form-group-custom">
              <label for="email">Adresse email</label>
              <input type="email" id="email" name="email" placeholder="exemple@email.com" class="input-custom" value="<?= $email_val ?>" required>
            </div>

            <!-- Mot de passe -->
            <div class="form-group-custom">
              <div class="form-label-row">
                <label for="password">Mot de passe</label>
                <a href="mot-de-passe-oublie.php" class="link-gold">Mot de passe oublié ?</a>
              </div>
              <input type="password" id="password" name="password" placeholder="••••••••" class="input-custom" required>
            </div>

            <!-- Se souvenir de moi -->
            <div class="form-check-custom">
              <input type="checkbox" id="remember" name="remember" class="check-custom">
              <label for="remember">Se souvenir de moi</label>
            </div>

            <!-- Bouton connexion -->
            <button type="submit" class="btn-submit">Se connecter</button>

            <!-- Lien inscription -->
            <p class="connexion-register">
              Pas encore de compte ? <a href="inscription.php" class="link-gold">Créer un compte</a>
            </p>

          </form>
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
            <li><a href="contact.html">Contact</a></li>
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
