<?php

ini_set('display_errors', 0);
error_reporting(0);

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
        header('Location: /HTML/connexion.php');
        exit;
    }
}

function requireRole(int $roleId): void
{
    requireConnexion();
    if (getRoleId() !== $roleId) {
        header('Location: /HTML/index.php');
        exit;
    }
}
