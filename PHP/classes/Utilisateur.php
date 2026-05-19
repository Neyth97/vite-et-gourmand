<?php

require_once __DIR__ . '/../config/db.php';

class Utilisateur
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function authentifier(string $email, string $motDePasse): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, password, role_id
             FROM utilisateur WHERE email = ?'
        );
        $stmt->execute([strtolower(trim($email))]);
        $utilisateur = $stmt->fetch();

        if (!$utilisateur || !password_verify($motDePasse, $utilisateur['password'])) {
            return false;
        }

        return $utilisateur;
    }

    public function inscrire(array $donnees): int
    {
        $this->pdo->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password, telephone, adresse, code_postal, ville, role_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 3)'
        )->execute([
            $donnees['nom'],
            $donnees['prenom'],
            strtolower(trim($donnees['email'])),
            password_hash($donnees['password'], PASSWORD_BCRYPT),
            $donnees['telephone'] ?? null,
            $donnees['adresse'] ?? null,
            $donnees['code_postal'] ?? null,
            $donnees['ville'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function trouverParId(int $utilisateurId): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, telephone, adresse, code_postal, ville, role_id
             FROM utilisateur WHERE utilisateur_id = ?'
        );
        $stmt->execute([$utilisateurId]);

        return $stmt->fetch();
    }

    public function trouverParEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, role_id FROM utilisateur WHERE email = ?'
        );
        $stmt->execute([strtolower(trim($email))]);

        return $stmt->fetch();
    }

    public function mettreAJour(int $utilisateurId, array $donnees): bool
    {
        return $this->pdo->prepare(
            'UPDATE utilisateur SET nom=?, prenom=?, telephone=?, adresse=?, code_postal=?, ville=?
             WHERE utilisateur_id=?'
        )->execute([
            $donnees['nom'],
            $donnees['prenom'],
            $donnees['telephone'] ?? null,
            $donnees['adresse'] ?? null,
            $donnees['code_postal'] ?? null,
            $donnees['ville'] ?? null,
            $utilisateurId,
        ]);
    }

    public function changerMotDePasse(int $utilisateurId, string $ancienMdp, string $nouveauMdp): bool
    {
        $stmt = $this->pdo->prepare('SELECT password FROM utilisateur WHERE utilisateur_id = ?');
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($ancienMdp, $row['password'])) {
            return false;
        }

        return $this->pdo->prepare('UPDATE utilisateur SET password=? WHERE utilisateur_id=?')
            ->execute([password_hash($nouveauMdp, PASSWORD_BCRYPT), $utilisateurId]);
    }

    public function emailDejaUtilise(string $email, ?int $excluId = null): bool
    {
        if ($excluId !== null) {
            $stmt = $this->pdo->prepare('SELECT 1 FROM utilisateur WHERE email = ? AND utilisateur_id != ?');
            $stmt->execute([strtolower(trim($email)), $excluId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM utilisateur WHERE email = ?');
            $stmt->execute([strtolower(trim($email))]);
        }

        return (bool)$stmt->fetch();
    }
}
