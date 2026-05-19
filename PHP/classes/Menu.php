<?php

require_once __DIR__ . '/../config/db.php';

class Menu
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function lister(bool $seulementActifs = false): array
    {
        $condition = $seulementActifs ? 'WHERE m.actif = 1 AND m.quantite_restante > 0' : '';

        return $this->pdo->query(
            "SELECT m.menu_id, m.titre, m.description, m.nombre_personne_minimum, m.prix_par_personne,
                    m.conditions, m.quantite_restante, m.actif, m.theme_id, m.regime_id,
                    t.libelle AS theme_libelle, r.libelle AS regime_libelle,
                    (SELECT chemin FROM menu_image WHERE menu_id = m.menu_id ORDER BY ordre ASC LIMIT 1) AS image
             FROM menu m
             JOIN theme t ON m.theme_id = t.theme_id
             JOIN regime r ON m.regime_id = r.regime_id
             $condition
             ORDER BY m.actif DESC, m.titre ASC"
        )->fetchAll();
    }

    public function listerDisponibles(): array
    {
        return $this->pdo->query(
            'SELECT menu_id, titre, nombre_personne_minimum, prix_par_personne, quantite_restante, conditions
             FROM menu WHERE actif = 1 AND quantite_restante > 0 ORDER BY titre'
        )->fetchAll();
    }

    public function trouverParId(int $menuId, bool $seulementActif = false): array|false
    {
        $condition = $seulementActif ? 'AND actif = 1' : '';
        $stmt = $this->pdo->prepare(
            "SELECT menu_id, titre, description, nombre_personne_minimum, prix_par_personne,
                    quantite_restante, conditions, actif, theme_id, regime_id
             FROM menu WHERE menu_id = ? $condition"
        );
        $stmt->execute([$menuId]);

        return $stmt->fetch();
    }

    public function creer(array $donnees): int
    {
        $this->pdo->prepare(
            'INSERT INTO menu (titre, description, theme_id, regime_id, nombre_personne_minimum,
             prix_par_personne, conditions, quantite_restante)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $donnees['titre'],
            $donnees['description'] ?: null,
            $donnees['theme_id'],
            $donnees['regime_id'],
            $donnees['nombre_personne_minimum'],
            $donnees['prix_par_personne'],
            $donnees['conditions'] ?: null,
            $donnees['quantite_restante'] ?? 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function modifier(int $menuId, array $donnees): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE menu SET titre=?, description=?, theme_id=?, regime_id=?,
             nombre_personne_minimum=?, prix_par_personne=?, conditions=?, quantite_restante=?, actif=?
             WHERE menu_id=?'
        );

        return $stmt->execute([
            $donnees['titre'],
            $donnees['description'] ?: null,
            $donnees['theme_id'],
            $donnees['regime_id'],
            $donnees['nombre_personne_minimum'],
            $donnees['prix_par_personne'],
            $donnees['conditions'] ?: null,
            $donnees['quantite_restante'] ?? 0,
            $donnees['actif'] ?? 1,
            $menuId,
        ]);
    }

    public function desactiver(int $menuId): bool
    {
        return $this->pdo->prepare('UPDATE menu SET actif = 0 WHERE menu_id = ?')
            ->execute([$menuId]);
    }

    public function validerDonnees(array $donnees): array
    {
        $erreurs = [];

        if (empty($donnees['titre'])) {
            $erreurs[] = 'Titre requis.';
        }
        if (empty($donnees['theme_id'])) {
            $erreurs[] = 'Thème requis.';
        }
        if (empty($donnees['regime_id'])) {
            $erreurs[] = 'Régime requis.';
        }
        if (empty($donnees['nombre_personne_minimum']) || $donnees['nombre_personne_minimum'] < 1) {
            $erreurs[] = 'Nombre de personnes minimum invalide.';
        }
        if (!isset($donnees['prix_par_personne']) || $donnees['prix_par_personne'] < 0) {
            $erreurs[] = 'Prix invalide.';
        }

        return $erreurs;
    }
}
