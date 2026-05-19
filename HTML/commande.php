<?php
require_once '../PHP/includes/session.php';
require_once '../PHP/includes/mailer.php';
require_once '../PHP/classes/Menu.php';
require_once '../PHP/classes/Commande.php';
require_once '../PHP/classes/Utilisateur.php';

requireConnexion();

$menuRepo    = new Menu();
$commandeRepo = new Commande();
$utilisateurRepo = new Utilisateur();
$erreurs = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$menu_id_get = filter_input(INPUT_GET, 'menu_id', FILTER_VALIDATE_INT);

$menus_dispo = $menuRepo->listerDisponibles();

$utilisateur = $utilisateurRepo->trouverParId($_SESSION['utilisateur_id']);

$form = [
    'menu_id'                => $menu_id_get ?? '',
    'date_prestation'        => '',
    'heure_livraison'        => '',
    'adresse_prestation'     => $utilisateur['adresse']     ?? '',
    'ville_prestation'       => $utilisateur['ville']       ?? '',
    'code_postal_prestation' => $utilisateur['code_postal'] ?? '',
    'nombre_personne'        => '',
    'distance_km'            => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide.';
    } else {

        $form['menu_id']                = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT) ?: '';
        $form['date_prestation']        = trim($_POST['date_prestation']        ?? '');
        $form['heure_livraison']        = trim($_POST['heure_livraison']        ?? '');
        $form['adresse_prestation']     = trim($_POST['adresse_prestation']     ?? '');
        $form['ville_prestation']       = trim($_POST['ville_prestation']       ?? '');
        $form['code_postal_prestation'] = trim($_POST['code_postal_prestation'] ?? '');
        $form['nombre_personne']        = filter_input(INPUT_POST, 'nombre_personne', FILTER_VALIDATE_INT) ?: '';
        $form['distance_km']            = filter_input(INPUT_POST, 'distance_km', FILTER_VALIDATE_FLOAT) ?? 0;

        if (!$form['menu_id']) {
            $erreurs[] = 'Veuillez sélectionner un menu.';
        }

        if (!$form['date_prestation'] || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['date_prestation'])) {
            $erreurs[] = 'La date de prestation est invalide.';
        } elseif ($form['date_prestation'] <= date('Y-m-d')) {
            $erreurs[] = 'La date de prestation doit être dans le futur.';
        }

        if (!$form['heure_livraison'] || !preg_match('/^\d{2}:\d{2}$/', $form['heure_livraison'])) {
            $erreurs[] = 'L\'heure de livraison est invalide.';
        }

        if (empty($form['adresse_prestation']))     $erreurs[] = 'L\'adresse de prestation est requise.';
        if (empty($form['ville_prestation']))       $erreurs[] = 'La ville de prestation est requise.';
        if (empty($form['code_postal_prestation'])) $erreurs[] = 'Le code postal est requis.';

        if ($form['nombre_personne'] === '' || $form['nombre_personne'] < 1) {
            $erreurs[] = 'Le nombre de personnes est invalide.';
        }

        $menu_cmd = null;
        if (empty($erreurs) && $form['menu_id']) {
            $menu_cmd = $menuRepo->trouverParId((int)$form['menu_id'], seulementActif: true);

            if (!$menu_cmd) {
                $erreurs[] = 'Menu introuvable.';
            } elseif ($menu_cmd['quantite_restante'] <= 0) {
                $erreurs[] = 'Ce menu n\'est plus disponible.';
            } elseif ($form['nombre_personne'] < $menu_cmd['nombre_personne_minimum']) {
                $erreurs[] = 'Ce menu nécessite au minimum ' . $menu_cmd['nombre_personne_minimum'] . ' personnes.';
            }
        }

        if (empty($erreurs) && $menu_cmd) {

            $nb   = (int)$form['nombre_personne'];
            $prix = $commandeRepo->calculerPrix(
                (float)$menu_cmd['prix_par_personne'],
                $nb,
                (int)$menu_cmd['nombre_personne_minimum'],
                (float)$form['distance_km'],
                $form['ville_prestation']
            );

            try {
                $result = $commandeRepo->creer([
                    'utilisateur_id'          => $_SESSION['utilisateur_id'],
                    'menu_id'                 => (int)$form['menu_id'],
                    'date_prestation'         => $form['date_prestation'],
                    'heure_livraison'         => $form['heure_livraison'],
                    'adresse_prestation'      => $form['adresse_prestation'],
                    'ville_prestation'        => $form['ville_prestation'],
                    'code_postal_prestation'  => $form['code_postal_prestation'],
                    'nombre_personne'         => $nb,
                    'prix_menu'               => $prix['prix_menu'],
                    'prix_livraison'          => $prix['prix_livraison'],
                    'prix_total'              => $prix['prix_total'],
                ]);

                $commande_id     = $result['id'];
                $numero_commande = $result['numero'];

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                try {
                    require_once '../PHP/config/mongodb.php';
                    getMongoDB()->commandes->insertOne([
                        'commande_id'     => $commande_id,
                        'menu_id'         => (int)$form['menu_id'],
                        'menu_titre'      => $menu_cmd['titre'],
                        'prix_total'      => $prix['prix_total'],
                        'nombre_personne' => $nb,
                        'date_commande'   => new \MongoDB\BSON\UTCDateTime(),
                    ]);
                } catch (\Throwable $e) {
                    // MongoDB indisponible — non bloquant
                }

                if ($utilisateur) {
                    mailConfirmationCommande($utilisateur['email'], $utilisateur['prenom'], $utilisateur['nom'], [
                        'numero'    => $numero_commande,
                        'menu'      => $menu_cmd['titre'],
                        'date'      => date('d/m/Y', strtotime($form['date_prestation'])),
                        'heure'     => substr($form['heure_livraison'], 0, 5),
                        'personnes' => $nb,
                        'adresse'   => $form['adresse_prestation'] . ', ' . $form['ville_prestation'],
                        'total'     => $prix['prix_total'],
                    ]);
                }


                header('Location: /HTML/commande-confirmation.php?id=' . $commande_id);
                exit;

            } catch (\Exception $e) {
                $erreurs[] = 'Une erreur est survenue. Veuillez réessayer.';
            }
        }
    }
}

