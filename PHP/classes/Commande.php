<?php

require_once __DIR__ . '/../config/db.php';

class Commande
{
    private PDO $pdo;

    private const STATUTS_VALIDES = [
        'en_attente', 'accepte', 'en_preparation',
        'en_cours_livraison', 'livre', 'attente_retour_materiel',
        'terminee', 'annulee',
    ];

    private const TRANSITIONS = [
        'en_attente'              => ['accepte'],
        'accepte'                 => ['en_preparation'],
        'en_preparation'          => ['en_cours_livraison'],
        'en_cours_livraison'      => ['livre'],
        'livre'                   => ['terminee', 'attente_retour_materiel'],
        'attente_retour_materiel' => ['terminee'],
        'terminee'                => [],
        'annulee'                 => [],
    ];

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function calculerPrix(float $prixParPersonne, int $nbPersonnes, int $nbMin, float $distanceKm, string $ville): array
    {
        $prixMenu = $nbPersonnes * $prixParPersonne;

        if ($nbPersonnes >= $nbMin + 5) {
            $prixMenu *= 0.9;
        }

        $prixLivraison = 0.00;
        if (strtolower($ville) !== 'bordeaux') {
            $prixLivraison = 5.00 + (max(0, $distanceKm) * 0.59);
        }

        return [
            'prix_menu'      => round($prixMenu, 2),
            'prix_livraison' => round($prixLivraison, 2),
            'prix_total'     => round($prixMenu + $prixLivraison, 2),
        ];
    }

    public function creer(array $donnees): array
    {
        $numero = 'VG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO commande
                 (numero_commande, utilisateur_id, menu_id, date_prestation, heure_livraison,
                  adresse_prestation, ville_prestation, code_postal_prestation,
                  nombre_personne, prix_menu, prix_livraison, prix_total, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'en_attente\')'
            )->execute([
                $numero,
                $donnees['utilisateur_id'],
                $donnees['menu_id'],
                $donnees['date_prestation'],
                $donnees['heure_livraison'],
                $donnees['adresse_prestation'],
                $donnees['ville_prestation'],
                $donnees['code_postal_prestation'],
                $donnees['nombre_personne'],
                $donnees['prix_menu'],
                $donnees['prix_livraison'],
                $donnees['prix_total'],
            ]);

            $commandeId = (int)$this->pdo->lastInsertId();

            $this->pdo->prepare(
                'INSERT INTO commande_historique (commande_id, statut, commentaire)
                 VALUES (?, \'en_attente\', \'Commande passée par le client\')'
            )->execute([$commandeId]);

            $this->pdo->prepare(
                'UPDATE menu SET quantite_restante = quantite_restante - 1
                 WHERE menu_id = ? AND quantite_restante > 0'
            )->execute([$donnees['menu_id']]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return ['id' => $commandeId, 'numero' => $numero];
    }

    public function listerParUtilisateur(int $utilisateurId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.commande_id, c.numero_commande, c.date_commande, c.date_prestation,
                    c.heure_livraison, c.nombre_personne, c.prix_total, c.statut,
                    m.titre AS menu_titre
             FROM commande c
             JOIN menu m ON c.menu_id = m.menu_id
             WHERE c.utilisateur_id = ?
             ORDER BY c.date_commande DESC'
        );
        $stmt->execute([$utilisateurId]);

        return $stmt->fetchAll();
    }

    public function listerTout(array $filtres = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtres['statut']) && in_array($filtres['statut'], self::STATUTS_VALIDES, true)) {
            $where[]  = 'c.statut = ?';
            $params[] = $filtres['statut'];
        }

        if (!empty($filtres['client'])) {
            $like     = '%' . $filtres['client'] . '%';
            $where[]  = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
            array_push($params, $like, $like, $like);
        }

        $clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare(
            "SELECT c.commande_id, c.numero_commande, c.date_commande, c.date_prestation,
                    c.heure_livraison, c.nombre_personne, c.prix_total, c.statut,
                    c.motif_annulation, c.mode_contact_annulation,
                    m.titre AS menu_titre,
                    u.nom AS client_nom, u.prenom AS client_prenom,
                    u.email AS client_email, u.telephone AS client_tel
             FROM commande c
             JOIN menu m ON c.menu_id = m.menu_id
             JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
             $clause
             ORDER BY c.date_commande DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function trouverParId(int $commandeId): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, m.titre AS menu_titre, u.nom AS client_nom, u.prenom AS client_prenom,
                    u.email AS client_email, u.telephone AS client_tel
             FROM commande c
             JOIN menu m ON c.menu_id = m.menu_id
             JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
             WHERE c.commande_id = ?'
        );
        $stmt->execute([$commandeId]);

        return $stmt->fetch();
    }

    public function changerStatut(int $commandeId, string $nouveauStatut): bool
    {
        $commande = $this->trouverParId($commandeId);
        if (!$commande) {
            return false;
        }

        $statutActuel = $commande['statut'];
        $transitionsAutorisees = self::TRANSITIONS[$statutActuel] ?? [];

        if (!in_array($nouveauStatut, $transitionsAutorisees, true)) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE commande SET statut = ? WHERE commande_id = ?')
                ->execute([$nouveauStatut, $commandeId]);

            $this->pdo->prepare('INSERT INTO commande_historique (commande_id, statut) VALUES (?, ?)')
                ->execute([$commandeId, $nouveauStatut]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }

        return true;
    }

    public function annuler(int $commandeId, string $motif, string $modeContact): bool
    {
        if (!in_array($modeContact, ['gsm', 'mail'], true) || empty($motif)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT commande_id FROM commande WHERE commande_id = ? AND statut NOT IN ("terminee","annulee")'
        );
        $stmt->execute([$commandeId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'UPDATE commande SET statut="annulee", motif_annulation=?, mode_contact_annulation=? WHERE commande_id=?'
            )->execute([$motif, $modeContact, $commandeId]);

            $this->pdo->prepare(
                'INSERT INTO commande_historique (commande_id, statut, commentaire) VALUES (?, "annulee", ?)'
            )->execute([$commandeId, $motif]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }

        return true;
    }

    public function getTransitionsPossibles(string $statut): array
    {
        return self::TRANSITIONS[$statut] ?? [];
    }
}
