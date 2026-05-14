<?php
require_once '../../PHP/includes/session.php';
require_once '../../PHP/config/db.php';

requireRole(3);

$pdo = getPDO();
$uid = (int)$_SESSION['utilisateur_id'];

$flash_ok  = $_SESSION['flash_ok']  ?? null; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? null; unset($_SESSION['flash_err']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// POST handlers
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_err'] = 'Requête invalide.';
        header('Location: index.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // --- Annuler commande ---
    if ($action === 'annuler_commande') {
        $cid = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
        if ($cid) {
            $stmt = $pdo->prepare(
                'SELECT commande_id FROM commande
                 WHERE commande_id = ? AND utilisateur_id = ? AND statut = "en_attente"'
            );
            $stmt->execute([$cid, $uid]);
            if ($stmt->fetch()) {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE commande SET statut = "annulee" WHERE commande_id = ?')
                    ->execute([$cid]);
                $pdo->prepare('INSERT INTO commande_historique (commande_id, statut) VALUES (?, "annulee")')
                    ->execute([$cid]);
                $pdo->commit();
                $_SESSION['flash_ok'] = 'Commande annulée avec succès.';
            } else {
                $_SESSION['flash_err'] = 'Cette commande ne peut pas être annulée.';
            }
        }
        header('Location: index.php');
        exit;
    }

    // --- Modifier commande ---
    if ($action === 'modifier_commande') {
        $cid         = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
        $date        = trim($_POST['date_prestation']        ?? '');
        $heure       = trim($_POST['heure_livraison']        ?? '');
        $adresse     = trim($_POST['adresse_prestation']     ?? '');
        $ville       = trim($_POST['ville_prestation']       ?? '');
        $cp          = trim($_POST['code_postal_prestation'] ?? '');
        $nb_pers     = filter_input(INPUT_POST, 'nombre_personne', FILTER_VALIDATE_INT);
        $distance_km = filter_input(INPUT_POST, 'distance_km', FILTER_VALIDATE_FLOAT) ?: 0;

        $stmt = $pdo->prepare(
            'SELECT c.commande_id, m.nombre_personne_minimum, m.prix_par_personne
             FROM commande c JOIN menu m ON c.menu_id = m.menu_id
             WHERE c.commande_id = ? AND c.utilisateur_id = ? AND c.statut = "en_attente"'
        );
        $stmt->execute([$cid, $uid]);
        $cmd = $stmt->fetch();

        $err = [];
        if (!$cmd) {
            $err[] = 'Commande introuvable ou non modifiable.';
        } else {
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) {
                $err[] = 'Date de prestation invalide.';
            }
            if (!$heure || !preg_match('/^\d{2}:\d{2}$/', $heure)) {
                $err[] = 'Heure de livraison invalide.';
            }
            if (empty($adresse)) $err[] = 'Adresse requise.';
            if (empty($ville))   $err[] = 'Ville requise.';
            if (empty($cp))      $err[] = 'Code postal requis.';
            if (!$nb_pers || $nb_pers < (int)$cmd['nombre_personne_minimum']) {
                $err[] = 'Minimum ' . (int)$cmd['nombre_personne_minimum'] . ' personnes requis pour ce menu.';
            }
        }

        if ($err) {
            $_SESSION['flash_err'] = implode(' ', $err);
            header('Location: index.php');
            exit;
        }

        $prix_unit      = (float)$cmd['prix_par_personne'];
        $min_pers       = (int)$cmd['nombre_personne_minimum'];
        $prix_menu      = $prix_unit * $nb_pers;
        if ($nb_pers >= $min_pers + 5) $prix_menu *= 0.9;
        $prix_livraison = (strtolower(trim($ville)) !== 'bordeaux' && $distance_km > 0)
                          ? round(5 + 0.59 * (float)$distance_km, 2)
                          : 0.00;
        $prix_total = round($prix_menu, 2) + $prix_livraison;

        $pdo->prepare(
            'UPDATE commande SET
             date_prestation = ?, heure_livraison = ?, adresse_prestation = ?,
             ville_prestation = ?, code_postal_prestation = ?, nombre_personne = ?,
             prix_menu = ?, prix_livraison = ?, prix_total = ?
             WHERE commande_id = ?'
        )->execute([
            $date, $heure, $adresse, $ville, $cp, $nb_pers,
            round($prix_menu, 2), $prix_livraison, $prix_total,
            $cid
        ]);

        $_SESSION['flash_ok'] = 'Commande modifiée avec succès.';
        header('Location: index.php');
        exit;
    }

    // --- Soumettre avis ---
    if ($action === 'soumettre_avis') {
        $cid         = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
        $note        = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_INT);
        $commentaire = trim($_POST['commentaire'] ?? '');

        $err = [];
        if (!$cid)                             $err[] = 'Commande invalide.';
        if (!$note || $note < 1 || $note > 5)  $err[] = 'Note invalide (1 à 5).';
        if (empty($commentaire))               $err[] = 'Le commentaire est requis.';

        if (!$err) {
            $stmt = $pdo->prepare(
                'SELECT commande_id FROM commande
                 WHERE commande_id = ? AND utilisateur_id = ? AND statut = "terminee"'
            );
            $stmt->execute([$cid, $uid]);
            if (!$stmt->fetch()) $err[] = 'Cette commande ne permet pas de laisser un avis.';
        }
        if (!$err) {
            $stmt = $pdo->prepare('SELECT avis_id FROM avis WHERE commande_id = ?');
            $stmt->execute([$cid]);
            if ($stmt->fetch()) $err[] = 'Vous avez déjà soumis un avis pour cette commande.';
        }

        if ($err) {
            $_SESSION['flash_err'] = implode(' ', $err);
            header('Location: index.php');
            exit;
        }

        $pdo->prepare(
            'INSERT INTO avis (utilisateur_id, commande_id, note, commentaire, statut)
             VALUES (?, ?, ?, ?, "en_attente")'
        )->execute([$uid, $cid, $note, $commentaire]);

        $_SESSION['flash_ok'] = 'Votre avis a été soumis. Il sera visible après validation par notre équipe.';
        header('Location: index.php');
        exit;
    }

    // --- Modifier profil ---
    if ($action === 'modifier_profil') {
        $nom         = trim($_POST['nom']         ?? '');
        $prenom      = trim($_POST['prenom']      ?? '');
        $email       = trim($_POST['email']       ?? '');
        $telephone   = trim($_POST['telephone']   ?? '');
        $adresse     = trim($_POST['adresse']     ?? '');
        $cp          = trim($_POST['code_postal'] ?? '');
        $ville       = trim($_POST['ville']       ?? '');
        $mdp_actuel  = $_POST['mdp_actuel']  ?? '';
        $mdp_nouveau = $_POST['mdp_nouveau'] ?? '';
        $mdp_confirm = $_POST['mdp_confirm'] ?? '';

        $err = [];
        if (empty($nom))    $err[] = 'Le nom est requis.';
        if (empty($prenom)) $err[] = 'Le prénom est requis.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Adresse email invalide.';

        if (!$err) {
            $s = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ? AND utilisateur_id != ?');
            $s->execute([$email, $uid]);
            if ($s->fetch()) $err[] = 'Cette adresse email est déjà utilisée.';
        }

        $new_hash = null;
        if ($mdp_nouveau !== '') {
            $s = $pdo->prepare('SELECT password FROM utilisateur WHERE utilisateur_id = ?');
            $s->execute([$uid]);
            $row = $s->fetch();
            if (!password_verify($mdp_actuel, $row['password'])) {
                $err[] = 'Mot de passe actuel incorrect.';
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $mdp_nouveau)) {
                $err[] = 'Nouveau mot de passe : 10 caractères min. avec majuscule, minuscule, chiffre et caractère spécial.';
            }
            if ($mdp_nouveau !== $mdp_confirm) {
                $err[] = 'Les nouveaux mots de passe ne correspondent pas.';
            }
            if (!$err) $new_hash = password_hash($mdp_nouveau, PASSWORD_BCRYPT);
        }

        if ($err) {
            $_SESSION['flash_err'] = implode(' ', $err);
            header('Location: index.php?section=informations');
            exit;
        }

        $params = [$nom, $prenom, $email, $telephone, $adresse, $cp, $ville];
        $sql = 'UPDATE utilisateur SET nom=?, prenom=?, email=?, telephone=?, adresse=?, code_postal=?, ville=?';
        if ($new_hash) {
            $sql .= ', password=?';
            $params[] = $new_hash;
        }
        $sql .= ' WHERE utilisateur_id=?';
        $params[] = $uid;
        $pdo->prepare($sql)->execute($params);

        $_SESSION['nom']    = $nom;
        $_SESSION['prenom'] = $prenom;
        $_SESSION['email']  = $email;

        $_SESSION['flash_ok'] = 'Vos informations ont été mises à jour.';
        header('Location: index.php?section=informations');
        exit;
    }

    header('Location: index.php');
    exit;
}