$menus_json = json_encode(array_map(fn($m) => [
    'id'   => (int)$m['menu_id'],
    'min'  => (int)$m['nombre_personne_minimum'],
    'prix' => (float)$m['prix_par_personne'],
    'cond' => $m['conditions'] ?? '',
], $menus_dispo), JSON_HEX_TAG | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Commander | Vite & Gourmand</title>
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
            <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="a-propos.html">À propos</a></li>
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

    <section id="commande-breadcrumb">
      <a href="menus.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Retour aux menus</a>
    </section>

    <section id="commande-header">
      <span class="commande-badge">Réservation</span>
      <h1>Passer une commande</h1>
      <p>Renseignez les informations ci-dessous pour finaliser votre réservation.</p>
    </section>

    <section id="commande-content">

      <?php if (!empty($erreurs)): ?>
      <div class="alert alert-danger mb-4" role="alert">
        <ul class="mb-0">
          <?php foreach ($erreurs as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (empty($menus_dispo)): ?>
        <div class="alert alert-info" role="status">
          Aucun menu n'est actuellement disponible à la commande.
          <a href="menus.php" class="link-gold">Voir tous les menus</a>
        </div>

      <?php else: ?>

      <form method="POST" action="commande.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="row g-4">

          <!-- Colonne gauche : formulaire -->
          <div class="col-lg-8">

            <!-- Bloc 1 : Infos client -->
            <div class="commande-section">
              <div class="commande-section-header">
                <span class="commande-step" aria-hidden="true">1</span>
                <h2>Vos informations</h2>
              </div>
              <p class="commande-section-note">Ces informations sont issues de votre compte. <a href="espace-utilisateur/index.php" class="link-gold">Les modifier</a></p>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group-cmd">
                    <label>Nom</label>
                    <input type="text" class="input-cmd" value="<?= htmlspecialchars($utilisateur['nom'] ?? '') ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-cmd">
                    <label>Prénom</label>
                    <input type="text" class="input-cmd" value="<?= htmlspecialchars($utilisateur['prenom'] ?? '') ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-cmd">
                    <label>Email</label>
                    <input type="email" class="input-cmd" value="<?= htmlspecialchars($utilisateur['email'] ?? '') ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-cmd">
                    <label>Téléphone</label>
                    <input type="text" class="input-cmd" value="<?= htmlspecialchars($utilisateur['telephone'] ?? 'Non renseigné') ?>" readonly>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bloc 2 : Infos prestation -->
            <div class="commande-section">
              <div class="commande-section-header">
                <span class="commande-step" aria-hidden="true">2</span>
                <h2>Informations de la prestation</h2>
              </div>
              <p class="commande-section-note">Date, heure et lieu où vous souhaitez être livré.</p>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group-cmd">
                    <label for="date_prestation">Date de la prestation <span class="required" aria-hidden="true">*</span></label>
                    <input type="date" class="input-cmd" id="date_prestation" name="date_prestation"
                           value="<?= htmlspecialchars($form['date_prestation']) ?>"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           required aria-required="true">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-cmd">
                    <label for="heure_livraison">Heure de livraison <span class="required" aria-hidden="true">*</span></label>
                    <input type="time" class="input-cmd" id="heure_livraison" name="heure_livraison"
                           value="<?= htmlspecialchars($form['heure_livraison']) ?>"
                           required aria-required="true">
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-group-cmd">
                    <label for="adresse_prestation">Adresse <span class="required" aria-hidden="true">*</span></label>
                    <input type="text" class="input-cmd" id="adresse_prestation" name="adresse_prestation"
                           value="<?= htmlspecialchars($form['adresse_prestation']) ?>"
                           placeholder="Ex : 12 rue des Fleurs"
                           required aria-required="true">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group-cmd">
                    <label for="code_postal_prestation">Code postal <span class="required" aria-hidden="true">*</span></label>
                    <input type="text" class="input-cmd" id="code_postal_prestation" name="code_postal_prestation"
                           value="<?= htmlspecialchars($form['code_postal_prestation']) ?>"
                           placeholder="Ex : 33000" pattern="\d{5}"
                           required aria-required="true">
                  </div>
                </div>
                <div class="col-md-8">
                  <div class="form-group-cmd">
                    <label for="ville_prestation">Ville <span class="required" aria-hidden="true">*</span></label>
                    <input type="text" class="input-cmd" id="ville_prestation" name="ville_prestation"
                           value="<?= htmlspecialchars($form['ville_prestation']) ?>"
                           placeholder="Ex : Bordeaux"
                           required aria-required="true">
                  </div>
                </div>
              </div>

              <!-- Frais livraison hors Bordeaux -->
              <div id="cmd-frais-livraison" hidden>
                <div class="cmd-livraison-alert" role="note">
                  <i class="bi bi-truck" aria-hidden="true"></i>
                  Livraison hors Bordeaux : <strong>5 € fixe + 0,59 € par kilomètre parcouru</strong>
                </div>
                <div class="form-group-cmd mt-3">
                  <label for="distance_km">Distance approximative depuis Bordeaux (km) <span class="required" aria-hidden="true">*</span></label>
                  <input type="number" class="input-cmd" id="distance_km" name="distance_km"
                         value="<?= htmlspecialchars((string)$form['distance_km']) ?>"
                         min="0" step="0.1" placeholder="Ex : 15">
                </div>
              </div>
            </div>

            <!-- Bloc 3 : Menu + personnes -->
            <div class="commande-section">
              <div class="commande-section-header">
                <span class="commande-step" aria-hidden="true">3</span>
                <h2>Votre menu</h2>
              </div>
              <p class="commande-section-note">Choisissez votre menu et le nombre de convives.</p>

              <div class="form-group-cmd">
                <label for="menu_id">Menu <span class="required" aria-hidden="true">*</span></label>
                <select class="input-cmd" id="menu_id" name="menu_id" required aria-required="true">
                  <option value="">-- Sélectionner un menu --</option>
                  <?php foreach ($menus_dispo as $m): ?>
                    <option value="<?= (int)$m['menu_id'] ?>"
                            <?= ((int)$form['menu_id'] === (int)$m['menu_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($m['titre']) ?>
                      — à partir de <?= number_format($m['nombre_personne_minimum'] * $m['prix_par_personne'], 0, ',', ' ') ?> €
                      (<?= (int)$m['quantite_restante'] ?> disponible<?= $m['quantite_restante'] > 1 ? 's' : '' ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Conditions du menu -->
              <div id="cmd-conditions" class="cmd-conditions-bloc" hidden aria-live="polite">
                <p class="cmd-conditions-titre">
                  <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                  Conditions — à lire attentivement avant de commander
                </p>
                <p id="cmd-conditions-texte"></p>
              </div>

              <!-- Nombre de personnes -->
              <div id="cmd-personnes-bloc" class="form-group-cmd mt-3" hidden>
                <label for="nombre_personne">
                  Nombre de personnes <span class="required" aria-hidden="true">*</span>
                  <span id="cmd-personnes-min" class="input-hint"></span>
                </label>
                <input type="number" class="input-cmd" id="nombre_personne" name="nombre_personne"
                       value="<?= htmlspecialchars((string)$form['nombre_personne']) ?>"
                       min="1" step="1" aria-required="true">
                <p id="cmd-reduction-hint" class="input-hint mt-2" hidden>
                  <i class="bi bi-tag-fill" aria-hidden="true"></i>
                  Réduction de <strong style="color:#6aad6a">10 %</strong> applicable pour 5 personnes ou plus au-delà du minimum du menu.
                </p>
              </div>

            </div>

          </div>

          <!-- Colonne droite : récapitulatif -->
          <div class="col-lg-4">
            <div class="commande-recap" aria-label="Récapitulatif de la commande" aria-live="polite">
              <h3>Récapitulatif</h3>

              <p id="cmd-recap-vide" class="input-hint">
                Sélectionnez un menu et complétez les informations pour voir le détail du prix.
              </p>

              <div id="cmd-recap-detail" hidden>
                <div class="recap-ligne recap-menu">
                  <span class="recap-label">Menu</span>
                  <span class="recap-value" id="recap-menu-titre">—</span>
                </div>
                <div class="recap-ligne">
                  <span class="recap-label">Personnes</span>
                  <span class="recap-value" id="recap-nb-personnes">—</span>
                </div>
                <div class="recap-ligne">
                  <span class="recap-label">Prix / personne</span>
                  <span class="recap-value" id="recap-prix-pers">—</span>
                </div>

                <div class="recap-separateur"></div>

                <div class="recap-ligne recap-reduction" id="recap-row-reduction" hidden>
                  <span class="recap-label">Réduction 10 %</span>
                  <span class="recap-value recap-reduction-value" id="recap-reduction">—</span>
                </div>
                <div class="recap-ligne">
                  <span class="recap-label">Sous-total menu</span>
                  <span class="recap-value" id="recap-prix-menu">—</span>
                </div>
                <div class="recap-ligne">
                  <span class="recap-label">Livraison</span>
                  <span class="recap-value" id="recap-livraison">—</span>
                </div>

                <div class="recap-separateur"></div>

                <div class="recap-ligne recap-total">
                  <span class="recap-label">Total TTC</span>
                  <span class="recap-value" id="recap-total">—</span>
                </div>
              </div>

              <p class="recap-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                En confirmant, vous acceptez les <a href="cgv.html" class="link-gold">conditions générales de vente</a>.
              </p>

              <button type="submit" class="btn-commander">
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                Confirmer la commande
              </button>

              <p class="recap-cgv">Paiement sécurisé · Données protégées RGPD</p>
            </div>
          </div>

        </div>
      </form>

      <?php endif; ?>

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
  <script>window.CMD_MENUS = <?= $menus_json ?>;</script>
  <script src="../JS/commande.js"></script>

</body>
</html>
