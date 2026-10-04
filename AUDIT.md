# AUDIT — Vite & Gourmand (ECF DWWM)

Audit en lecture seule réalisé le 2026-09-29 sur la branche `dev` (working tree propre).
Stack constatée : PHP 8.2+ vanilla MVC maison, MariaDB (PDO), MongoDB (`mongodb/mongodb` ^2.1), Bootstrap 5, JS natif.

Limites de l'audit :
- Aucun `.pdf` dans le projet. L'énoncé n'existe que sous forme de transcription Markdown : `docs2/enoncer_ecf.md`. Les numéros de ligne cités comme « source » renvoient à ce fichier.
- Le binaire `git` est bloqué sur ce poste (« Une stratégie de contrôle d'application a bloqué ce fichier »). L'historique git n'a été lu qu'à travers `.git/` (refs, reflog, index).
- Aucun test navigateur ni aucune requête HTTP n'ont été exécutés. Les statuts sont déduits de la lecture du code. Seules vérifications exécutées : `php -l` sur `App/`, `config/` et `public/` (0 erreur de syntaxe), et un `password_verify` sur le hash du seed.

---

## 1. Résumé

- **État global :** le socle back-end est sérieux. Routeur, conteneur DI, middlewares, PDO avec requêtes préparées partout, CSRF global, échappement systématique dans les vues, transactions sur les commandes. Des fonctionnalités clés de l'énoncé sont cassées ou absentes, et les livrables hors code n'existent pas.
- **Avancement estimé :** code fonctionnel ≈ 60-65 % des exigences, livrables annexes ≈ 5 %, soit **≈ 45 % au global** (estimation à partir du tableau §2 : 31 ✅, 26 🟡, 13 ❌, 3 ❓). **Mise à jour 2026-09-30 :** après corrections, 59 ✅ · 2 🟡 · 9 ❌ · 3 ❓ ; les ❌ restants sont le déploiement et les livrables documentaires (voir CORRECTIONS.md, « Reste à faire »).
- **Risque 1 — La commande est bloquée côté front.** `public/assets/js/order.js:72,168,193` déballe deux fois `data`, alors que `VgApi.get()` renvoie déjà `payload.data` (`api.js:21`). Résultat : la liste des menus reste vide, aucun créneau horaire n'est proposé et le formulaire ne peut pas être soumis. Le menu n'est pas non plus pré-sélectionné depuis le bouton « Commander ».
- **Risque 2 — Rien n'est déployé ni documenté.** L'énoncé prévoit des pénalités si l'application n'est pas en ligne (L257). Manquent aussi : manuel PDF, charte PDF, maquettes, documentation technique et documentation de gestion de projet. Le README renvoie vers un dossier `docs/` et un workflow CI qui n'existent pas.
- **Risque 3 — Fonctions obligatoires absentes ou fragiles.** Pas de gestion des plats. Pas de mail à l'employé créé. Envoi d'e-mails via `mail()` sans SMTP (config `MAIL_*` ignorée). Les trois comptes du seed ont le mot de passe `password`. La connexion accepte un mot de passe stocké en clair.

---

## 2. Exigences de l'énoncé

Légende : ✅ fait (d'après le code) · 🟡 partiel · ❌ absent · ❓ non vérifiable.
Source : `docs2/enoncer_ecf.md`, ligne.

### 2.1 Fonctionnalités

| EX | Description | Source | Statut | Fichiers concernés | Commentaire |
|---|---|---|---|---|---|
| EX-01 | Accueil : présentation de l'entreprise | L73 | ✅ | `App/View/home/index.php` | Texte générique (hero et encarts). |
| EX-02 | Accueil : mise en avant du professionnalisme de l'équipe | L75 | ✅ | `App/View/home/index.php:22-35` | Corrigé : section « Notre équipe » (Julie, José, 25 ans à Bordeaux) sur l'accueil. Lot 8. |
| EX-03 | Accueil : avis clients validés | L77 | ✅ | `CommentRepository.php:51-57`, `home/index.php:125-138` | Filtre `isValidated: true`. Avis du seed rattachés aux commandes et au client réels (P-17 corrigé, lot 6). |
| EX-04 | Menu de navigation : accueil, tous les menus, connexion, contact | L82-90 | ✅ | `App/View/layout/header.php` | Présent. |
| EX-05 | Connexion réservée aux 3 rôles, respect du RGPD et de la sécurité | L88 | ✅ | `home/legal.php`, `auth/register.php` | Corrigé : mentions légales complètes, politique de confidentialité (`/confidentialite`), case de consentement vérifiée côté serveur. Lot 8. |
| EX-06 | Pied de page : horaires du lundi au dimanche | L94 | ✅ | `layout/footer.php:12-18` | Corrigé : horaires lus en base, les modifications de `/admin/hours` apparaissent dans le pied de page (P-08, lot 5). |
| EX-07 | Pied de page : liens mentions légales et CGV | L94 | ✅ | `layout/footer.php:26-27` | Liens présents, contenus complets (lot 8), plus un lien Confidentialité. |
| EX-08 | Menus configurables par l'administrateur **et** l'employé | L98 | ✅ | `AdminController.php:262-301`, `admin/menus.php` | Corrigé : composition en plats (`/admin/menus/{id}/content`), galerie, menus épuisés visibles, thèmes en liste fermée. Lots 4 et 5. |
| EX-09 | Caractéristiques d'un menu : titre, description, nb min., prix, conditions, régime, stock | L100-122 | ✅ | `database/schema.sql:48-67`, `Entity/Menu.php` | Conforme. |
| EX-10 | Galerie d'images par menu | L102 | ✅ | `PublicController.php:179-196`, `database/mongodb-init.js:38-65` | Corrigé : galerie MongoDB gérée en admin (ajout, ordre, suppression), toutes les images en carrousel sur la page détail. Lot 4. |
| EX-11 | Thème : Noël, Pâques, classique, évènement | L106 | ✅ | `admin/menus.php:2`, `seed.sql:297-385` | Corrigé : liste fermée Noël, Pâques, Classique, Évènement (admin, validation serveur, filtre, seed). Lot 5. |
| EX-12 | Plats (entrée, plat, dessert) partagés entre plusieurs menus | L108, L120 | ✅ | `schema.sql:69-102` | Relation N-N `menu_dishes`, correctement modélisée et affichée. |
| EX-13 | Allergènes par plat | L114 | ✅ | `schema.sql:83-112`, `MenuRepository.php:115-130` | Corrigé : allergènes affichés sous chaque plat (et récapitulatif du menu). Lot 4. |
| EX-14 | Vue globale : titre, description, nb min., prix, bouton détail ; accessible à tous | L125 | ✅ | `home/menus.php`, `config/routes.php:6` | Route publique. |
| EX-15 | Filtres : prix max, fourchette de prix, thème, régime, nb de personnes | L127-137 | ✅ | `MenuRepository.php:37-93` | Complet. Saisie invalide : message 422 au lieu d'une 500 (P-20, lot 7). |
| EX-16 | Filtrage dynamique sans rechargement | L139 | ✅ | `public/assets/js/menus.js`, `PublicController::filterMenus` | Requête `fetch` et rendu échappé. |
| EX-17 | Inscription : nom, prénom, GSM, e-mail, adresse postale, mot de passe fort (10 car., maj., min., chiffre, spécial) | L143-151 | ✅ | `AuthController.php:60-88`, `AuthService.php:20-41` | Conforme. Téléphone validé avec libphonenumber. |
| EX-18 | Rôle « utilisateur » à la création | L153 | ✅ | `AuthService.php:111` | `role = 'user'` forcé. |
| EX-19 | Mail de bienvenue automatique | L153 | ✅ | `AuthController.php:74-78`, `MailService.php:18-38` | Corrigé : envoi SMTP par PHPMailer (P-05, lot 3). Nécessite `MAIL_HOST` renseigné. |
| EX-20 | Connexion par e-mail et mot de passe | L157 | ✅ | `AuthController::login`, `AdminController::login` | Deux écrans séparés (client / équipe). Verrouillage après 5 échecs. |
| EX-21 | Mot de passe oublié : lien envoyé par mail | L159 | ✅ | `AuthService.php:248-305`, `AuthController.php:197-261` | Jeton de 64 hex haché en SHA-256, valable 1 h, réponse non énumérante. Envoi SMTP (lot 3). |
| EX-22 | Vue détaillée : toutes les informations en base | L163 | ✅ | `home/menu_detail.php` | Corrigé : galerie complète et allergènes par plat. Lot 4. |
| EX-23 | Bouton « Commander » vers la commande **avec le menu pré-rempli** | L165, L187 | ✅ | `order/new.php:7`, `order.js:123-132` | Corrigé : `?menu=ID` pré-sélectionne le menu et propose son minimum de personnes (P-02, lot 1). |
| EX-24 | Visiteur non connecté : invitation à se connecter ou s'inscrire | L168 | ✅ | `home/menu_detail.php`, middleware `Auth`, `Session::rememberReturnUrl()` | Présent. Corrigé avant recette : après la connexion, retour à l'URL demandée (par exemple `/orders/new?menu=3`), chemin interne seulement. |
| EX-25 | Conditions du menu bien mises en évidence | L168-170 | ✅ | `home/menu_detail.php:70-73` | Bloc `alert-warning`. |
| EX-26 | Commande : nom, prénom et e-mail pré-remplis depuis le compte | L179 | ✅ | `order/new.php` | Corrigé : nom, prénom et e-mail affichés en lecture seule. Lot 1. |
| EX-27 | Commande : adresse, date, heure et lieu de prestation | L181-183 | ✅ | `order/new.php`, `order.js:176-227` | Corrigé : créneaux chargés depuis les horaires, parcours complet possible (P-01, lot 1). |
| EX-28 | Livraison hors Bordeaux : 5 € + 0,59 €/km | L181 | ✅ | `OrderService.php:780-797` | Corrigé : distance calculée par le serveur (Google Distance Matrix), Bordeaux détecté par ville normalisée ou code postal, refus si l'API échoue (P-10, lot 5). Nécessite `GOOGLE_MAPS_API_KEY`. |
| EX-29 | GSM pré-rempli | L185 | ✅ | `order/new.php:113` | Pré-rempli avec le GSM, champ intl-tel-input, stocké en E.164 (lot 7 bis). |
| EX-30 | Nombre de personnes ≥ minimum, prix mis à jour | L189-191 | ✅ | `OrderService.php:64-68`, `order.js:149-174` | Corrigé : aperçu du prix affiché et mis à jour (P-01, lot 1). |
| EX-31 | Remise de 10 % à partir de minimum + 5 personnes | L193 | ✅ | `OrderService.php:527-541`, `OrderPriceCalculator.js` | Conforme, calculé par le serveur (aperçu et commande). |
| EX-32 | Détail du prix (menu + livraison) avant validation | L195 | ✅ | `order/new.php:175-199` | Corrigé : menu, remise, livraison (avec distance) et total affichés avant validation. Lots 1 et 5. |
| EX-33 | Mail de confirmation de commande | L197 | ✅ | `OrderService.php:206-223` | Corrigé : envoi SMTP (lot 3). |
| EX-34 | Espace utilisateur : consulter le détail de ses commandes | L203 | ✅ | `OrderController::index`, `order/index.php` | Présent. |
| EX-35 | Espace utilisateur : modifier ses informations personnelles | L203 | ✅ | `AuthController.php:111-161`, `auth/profile.php` | Présent, avec le formulaire de changement de mot de passe (P-11, lot 2). |
| EX-36 | Annulation possible tant que la commande n'est pas « acceptée » | L205 | ✅ | `OrderController.php:444-448` | Limitée au statut `pending`. |
| EX-37 | Modification possible avant acceptation (tout sauf le menu) | L205 | ✅ | `order/index.php:43-62`, `OrderService.php:558-627` | Corrigé : modification de tout sauf le menu, pour tous les modes de prestation, avant acceptation. Lot 4. |
| EX-38 | Suivi : tous les états avec date et heure | L207-209 | ✅ | `OrderRepository.php:133-163`, `order/index.php:40-42` | Historique `order_status_history`. |
| EX-39 | Mail « commande terminée, donnez votre avis » | L211 | ✅ | `OrderService.php:762-768`, `MailService.php:76-83` | Corrigé : sauts de ligne réels (P-22) et envoi SMTP. Lot 3. |
| EX-40 | Avis : note de 1 à 5 et commentaire | L213 | ✅ | `CommentService.php:59-94`, `OrderController.php:375-409` | Stocké dans MongoDB. Un seul avis par commande, uniquement si `completed`. |
| EX-41 | Employé : modifier et supprimer les menus | L220 | ✅ | `AdminController.php:262-301` | Corrigé : suppression avec redirection et message (P-13), menus épuisés visibles (P-07). Lot 5. |
| EX-42 | Employé : modifier et supprimer les **plats** | L220 | ✅ | `App/View/admin/dishes.php`, `header.php:42` | Corrigé : CRUD des plats et de leurs allergènes pour l'employé et l'admin (`/admin/dishes`). Lot 4. |
| EX-43 | Employé : modifier les horaires | L220 | ✅ | `AdminController.php:160-192`, `OpeningHoursRepository.php` | Utilisés pour les créneaux et affichés dans le pied de page (lot 5). |
| EX-44 | Modifier ou annuler une commande après contact client (mode de contact + motif) | L222 | ✅ | `AdminController.php:120-158`, `admin/orders.php:123-137` | Corrigé : modification du contenu après contact, mode de contact et motif obligatoires, historisés. Lot 4. |
| EX-45 | Filtre des commandes par statut et par client | L222 | ✅ | `OrderRepository.php:59-73`, `admin/orders.php:10-34` | Présent, avec le nom du client et le titre du menu (P-24, lot 5). |
| EX-46 | Statuts : accepté, en préparation, en livraison, livré, attente retour matériel, terminé | L224-236 | ✅ | `OrderService.php:643-695` | Machine à états explicite. |
| EX-47 | Mail « retour du matériel sous 10 jours ouvrés, sinon 600 € (CGV) » | L234 | ✅ | `MailService.php:85-92` | Corrigé : e-mail correct (lot 3) et clause des 600 € / 10 jours ouvrés dans les CGV (lot 8). |
| EX-48 | Employé : valider ou refuser les avis | L238 | ✅ | `AdminController.php:303-320`, `admin/comments.php` | Corrigé : redirection et message au lieu de JSON (P-13, lot 5). |
| EX-49 | Admin : créer un compte employé (e-mail + mot de passe) | L243 | ✅ | `AdminService.php:22-51` | Rôle `employee` forcé. |
| EX-50 | Mail à l'employé créé, sans le mot de passe | L245 | ✅ | `AdminService::createEmployee`, `AdminController::createEmployee` | Corrigé : e-mail d'information sans le mot de passe (P-06, lot 3). |
| EX-51 | Admin : désactiver un compte employé | L247 | ✅ | `UserRepository.php:229-242` | Corrigé : redirection et message au lieu de JSON (P-13, lot 5). |
| EX-52 | Compte admin créé par le développeur, pas de création d'admin via l'application | L247 | ✅ | `seed.sql:14-17`, `AdminService.php:44` | Conforme. Mots de passe du seed distincts et conformes (P-03, lot 2). |
| EX-53 | L'admin peut faire tout ce que fait l'employé | L249 | ✅ | `Middleware/Staff.php:20` | Rôles `employee` et `admin` autorisés. |
| EX-54 | Graphique du nombre de commandes par menu, données issues du NoSQL | L251 | ✅ | `admin/dashboard.php`, `MenuStatisticsService.php`, `AdminService.php:122` | Corrigé : statistiques `all_time` du seed alignées sur les commandes, même critère `completed` que le CA (P-16, P-27, lot 6). |
| EX-55 | Chiffre d'affaires par menu, filtres menu et période | L251 | ✅ | `AdminService.php:150-201`, `OrderRepository::revenueByMenu`, `admin/revenue.php` | Calculé en SQL, même critère que les statistiques MongoDB (P-27 corrigé). Corrigé avant recette : avec un filtre de période, les menus sans commande restent listés à 0 €. |
| EX-56 | Contact : titre, description, e-mail ; envoi par mail à l'entreprise | L255 | ✅ | `ContactController.php:25-93` | Corrigé : envoi SMTP vers `MAIL_TO_ADDRESS` lu dans `.env` (P-33, lot 3). |
| EX-57 | Application déployée, en ligne et fonctionnelle | L257 | ❌ | — | Aucune configuration de déploiement (Dockerfile, fly.toml, Procfile…). |
| EX-58 | Accessibilité conforme RGAA | L257 | 🟡 | vues | Labels tous reliés (`for`), erreurs liées par `aria-describedby`, carrousel sans défilement automatique (lots 7 bis et 8). Aucun audit RGAA outillé (contrastes, clavier complet) : conformité non vérifiée. |

### 2.2 Contraintes techniques

| EX | Description | Source | Statut | Fichiers concernés | Commentaire |
|---|---|---|---|---|---|
| EX-59 | Utiliser une base relationnelle **et** une base non relationnelle | L264 | ✅ | `Core/Database.php` | MariaDB avec PDO et MongoDB avec `mongodb/mongodb`. |
| EX-60 | Justifier les choix techniques et détailler les mesures de sécurité dans la copie | L283 | ❌ | — | Copie hors périmètre de ces corrections. Le README contient déjà une section choix techniques et sécurité réutilisable. |
| EX-61 | MCD fourni en annexe 1 | L260 | ❓ | — | Annexe absente de la transcription `.md`. Impossible de comparer le schéma au MCD attendu. |

### 2.3 Livrables

| EX | Description | Source | Statut | Fichiers concernés | Commentaire |
|---|---|---|---|---|---|
| EX-62 | Dépôt GitHub **public** | L287 | ❓ | `.git/config` | Remote `github.com/dtrabuc2/ecf-studi-vite-gourmand`. Visibilité publique non vérifiée. |
| EX-63 | Lien de l'application déployée | L289 | ❌ | — | — |
| EX-64 | Lien vers l'outil de gestion de projet | L291 | ❌ | — | Aucune trace dans le dépôt. |
| EX-65 | README : procédure d'installation locale | L295 | ✅ | `README.md` | Corrigé : README réécrit, chemins vérifiés, import MongoDB avec authentification, comptes de test (P-50, lot 9). |
| EX-66 | Bonnes pratiques git : `main`, `dev`, une branche par fonctionnalité fusionnée dans `dev` | L297-305 | 🟡 | `.git/refs` | `main` et `dev` existent. Aucune branche de fonctionnalité locale ou distante, seulement `backup/…` et `legacy/…`. Le reflog montre de nombreux `reset` vers `origin/dev`. Historique non vérifiable (git bloqué). |
| EX-67 | Fichiers SQL de création **et** d'insertion de données | L307 | ✅ | `database/schema.sql`, `database/seed.sql` | Présents et cohérents avec les repositories. |
| EX-68 | Manuel d'utilisation PDF avec identifiants de test | L309-311 | ❌ | — | — |
| EX-69 | Charte graphique PDF : palette et police | L313-315 | ❌ | `public/assets/css/tokens.css` | Palette uniquement dans le CSS. |
| EX-70 | Maquettes : 3 bureau et 3 mobile (wireframes et mockups) | L317 | ❌ | — | — |
| EX-71 | Documentation de gestion de projet | L320-322 | ❌ | — | — |
| EX-72 | Documentation technique : réflexions, environnement, MCD, diagrammes de cas d'utilisation et de séquence, déploiement | L324-334 | ❌ | — | — |
| EX-73 | Copie à rendre (Word ou Excel) renommée selon la convention | L13-17 | ❓ | — | Absente du dépôt, peut-être gérée ailleurs. |

**Décompte (mis à jour le 2026-09-30, après les lots 1 à 9 de CORRECTIONS.md) :** 59 ✅ · 2 🟡 · 9 ❌ · 3 ❓ (73 exigences). Décompte initial de l'audit : 31 ✅ · 26 🟡 · 13 ❌ · 3 ❓.

---

## 3. Architecture actuelle

### 3.1 Arborescence commentée

```text
.
├── public/                  Racine web (DocumentRoot)
│   ├── index.php            Contrôleur frontal : autoload Composer puis Application::boot()->run()
│   ├── .htaccess            Réécriture de toutes les URL vers index.php
│   └── assets/css|js        Bootstrap local, tokens.css, CSS par composant/page, JS natif
├── App/
│   ├── Core/                Framework maison
│   │   ├── Application.php  Chargement du .env (parseur maison), gestion des erreurs, session, routeur
│   │   ├── Router.php       Routes, paramètres {x}, pile de middlewares, typage des arguments par réflexion
│   │   ├── Container.php    Conteneur DI : fabriques déclarées à la main pour chaque classe
│   │   ├── Database.php     Singletons PDO (MariaDB) et MongoDB\Client
│   │   ├── View.php         require du template + closure $escape + $csrfToken
│   │   ├── Session.php      Identité, rôle, jeton CSRF, messages flash
│   │   ├── Response.php     redirect() / json() (appellent exit)
│   │   ├── Config.php + functions.php   Helper config() qui lit config/app.php
│   │   └── Security.php, UploadFile.php, Autoloader.php, Exception/*   Code mort (voir §4)
│   ├── Middleware/          Security (en-têtes, CSRF, limitation de débit), Auth, Staff, Admin, Guest
│   ├── Controller/          10 contrôleurs, tous héritent de BaseController (render/json/redirect)
│   ├── Service/             Logique métier (OrderService ≈ 800 lignes), mails, cache fichier
│   ├── Repository/          Accès aux données : SQL (User, Menu, Order, OpeningHours, Notification), MongoDB (Comment)
│   ├── Entity/              Objets à getters/setters : User, Menu, Order, Comment (Comment inutilisé)
│   └── View/                Templates PHP : layout/, home/, auth/, order/, admin/, contact/, quote/, notification/
├── config/
│   ├── app.php              Configuration lue depuis $_ENV (bases, session, mail, clé Google Maps)
│   └── routes.php           76 routes [méthode, URI, Contrôleur@action, middlewares]
├── database/                schema.sql (13 tables, destructif), seed.sql (upsert), mongodb-init.js
├── email-templates/         contact-reply.html (placeholders {{x}})
├── docs2/                   enoncer_ecf.md (énoncé), bugs.md (journal de corrections)
├── scripts/test-site.ps1    Tests de fumée PowerShell (php -l + codes HTTP), écrit dans docs/ (absent)
├── old_frontend/            Ancien front statique (HTML/JS mock) — orphelin, assets en double
├── rector.php               Configuration Rector, sans dépendance rector dans composer.json
├── composer.json            php ^8.2, ext-mongodb, mongodb/mongodb, libphonenumber ; PSR-4 App\ → App/
└── .env / .env.example      .env ignoré par git (absent de l'index : vérifié)
```

### 3.2 Fonctionnement réel du MVC

1. `public/index.php` charge `vendor/autoload.php` (PSR-4 via Composer) puis `Application::boot()`. Cette méthode lit `.env`, fixe le fuseau horaire, `display_errors` selon `APP_DEBUG` et démarre la session avec `httponly`, `SameSite=Lax` et `use_strict_mode`.
2. `Router::setRoutes()` trie les routes : les routes statiques passent avant les routes paramétrées. `dispatch()` fait la correspondance sur méthode + chemin, exécute les middlewares résolus par le conteneur, puis appelle `Contrôleur@méthode` en injectant les paramètres d'URL typés par réflexion.
3. **Contrôleurs** : lisent `$_GET`/`$_POST`, délèguent aux Services, puis `render()` ou `json()`. `BaseController::render()` rend la vue, puis l'injecte dans `layout/layout.php` (en-tête + flash + contenu + pied de page).
4. **Services** : règles métier (tarification, machine à états, validation). Certains exécutent directement du SQL, sans repository : `AdminService`, `QuoteService`, `EmailService`, `ContactService`, `MenuStatisticsService`.
5. **Repositories** : PDO avec requêtes préparées (`ATTR_EMULATE_PREPARES = false`), hydratation en entités. `CommentRepository` interroge MongoDB.
6. **Vues** : PHP natif. Toutes les sorties dynamiques passent par `$escape()` (`htmlspecialchars`). Aucune sortie non échappée de donnée utilisateur n'a été trouvée.
7. **Front** : `menus.js` (filtres), `order.js` (assistant de commande), `address.js` (autocomplétion Google Places par proxy PHP), `script.js` (confirmations). Tous les scripts sont chargés sur toutes les pages (`footer.php:36-42`).

### 3.3 MariaDB

- **Connexion :** `Database::pdo()`, DSN `mysql:` en utf8mb4, `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, vraies requêtes préparées. Identifiants lus dans `.env`.
- **Tables (`schema.sql`)** : `users`, `menus`, `dishes`, `allergens`, `menu_dishes` (N-N), `dish_allergens` (N-N), `orders`, `order_status_history`, `opening_hours`, `contact_messages`, `email_outbox`, `quote_requests`, `user_notifications`. Clés étrangères InnoDB avec `RESTRICT`, `CASCADE` ou `SET NULL` selon le cas, index adaptés aux filtres.
- **Cohérence avec le PHP :** les colonnes utilisées par les repositories existent. Pas de gestion applicative de `dishes`, `allergens`, `dish_allergens` et `menu_dishes` : lecture seule, données fournies par le seed.
- **Seed :** 3 comptes de démonstration (admin, employé, utilisateur), horaires, 10 allergènes, 32 plats, 9 menus et leurs compositions. Aucune commande de démonstration.
- **Ajouts hors énoncé :** `quote_requests` (devis au-delà de 30 personnes), `user_notifications`, `contact_messages` et `email_outbox` (boîte mail interne), `service_type` et `payment_method`.

### 3.4 MongoDB

- **Pilote :** extension `ext-mongodb` ^2.1 et bibliothèque `mongodb/mongodb` ^2.1. Client singleton `Database::mongo()`, URI depuis `MONGO_CONNECTION_STRING` ou reconstruite depuis `MONGO_*`.
- **Collections (`mongodb-init.js`) :**
  - `menu_images` : galerie (URL Unsplash). Lue uniquement par `PublicController::menuCover()` (`:182`), qui accède à MongoDB **depuis un contrôleur**.
  - `comments` : avis clients. Lecture, écriture et validation dans `CommentRepository`. Index unique `{userId, orderId}`.
  - `menu_statistics` : agrégats commandes et CA par menu. Écrits par `MenuStatisticsService::aggregateAndStore()` quand une commande passe à `completed` (`OrderService.php:768`), lus par le tableau de bord admin.
- **Justification de l'usage :** exigée par l'énoncé pour le graphique (L251). Le choix des avis et des images n'est documenté nulle part.

---

## 4. Problèmes identifiés

Gravité : **critique** (fonction obligatoire cassée, ou faille exploitable) · **majeur** (non-conformité ou risque sérieux) · **mineur**. Statut : corrigé, avec le lot correspondant de `CORRECTIONS.md` (« Lot 9 (MVC) » = lot fait sous l'ancienne consigne, complété par le lot 10 de nettoyage).

| # | Gravité | Fichier:ligne | Problème | Statut (2026-09-30) |
|---|---|---|---|---|
| P-01 | critique | `public/assets/js/order.js:68-72`, `:167-168`, `:190-194` | `VgApi.get()` renvoie déjà `payload.data` (`api.js:21`). `order.js` relit ensuite `payload?.data`, qui vaut `undefined`, puis `{}`. Conséquences : `menus` et `slots` sont toujours vides, le sélecteur de menu et le créneau restent `disabled` et la soumission est bloquée (`:301-318`). **Aucune commande n'est possible via l'interface** (d'après le code, non testé en navigateur). | ✅ Lot 1 |
| P-02 | critique | `App/View/order/new.php:7`, `order.js:123-132` | La pré-sélection du menu (`?menu=ID`) n'est pas implémentée : `$selectedId` n'est jamais utilisé et `data-old-menu-id` n'est jamais écrit. Exigence explicite (L165, L187). | ✅ Lot 1 |
| P-03 | critique | `database/seed.sql:15,19,23` | Les 3 comptes de démonstration (dont l'admin) partagent le hash public `$2y$10$92IXUNpkj…` qui correspond à `password` (vérifié avec `password_verify`). Le mot de passe ne respecte pas la politique du projet et sera connu de quiconque lit le dépôt public. | ✅ Lot 2 |
| P-04 | majeur | `App/Service/AuthService.php:139-142` | Si le hash stocké n'est pas reconnu, la connexion compare le mot de passe **en clair** (`hash_equals($storedPassword, $password)`), sans re-hachage ensuite. Accepte des mots de passe non hashés en base (cas décrit dans `docs2/bugs.md`). | ✅ Lot 2 |
| P-05 | majeur | `App/Service/MailService.php:31`, `config/app.php:40-49` | Tous les mails passent par `@mail()`. La configuration SMTP (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`…) n'est jamais lue. Sur WAMP ou chez la plupart des hébergeurs, les mails de bienvenue, réinitialisation, confirmation, avis et retour de matériel échouent silencieusement. | ✅ Lot 3 |
| P-06 | majeur | `App/Service/AdminService.php:22-51` | Aucun mail n'est envoyé à l'employé créé (EX-50). | ✅ Lot 3 |
| P-07 | majeur | `App/Repository/MenuRepository.php:12`, `AdminController.php:262-267` | `findAll()` filtre `available_stock > 0`, et c'est aussi la source de la page d'administration des menus. Un menu épuisé disparaît de l'admin et ne peut plus être réapprovisionné depuis l'interface. | ✅ Lot 5 |
| P-08 | majeur | `App/View/layout/footer.php:12-18`, `order/new.php:170-171` | Horaires codés en dur. `BaseController::render()` charge pourtant `openingHours` à chaque page (`BaseController.php:16`) sans l'utiliser, d'où une requête inutile. Les modifications faites dans `/admin/hours` ne se voient pas dans le pied de page. | ✅ Lot 5 |
| P-09 | majeur | `App/View/admin/dishes.php:3`, `layout/header.php:42`, `admin/dashboard.php:1` | Gestion des plats absente. Les liens `/admin/dishes` mènent à une 404 JSON (aucune route). `dishes.php` utilise `$this` dans un template rendu statiquement, ce qui provoquerait une erreur fatale s'il était inclus. | ✅ Lot 4 |
| P-10 | majeur | `OrderController.php:198-200`, `OrderService.php:780-797` | Les frais de livraison reposent sur une distance **saisie par le client** : saisir `0` km donne 5 €. « Bordeaux » est détecté par égalité stricte de chaîne (`'bordeaux'`). | ✅ Lot 5 |
| P-11 | majeur | `App/Controller/AuthController.php` (routes `/password`), `auth/profile.php` | Route `POST /password` et action `changePassword` sans formulaire. Fonction inaccessible. | ✅ Lot 2 |
| P-12 | majeur | `.env.example:2`, `Application.php:41-43,98-100` | `APP_DEBUG=true` par défaut : les exceptions sont relancées et affichées (`display_errors=1`), avec traces de pile et chemins serveur. Dangereux si déployé tel quel. | ✅ Lot 2 |
| P-13 | majeur | `AdminController.php:250-260`, `:293-320` ; vues `admin/comments.php:15-22`, `admin/employees.php:44-52`, `admin/menus.php:3` | Des formulaires HTML classiques postent vers des actions qui répondent en JSON : validation et refus d'avis, activation et désactivation d'employé, suppression de menu. L'utilisateur voit `{"success":true}` à l'écran. | ✅ Lot 5 |
| P-14 | majeur | `App/Service/OrderService.php:58-62`, `OrderController.php:178-191,213` | Règle inventée : commande directe plafonnée à 30 personnes, au-delà redirection obligatoire vers un devis. Absente de l'énoncé et contradictoire en interne (`< 50` à la ligne 213). | ✅ Lot 5 |
| P-15 | majeur | `public/assets/js/menus.js:29-33,161` | Au chargement, `load('/public/menus')` remplace la grille rendue par PHP. L'API ne renvoie pas `images`, donc toutes les cartes affichent « Aucune image disponible ». Requête N+1 : `getMenuDetails` est appelé pour chaque menu (`PublicController.php:151-168`). | ✅ Lot 5 |
| P-16 | majeur | `database/mongodb-init.js:103-130`, `AdminService.php:122` | Le seed Mongo écrit des statistiques `periodIdentifier: "2026-09"`, alors que le tableau de bord lit `all_time`. Le graphique reste vide tant qu'aucune commande n'est terminée. | ✅ Lot 6 |
| P-17 | majeur | `database/mongodb-init.js:67-93`, `CommentRepository.php:127-144` | Les avis du seed référencent des `userId` 1 à 5 et des `orderId` 1001 à 1005 inexistants côté MariaDB. Si `userId` 1 existe (l'admin), l'avis s'affiche signé « Admin Istrator » au lieu de `authorName`. | ✅ Lot 6 |
| P-18 | majeur | `App/View/home/cgv.php`, `home/legal.php` | CGV et mentions légales provisoires (« à compléter »). Les CGV ne mentionnent pas les 600 € de pénalité matériel, alors que l'énoncé l'exige (L234). Pas de politique de confidentialité (RGPD). | ✅ Lot 8 |
| P-19 | mineur | `App/Core/Router.php:226-230` | Un paramètre `int` invalide (`/menus/abc`) devient `null`, puis provoque une `TypeError` sous `strict_types`, donc une 500 au lieu d'une 404. | ✅ Lot 7 |
| P-20 | mineur | `PublicController.php:120-134`, `MenuRepository.php:43-87` | Filtre invalide : `InvalidArgumentException` non interceptée, donc 500. Côté client, `menus.js` affiche « réponse invalide ». | ✅ Lot 7 |
| P-21 | mineur | `App/Core/Router.php:90-93` | Toute route inconnue renvoie une 404 **JSON**, y compris pour les pages HTML. | ✅ Lot 7 |
| P-22 | mineur | `App/Service/MailService.php:81,90` | `"\\n"` dans des chaînes entre guillemets doubles : les mails d'avis et de retour de matériel contiennent des `\n` littéraux. | ✅ Lot 3 |
| P-23 | mineur | `App/View/quote/index.php:2-3` | La vue lit `$_SESSION['quote_old_input']` alors que les contrôleurs écrivent via `Session::flash()` (`$_SESSION['_flash'][…]`). Le pré-remplissage du devis ne fonctionne jamais, et la clé flash n'est jamais consommée. | ✅ Lot 5 |
| P-24 | mineur | `App/View/admin/orders.php:52,82,90`, `OrderRepository.php:61,181-211` | La liste des commandes côté équipe affiche l'ID client et l'ID menu. `customer_name` est pourtant sélectionné en SQL, mais perdu à l'hydratation. | ✅ Lot 5 |
| P-25 | mineur | `App/View/admin/emails.php:50` | `(int) $selectedMessage['source']` transforme `'inbox'` en `0`. L'URL `/admin/emails/0/{id}/trash` ne fonctionne que par accident (`EmailController.php:135`). | ✅ Lot 5 |
| P-26 | mineur | `OrderService.php:677-708` | Contrôle du matériel prêté fait **avant** l'application de la nouvelle case cochée. On peut passer `delivered → completed` tout en cochant « matériel prêté », sans passer par l'état d'attente de retour. | ✅ Lot 5 |
| P-27 | mineur | `MenuStatisticsService.php:21` vs `AdminService.php:113-116,163` | Statistiques Mongo : `completed` et `delivered`. CA SQL : `completed` seul. Chiffres incohérents entre les deux écrans. | ✅ Lot 6 |
| P-28 | mineur | `MenuService.php:17-51`, `OrderService.php:168` | Menus mis en cache 5 min. La décrémentation du stock à la commande n'invalide pas ce cache, donc stock affiché périmé. `OrderController::create:220-224` vérifie aussi le stock sur la version en cache. | ✅ Lot 6 |
| P-29 | mineur | `App/Service/CacheService.php:29,100` | `unserialize(..., allowed_classes => true)` sur des fichiers du répertoire temporaire système (partagé). Sert aussi de stockage pour la limitation de débit. | ✅ Lot 2 |
| P-30 | mineur | `App/Middleware/Security.php:20-25,81-86` | La limitation de débit compte aussi les connexions réussies. `/admin/login` n'est pas couvert (le préfixe `/login` ne correspond pas). | ✅ Lot 2 |
| P-31 | mineur | `OrderController.php:488-501`, `OrderService.php:82-85`, `AuthService.php:43-70` | Trois validations de téléphone divergentes. L'inscription accepte BE, GB et IT ; la commande n'accepte que FR et ES. Un client belge inscrit ne peut pas commander avec son numéro. | ✅ Lots 7 et 7 bis |
| P-32 | mineur | `AuthService.php:20-41`, `AdminService.php:203-224` | Politique de mot de passe dupliquée. `config('security.password_hash_cost')` n'est jamais utilisé. | ✅ Lot 7 |
| P-33 | mineur | `ContactController.php:53-54`, `email-templates/contact-reply.html:13` | Adresse `contact@website.dylan.local` codée en dur (repli et pied de mail). | ✅ Lot 3 |
| P-34 | mineur | `OrderController.php:358-363` | Accès direct à `$_POST['number_of_people']` et suivants sans `??` : avertissements ou `TypeError` si un champ manque. | ✅ Lot 1 |
| P-35 | mineur | `order/index.php:49,74-119` | En modification, les créneaux sont générés de 00:00 à 23:45 sans tenir compte des horaires. Le créneau actuel (`HH:MM:SS`) n'est jamais re-sélectionné, car les options sont au format `HH:MM`. | ✅ Lot 1 |
| P-36 | mineur | `NotificationController.php:24-25` | Un `GET /notifications` marque tout comme lu : effet de bord sur une requête GET. | ✅ Lot 5 |
| P-37 | mineur | `OrderService.php:741` | Notification client avec le statut technique anglais (« accepted »). | ✅ Lot 5 |
| P-38 | MVC | `AdminService.php:70-148`, `QuoteService.php`, `EmailService.php`, `ContactService.php`, `MenuStatisticsService.php:16-38` | SQL écrit directement dans des Services, sans repository. | ✅ Lot 9 (MVC) |
| P-39 | MVC | `PublicController.php:179-196` | Accès MongoDB directement depuis un contrôleur. | ✅ Lot 4 |
| P-40 | MVC | `contact/index.php:2-4`, `quote/index.php:2-6`, `admin/customers.php:4`, `auth/profile.php:2-3`, `auth/register.php:2-3` | Vues qui lisent ou modifient `$_SESSION` et `$_GET`. | ✅ Lot 9 (MVC) |
| P-41 | MVC | `OrderController.php:165-323` | Contrôleur obèse : la validation de commande duplique celle de `OrderService::createOrder`. | ✅ Lots 9 (MVC) et 10 (B-10) |
| P-42 | MVC | `order/*.php`, `admin/orders.php`, `order/confirmation.php` | Libellés de statuts et de modes de prestation copiés dans au moins 5 vues. | ✅ Lots 9 (MVC) et 10 (B-8, B-9) |
| P-43 | code mort | `Core/Security.php`, `Core/UploadFile.php`, `Core/Autoloader.php`, `Core/Exception/*`, `Entity/Comment.php`, `CacheService::clear/has`, `CommentService::getApprovedReviews`, `NotificationService::unreadCount`, `Database::getMongo` | Jamais appelés. `Core/Security::verifyCsrf` lit le champ `token`, incohérent avec le middleware qui attend `csrf_token`. | ✅ Lots 9 (MVC) et 10 |
| P-44 | code mort | `public/assets/js/admin.js`, `public/assets/css/style.css`, `script.js:3-36` | `admin.js` et `style.css` ne sont pas chargés par le layout. `updateOrderPrice` cherche `#orderTotalPrice`, qui n'existe pas, et sort immédiatement. | ✅ Lots 9 (MVC) et 10 |
| P-45 | code mort | `old_frontend/` | Ancien front statique non référencé. `assets/` et `public/` y sont dupliqués à l'identique (mêmes tailles de fichiers). | ✅ Lot 9 (MVC) |
| P-46 | code mort | `config/routes.php:71`, `admin/menus.php:3` | Route `DELETE /admin/menus/{id}` inatteignable (pas de méthode surchargée). Champ `_method=PUT` ignoré. | ✅ Lot 9 (MVC) |
| P-47 | mineur | `admin/menus.php:3` | Imbrication HTML invalide : le `</form>` ferme à l'intérieur de `div.col-12`, qui s'était ouvert après le `<form>`. Labels sans `for` (RGAA). | ✅ Lot 8 |
| P-48 | mineur | `.gitignore:4`, `rector.php` | `*.lock` est ignoré, donc `composer.lock` n'est pas versionné et l'installation n'est pas reproductible. `rector.php` n'a pas de dépendance `rector/rector`. | ✅ Lots 9 et 10 (rector.php) |
| P-49 | mineur | fichiers sans `declare(strict_types=1)` | `MenuRepository`, `OrderRepository`, `MailService`, `ContactService`, `MenuStatisticsService`, `Middleware/Security`, entités : typage incohérent avec le reste du code. | ✅ Lot 9 (MVC) |
| P-50 | mineur | `README.md:86-89,141-151` | Le README cite `docs/`, deux rapports et un workflow GitHub Actions inexistants. Le dossier réel s'appelle `docs2/`. | ✅ Lot 9 |

**Aucune injection SQL trouvée** : toutes les requêtes sont préparées. Seul `LIMIT {$limit}` est interpolé, après bornage entier (`NotificationRepository.php:39-46`). **Aucun XSS trouvé** : sorties échappées en PHP, `escapeHtml` en JS. **CSRF** couvert globalement sur POST, PUT, PATCH et DELETE (`Middleware/Security.php:50-70`). **`.env` non versionné** (absent de `.git/index`).

---

## 5. Livrables annexes

| Livrable (source) | État | Constat |
|---|---|---|
| Copie à rendre Word/Excel (L13-17) | ❓ | Absente du dépôt. |
| Dépôt GitHub public (L287) | ❓ | Remote configuré ; visibilité non vérifiée. |
| Application déployée (L257, L289) | ❌ | Aucun fichier ni aucune documentation de déploiement. `APP_DEBUG=true` par défaut (P-12). |
| Outil de gestion de projet (L291) | ❌ | Aucun lien. |
| README d'installation locale (L295) | 🟡 | Existe mais contient des inexactitudes (P-50, auth Mongo). |
| Workflow git main / dev / branches de fonctionnalité (L297-305) | 🟡 | Pas de branche de fonctionnalité visible (voir EX-66). |
| Scripts SQL de création et de données (L307) | ✅ | `database/schema.sql`, `database/seed.sql`, plus `mongodb-init.js`. |
| Manuel d'utilisation PDF avec identifiants (L309-311) | ❌ | Absent. Identifiants du seed à changer de toute façon (P-03). |
| Charte graphique PDF : palette et police (L313-315) | ❌ | Palette présente seulement dans `tokens.css`. Aucune police définie hors Bootstrap. |
| Maquettes : 3 bureau et 3 mobile, wireframes et mockups (L317) | ❌ | Absentes. |
| Documentation de gestion de projet (L320-322) | ❌ | Absente. |
| Documentation technique : réflexions, environnement, MCD ou diagramme de classes, cas d'utilisation, séquence, déploiement (L324-334) | ❌ | Absente. `docs2/bugs.md` n'est qu'un journal de corrections. |
| Justification sécurité et choix techniques (L283) | ❌ | Absente. |

---

## 6. Plan de reprise priorisé

Ordre : du plus bloquant au moins urgent. Aucun changement de stack.

1. **Rétablir le parcours de commande.** Corriger le double déballage de `data` dans `order.js` (P-01), implémenter la pré-sélection du menu (P-02) et afficher nom, prénom et e-mail pré-remplis (EX-26). Tester le parcours complet de bout en bout : menu → détail → commande → confirmation.
2. **Assainir les comptes et l'authentification.** Nouveaux mots de passe conformes dans le seed (P-03), suppression du repli en clair dans `AuthService::login` (P-04), `APP_DEBUG=false` par défaut (P-12).
3. **Rendre l'envoi de mails réel.** Brancher un vrai transport SMTP qui utilise la config `MAIL_*` (P-05), ajouter le mail de création d'employé (P-06) et corriger les `\\n` (P-22).
4. **Livrer les fonctions obligatoires manquantes.** CRUD des plats avec allergènes et composition des menus (EX-42, P-09), gestion de la galerie d'images (EX-10), modification d'une commande par l'employé après contact (EX-44).
5. **Corriger les écarts métier visibles.** Menus épuisés visibles en admin (P-07), horaires du pied de page lus en base (P-08), réponses JSON des formulaires admin remplacées par redirection et message flash (P-13), images supprimées par `menus.js` (P-15), thèmes de l'énoncé (EX-11). Trancher le plafond de 30 personnes (P-14) et le calcul de la distance (P-10).
6. **Cohérence NoSQL.** Aligner le seed et le tableau de bord (P-16, P-17), unifier les critères statistiques Mongo et CA (P-27), invalider le cache après une commande (P-28).
7. **Déployer.** Choisir l'hébergeur (PHP, MariaDB, MongoDB), configurer les variables d'environnement de production et HTTPS (`SESSION_SECURE=true`), mettre en ligne et consigner chaque étape pour la documentation de déploiement.
8. **Conformité réglementaire et accessibilité.** Rédiger mentions légales, CGV (avec la clause des 600 €) et politique de confidentialité (P-18). Faire une passe RGAA (labels, contrastes, navigation clavier) et corriger P-47.
9. **Robustesse et erreurs.** Pages 404/500 en HTML (P-19, P-20, P-21), accès `$_POST` sécurisés (P-34), limitation de débit sur `/admin/login` (P-30), validation téléphone unique (P-31), pré-remplissage du devis (P-23), formulaire de changement de mot de passe (P-11).
10. **Nettoyage MVC et code mort.** Déplacer le SQL des services vers des repositories (P-38, P-39), sortir la logique de session des vues (P-40), factoriser libellés et validations (P-32, P-41, P-42), supprimer le code mort et `old_frontend/` (P-43 à P-46), versionner `composer.lock` (P-48).
11. **Livrables documentaires.** Maquettes (3 bureau et 3 mobile), charte graphique PDF, manuel d'utilisation PDF avec identifiants, documentation technique (MCD, cas d'utilisation, séquence, déploiement, sécurité), documentation de gestion de projet et lien vers l'outil, README corrigé (P-50).
12. **Hygiène git pour la suite.** Une branche par fonctionnalité issue de `dev`, fusion dans `dev` après test, puis `dev` vers `main`, avec des messages de commit explicites.
