<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/includes/mailer.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message      = '';
$message_type = '';
$vals         = ['titre' => '', 'email' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message      = 'Requête invalide. Veuillez réessayer.';
        $message_type = 'danger';
    } else {
        $titre       = trim($_POST['titre']       ?? '');
        $email       = trim($_POST['email']       ?? '');
        $description = trim($_POST['description'] ?? '');

        $vals = [
            'titre'       => htmlspecialchars($titre),
            'email'       => htmlspecialchars($email),
            'description' => htmlspecialchars($description),
        ];

        $err = [];
        if (empty($titre))                                 $err[] = 'Le sujet est requis.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $err[] = 'Adresse email invalide.';
        if (empty($description))                           $err[] = 'Le message est requis.';

        if ($err) {
            $message      = implode(' ', $err);
            $message_type = 'danger';
        } else {
            $html = '
            <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
              <div style="background:#b8860b;padding:24px 32px">
                <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand — Nouveau message</h1>
              </div>
              <div style="padding:32px">
                <p><strong>De :</strong> ' . htmlspecialchars($email) . '</p>
                <p><strong>Sujet :</strong> ' . htmlspecialchars($titre) . '</p>
                <hr style="border:none;border-top:1px solid #eee;margin:16px 0">
                <p style="white-space:pre-wrap">' . htmlspecialchars($description) . '</p>
              </div>
            </div>';

            $sent = sendMail('contact@vite-et-gourmand.fr', 'Vite & Gourmand', '[Contact] ' . $titre, $html);

            if ($sent) {
                $message      = 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.';
                $message_type = 'success';
                $vals         = ['titre' => '', 'email' => '', 'description' => ''];
            } else {
                $message      = 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.';
                $message_type = 'danger';
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
  <title>Contact | Vite &amp; Gourmand</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style.css">
  <link rel="stylesheet" href="../CSS/contact.css">
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
            <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="a-propos.html">À propos</a></li>
            <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
            <li class="nav-item"><a class="nav-link" href="connexion.php">Connexion</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main id="contenu-principal">

    <section class="contact-hero">
      <div class="contact-hero-inner">
        <h1>Contactez-nous</h1>
        <p>Une question, un projet ? Écrivez-nous et nous vous répondrons rapidement.</p>
      </div>
    </section>

    <section class="contact-section">
      <div class="container">
        <div class="row justify-content-center">

          <div class="col-lg-5 col-md-6 mb-5 mb-lg-0">
            <h2 class="contact-info-title">Nos coordonnées</h2>
            <ul class="contact-info-list">
              <li>
                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                <span>Bordeaux, 33000 — et ses environs</span>
              </li>
              <li>
                <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                <span>+33 5 XX XX XX XX</span>
              </li>
              <li>
                <i class="bi bi-envelope-fill" aria-hidden="true"></i>
                <span>contact@vite-et-gourmand.fr</span>
              </li>
            </ul>

            <h3 class="contact-hours-title">Horaires</h3>
            <ul class="contact-hours-list">
              <li><span>Lun – Ven</span> 7h00 – 21h00</li>
              <li><span>Samedi</span> 8h00 – 18h00</li>
              <li><span>Dimanche</span> Fermé</li>
            </ul>
          </div>

          <div class="col-lg-6 col-md-6">
            <div class="contact-form-card">
              <h2 class="contact-form-title">Envoyer un message</h2>

              <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>" role="alert">
                  <?= $message ?>
                </div>
              <?php endif; ?>

              <form action="contact.php" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group-custom">
                  <label for="titre">Sujet</label>
                  <input type="text" id="titre" name="titre" class="input-custom"
                         placeholder="Ex : Demande de devis pour un mariage"
                         value="<?= $vals['titre'] ?>" required>
                </div>

                <div class="form-group-custom">
                  <label for="email">Votre adresse email</label>
                  <input type="email" id="email" name="email" class="input-custom"
                         placeholder="exemple@email.com"
                         value="<?= $vals['email'] ?>" required>
                </div>

                <div class="form-group-custom">
                  <label for="description">Message</label>
                  <textarea id="description" name="description" class="input-custom" rows="6"
                            placeholder="Décrivez votre demande…" required><?= $vals['description'] ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                  <i class="bi bi-send" aria-hidden="true"></i> Envoyer le message
                </button>
              </form>
            </div>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/main.js"></script>

</body>
</html>
