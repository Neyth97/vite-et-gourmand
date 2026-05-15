<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo   = getPDO();
$token = trim($_GET['token'] ?? '');

$message      = '';
$message_type = '';
$token_valide = false;
$user         = null;

if (empty($token)) {
    $message      = 'Lien invalide. Veuillez refaire une demande de réinitialisation.';
    $message_type = 'danger';
} else {
    $stmt = $pdo->prepare(
        'SELECT utilisateur_id, prenom, nom, email, token_reset_expire
         FROM utilisateur
         WHERE token_reset = ? AND actif = 1'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message      = 'Lien invalide ou déjà utilisé.';
        $message_type = 'danger';
    } elseif ($user['token_reset_expire'] < date('Y-m-d H:i:s')) {
        $message      = 'Ce lien a expiré. Veuillez refaire une demande.';
        $message_type = 'danger';
    } else {
        $token_valide = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valide) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message      = 'Requête invalide. Veuillez réessayer.';
        $message_type = 'danger';
    } else {
        $password         = $_POST['password']         ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $password)) {
            $message      = 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
            $message_type = 'danger';
        } elseif ($password !== $password_confirm) {
            $message      = 'Les mots de passe ne correspondent pas.';
            $message_type = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare(
                'UPDATE utilisateur SET password = ?, token_reset = NULL, token_reset_expire = NULL
                 WHERE utilisateur_id = ?'
            )->execute([$hash, $user['utilisateur_id']]);

            $token_valide = false;
            $message      = 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.';
            $message_type = 'success';
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
  <title>Réinitialisation du mot de passe | Vite &amp; Gourmand</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style.css">
  <link rel="stylesheet" href="../CSS/mot-de-passe-oublie.css">
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
            <li class="nav-item"><a class="nav-link" href="connexion.php">Connexion</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main id="contenu-principal">
    <div class="mdp-wrapper">

      <div class="mdp-img-col">
        <img src="../assets/img/Image6.PNG" alt="Cuisine Vite & Gourmand" class="mdp-img">
        <div class="mdp-img-overlay">
          <h1>Nouveau<br>mot de<br><em>passe.</em></h1>
        </div>
      </div>

      <div class="mdp-form-col">
        <div class="mdp-form-inner">

          <span class="mdp-badge">Espace client</span>
          <h2 class="mdp-title">Réinitialisation</h2>

          <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>" role="alert"><?= $message ?></div>
          <?php endif; ?>

          <?php if ($message_type === 'success'): ?>
            <p style="text-align:center;margin-top:1rem;">
              <a href="connexion.php" class="btn-submit" style="display:inline-block;text-align:center;">Se connecter</a>
            </p>
          <?php elseif ($token_valide): ?>
            <p class="mdp-subtitle">
              Bonjour <?= htmlspecialchars($user['prenom']) ?>, choisissez votre nouveau mot de passe.
            </p>
            <form action="reinitialiser-mot-de-passe.php?token=<?= urlencode($token) ?>" method="post">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

              <div class="form-group-custom">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" class="input-custom"
                       placeholder="••••••••" required aria-describedby="password-hint">
                <p class="password-hint" id="password-hint">
                  10 caractères minimum — majuscule, minuscule, chiffre et caractère spécial requis.
                </p>
              </div>

              <div class="form-group-custom">
                <label for="password-confirm">Confirmer le mot de passe</label>
                <input type="password" id="password-confirm" name="password_confirm" class="input-custom"
                       placeholder="••••••••" required>
              </div>

              <button type="submit" class="btn-submit">Enregistrer le nouveau mot de passe</button>
            </form>
          <?php else: ?>
            <p style="text-align:center;margin-top:1rem;">
              <a href="mot-de-passe-oublie.php" class="link-gold">
                <i class="bi bi-arrow-left"></i> Faire une nouvelle demande
              </a>
            </p>
          <?php endif; ?>

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
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>

</body>
</html>
