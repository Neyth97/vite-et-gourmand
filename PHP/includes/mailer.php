<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function appUrl(string $path = ''): string
{
    $base = rtrim(getenv('APP_URL') ?: 'http://localhost/vite-et-gourmand', '/');
    return $base . $path;
}

function sendMail(string $to, string $toName, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'localhost';
        $mail->Port       = (int)(getenv('SMTP_PORT') ?: 1025);
        $mail->SMTPSecure = getenv('SMTP_SECURE') ?: '';
        $mail->Timeout    = 10;
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;

        if (getenv('SMTP_USER')) {
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER');
            $mail->Password = getenv('SMTP_PASS') ?: '';
        } else {
            $mail->SMTPAuth = false;
        }

        $from     = getenv('MAIL_FROM')      ?: 'noreply@vite-et-gourmand.fr';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Vite & Gourmand';
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[MAIL ERROR] ' . $mail->ErrorInfo);
        return false;
    }
}

function mailBienvenue(string $email, string $prenom, string $nom): bool
{
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
      <div style="background:#b8860b;padding:24px 32px">
        <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand</h1>
        <p style="color:rgba(255,255,255,.85);margin:4px 0 0">Traiteur professionnel — Bordeaux</p>
      </div>
      <div style="padding:32px">
        <h2 style="color:#b8860b">Bienvenue, ' . htmlspecialchars($prenom) . '&nbsp;!</h2>
        <p>Votre compte a bien été créé sur <strong>Vite &amp; Gourmand</strong>.</p>
        <p>Vous pouvez désormais :</p>
        <ul>
          <li>Parcourir nos menus et passer commande</li>
          <li>Suivre vos commandes en temps réel</li>
          <li>Laisser un avis après chaque prestation</li>
        </ul>
        <a href="' . appUrl('/HTML/connexion.php') . '"
           style="display:inline-block;background:#b8860b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:8px">
          Se connecter
        </a>
      </div>
      <div style="padding:16px 32px;background:#f5f5f5;font-size:12px;color:#888">
        Vite &amp; Gourmand — Bordeaux · Ce message a été envoyé automatiquement.
      </div>
    </div>';

    return sendMail($email, "$prenom $nom", 'Bienvenue sur Vite & Gourmand !', $html);
}

function mailResetPassword(string $email, string $prenom, string $nom, string $token): bool
{
    $lien = appUrl('/HTML/reinitialiser-mot-de-passe.php?token=' . urlencode($token));
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
      <div style="background:#b8860b;padding:24px 32px">
        <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand</h1>
      </div>
      <div style="padding:32px">
        <h2 style="color:#b8860b">Réinitialisation de votre mot de passe</h2>
        <p>Bonjour ' . htmlspecialchars($prenom) . ',</p>
        <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous :</p>
        <a href="' . $lien . '"
           style="display:inline-block;background:#b8860b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin:16px 0">
          Réinitialiser mon mot de passe
        </a>
        <p style="color:#888;font-size:13px">Ce lien est valable <strong>1 heure</strong>. Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message.</p>
      </div>
      <div style="padding:16px 32px;background:#f5f5f5;font-size:12px;color:#888">
        Vite &amp; Gourmand — Bordeaux
      </div>
    </div>';

    return sendMail($email, "$prenom $nom", 'Réinitialisation de votre mot de passe', $html);
}

function mailConfirmationCommande(string $email, string $prenom, string $nom, array $commande): bool
{
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
      <div style="background:#b8860b;padding:24px 32px">
        <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand</h1>
      </div>
      <div style="padding:32px">
        <h2 style="color:#b8860b">Confirmation de votre commande</h2>
        <p>Bonjour ' . htmlspecialchars($prenom) . ',</p>
        <p>Votre commande a bien été enregistrée. Voici le récapitulatif :</p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0">
          <tr style="background:#f9f9f9">
            <td style="padding:10px;border:1px solid #eee"><strong>N° commande</strong></td>
            <td style="padding:10px;border:1px solid #eee">' . htmlspecialchars($commande['numero']) . '</td>
          </tr>
          <tr>
            <td style="padding:10px;border:1px solid #eee"><strong>Menu</strong></td>
            <td style="padding:10px;border:1px solid #eee">' . htmlspecialchars($commande['menu']) . '</td>
          </tr>
          <tr style="background:#f9f9f9">
            <td style="padding:10px;border:1px solid #eee"><strong>Date de prestation</strong></td>
            <td style="padding:10px;border:1px solid #eee">' . htmlspecialchars($commande['date']) . ' à ' . htmlspecialchars($commande['heure']) . '</td>
          </tr>
          <tr>
            <td style="padding:10px;border:1px solid #eee"><strong>Nombre de personnes</strong></td>
            <td style="padding:10px;border:1px solid #eee">' . (int)$commande['personnes'] . '</td>
          </tr>
          <tr style="background:#f9f9f9">
            <td style="padding:10px;border:1px solid #eee"><strong>Adresse de prestation</strong></td>
            <td style="padding:10px;border:1px solid #eee">' . htmlspecialchars($commande['adresse']) . '</td>
          </tr>
          <tr>
            <td style="padding:10px;border:1px solid #eee"><strong>Total</strong></td>
            <td style="padding:10px;border:1px solid #eee"><strong>' . number_format((float)$commande['total'], 2, ',', ' ') . ' €</strong></td>
          </tr>
        </table>
        <p>Vous pouvez suivre votre commande depuis votre espace client.</p>
        <a href="' . appUrl('/HTML/espace-utilisateur/index.php') . '"
           style="display:inline-block;background:#b8860b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none">
          Mon espace
        </a>
      </div>
      <div style="padding:16px 32px;background:#f5f5f5;font-size:12px;color:#888">
        Vite &amp; Gourmand — Bordeaux
      </div>
    </div>';

    return sendMail($email, "$prenom $nom", 'Confirmation de votre commande ' . $commande['numero'], $html);
}

