<?php
// Script de reset one-time — À SUPPRIMER après utilisation
if ($_SERVER['QUERY_STRING'] !== 'token=veg-setup-2026') {
    http_response_code(404);
    exit('Not found');
}

require_once '../PHP/config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$email || !$password || !$confirm) {
        $message = 'Tous les champs sont requis.';
    } elseif ($password !== $confirm) {
        $message = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 10) {
        $message = 'Mot de passe trop court (minimum 10 caractères).';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = getPDO()->prepare('UPDATE utilisateur SET password = ? WHERE email = ?');
        $ok   = $stmt->execute([$hash, $email]);
        if ($ok && $stmt->rowCount() > 0) {
            $message = 'Mot de passe mis à jour pour ' . htmlspecialchars($email) . '. Supprimez ce fichier maintenant.';
        } else {
            $message = 'Email introuvable dans la base.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Reset mot de passe — one-time</title>
  <style>
    body { font-family: monospace; max-width: 480px; margin: 80px auto; padding: 1rem; background:#111; color:#eee; }
    input { display:block; width:100%; padding:.5rem; margin:.4rem 0 1rem; background:#222; border:1px solid #444; color:#eee; }
    button { background:#c8a84b; border:none; color:#000; padding:.6rem 1.4rem; cursor:pointer; font-weight:bold; }
    .msg { padding:.6rem; background:#1e3a1e; border:1px solid #4caf50; margin-bottom:1rem; }
    .err { background:#3a1e1e; border-color:#f44; }
  </style>
</head>
<body>
  <h2>Reset mot de passe</h2>
  <?php if ($message): ?>
    <div class="msg"><?= $message ?></div>
  <?php endif; ?>
  <form method="post">
    <label>Email du compte</label>
    <input type="email" name="email" value="jose@vitegourmand.fr" required>
    <label>Nouveau mot de passe</label>
    <input type="password" name="password" required>
    <label>Confirmer</label>
    <input type="password" name="confirm" required>
    <button type="submit">Mettre à jour</button>
  </form>
</body>
</html>
