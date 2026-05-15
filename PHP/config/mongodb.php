<?php
require_once __DIR__ . '/../../vendor/autoload.php';

function getMongoDB(): \MongoDB\Database
{
    static $db = null;
    if ($db === null) {
        $uri    = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017';
        $client = new \MongoDB\Client($uri);
        $db     = $client->vite_gourmand;
    }
    return $db;
}
