<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';
require_once '../PHP/includes/mailer.php';

if (isConnecte()) {
    header('Location: espace-utilisateur/index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erreur = '';
$succes = '';
$vals   = ['prenom' => '', 'nom' => '', 'email' => '', 'gsm' => '', 'adresse' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $erreur = 'Requête invalide. Veuillez réessayer.';
    } else {
        $prenom           = trim($_POST['prenom'] ?? '');
        $nom              = trim($_POST['nom'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $gsm              = trim($_POST['gsm'] ?? '');
        $adresse          = trim($_POST['adresse'] ?? '');
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        $vals = [
            'prenom'  => htmlspecialchars($prenom),
            'nom'     => htmlspecialchars($nom),
            'email'   => htmlspecialchars($email),
            'gsm'     => htmlspecialchars($gsm),
            'adresse' => htmlspecialchars($adresse),
        ];

        if (empty($prenom) || empty($nom) || empty($email) || empty($gsm) || empty($adresse) || empty($password)) {
            $erreur = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse email invalide.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $password)) {
            $erreur = 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
        } elseif ($password !== $password_confirm) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } elseif (empty($_POST['cgv'])) {
            $erreur = 'Vous devez accepter les conditions générales de vente.';
        } else {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erreur = 'Cette adresse email est déjà utilisée.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO utilisateur (email, password, nom, prenom, telephone, adresse, role_id) VALUES (?, ?, ?, ?, ?, ?, 3)');
                $stmt->execute([$email, $hash, $nom, $prenom, $gsm, $adresse]);

                mailBienvenue($email, $prenom, $nom);

                $succes = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
                $vals   = ['prenom' => '', 'nom' => '', 'email' => '', 'gsm' => '', 'adresse' => ''];
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
  <title>Créer un compte | Vite & Gourmand</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- CSS global (navbar, footer) -->
  <link rel="stylesheet" href="../CSS/style.css">
  <!-- CSS page inscription -->
  <link rel="stylesheet" href="../CSS/inscription.css">
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
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            <li class="nav-item"><a class="nav-link active" href="connexion.php">Connexion</a></li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <main id="contenu-principal">
    <div class="inscription-wrapper">

      <!-- Colonne gauche : image -->
      <div class="inscription-img-col">
        <img src="../assets/img/Image6.PNG" alt="Cuisine Vite & Gourmand" class="inscription-img">
        <div class="inscription-img-overlay">
          <h1>Rejoignez<br>l'expérience<br><em>Gourmande.</em></h1>
        </div>
      </div>

      <!-- Colonne droite : formulaire -->
      <div class="inscription-form-col">
        <div class="inscription-form-inner">

          <span class="inscription-badge">Espace client</span>
          <h2 class="inscription-title">Créer un compte</h2>
          <p class="inscription-subtitle">Rejoignez-nous et commandez en toute simplicité.</p>

          <?php if ($erreur): ?>
            <div class="alert alert-danger" role="alert"><?= $erreur ?></div>
          <?php endif; ?>
          <?php if ($succes): ?>
            <div class="alert alert-success" role="alert">
              <?= $succes ?> <a href="connexion.php" class="link-gold">Se connecter</a>
            </div>
          <?php endif; ?>

          <form action="inscription.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Prénom + Nom -->
            <div class="inscription-name-row">
              <div class="form-group-custom">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" placeholder="Jean" class="input-custom" value="<?= $vals['prenom'] ?>" required>
              </div>
              <div class="form-group-custom">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" placeholder="Dupont" class="input-custom" value="<?= $vals['nom'] ?>" required>
              </div>
            </div>

            <!-- Email -->
            <div class="form-group-custom">
              <label for="email">Adresse email</label>
              <input type="email" id="email" name="email" placeholder="exemple@email.com" class="input-custom" value="<?= $vals['email'] ?>" required>
            </div>

            <!-- Téléphone (GSM) -->
            <div class="form-group-custom">
              <label for="gsm">Numéro de téléphone</label>
              <input type="tel" id="gsm" name="gsm" placeholder="+33 6 00 00 00 00" class="input-custom" value="<?= $vals['gsm'] ?>" required>
            </div>

            <!-- Adresse postale -->
            <div class="form-group-custom">
              <label for="adresse">Adresse postale</label>
              <input type="text" id="adresse" name="adresse" placeholder="12 rue des Fleurs, 33000 Bordeaux" class="input-custom" value="<?= $vals['adresse'] ?>" required>
            </div>

            <!-- Mot de passe -->
            <div class="form-group-custom">
              <label for="password">Mot de passe</label>
              <input type="password" id="password" name="password" placeholder="••••••••" class="input-custom" required aria-describedby="password-hint">
              <p class="password-hint" id="password-hint">10 caractères minimum — majuscule, minuscule, chiffre et caractère spécial requis.</p>
            </div>

            <!-- Confirmer le mot de passe -->
            <div class="form-group-custom">
              <label for="password-confirm">Confirmer le mot de passe</label>
              <input type="password" id="password-confirm" name="password_confirm" placeholder="••••••••" class="input-custom" required>
            </div>

            <!-- CGV -->
            <div class="form-check-custom">
              <input type="checkbox" id="cgv" name="cgv" class="check-custom" required>
              <label for="cgv">J'accepte les <a href="cgv.html" class="link-gold">conditions générales de vente</a></label>
            </div>

            <!-- Bouton inscription -->
            <button type="submit" class="btn-submit">Créer mon compte</button>

            <!-- Lien connexion -->
            <p class="inscription-login">
              Déjà un compte ? <a href="connexion.php" class="link-gold">Se connecter</a>
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
