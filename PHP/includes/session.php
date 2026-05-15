<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    $started = session_start();
    error_log('[VEG-SESSION] start=' . ($started ? 'OK' : 'FAIL') . ' id=' . session_id() . ' save_path=' . ini_get('session.save_path'));
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
        header('Location: /HTML/index.html');
        exit;
    }
}
