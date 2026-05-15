<?php

ini_set('display_errors', 0);
error_reporting(0);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
// ini_set('session.cookie_secure', 1); // décommenter en production (HTTPS)
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isConnecte(): bool
{
    return isset($_SESSION['utilisateur_id']);
}

function getRoleId(): ?int
{
    return $_SESSION['role_id'] ?? null;
}

function isAdmin(): bool
{
    return getRoleId() === 1;
}

function isEmploye(): bool
{
    return getRoleId() === 2;
}

function isUtilisateur(): bool
{
    return getRoleId() === 3;
}

function requireConnexion(): void
{
    if (!isConnecte()) {
        header('Location: /vite-et-gourmand/HTML/connexion.php');
        exit;
    }
}

function requireRole(int $roleId): void
{
    requireConnexion();
    if (getRoleId() !== $roleId) {
        header('Location: /vite-et-gourmand/HTML/index.html');
        exit;
    }
}