function mailCommandeTerminee(string $email, string $prenom, string $nom, string $numeroCommande, int $commandeId): bool
{
    $lien = appUrl('/HTML/espace-utilisateur/index.php?section=commandes');
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
      <div style="background:#b8860b;padding:24px 32px">
        <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand</h1>
      </div>
      <div style="padding:32px">
        <h2 style="color:#b8860b">Votre commande est terminée</h2>
        <p>Bonjour ' . htmlspecialchars($prenom) . ',</p>
        <p>Votre commande <strong>' . htmlspecialchars($numeroCommande) . '</strong> est maintenant terminée.</p>
        <p>Nous serions ravis d\'avoir votre avis sur cette prestation. Votre retour nous aide à améliorer nos services.</p>
        <a href="' . $lien . '"
           style="display:inline-block;background:#b8860b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:8px">
          Laisser un avis
        </a>
        <p style="color:#888;font-size:13px;margin-top:24px">Merci de votre confiance.</p>
      </div>
      <div style="padding:16px 32px;background:#f5f5f5;font-size:12px;color:#888">
        Vite &amp; Gourmand — Bordeaux
      </div>
    </div>';

    return sendMail($email, "$prenom $nom", 'Votre commande ' . $numeroCommande . ' est terminée — laissez votre avis !', $html);
}

function mailRetourMateriel(string $email, string $prenom, string $nom, string $numeroCommande): bool
{
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
      <div style="background:#b8860b;padding:24px 32px">
        <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand</h1>
      </div>
      <div style="padding:32px">
        <h2 style="color:#b8860b">Retour de matériel — commande ' . htmlspecialchars($numeroCommande) . '</h2>
        <p>Bonjour ' . htmlspecialchars($prenom) . ',</p>
        <p>Dans le cadre de votre commande <strong>' . htmlspecialchars($numeroCommande) . '</strong>, du matériel vous a été prêté.</p>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:16px;margin:16px 0">
          <p style="margin:0;color:#856404">
            <strong>Important :</strong> vous disposez de <strong>10 jours ouvrés</strong> pour restituer ce matériel.<br>
            Passé ce délai, des frais de <strong>600 €</strong> seront facturés conformément aux conditions générales de vente.
          </p>
        </div>
        <p>Pour organiser la restitution, contactez-nous :</p>
        <ul>
          <li>Par email : <a href="mailto:contact@vite-et-gourmand.fr">contact@vite-et-gourmand.fr</a></li>
          <li>Via le formulaire de contact de notre site</li>
        </ul>
        <a href="' . appUrl('/HTML/contact.php') . '"
           style="display:inline-block;background:#b8860b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none">
          Nous contacter
        </a>
      </div>
      <div style="padding:16px 32px;background:#f5f5f5;font-size:12px;color:#888">
        Vite &amp; Gourmand — Bordeaux · Conditions générales de vente disponibles sur notre site.
      </div>
    </div>';

    return sendMail($email, "$prenom $nom", 'Retour de matériel requis — commande ' . $numeroCommande, $html);
}

function mailCreationCompteEmploye(string $email): bool
{
    $html = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333">
      <div style="background:#b8860b;padding:24px 32px">
        <h1 style="color:#fff;margin:0;font-size:22px">Vite &amp; Gourmand</h1>
      </div>
      <div style="padding:32px">
        <h2 style="color:#b8860b">Votre compte employé a été créé</h2>
        <p>Un compte employé a été créé pour l\'adresse <strong>' . htmlspecialchars($email) . '</strong>.</p>
        <p>Pour obtenir votre mot de passe, rapprochez-vous de l\'administrateur.</p>
        <p>Une fois en possession de vos identifiants, vous pourrez vous connecter à votre espace :</p>
        <a href="' . appUrl('/HTML/connexion.php') . '"
           style="display:inline-block;background:#b8860b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:8px">
          Se connecter
        </a>
      </div>
      <div style="padding:16px 32px;background:#f5f5f5;font-size:12px;color:#888">
        Vite &amp; Gourmand — Bordeaux
      </div>
    </div>';

    return sendMail($email, 'Employé', 'Votre compte Vite & Gourmand a été créé', $html);
}
