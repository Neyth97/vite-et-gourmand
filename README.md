# Vite & Gourmand

Application web de traiteur professionnel permettant la présentation des menus, la prise de commande en ligne et la gestion des espaces client, employé et administrateur.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Front-end | HTML5, CSS3, Bootstrap 5, JavaScript |
| Back-end | PHP 8.3 (PDO) |
| Base de données relationnelle | MySQL 8 |
| Base de données NoSQL | MongoDB Atlas |
| Serveur local | Laragon (Windows) |
| Serveur de production | FrankenPHP sur Railway |

---

## Prérequis

- [Laragon](https://laragon.org/) (inclut Apache/Nginx, PHP 8.x, MySQL)
- PHP **8.3** avec les extensions : `pdo_mysql`, `mongodb`, `zip`, `curl`
- [Composer](https://getcomposer.org/) v2
- Un compte [MongoDB Atlas](https://www.mongodb.com/atlas) (gratuit, tier M0)

---

## Installation locale

### 1. Cloner le dépôt

```bash
git clone https://github.com/Neyth97/vite-et-gourmand.git
cd vite-et-gourmand
```

Placez le dossier dans le répertoire `www` de Laragon :
```
C:\laragon\www\vite-et-gourmand\
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer PHP

Dans le `php.ini` de Laragon (Menu Laragon → PHP → `php.ini`), vérifiez ou ajoutez :

```ini
output_buffering = 4096
extension=mongodb
extension=pdo_mysql
extension=zip
extension=curl
```

> `output_buffering = 4096` est indispensable pour le bon fonctionnement des sessions PHP.

Redémarrez Laragon après modification.

### 4. Créer la base de données MySQL

Dans **phpMyAdmin** (accessible via Laragon) ou en ligne de commande :

```sql
CREATE DATABASE vite_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vg_user'@'localhost' IDENTIFIED BY 'pr!&AjKtrL#y5AEt';
GRANT ALL PRIVILEGES ON vite_gourmand.* TO 'vg_user'@'localhost';
FLUSH PRIVILEGES;
```

Ensuite, importez les fichiers SQL dans l'ordre :

```bash
mysql -u vg_user -p vite_gourmand < SQL/create.sql
mysql -u vg_user -p vite_gourmand < SQL/seed.sql
```

Ou via phpMyAdmin : sélectionnez la base `vite_gourmand` → onglet **Importer** → importez `create.sql` puis `seed.sql`.

### 5. Configurer MongoDB

1. Créez un cluster gratuit sur [MongoDB Atlas](https://www.mongodb.com/atlas)
2. Créez un utilisateur de base de données (Database Access)
3. Autorisez votre IP (Network Access → Add IP Address)
4. Récupérez l'URI de connexion : **Connect → Drivers → PHP**

L'URI est de la forme :
```
mongodb+srv://<user>:<password>@cluster0.xxxxx.mongodb.net/vite_gourmand?appName=Cluster0
```

Définissez la variable d'environnement (ou modifiez temporairement `PHP/config/mongodb.php`) :

```bash
# Windows (PowerShell)
$env:MONGODB_URI = "mongodb+srv://..."
```

### 6. Variables d'environnement (optionnel en local)

Pour activer les emails et personnaliser l'URL, créez un fichier `.env` ou définissez ces variables système :

| Variable | Description | Valeur par défaut |
|---|---|---|
| `DB_HOST` | Hôte MySQL | `localhost` |
| `DB_PORT` | Port MySQL | `3306` |
| `DB_NAME` | Nom de la base | `vite_gourmand` |
| `DB_USER` | Utilisateur MySQL | `vg_user` |
| `DB_PASS` | Mot de passe MySQL | *(voir db.php)* |
| `MONGODB_URI` | URI MongoDB Atlas | — |
| `APP_URL` | URL de base de l'application | `http://localhost/vite-et-gourmand` |
| `BREVO_API_KEY` | Clé API Brevo pour les emails | — |
| `MAIL_FROM` | Adresse expéditeur | `noreply@vite-et-gourmand.fr` |
| `MAIL_FROM_NAME` | Nom expéditeur | `Vite & Gourmand` |

> En local, les emails ne sont pas envoyés si `BREVO_API_KEY` est absente — cela n'empêche pas le fonctionnement de l'application.

---

## Accès à l'application

Une fois Laragon démarré, ouvrez :

```
http://localhost/vite-et-gourmand
```

---

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `jose@vitegourmand.fr` | `Admin@Vite2026!` |
| Employé | `julie@vitegourmand.fr` | `Employe@Vite2026!` |
| Utilisateur | `alice@example.com` | `User@Vite2026!` |

---

## Déploiement en production

L'application est déployée sur **Railway** via Docker (FrankenPHP).

Voir la documentation technique pour le détail complet. En résumé :

1. Pusher sur `main` → Railway déclenche un build automatique via le `Dockerfile`
2. Les variables d'environnement sont configurées dans Railway → Variables
3. La base MySQL est fournie par le plugin Railway MySQL
4. MongoDB Atlas est hébergé séparément (tier M0 gratuit)

**URL de production :**
```
https://vite-et-gourmand-production-9367.up.railway.app
```

---

## Structure du projet

```
vite-et-gourmand/
├── assets/          # Images, logo, icônes
├── CSS/             # Feuilles de style
├── HTML/            # Pages PHP (front + espaces)
│   ├── espace-admin/
│   ├── espace-employe/
│   └── espace-utilisateur/
├── JS/              # Scripts JavaScript
├── PHP/
│   ├── config/      # Connexion BDD (db.php, mongodb.php)
│   └── includes/    # Session, mailer
├── SQL/
│   ├── create.sql   # Création des tables
│   └── seed.sql     # Données de démonstration
├── Caddyfile        # Configuration FrankenPHP (production)
├── Dockerfile       # Image Docker Railway
├── composer.json    # Dépendances PHP
└── php-session.ini  # Configuration PHP sessions (production)
```
