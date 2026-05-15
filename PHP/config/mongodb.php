<?php
require_once __DIR__ . '/../../vendor/autoload.php';

function getMongoDB(): \MongoDB\Database
{
    static $db = null;
    if ($db === null) {
        $client = new \MongoDB\Client('mongodb://localhost:27017');
        $db     = $client->vite_gourmand;
    }
    return $db;
}
