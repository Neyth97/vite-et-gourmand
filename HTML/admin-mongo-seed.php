<?php
// Script one-shot — synchronise MySQL → MongoDB
// Usage : /HTML/admin-mongo-seed.php?token=veg-mongo-2026
// À supprimer après exécution

$token = $_GET['token'] ?? '';
if (!hash_equals('veg-mongo-2026', $token)) {
    http_response_code(403);
    exit('Accès refusé.');
}

require_once '../PHP/config/db.php';
require_once '../PHP/config/mongodb.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getPDO();

    $rows = $pdo->query('
        SELECT c.commande_id, c.menu_id, m.titre AS menu_titre,
               c.prix_total, c.nombre_personne, c.date_commande
        FROM commande c
        JOIN menu m ON m.menu_id = c.menu_id
        ORDER BY c.commande_id
    ')->fetchAll(PDO::FETCH_ASSOC);

    $col = getMongoDB()->commandes;

    // Récupère les commande_id déjà présents dans MongoDB
    $existing = [];
    foreach ($col->find([], ['projection' => ['commande_id' => 1, '_id' => 0]]) as $doc) {
        $existing[(int)$doc['commande_id']] = true;
    }

    $inserted = 0;
    $skipped  = 0;

    foreach ($rows as $row) {
        $id = (int)$row['commande_id'];

        if (isset($existing[$id])) {
            $skipped++;
            continue;
        }

        $ts = strtotime($row['date_commande']);
        $col->insertOne([
            'commande_id'     => $id,
            'menu_id'         => (int)$row['menu_id'],
            'menu_titre'      => $row['menu_titre'],
            'prix_total'      => (float)$row['prix_total'],
            'nombre_personne' => (int)$row['nombre_personne'],
            'date_commande'   => new \MongoDB\BSON\UTCDateTime($ts * 1000),
        ]);

        echo "Insérée : commande #$id — {$row['menu_titre']} — {$row['prix_total']} €\n";
        $inserted++;
    }

    echo "\n---\n";
    echo "Terminé : $inserted insérée(s), $skipped déjà présente(s).\n";
    echo "Total MySQL : " . count($rows) . " commande(s).\n";

} catch (\Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}
