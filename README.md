# Vite & Gourmand — ECF Studi (DWWM)

Application web de commande de menus traiteur pour « Vite & Gourmand », une entreprise **fictive** de Bordeaux (Julie et José, 25 ans de métier), réalisée pour l'Évaluation en Cours de Formation du titre Développeur Web et Web Mobile.

Les visiteurs consultent et filtrent les menus. Les clients commandent (livraison, retrait ou sur place), suivent et modifient leurs commandes, puis laissent un avis. L'équipe gère les commandes, les menus, les plats, les horaires et les avis. L'administrateur a en plus les employés, le chiffre d'affaires et les statistiques.

## 1. Stack

| Couche | Choix |
|---|---|
| Back | PHP 8.2+ sans framework, MVC maison (routeur, conteneur d'injection, middlewares) |
| Base relationnelle | MariaDB, accès en PDO avec requêtes préparées |
| Base NoSQL | MongoDB (extension `mongodb` + bibliothèque `mongodb/mongodb`) |
| Front | HTML/PHP, Bootstrap 5, JavaScript natif (sans bundler ni npm) |
| Dépendances Composer | `mongodb/mongodb`, `giggsey/libphonenumber-for-php` (téléphones), `phpmailer/phpmailer` (SMTP) |
| Bibliothèque front locale | intl-tel-input 29.5.3 (champs téléphone), dans `public/assets/vendor/` |

## 2. Prérequis

- **PHP 8.2 ou plus récent** (le projet est testé en local avec PHP 8.5.0 de WAMP).
- **Extensions PHP** : `pdo_mysql`, `mongodb` (2.1 ou plus), `mbstring`, `openssl` (SMTP en TLS et appels HTTPS à Google), `ctype`, `filter`, `hash` et `iconv` (demandées par les dépendances). Vérifier avec `php -m`.
- `allow_url_fopen = On` dans `php.ini` : les appels à l'API Google passent par `file_get_contents`.
- **Composer 2**.
- **MariaDB** (10.x ou 11.x) et **MongoDB** (6 ou plus) avec `mongosh`.
- Une connexion Internet pour Bootstrap JS (chargé depuis jsDelivr), les photos de démonstration (Unsplash) et l'API Google Maps.

### Activer l'extension `mongodb` sous Windows

1. Repérer la version de PHP, son architecture et le mode thread safe (TS) ou non (NTS) : `php -v` (par exemple `PHP 8.5.0 … ZTS … x64` = thread safe, 64 bits).
2. Télécharger la DLL correspondante (`php_mongodb.dll`, version 2.1 ou plus) depuis les releases du dépôt GitHub `mongodb/mongo-php-driver`.
3. La copier dans le dossier `ext` de PHP (sous WAMP : `C:\wamp64\bin\php\php8.x.x\ext\`).
4. Ajouter `extension=mongodb` dans le `php.ini` utilisé par la ligne de commande (`php --ini` indique lequel) et, sous WAMP, dans celui d'Apache.
5. Vérifier : `php -m` doit lister `mongodb`.

## 3. Installation

```bash
composer install
cp .env.example .env          # PowerShell : Copy-Item .env.example .env
```

Renseigner ensuite `.env` :

| Variable | Rôle | Exemple en local |
|---|---|---|
| `APP_ENV` | Environnement | `development` |
| `APP_DEBUG` | `true` affiche les erreurs PHP (développement uniquement) | `false` |
| `APP_URL` | Adresse du site, utilisée dans les liens des e-mails (mot de passe oublié…) | `http://localhost:8000` |
| `GOOGLE_MAPS_API_KEY` | Clé Google (API Places pour l'autocomplétion, API Distance Matrix pour les frais de livraison). Sans clé, les livraisons hors Bordeaux sont refusées. | votre clé |
| `HOSTING_NAME`, `HOSTING_ADDRESS`, `HOSTING_WEBSITE` | Hébergeur affiché dans les mentions légales | vide en local |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion MariaDB | `127.0.0.1`, `3306`, `viteetgourmand`, `root`, vide |
| `MONGO_CONNECTION_STRING` | URI MongoDB complète (prioritaire sur les variables suivantes) | voir la section 4 |
| `MONGO_DATABASE`, `MONGO_HOST`, `MONGO_PORT`, `MONGO_USERNAME`, `MONGO_PASSWORD`, `MONGO_AUTH_SOURCE` | Connexion MongoDB, utilisée si l'URI est vide | `viteetgourmand`, `127.0.0.1`, `27017`… |
| `SESSION_NAME`, `SESSION_LIFETIME`, `SESSION_SECURE` | Cookie de session (durée en minutes ; `SESSION_SECURE=true` seulement en HTTPS) | `viteetgourmand_session`, `120`, `false` |
| `PASSWORD_HASH_COST` | Coût bcrypt des mots de passe | `12` |
| `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | Serveur SMTP (PHPMailer). Sans `MAIL_HOST`, aucun e-mail ne part (l'erreur est écrite dans le journal PHP). `MAIL_ENCRYPTION` : `tls`, `ssl` ou `none`. | Mailpit : `smtp`, `127.0.0.1`, `1025`, vide, vide, `none` |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Expéditeur des e-mails | `noreply@viteetgourmand.local` |
| `MAIL_TO_ADDRESS` | Adresse de l'entreprise (contact, nouvelles commandes, devis) | `contact@viteetgourmand.local` |

Pour voir les e-mails en local sans vrai serveur, on peut utiliser Mailpit (SMTP sur le port 1025, interface sur http://localhost:8025).

## 4. Import des bases (dans cet ordre)

### 4.1 MariaDB : `schema.sql` puis `seed.sql`

`schema.sql` **supprime et recrée** toutes les tables (base `viteetgourmand`). `seed.sql` peut être relancé : il ne touche ni aux autres comptes, ni aux commandes, devis ou messages existants. Il remet par contre dans leur état d'origine les 3 comptes de démonstration, les horaires, le catalogue de démonstration (allergènes, plats 1 à 32, menus 1 à 9, leurs compositions et les allergènes des plats), ce qui écrase les modifications faites dans l'admin sur ces éléments. Il ajoute aussi 5 commandes terminées de démonstration (n° 1001 à 1005) si elles n'existent pas.

```bash
mysql -u root -p -e "source database/schema.sql"
mysql -u root -p -e "source database/seed.sql"
```

Sous WAMP, si `mysql` n'est pas dans le PATH (PowerShell, à adapter à votre version) :

```powershell
& "C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe" -u root -p -e "source database/schema.sql"
& "C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe" -u root -p -e "source database/seed.sql"
```

### 4.2 MongoDB : `mongodb-init.js`

Le script crée les collections `menu_images`, `comments` et `menu_statistics` et leurs index, puis charge les galeries, les avis et les statistiques. Il ne supprime rien d'autre.

**Avec authentification** (configuration de `.env.example`) : créer une fois l'utilisateur applicatif, en se connectant avec un compte administrateur MongoDB :

```javascript
// dans mongosh, connecté en administrateur
use viteetgourmand
db.createUser({ user: "vgt_app", pwd: "MotDePasseSolide", roles: [{ role: "readWrite", db: "viteetgourmand" }] })
```

puis lancer le script avec ce compte (et reporter le même mot de passe dans `.env`) :

```bash
mongosh "mongodb://vgt_app:MotDePasseSolide@127.0.0.1:27017/viteetgourmand?authSource=viteetgourmand" --file database/mongodb-init.js
```

**Sans authentification** (MongoDB local sans contrôle d'accès) : mettre `MONGO_CONNECTION_STRING=mongodb://127.0.0.1:27017/viteetgourmand` dans `.env`, puis :

```bash
mongosh "mongodb://127.0.0.1:27017/viteetgourmand" --file database/mongodb-init.js
```

Les avis et les statistiques sont rattachés aux commandes 1001 à 1005 du compte `user@viteetgourmand.com`. Sur une base neuve, ce compte a l'identifiant 3. Sinon, récupérer son identifiant (`SELECT id FROM users WHERE email = 'user@viteetgourmand.com';`) et le passer au script :

```powershell
$env:VG_CLIENT_USER_ID = '4'   # bash : VG_CLIENT_USER_ID=4 mongosh …
mongosh "…" --file database/mongodb-init.js
```

## 5. Lancer le serveur en local

Depuis la racine du projet, avec le serveur intégré de PHP (le dossier `public/` est la racine web ; les fichiers CSS, JS et images sont servis directement, tout le reste passe par `public/index.php`) :

```bash
php -S localhost:8000 -t public
```

Si `php` n'est pas dans le PATH (PowerShell, WAMP) :

```powershell
& "C:\wamp64\bin\php\php8.5.0\php.exe" -S localhost:8000 -t public
```

Puis ouvrir **http://localhost:8000** (arrêt : `Ctrl+C`). Penser à mettre `APP_URL=http://localhost:8000` dans `.env`.

Avec Apache (WAMP), faire pointer le DocumentRoot sur `public/` : le fichier `public/.htaccess` redirige les URL vers `index.php` (module `mod_rewrite` requis) et sert les `.mjs` en JavaScript.

## 6. Comptes de test

Créés par `database/seed.sql` (mots de passe conformes à la politique : 10 caractères minimum, majuscule, minuscule, chiffre et caractère spécial).

| Rôle | E-mail | Mot de passe | Connexion |
|---|---|---|---|
| Administrateur | `admin@viteetgourmand.com` | `Admin#Vite2026!` | `/admin/login` |
| Employé | `employee@viteetgourmand.com` | `Employe#Gourmand26` | `/admin/login` |
| Client | `user@viteetgourmand.com` | `Client#Bordeaux33` | `/login` |

Ces identifiants sont publics (dépôt) : ils servent uniquement aux démonstrations. Sur une instance exposée, changez-les depuis le profil (« Changer mon mot de passe »).

## 7. Arborescence

```text
App/
├── Controller/    requêtes HTTP → services → vues (Public, Auth, Order, Admin, Dish, MenuComposition, Email, Quote, Contact, Notification, Error…)
├── Core/          Application, Router, Container, Config (+ functions.php), Database, View, Session, Response, Request, Labels, UploadFile, Exception/
├── Entity/        Menu, Order, User
├── Middleware/    Security (en-têtes, CSRF, limitation de débit), Auth, Staff, Admin, Guest
├── Repository/    accès MariaDB (users, menus, plats, commandes, horaires, devis, messages, notifications) et MongoDB (images, avis, statistiques)
├── Service/       règles métier (commandes, livraison, menus, plats, e-mails, téléphone, mots de passe, cache…)
└── View/          gabarits PHP (layout, home, auth, order, admin, contact, quote, notification, errors)
config/            app.php (configuration lue dans .env) et routes.php
database/          schema.sql, seed.sql, mongodb-init.js
docs2/             énoncé de l'ECF
email-templates/   modèle HTML des réponses envoyées depuis la boîte e-mail
public/            racine web : index.php, .htaccess, assets/ (css, js, vendor/intl-tel-input)
storage/           créé à l'exécution : cache et limitation de débit (ignoré par git)
public/uploads/    créé à l'exécution : images ajoutées aux galeries (ignoré par git)
```

## 8. Fonctionnalités par rôle

**Visiteur**
- Accueil : présentation de l'entreprise et de l'équipe, avis validés, horaires.
- Catalogue des menus avec filtres sans rechargement (prix, fourchette de prix, thème, régime, nombre de personnes).
- Détail d'un menu : galerie d'images, plats avec leurs allergènes, conditions.
- Contact, demande de devis (facultative), inscription, mot de passe oublié, mentions légales, CGV, politique de confidentialité.

**Client** (en plus)
- Commande : menu pré-sélectionné, créneaux selon les horaires, prix détaillé (remise de 10 % dès le minimum + 5 personnes, livraison offerte dans Bordeaux, sinon 5 € + 0,59 €/km calculés par le serveur).
- Suivi des commandes avec l'historique, modification (tout sauf le menu) et annulation tant que la commande n'est pas acceptée.
- Avis (note de 1 à 5 et commentaire) une fois la commande terminée ; profil, changement de mot de passe, notifications.

**Employé**
- Commandes : filtres, changement de statut (mode de contact obligatoire, motif en cas d'annulation), modification après contact client (historisée), prêt de matériel.
- Menus (y compris épuisés), composition à partir des plats, galerie d'images ; CRUD des plats et de leurs allergènes.
- Horaires, avis à valider ou refuser, clients, devis, boîte e-mail.

**Administrateur** (en plus de tout ce que fait l'employé)
- Création d'employés (e-mail d'information sans le mot de passe), désactivation et réactivation.
- Tableau de bord : graphique des commandes par menu (données MongoDB) ; chiffre d'affaires par menu avec filtres menu et période.

## 9. Choix techniques : MariaDB et MongoDB

**MariaDB** contient les données structurées et liées entre elles, qui ont besoin d'intégrité (clés étrangères, transactions) : comptes, menus, plats, allergènes et leurs tables de liaison, commandes et historique des statuts, horaires, devis, messages de contact, boîte d'envoi, notifications. Une commande est enregistrée dans une transaction : création, décrémentation du stock et première ligne d'historique, tout ou rien.

**MongoDB** contient des documents indépendants, lus tels quels :
- `menu_images` : la galerie de chaque menu (URL, texte alternatif, ordre), dont le nombre d'éléments varie ;
- `comments` : les avis clients et leur état de modération ;
- `menu_statistics` : les agrégats « commandes et CA par menu » affichés dans le graphique du tableau de bord (l'énoncé demande que ces données viennent d'une base NoSQL). Ils sont recalculés à partir des commandes terminées de MariaDB, le même critère que la page CA.

## 10. Sécurité en bref

- **Requêtes préparées** partout (PDO, préparations natives) ; aucune valeur saisie n'est concaténée dans le SQL.
- **CSRF** : jeton vérifié sur toutes les requêtes POST (middleware `Security`).
- **Échappement** de toutes les sorties dans les vues (`$escape()` = `htmlspecialchars`) ; en-têtes de sécurité (CSP, `X-Frame-Options`, `nosniff`…).
- **Mots de passe** : bcrypt (coût `PASSWORD_HASH_COST`), politique de robustesse unique, blocage 15 minutes après 5 échecs, limitation des tentatives par adresse IP ; lien de réinitialisation à usage unique valable 1 heure, stocké haché.
- **Validation côté serveur** de toutes les saisies (le JavaScript ne fait qu'aider) ; prix, remises et frais de livraison recalculés par le serveur.
- **Téléphones** : validés avec libphonenumber (France, Espagne, Belgique, Royaume-Uni, Italie) et stockés au format international **E.164** (`+33612345678`).
- **Session** : cookie `HttpOnly` et `SameSite=Lax`, identifiant régénéré à la connexion ; `APP_DEBUG=false` par défaut (pas de trace d'erreur affichée).
- **Fichiers envoyés** (galerie) : type vérifié sur le contenu (JPEG, PNG, WebP), 5 Mo maximum, nom aléatoire ; le cache n'accepte aucun objet désérialisé.
