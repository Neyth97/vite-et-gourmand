<?php
$token = $_GET['token'] ?? '';
if (!hash_equals('veg-mongo-2026', $token)) {
    http_response_code(403);
    exit('Accès refusé.');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== Diagnostic MongoDB ===\n\n";

// 1. Extension PHP
echo "Extension mongodb chargée : " . (extension_loaded('mongodb') ? "OUI" : "NON") . "\n";
echo "Version extension          : " . (extension_loaded('mongodb') ? phpversion('mongodb') : 'n/a') . "\n\n";

// 2. Autoload
$autoload = file_exists(__DIR__ . '/../vendor/autoload.php');
echo "vendor/autoload.php        : " . ($autoload ? "OUI" : "NON") . "\n";

if ($autoload) {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "Classe MongoDB\\Client      : " . (class_exists('MongoDB\\Client') ? "OUI" : "NON") . "\n\n";
} else {
    echo "\nERREUR : composer install non exécuté ou vendor/ absent.\n";
    exit;
}

// 3. URI
$uri = getenv('MONGODB_URI') ?: '(non définie)';
$uriSafe = preg_replace('/:[^:@]+@/', ':***@', $uri);
echo "MONGODB_URI                : $uriSafe\n\n";

// 4. Connexion
echo "Tentative de connexion...\n";
try {
    $client = new \MongoDB\Client($uri, [], ['serverSelectionTimeoutMS' => 5000]);
    $db     = $client->vite_gourmand;

    // Ping
    $db->command(['ping' => 1]);
    echo "Ping Atlas                 : OK\n";

    // Compter les documents
    $count = $db->commandes->countDocuments();
    echo "Documents dans commandes   : $count\n";

} catch (\Throwable $e) {
    echo "ERREUR : " . get_class($e) . "\n";
    echo "Message : " . $e->getMessage() . "\n";
}
