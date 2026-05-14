<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$theme    = trim($_GET['theme']    ?? '');
$regime   = trim($_GET['regime']   ?? '');
$prix_min = (float)($_GET['prix_min'] ?? 0);
$prix_max = (float)($_GET['prix_max'] ?? 0);
$personnes = (int)($_GET['personnes'] ?? 0);

$sql = 'SELECT m.menu_id, m.titre, m.description, m.nombre_personne_minimum,
               m.prix_par_personne, m.quantite_restante,
               t.libelle AS theme, r.libelle AS regime,
               (SELECT chemin FROM menu_image WHERE menu_id = m.menu_id ORDER BY ordre ASC LIMIT 1) AS image
        FROM menu m
        JOIN theme t ON m.theme_id = t.theme_id
        JOIN regime r ON m.regime_id = r.regime_id
        WHERE m.actif = 1';

$params = [];

if ($theme) {
    $sql     .= ' AND t.libelle = ?';
    $params[] = $theme;
}
if ($regime) {
    $sql     .= ' AND r.libelle = ?';
    $params[] = $regime;
}
if ($prix_min > 0) {
    $sql     .= ' AND (m.nombre_personne_minimum * m.prix_par_personne) >= ?';
    $params[] = $prix_min;
}
if ($prix_max > 0) {
    $sql     .= ' AND (m.nombre_personne_minimum * m.prix_par_personne) <= ?';
    $params[] = $prix_max;
}
if ($personnes > 0) {
    $sql     .= ' AND m.nombre_personne_minimum <= ?';
    $params[] = $personnes;
}

$sql .= ' ORDER BY m.menu_id ASC';

$stmt = getPDO()->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll());