// ============================================================
// GET — fetch data
// ============================================================

$s = $pdo->prepare(
    'SELECT nom, prenom, email, telephone, adresse, code_postal, ville
     FROM utilisateur WHERE utilisateur_id = ?'
);
$s->execute([$uid]);
$user = $s->fetch();

$s = $pdo->prepare(
    'SELECT c.commande_id, c.numero_commande, c.date_commande,
            c.date_prestation, c.heure_livraison,
            c.adresse_prestation, c.ville_prestation, c.code_postal_prestation,
            c.nombre_personne, c.prix_menu, c.prix_livraison, c.prix_total,
            c.statut, c.pret_materiel,
            m.titre AS menu_titre, m.nombre_personne_minimum
     FROM commande c
     JOIN menu m ON c.menu_id = m.menu_id
     WHERE c.utilisateur_id = ?
     ORDER BY c.date_commande DESC'
);
$s->execute([$uid]);
$commandes = $s->fetchAll();

$historiques       = [];
$avis_par_commande = [];

if ($commandes) {
    $ids = array_column($commandes, 'commande_id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));

    $s = $pdo->prepare(
        "SELECT commande_id, statut, created_at
         FROM commande_historique WHERE commande_id IN ($ph)
         ORDER BY created_at ASC"
    );
    $s->execute($ids);
    foreach ($s->fetchAll() as $h) {
        $historiques[$h['commande_id']][] = $h;
    }

    $s = $pdo->prepare(
        "SELECT commande_id, note, commentaire, statut FROM avis WHERE commande_id IN ($ph)"
    );
    $s->execute($ids);
    foreach ($s->fetchAll() as $a) {
        $avis_par_commande[$a['commande_id']] = $a;
    }
}

$statut_labels = [
    'en_attente'              => 'En attente',
    'accepte'                 => 'Acceptée',
    'en_preparation'          => 'En préparation',
    'en_cours_livraison'      => 'En cours de livraison',
    'livre'                   => 'Livrée',
    'attente_retour_materiel' => 'En attente de retour matériel',
    'terminee'                => 'Terminée',
    'annulee'                 => 'Annulée',
];

$statut_badges = [
    'en_attente'              => 'badge-attente',
    'accepte'                 => 'badge-accepte',
    'en_preparation'          => 'badge-preparation',
    'en_cours_livraison'      => 'badge-livraison',
    'livre'                   => 'badge-livre',
    'attente_retour_materiel' => 'badge-materiel',
    'terminee'                => 'badge-terminee',
    'annulee'                 => 'badge-annulee',
];

$timeline_steps = [
    'en_attente'              => 'Commande reçue',
    'accepte'                 => 'Acceptée',
    'en_preparation'          => 'En préparation',
    'en_cours_livraison'      => 'En cours de livraison',
    'livre'                   => 'Livrée',
    'attente_retour_materiel' => 'En attente de retour matériel',
    'terminee'                => 'Terminée',
];

// Section active (commandes ou informations)
$allowed_sections  = ['commandes', 'informations'];
$section_param     = $_GET['section'] ?? 'commandes';
$active_section    = in_array($section_param, $allowed_sections, true) ? $section_param : 'commandes';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon espace | Vite & Gourmand</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/espace-utilisateur.css">
</head>
<body>

  <!-- Header -->
  <header class="eu-header">
    <div class="eu-header-inner">
      <a href="../index.html" class="eu-logo">Vite &amp; Gourmand</a>
      <span class="eu-user-name">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
      <a href="../../PHP/deconnexion.php" class="eu-logout">
        <i class="bi bi-box-arrow-right"></i> Déconnexion
      </a>
    </div>
  </header>

  <div class="eu-layout">

    <!-- Sidebar -->
    <aside class="eu-sidebar">
      <nav aria-label="Navigation espace utilisateur">
        <ul class="eu-nav">
          <li>
            <a href="?section=commandes"
               class="eu-nav-link <?= $active_section === 'commandes' ? 'active' : '' ?>"
               data-section="commandes">
              <i class="bi bi-bag" aria-hidden="true"></i> Mes commandes
            </a>
          </li>
          <li>
            <a href="?section=informations"
               class="eu-nav-link <?= $active_section === 'informations' ? 'active' : '' ?>"
               data-section="informations">
              <i class="bi bi-person" aria-hidden="true"></i> Mes informations
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Contenu principal -->
    <main class="eu-main" id="contenu-principal">

      <?php if ($flash_ok): ?>
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
          <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
          <?= htmlspecialchars($flash_ok) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
      <?php endif; ?>

      <?php if ($flash_err): ?>
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
          <?= htmlspecialchars($flash_err) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
      <?php endif; ?>

      <!-- ===== SECTION : MES COMMANDES ===== -->
      <section id="section-commandes" class="eu-section"
               <?= $active_section !== 'commandes' ? 'style="display:none"' : '' ?>>

        <h1 class="eu-page-title">Mes commandes</h1>

        <?php if (empty($commandes)): ?>
          <div class="eu-order-card" style="padding:1.5rem; text-align:center; color:#888;">
            <i class="bi bi-bag-x" style="font-size:2rem;display:block;margin-bottom:.75rem;" aria-hidden="true"></i>
            Vous n'avez pas encore passé de commande.
            <br><a href="../menus.php" class="eu-btn eu-btn-primary" style="margin-top:1rem;display:inline-flex;">Voir nos menus</a>
          </div>
        <?php else: ?>
          <?php foreach ($commandes as $cmd): ?>
            <?php
              $cid        = (int)$cmd['commande_id'];
              $statut     = $cmd['statut'];
              $label      = $statut_labels[$statut] ?? $statut;
              $badge      = $statut_badges[$statut] ?? 'badge-attente';
              $hist       = $historiques[$cid] ?? [];
              $avis       = $avis_par_commande[$cid] ?? null;

              // Statuts qui passés en revue dans la historique
              $done_statuts = array_column($hist, 'statut');
              $done_times   = [];
              foreach ($hist as $h) {
                  $done_times[$h['statut']] = $h['created_at'];
              }

              $show_tracking = !in_array($statut, ['en_attente', 'annulee'], true);
              $show_actions  = ($statut === 'en_attente');
              $show_avis_form = ($statut === 'terminee' && $avis === null);
              $show_avis_done = ($statut === 'terminee' && $avis !== null);

              // Prix livraison
              $livraison_str = (float)$cmd['prix_livraison'] > 0
                  ? number_format((float)$cmd['prix_livraison'], 2, ',', ' ') . ' €'
                  : 'Incluse (Bordeaux)';

              // Réduction appliquée ?
              $discount = ($cmd['nombre_personne'] >= $cmd['nombre_personne_minimum'] + 5);
            ?>
            <article class="eu-order-card">
              <div class="eu-order-header">
                <div class="eu-order-meta">
                  <span class="eu-order-num"><?= htmlspecialchars($cmd['numero_commande']) ?></span>
                  <span class="eu-badge <?= $badge ?>"><?= $label ?></span>
                </div>
                <button class="eu-toggle-btn" data-target="detail-<?= $cid ?>" aria-expanded="false"
                        aria-controls="detail-<?= $cid ?>">
                  Voir le détail <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
              </div>

              <div class="eu-order-summary">
                <span><i class="bi bi-fork-knife" aria-hidden="true"></i>
                  <?= htmlspecialchars($cmd['menu_titre']) ?></span>
                <span><i class="bi bi-people" aria-hidden="true"></i>
                  <?= (int)$cmd['nombre_personne'] ?> personnes</span>
                <span><i class="bi bi-calendar" aria-hidden="true"></i>
                  <?= date('d/m/Y', strtotime($cmd['date_prestation'])) ?></span>
                <span class="eu-order-price">
                  <?= number_format((float)$cmd['prix_total'], 2, ',', ' ') ?> €
                </span>
              </div>

              <!-- Détail dépliable -->
              <div class="eu-order-detail" id="detail-<?= $cid ?>" style="display:none">

                <div class="eu-detail-grid">
                  <div>
                    <p class="eu-detail-label">Menu</p>
                    <p class="eu-detail-value"><?= htmlspecialchars($cmd['menu_titre']) ?></p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Nombre de personnes</p>
                    <p class="eu-detail-value"><?= (int)$cmd['nombre_personne'] ?></p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Date de prestation</p>
                    <p class="eu-detail-value">
                      <?= date('d/m/Y', strtotime($cmd['date_prestation'])) ?>
                      à <?= htmlspecialchars(substr($cmd['heure_livraison'], 0, 5)) ?>
                    </p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Adresse de livraison</p>
                    <p class="eu-detail-value">
                      <?= htmlspecialchars($cmd['adresse_prestation']) ?>,
                      <?= htmlspecialchars($cmd['code_postal_prestation']) ?>
                      <?= htmlspecialchars($cmd['ville_prestation']) ?>
                    </p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Sous-total menu</p>
                    <p class="eu-detail-value">
                      <?= number_format((float)$cmd['prix_menu'], 2, ',', ' ') ?> €
                      <?php if ($discount): ?>
                        <small style="color:#198754;">(-10 %)</small>
                      <?php endif; ?>
                    </p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Frais de livraison</p>
                    <p class="eu-detail-value"><?= $livraison_str ?></p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Total TTC</p>
                    <p class="eu-detail-value eu-detail-total">
                      <?= number_format((float)$cmd['prix_total'], 2, ',', ' ') ?> €
                    </p>
                  </div>
                  <div>
                    <p class="eu-detail-label">Commandé le</p>
                    <p class="eu-detail-value">
                      <?= date('d/m/Y à H\hi', strtotime($cmd['date_commande'])) ?>
                    </p>
                  </div>
                </div>

                <!-- Actions (en_attente uniquement) -->
                <?php if ($show_actions): ?>
                  <div class="eu-order-actions">
                    <button class="eu-btn eu-btn-secondary" data-modal="modal-modifier-<?= $cid ?>">
                      <i class="bi bi-pencil" aria-hidden="true"></i> Modifier
                    </button>
                    <button class="eu-btn eu-btn-danger" data-modal="modal-annuler-<?= $cid ?>">
                      <i class="bi bi-x-circle" aria-hidden="true"></i> Annuler
                    </button>
                  </div>
                <?php endif; ?>

                <!-- Suivi de commande (accepte et au-delà) -->
                <?php if ($show_tracking): ?>
                  <div class="eu-tracking">
                    <h2 class="eu-tracking-title">Suivi de la commande</h2>
                    <ul class="eu-timeline">
                      <?php
                        $steps = $timeline_steps;
                        if (!(bool)$cmd['pret_materiel']) {
                            unset($steps['attente_retour_materiel']);
                        }
                        foreach ($steps as $step_statut => $step_label):
                            $is_done   = in_array($step_statut, $done_statuts, true);
                            $is_active = ($step_statut === $statut && $statut !== 'terminee');
                            $class = 'eu-timeline-item';
                            if ($is_active) $class .= ' active';
                            elseif ($is_done) $class .= ' done';

                            $date_str = '—';
                            if ($is_done && isset($done_times[$step_statut])) {
                                $dt = new DateTime($done_times[$step_statut]);
                                $date_str = $dt->format('d/m/Y') . ' — ' . $dt->format('H\hi');
                            }
                      ?>
                        <li class="<?= $class ?>">
                          <span class="eu-timeline-dot"></span>
                          <div class="eu-timeline-content">
                            <p class="eu-timeline-status"><?= htmlspecialchars($step_label) ?></p>
                            <p class="eu-timeline-date"><?= $date_str ?></p>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>

                <!-- Formulaire avis (commande terminée, pas encore d'avis) -->
                <?php if ($show_avis_form): ?>
                  <div class="eu-review">
                    <h2 class="eu-review-title">Laisser un avis</h2>
                    <form action="index.php" method="post">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <input type="hidden" name="action" value="soumettre_avis">
                      <input type="hidden" name="commande_id" value="<?= $cid ?>">

                      <div class="eu-stars" role="group" aria-label="Note sur 5">
                        <input type="radio" id="star-5-<?= $cid ?>" name="note" value="5" required>
                        <label for="star-5-<?= $cid ?>"><i class="bi bi-star-fill" aria-hidden="true"></i></label>
                        <input type="radio" id="star-4-<?= $cid ?>" name="note" value="4">
                        <label for="star-4-<?= $cid ?>"><i class="bi bi-star-fill" aria-hidden="true"></i></label>
                        <input type="radio" id="star-3-<?= $cid ?>" name="note" value="3">
                        <label for="star-3-<?= $cid ?>"><i class="bi bi-star-fill" aria-hidden="true"></i></label>
                        <input type="radio" id="star-2-<?= $cid ?>" name="note" value="2">
                        <label for="star-2-<?= $cid ?>"><i class="bi bi-star-fill" aria-hidden="true"></i></label>
                        <input type="radio" id="star-1-<?= $cid ?>" name="note" value="1">
                        <label for="star-1-<?= $cid ?>"><i class="bi bi-star-fill" aria-hidden="true"></i></label>
                      </div>

                      <div class="eu-form-group">
                        <label for="commentaire-<?= $cid ?>">Votre commentaire</label>
                        <textarea id="commentaire-<?= $cid ?>" name="commentaire" rows="4"
                                  placeholder="Partagez votre expérience…" required></textarea>
                      </div>

                      <button type="submit" class="eu-btn eu-btn-primary">
                        <i class="bi bi-send" aria-hidden="true"></i> Envoyer l'avis
                      </button>
                    </form>
                  </div>
                <?php endif; ?>

                <!-- Avis déjà soumis -->
                <?php if ($show_avis_done && $avis): ?>
                  <div class="eu-review">
                    <h2 class="eu-review-title">Votre avis</h2>
                    <div style="display:flex;gap:.25rem;margin-bottom:.75rem;" aria-label="Note : <?= (int)$avis['note'] ?> sur 5">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?= $i <= $avis['note'] ? 'bi-star-fill' : 'bi-star' ?>"
                           style="color:<?= $i <= $avis['note'] ? '#f5a623' : '#ddd' ?>;font-size:1.25rem;"
                           aria-hidden="true"></i>
                      <?php endfor; ?>
                    </div>
                    <p style="font-size:.875rem;color:#555;margin-bottom:.5rem;">
                      <?= htmlspecialchars($avis['commentaire']) ?>
                    </p>
                    <?php if ($avis['statut'] === 'en_attente'): ?>
                      <small style="color:#888;">
                        <i class="bi bi-clock" aria-hidden="true"></i> En attente de validation
                      </small>
                    <?php elseif ($avis['statut'] === 'valide'): ?>
                      <small style="color:#198754;">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Avis validé et publié
                      </small>
                    <?php elseif ($avis['statut'] === 'refuse'): ?>
                      <small style="color:#dc3545;">
                        <i class="bi bi-x-circle-fill" aria-hidden="true"></i> Avis refusé
                      </small>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

              </div><!-- /eu-order-detail -->
            </article>
          <?php endforeach; ?>
        <?php endif; ?>

      </section><!-- /section-commandes -->

      <!-- ===== SECTION : MES INFORMATIONS ===== -->
      <section id="section-informations" class="eu-section"
               <?= $active_section !== 'informations' ? 'style="display:none"' : '' ?>>

        <h1 class="eu-page-title">Mes informations</h1>

        <form action="index.php?section=informations" method="post" class="eu-info-form">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="action" value="modifier_profil">

          <div class="eu-form-row">
            <div class="eu-form-group">
              <label for="prenom">Prénom</label>
              <input type="text" id="prenom" name="prenom"
                     value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
            </div>
            <div class="eu-form-group">
              <label for="nom">Nom</label>
              <input type="text" id="nom" name="nom"
                     value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
            </div>
          </div>

          <div class="eu-form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
          </div>

          <div class="eu-form-group">
            <label for="telephone">Numéro de téléphone</label>
            <input type="tel" id="telephone" name="telephone"
                   value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
          </div>

          <div class="eu-form-group">
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse"
                   value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
          </div>

          <div class="eu-form-row">
            <div class="eu-form-group">
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" name="code_postal"
                     value="<?= htmlspecialchars($user['code_postal'] ?? '') ?>">
            </div>
            <div class="eu-form-group">
              <label for="ville">Ville</label>
              <input type="text" id="ville" name="ville"
                     value="<?= htmlspecialchars($user['ville'] ?? '') ?>">
            </div>
          </div>

          <hr class="eu-separator">

          <h2 class="eu-section-subtitle">Modifier le mot de passe</h2>
          <p style="font-size:.8rem;color:#888;margin-bottom:1rem;">
            Laissez vide si vous ne souhaitez pas changer de mot de passe.
          </p>

          <div class="eu-form-group">
            <label for="mdp-actuel">Mot de passe actuel</label>
            <input type="password" id="mdp-actuel" name="mdp_actuel"
                   placeholder="••••••••" autocomplete="current-password">
          </div>

          <div class="eu-form-row">
            <div class="eu-form-group">
              <label for="mdp-nouveau">Nouveau mot de passe</label>
              <input type="password" id="mdp-nouveau" name="mdp_nouveau"
                     placeholder="••••••••" autocomplete="new-password">
            </div>
            <div class="eu-form-group">
              <label for="mdp-confirm">Confirmer le mot de passe</label>
              <input type="password" id="mdp-confirm" name="mdp_confirm"
                     placeholder="••••••••" autocomplete="new-password">
            </div>
          </div>

          <div class="eu-form-actions">
            <button type="submit" class="eu-btn eu-btn-primary">
              <i class="bi bi-floppy" aria-hidden="true"></i> Enregistrer les modifications
            </button>
          </div>

        </form>
      </section><!-- /section-informations -->

    </main>
  </div>

  <!-- ===== MODALS (générés pour chaque commande en_attente) ===== -->
  <?php foreach ($commandes as $cmd): ?>
    <?php if ($cmd['statut'] !== 'en_attente') continue; ?>
    <?php $cid = (int)$cmd['commande_id']; ?>

    <!-- Modal : Modifier commande #<?= $cid ?> -->
    <div class="eu-modal-overlay" id="modal-modifier-<?= $cid ?>" style="display:none"
         role="dialog" aria-modal="true" aria-labelledby="modal-modifier-title-<?= $cid ?>">
      <div class="eu-modal">
        <div class="eu-modal-header">
          <h2 id="modal-modifier-title-<?= $cid ?>">
            Modifier <?= htmlspecialchars($cmd['numero_commande']) ?>
          </h2>
          <button class="eu-modal-close" data-modal="modal-modifier-<?= $cid ?>"
                  aria-label="Fermer">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>
        <form action="index.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="action" value="modifier_commande">
          <input type="hidden" name="commande_id" value="<?= $cid ?>">

          <div class="eu-form-row">
            <div class="eu-form-group">
              <label for="mod-date-<?= $cid ?>">Date de prestation</label>
              <input type="date" id="mod-date-<?= $cid ?>" name="date_prestation"
                     value="<?= htmlspecialchars($cmd['date_prestation']) ?>"
                     min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="eu-form-group">
              <label for="mod-heure-<?= $cid ?>">Heure de livraison</label>
              <input type="time" id="mod-heure-<?= $cid ?>" name="heure_livraison"
                     value="<?= htmlspecialchars(substr($cmd['heure_livraison'], 0, 5)) ?>" required>
            </div>
          </div>

          <div class="eu-form-group">
            <label for="mod-adresse-<?= $cid ?>">Adresse</label>
            <input type="text" id="mod-adresse-<?= $cid ?>" name="adresse_prestation"
                   value="<?= htmlspecialchars($cmd['adresse_prestation']) ?>" required>
          </div>

          <div class="eu-form-row">
            <div class="eu-form-group">
              <label for="mod-ville-<?= $cid ?>">Ville</label>
              <input type="text" id="mod-ville-<?= $cid ?>" name="ville_prestation"
                     value="<?= htmlspecialchars($cmd['ville_prestation']) ?>" required>
            </div>
            <div class="eu-form-group">
              <label for="mod-cp-<?= $cid ?>">Code postal</label>
              <input type="text" id="mod-cp-<?= $cid ?>" name="code_postal_prestation"
                     value="<?= htmlspecialchars($cmd['code_postal_prestation']) ?>" required>
            </div>
          </div>

          <div class="eu-form-row">
            <div class="eu-form-group">
              <label for="mod-pers-<?= $cid ?>">
                Nombre de personnes (min. <?= (int)$cmd['nombre_personne_minimum'] ?>)
              </label>
              <input type="number" id="mod-pers-<?= $cid ?>" name="nombre_personne"
                     value="<?= (int)$cmd['nombre_personne'] ?>"
                     min="<?= (int)$cmd['nombre_personne_minimum'] ?>" required>
            </div>
            <div class="eu-form-group">
              <label for="mod-km-<?= $cid ?>">Distance en km (si hors Bordeaux)</label>
              <input type="number" id="mod-km-<?= $cid ?>" name="distance_km"
                     value="0" min="0" step="0.1">
            </div>
          </div>

          <div class="eu-modal-footer">
            <button type="button" class="eu-btn eu-btn-secondary"
                    data-modal="modal-modifier-<?= $cid ?>">Annuler</button>
            <button type="submit" class="eu-btn eu-btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal : Annuler commande #<?= $cid ?> -->
    <div class="eu-modal-overlay" id="modal-annuler-<?= $cid ?>" style="display:none"
         role="dialog" aria-modal="true" aria-labelledby="modal-annuler-title-<?= $cid ?>">
      <div class="eu-modal">
        <div class="eu-modal-header">
          <h2 id="modal-annuler-title-<?= $cid ?>">
            Annuler <?= htmlspecialchars($cmd['numero_commande']) ?>
          </h2>
          <button class="eu-modal-close" data-modal="modal-annuler-<?= $cid ?>"
                  aria-label="Fermer">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
        </div>
        <p class="eu-modal-body">
          Êtes-vous sûr de vouloir annuler la commande
          <strong><?= htmlspecialchars($cmd['numero_commande']) ?></strong> ?
          Cette action est irréversible.
        </p>
        <form action="index.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="action" value="annuler_commande">
          <input type="hidden" name="commande_id" value="<?= $cid ?>">
          <div class="eu-modal-footer">
            <button type="button" class="eu-btn eu-btn-secondary"
                    data-modal="modal-annuler-<?= $cid ?>">Retour</button>
            <button type="submit" class="eu-btn eu-btn-danger">
              <i class="bi bi-x-circle" aria-hidden="true"></i> Confirmer l'annulation
            </button>
          </div>
        </form>
      </div>
    </div>

  <?php endforeach; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Navigation sidebar — synchronise avec les liens <a href="?section=...">
    document.querySelectorAll('.eu-nav-link').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const section = link.dataset.section;
        document.querySelectorAll('.eu-nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        document.querySelectorAll('.eu-section').forEach(s => s.style.display = 'none');
        document.getElementById('section-' + section).style.display = 'block';
        history.replaceState(null, '', '?section=' + section);
      });
    });

    // Dépliage détail commande
    document.querySelectorAll('.eu-toggle-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        const icon   = btn.querySelector('i');
        const open   = target.style.display === 'none';
        target.style.display = open ? 'block' : 'none';
        icon.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });

    // Ouverture / fermeture modals
    document.querySelectorAll('[data-modal]').forEach(el => {
      el.addEventListener('click', () => {
        const modal = document.getElementById(el.dataset.modal);
        if (!modal) return;
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
      });
    });

    // Fermeture modal en cliquant sur l'overlay
    document.querySelectorAll('.eu-modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.style.display = 'none';
      });
    });
  </script>
</body>
</html>
