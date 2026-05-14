<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
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
        header('Location: /vite-et-gourmand/HTML/connexion.html');
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
