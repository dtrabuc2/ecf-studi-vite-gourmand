# CLAUDE.md — Vite & Gourmand (ECF DWWM)

Application de traiteur : PHP 8.2+ vanilla en MVC maison, MariaDB (PDO) + MongoDB, Bootstrap 5, JS natif.
Énoncé : `docs2/enoncer_ecf.md`. Audit : `AUDIT.md`. Journal des modifications : `CORRECTIONS.md`.

## Avant de commencer une tâche
1. Lire `CORRECTIONS.md` (ce qui est déjà fait, sections « Reste à faire » et « Problèmes non résolus »).
2. Ne jamais refaire ce qui y est marqué fait sans avoir vérifié dans le code que c'est faux.

## Règles fixes
- Stack figée : pas de framework, pas de npm ni de bundler, pas de CDN. Les bibliothèques front sont copiées dans `public/assets/vendor/<lib>/<version>/`.
- Dépendances Composer : `mongodb/mongodb`, `giggsey/libphonenumber-for-php`, `phpmailer/phpmailer`. Aucune autre sans accord.
- **git est bloqué sous Windows sur ce poste : ne lance aucune commande git.** L'utilisateur commite lui-même.
- Code complet et fonctionnel : pas de TODO, pas de stub, pas de placeholder.
- Pas de refacto ni de renommage hors de la tâche demandée.
- Le dépôt est en LF (`.gitattributes`) ; les fichiers de travail peuvent être en CRLF, c'est normal.
- Aucun déploiement ni configuration de production : l'utilisateur met en ligne lui-même.
- Aucun identifiant ni mot de passe dans un fichier versionné (README, docs, code) : les comptes de démonstration sont dans le `.env` (`DEMO_*`). Les mots de passe se changent uniquement avec `php scripts/set-password.php`, qui met à jour MariaDB, `database/schema.sql` et le `.env`. Après un changement fait depuis le site : `php scripts/set-password.php --pull` (MariaDB → `schema.sql`). L'application web ne doit jamais écrire dans `schema.sql` ni dans le `.env`.

## Conventions de code
- `declare(strict_types=1)` dans toutes les classes PHP (pas dans les vues).
- Requêtes préparées uniquement ; SQL et MongoDB seulement dans `App/Repository/`.
- Vues : toute donnée affichée passe par `$escape()` ; les vues ne lisent ni `$_SESSION` ni `$_GET` (le contrôleur leur passe les données).
- Formulaires POST : jeton CSRF (`csrf_token`), réponse = redirection + `Session::flash()`, jamais du JSON. Le JSON est réservé aux routes appelées par `fetch`.
- Erreurs : `NotFoundException` → page 404 commune (`ErrorController`).
- Listes de valeurs (statuts, modes de prestation, régimes) : uniquement dans `App/Core/Labels.php`.
- Nouvelle classe = fabrique à déclarer dans `App/Core/Container.php`. Nouvelle route = `config/routes.php`.
- Commentaires en français, simples et courts, comme un étudiant qui commente son projet. Commentaires PHP dans les vues, jamais HTML.

## Architecture
`public/index.php` → `App/Core/Application` (.env, session) → `Router` (`config/routes.php`, middlewares `App/Middleware`) → `Controller` → `Service` (règles métier) → `Repository` (PDO ou MongoDB) → vue `App/View/...` rendue dans `layout/layout.php`.

- MariaDB : utilisateurs, menus, plats, allergènes, commandes et historique, horaires, devis, notifications.
- Pas de boîte e-mail dans l'application (supprimée) : les e-mails reçus se gèrent dans la messagerie de l'hébergeur. Le formulaire de contact envoie à `MAIL_TO_ADDRESS` avec le visiteur en « Répondre à ».
- MongoDB : `menu_images` (galerie), `comments` (avis), `menu_statistics` (graphique admin, alimenté quand une commande passe à `completed`).

## Décisions déjà prises (ne pas rediscuter)
- Pas de plafond de personnes ; la page devis est facultative.
- Livraison hors Bordeaux : 5 € + 0,59 €/km, distance calculée côté serveur (Google Distance Matrix, `DeliveryDistanceService`). Si l'API échoue, la commande est refusée, jamais 0 km.
- Mails : PHPMailer en SMTP via les variables `MAIL_*`, envoi uniquement par `MailService`.
- Téléphones : pays FR, ES, BE, GB, IT ; validation serveur par `PhoneValidator` ; stockage en E.164 ; saisie avec intl-tel-input 29.5.3 (API v29 : `loadUtils`, `hiddenInputs`, `onlyCountries`).
- Mots de passe : `PasswordPolicy` (10 caractères, majuscule, minuscule, chiffre, caractère spécial), bcrypt.
- Thèmes de menu : Noël, Pâques, Classique, Évènement.
- Ne jamais réintroduire : mot de passe comparé en clair, `mail()` natif, distance saisie par le client, réponses JSON sur des formulaires HTML.

## Commandes
- Dépendances : `composer install`
- Serveur local : `php -S localhost:8000 -t public` (sert seul les fichiers statiques de `public/`)
- Vérification syntaxe : `php -l` sur chaque fichier PHP modifié, puis sur `App/`, `config/`, `public/`
- Bases : importer `database/schema.sql` (fichier unique de référence : structure + données, destructif) puis lancer `database/mongodb-init.js` (détails et comptes de test dans le README)
- Toute modification de structure ou de données de démonstration va dans `database/schema.sql`. Il n'y a plus de `seed.sql`. Garder `mongodb-init.js` aligné (client 3, commandes 1001 à 1005, menus 1 à 9).

## Après chaque modification
- `php -l` sur les fichiers PHP modifiés ; `node --check` sur les JS modifiés.
- Consigner dans `CORRECTIONS.md` : élément | fichiers modifiés | ce qui a été fait | comment le tester en local.
- Mettre à jour le statut dans `AUDIT.md` si un P-xx ou un EX-xx est concerné.
