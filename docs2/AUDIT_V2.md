# AUDIT V2 — Vite & Gourmand (ECF DWWM)

Audit réalisé le 2026-10-04 sur la branche `dev` du dossier `E:\Codage\ECF\ecf-studi-vite-gourmand`.
Il remplace l'audit du 2026-09-29 (`AUDIT.md`), qui sert désormais d'historique.

## 0. Méthode et limites

Contrairement au premier audit (lecture du code uniquement), celui-ci a **fait tourner l'application** dans un environnement de test :

- copie du projet (hors `vendor/`, `.git/`, `.env`), `composer install` ;
- **MariaDB 10.11** : import réel de `database/schema.sql` puis `database/seed.sql` ;
- serveur `php -S` (PHP 8.4) et faux serveur SMTP qui capture les e-mails ;
- **67 contrôles de bout en bout** par de vraies requêtes HTTP (formulaires avec jeton CSRF, sessions, redirections), en visiteur, client, employé et administrateur ;
- lecture du code pour ce qui ne pouvait pas tourner.

Limites, à garder en tête :

- **MongoDB n'a pas pu être installé** dans l'environnement de test (téléchargement bloqué). Les 3 repositories MongoDB ont été remplacés, **dans la copie de test uniquement**, par des versions en mémoire avec la même interface. Les parcours qui passent par MongoDB (avis, galerie, statistiques) ont donc été testés côté contrôleurs, services et SQL ; le code MongoDB lui-même a été relu, pas exécuté.
- Aucun navigateur : le JavaScript (intl-tel-input, aperçu dynamique, carrousel, burger) n'a pas été exécuté. Les points d'API qu'il appelle, eux, ont été testés.
- Ni l'API Google Distance Matrix ni un vrai serveur SMTP n'ont été appelés.
- Ton poste Windows n'a pas été utilisé : ta base locale n'a pas été touchée.

## 1. Résumé

- **Le code est en bon état.** 105 fichiers PHP sans erreur de syntaxe, schéma et seed importés sans erreur, **66 contrôles sur 67 réussis**, aucun avertissement ni erreur PHP dans le journal du serveur pendant toute la campagne. Les mots de passe du README correspondent bien aux hashs du seed.
- **Le problème n'est plus le code, c'est la livraison.**
  - Sur GitHub, `dev` est resté au 21 septembre : les 12 commits du 1er octobre n'ont jamais été poussés, et les « corrections avant recette » ne sont même pas commitées.
  - `main` ne contient que 3 commits sans rapport avec `dev` (pas d'ancêtre commun).
  - Rien n'est déployé, et aucun livrable documentaire n'existe.
- **Exigences (73)** : 57 ✅ · 5 🟡 · 9 ❌ · 2 ❓. Toutes les ❌ sont des livrables hors code (déploiement, documents, maquettes).
- **3 risques principaux**
  1. Le dépôt public ne montre pas ton travail (branche par défaut quasi vide, `dev` en retard de 12 commits).
  2. Pas d'application en ligne : l'énoncé prévoit des pénalités.
  3. Aucun des 7 livrables documentaires n'est commencé (manuel, charte, maquettes, doc technique, gestion de projet, copie).

## 2. État du dépôt

| Élément | Constat |
|---|---|
| Branche locale | `dev`, dernier commit `25f2eb3` (ajout de `CLAUDE.md`) |
| Non commité | 13 fichiers modifiés + `public/assets/vendor/bootstrap/` (les « corrections avant recette » : mot de passe en session, CA filtré, retour après connexion, Bootstrap JS local) |
| `origin/dev` (GitHub) | `6ec1c96`, du 21 septembre : **12 commits locaux non poussés** |
| `origin/main` (GitHub) | 3 commits (`Initial commit`, `App/View/admin/quotes.php`, `.github/workflows/php.yml`). **Aucun ancêtre commun avec `dev`** : la fusion `dev` → `main` demandera `--allow-unrelated-histories` |
| Branches distantes | `main`, `dev`, `backup/pre-seed-user-restore-2026-09-21`, `legacy/a-la-carte-mongodb-images` |
| Branche de fonctionnalité | `feature/corrections-audit`, fusionnée dans `dev` en local, jamais poussée |
| Visibilité | Dépôt lisible sans authentification (`git ls-remote` anonyme réussi) : il est public |
| CI | `php.yml` (PHP 8.5, Windows) n'existe que sur `main` : elle ne se déclenche donc jamais sur `dev` |

## 3. Exigences de l'énoncé

Vérification : **E2E** = testé par requêtes HTTP réelles · **E2E\*** = testé avec MongoDB simulé · **Import** = import SQL réel · **Code** = lecture du code.

### 3.1 Fonctionnalités

| EX | Exigence | Statut | Vérif. | Constat |
|---|---|---|---|---|
| EX-01 | Accueil : présentation de l'entreprise | ✅ | E2E | Présente. |
| EX-02 | Accueil : professionnalisme de l'équipe | ✅ | E2E | Section équipe avec Julie et José. |
| EX-03 | Accueil : avis validés | ✅ | E2E\* | Seuls les avis validés s'affichent ; un avis validé par l'employé apparaît. |
| EX-04 | Menu : accueil, menus, connexion, contact | ✅ | E2E | Présent. |
| EX-05 | Connexion 3 rôles, RGPD et sécurité | ✅ | E2E | Consentement obligatoire à l'inscription, politique de confidentialité, mentions légales. |
| EX-06 | Pied de page : horaires du lundi au dimanche | ✅ | E2E | Lus en base : une modification dans `/admin/hours` apparaît aussitôt. |
| EX-07 | Liens mentions légales et CGV | ✅ | E2E | `/legal`, `/cgv`, `/confidentialite` répondent 200. |
| EX-08 | Menus configurables par admin et employé | ✅ | E2E\* | Création, composition en plats et galerie accessibles à l'employé. |
| EX-09 | Caractéristiques d'un menu | ✅ | Import | Toutes présentes dans `menus`. |
| EX-10 | Galerie d'images | ✅ | E2E\* + Code | Gestion admin et affichage ; code MongoDB relu, non exécuté. |
| EX-11 | Thèmes Noël, Pâques, Classique, Évènement | ✅ | E2E | Thème hors liste refusé ; seed conforme. |
| EX-12 | Plats partagés entre menus | ✅ | Import | N-N `menu_dishes` (38 liaisons). |
| EX-13 | Allergènes par plat | ✅ | E2E | Affichés sur la page détail ; N-N `dish_allergens`. |
| EX-14 | Vue globale publique | ✅ | E2E | `/menus` en 200 pour un visiteur. |
| EX-15 | Filtres prix max, fourchette, thème, régime, nombre | ✅ | E2E | Les 5 filtres répondent ; prix max respecté ; saisie invalide → 422, pas 500. |
| EX-16 | Filtrage sans rechargement | ✅ | E2E + Code | Point d'API JSON testé ; JS relu, non exécuté. |
| EX-17 | Inscription et mot de passe fort | ✅ | E2E | Mot de passe faible refusé ; téléphones stockés en E.164 ; GSM étranger hors liste refusé. |
| EX-18 | Rôle « utilisateur » à la création | ✅ | E2E | Un champ `role=admin` injecté est ignoré. |
| EX-19 | Mail de bienvenue | ✅ | E2E | Reçu par le SMTP de test. |
| EX-20 | Connexion e-mail + mot de passe | ✅ | E2E | Les 3 comptes du README se connectent. |
| EX-21 | Mot de passe oublié par lien | ✅ | E2E | Lien reçu, réinitialisation, puis connexion avec le nouveau mot de passe. |
| EX-22 | Vue détaillée complète | ✅ | E2E | Conditions, allergènes, stock, galerie. |
| EX-23 | Bouton « Commander », menu pré-rempli | ✅ | E2E | `?menu=3` pré-sélectionne le menu et l'annonce. |
| EX-24 | Visiteur : se connecter **ou créer un compte** | 🟡 | E2E | Le bouton mène à la connexion, avec retour au menu ensuite (testé). Mais la page détail ne propose pas de lien « Créer un compte » : il faut passer par la page de connexion. |
| EX-25 | Conditions bien en évidence | ✅ | E2E | Bloc d'alerte dédié. |
| EX-26 | Nom, prénom, e-mail pré-remplis | ✅ | E2E | En lecture seule. |
| EX-27 | Adresse, date, heure, lieu | ✅ | E2E | Créneaux calculés depuis les horaires ; commande créée. |
| EX-28 | Livraison hors Bordeaux : 5 € + 0,59 €/km | 🟡 | E2E + Code | Bordeaux : 0 € (testé). Hors Bordeaux sans clé API : refus propre (testé). **Le calcul avec la vraie API n'a jamais été exécuté.** |
| EX-29 | GSM pré-rempli | ✅ | E2E | Pré-rempli depuis le compte. |
| EX-30 | Minimum de personnes, prix mis à jour | ✅ | E2E | Aperçu du prix par l'API. |
| EX-31 | −10 % dès minimum + 5 personnes | ✅ | E2E | 6 personnes sur un menu à minimum 1 : 11,90 × 6 × 0,9 = **64,26 €**, exact. |
| EX-32 | Détail du prix avant validation | ✅ | E2E | Prix du menu, remise, livraison et total. |
| EX-33 | Mail de confirmation | ✅ | E2E | Reçu. Le stock du menu baisse de 1. |
| EX-34 | Consulter ses commandes | ✅ | E2E | `/orders`. |
| EX-35 | Modifier ses informations | ✅ | Code | Profil et changement de mot de passe. |
| EX-36 | Annulation avant « acceptée » | ✅ | E2E | Annulation d'une commande en attente. |
| EX-37 | Modification avant acceptation, sauf le menu | ✅ | E2E | Nombre de personnes et mode de prestation modifiés. |
| EX-38 | Suivi avec date et heure | ✅ | E2E | 7 lignes d'historique pour une commande menée jusqu'à « terminée ». |
| EX-39 | Mail « terminée, donnez votre avis » | ✅ | E2E | Reçu. |
| EX-40 | Avis : note 1 à 5 + commentaire | ✅ | E2E\* | Note hors limites et second avis refusés. |
| EX-41 | Employé : modifier et supprimer les menus | ✅ | E2E | Création testée ; suppression logique relue. |
| EX-42 | Employé : modifier et supprimer les plats | ✅ | E2E | Création d'un plat avec allergène ; page d'édition en 200. |
| EX-43 | Employé : modifier les horaires | ✅ | E2E | Modification visible dans le pied de page. |
| EX-44 | Modifier ou annuler après contact (mode + motif) | ✅ | E2E | Annulation sans mode de contact refusée, acceptée avec. |
| EX-45 | Filtre des commandes | ✅ | E2E | Par statut et par client. |
| EX-46 | Les 6 statuts | ✅ | E2E | Parcours complet : acceptée → … → en attente de retour → terminée. |
| EX-47 | Mail 600 € / 10 jours ouvrés | ✅ | E2E | Reçu au passage en « attente du retour de matériel » ; clause présente dans les CGV. |
| EX-48 | Valider ou refuser les avis | ✅ | E2E\* | Validation avec redirection (plus de JSON). |
| EX-49 | Admin : créer un employé | ✅ | E2E | Un champ `role=admin` injecté donne quand même `employee`. |
| EX-50 | Mail à l'employé, sans mot de passe | ✅ | E2E | Reçu ; le mot de passe n'y figure pas. |
| EX-51 | Désactiver un employé | ✅ | E2E | L'employé désactivé ne peut plus se connecter. |
| EX-52 | Pas de création d'admin depuis l'application | ✅ | E2E | Voir EX-18 et EX-49. |
| EX-53 | L'admin fait tout ce que fait l'employé | ✅ | E2E | Accès aux pages de l'équipe ; l'employé n'a pas accès aux pages admin. |
| EX-54 | Graphique commandes par menu, données NoSQL | ✅ | E2E\* + Code | Statistiques écrites dans MongoDB à chaque commande terminée, puis lues par le tableau de bord. Voir N-12. |
| EX-55 | CA par menu, filtres menu et période | ✅ | E2E | Période sans commande : tous les menus listés à 0 €. |
| EX-56 | Contact envoyé par mail à l'entreprise | ✅ | E2E | Reçu à `MAIL_TO_ADDRESS`. |
| EX-57 | Application déployée | ❌ | — | Aucune mise en ligne, aucune configuration de déploiement. |
| EX-58 | Accessibilité RGAA | 🟡 | Code | `lang="fr"`, labels reliés, erreurs liées par `aria-describedby`. Pas de lien d'évitement, aucun audit outillé (contrastes, clavier, lecteur d'écran). |

### 3.2 Contraintes techniques et livrables

| EX | Exigence | Statut | Constat |
|---|---|---|---|
| EX-59 | Base relationnelle + non relationnelle | ✅ | MariaDB (PDO) + MongoDB. |
| EX-60 | Justifier les choix techniques et la sécurité | ❌ | Seul le README en donne une base. |
| EX-61 | MCD de l'annexe 1 | ❓ | Annexe absente de `docs2/enoncer_ecf.md`. L'énoncé PDF original existe dans `E:\ECF\vite-et-gourmand\docs\ecf-enonce.pdf` : à récupérer pour comparer. |
| EX-62 | Dépôt GitHub public | 🟡 | Public, mais la branche `main` ne contient pas le projet, et `dev` y a 12 commits de retard. |
| EX-63 | Lien de l'application déployée | ❌ | — |
| EX-64 | Lien de l'outil de gestion de projet | ❌ | — |
| EX-65 | README : installation locale | ✅ | Complet, avec les comptes de test (vérifiés). |
| EX-66 | Bonnes pratiques git | 🟡 | `main`, `dev` et une branche de fonctionnalité existent, mais rien n'est poussé, et `main` n'a jamais reçu `dev`. |
| EX-67 | SQL de création et d'insertion | ✅ | Import réel sans erreur : 3 utilisateurs, 9 menus, 32 plats, 10 allergènes, 5 commandes, 7 jours d'horaires. |
| EX-68 | Manuel d'utilisation PDF | ❌ | — |
| EX-69 | Charte graphique PDF | ❌ | Palette seulement dans `tokens.css`. |
| EX-70 | Maquettes : 3 bureau + 3 mobile | ❌ | — |
| EX-71 | Documentation de gestion de projet | ❌ | — |
| EX-72 | Documentation technique | ❌ | — |
| EX-73 | Copie à rendre | ❓ | Hors dépôt. |

**Décompte V2 : 57 ✅ · 5 🟡 · 9 ❌ · 2 ❓ (73).** Audit V1 au départ : 31 ✅ · 26 🟡 · 13 ❌ · 3 ❓.

## 4. Problèmes identifiés

| # | Gravité | Où | Problème |
|---|---|---|---|
| N-01 | **critique** | git | Corrections avant recette non commitées, 12 commits non poussés, `main` sans rapport avec `dev`. Un correcteur qui clone le dépôt aujourd'hui ne voit pas le projet. |
| N-02 | **critique** | — | Aucune application en ligne (EX-57, EX-63) : pénalités prévues par l'énoncé. |
| N-03 | **majeur** | — | Aucun livrable documentaire (EX-60, 64, 68 à 72). C'est la plus grosse charge de travail restante. |
| N-04 | majeur | ton poste | Base locale jamais réimportée depuis les corrections : les comptes du README ne fonctionnent pas chez toi. `MAIL_HOST` et `GOOGLE_MAPS_API_KEY` sont vides dans ton `.env` : ni mails ni livraison hors Bordeaux testables. |
| N-05 | majeur | `DeliveryDistanceService` | Le calcul hors Bordeaux n'a jamais été exécuté avec la vraie API (EX-28). |
| N-06 | mineur | `home/menu_detail.php:114` | Pas de lien « Créer un compte » pour le visiteur (EX-24). **Résolu le 2026-10-04** (voir `CORRECTIONS.md`, partie graphique). |
| N-07 | mineur | `Middleware/Security.php:16` | CSP `script-src 'unsafe-inline'` alors qu'aucun script ni attribut `on…` n'est inline dans les vues : protection XSS affaiblie sans raison. **Résolu le 2026-10-04** (voir `CORRECTIONS.md`, partie graphique). |
| N-08 | ~~mineur~~ corrigé le 2026-10-04 | `database/mongodb-init.js` | ~~Index `menu_statistics_menu_period_unique` créé sans `unique: true`.~~ L'index est maintenant unique. |
| N-09 | mineur | seed Mongo, CSP | Photos de démonstration chargées depuis Unsplash : sans Internet (ou si une URL disparaît), plus d'images à la démo. |
| N-10 | mineur | `public/` | Pas de `favicon.ico` : une 404 à chaque page. |
| N-11 | mineur | `Middleware/Security.php` | `X-Powered-By: PHP/…` exposé (à couper en production avec `expose_php = Off`). `X-XSS-Protection` est obsolète, sans effet. |
| N-12 | observation | `MenuStatisticsService` | Les statistiques MongoDB sont calculées en SQL puis copiées dans MongoDB à chaque commande terminée. C'est conforme (le graphique lit MongoDB), mais à justifier dans la doc technique, le jury posera la question. |
| N-13 | observation | `AdminController::updateOrderStatus` | Mode de contact obligatoire pour **chaque** changement de statut, même « en préparation ». L'énoncé ne l'exige que pour modifier ou annuler. Défendable, mais lourd à l'usage : à justifier ou à alléger. |
| N-14 | observation | `RateLimiter` | Limitation des connexions par IP : derrière une même IP (salle d'examen, réseau partagé), quelques échecs bloquent tout le monde pendant la fenêtre (constaté pendant les tests : 429). |
| N-15 | mineur | RGAA | Pas de lien « Aller au contenu ». Aucun audit outillé (contrastes, navigation clavier, lecteur d'écran). **Résolu le 2026-10-04** (voir `CORRECTIONS.md`, partie graphique). |
| N-16 | mineur | `.github/workflows/php.yml` | CI présente uniquement sur `main`, en PHP 8.5 sous Windows, alors que le projet cible PHP 8.2+ : elle ne vérifie jamais `dev`. |
| N-17 | mineur | RGPD | Consentement à l'inscription non horodaté en base (déjà noté dans `CORRECTIONS.md`). |
| N-18 | mineur | lot 10 | Commentaires absents sur les fichiers non modifiés ; `layout/flash.php` lit encore la session. |

### Ce qui a été vérifié et tient

- CSRF : un POST sans jeton reçoit un 403.
- Rôles : un client ne peut pas ouvrir l'administration, un employé ne peut pas ouvrir la gestion des employés, aucun moyen de créer un admin.
- Verrouillage après plusieurs échecs de connexion (429).
- Mot de passe oublié : jeton à usage unique, puis connexion avec le nouveau mot de passe.
- Téléphones validés côté serveur et stockés en E.164.
- Session régénérée à la connexion, cookie `HttpOnly`.
- En-têtes `X-Frame-Options`, `nosniff`, `Referrer-Policy` et CSP présents.
- Aucune erreur 500, aucun avertissement PHP pendant les 67 contrôles.

## 5. Livrables

| Livrable | État |
|---|---|
| Dépôt GitHub public | 🟡 Public, mais à pousser, et `main` à mettre à jour |
| Application en ligne | ❌ |
| Outil de gestion de projet | ❌ |
| README d'installation | ✅ |
| Workflow git | 🟡 |
| Fichiers SQL | ✅ |
| Manuel d'utilisation PDF | ❌ |
| Charte graphique PDF | ❌ |
| Maquettes (3 bureau + 3 mobile, wireframes et mockups) | ❌ |
| Documentation de gestion de projet | ❌ |
| Documentation technique (choix, environnement, MCD, cas d'utilisation, séquence, déploiement) | ❌ |
| Copie à rendre | ❓ |

## 6. Plan de reprise priorisé

1. **Sauver et publier le travail (N-01).** Commiter les corrections avant recette, pousser `dev`. Pas de fusion vers `main` tant que la recette n'est pas faite.
2. **Préparer ton poste (N-04).** Réimporter MariaDB et MongoDB (sauvegarde d'abord), installer un SMTP de test (Mailpit), et décider pour la clé Google : la renseigner, ou accepter le refus hors Bordeaux pendant les tests.
3. **Recette navigateur.** C'est la seule partie que cet audit n'a pas pu couvrir : JavaScript, intl-tel-input, carrousel, affichage mobile, MongoDB réel. Checklist par rôle dans `CORRECTIONS.md`.
4. **Petites corrections** (moins d'une heure) : N-06, N-07, N-08, N-10, et décider pour N-13.
5. **Fusionner `dev` dans `main`** après la recette, avec `--allow-unrelated-histories`. Décider avant ce qu'on garde de `main` (`LICENSE`, `php.yml` à corriger ou supprimer, `quotes.php` déjà présent dans `dev`).
6. **Déployer** (EX-57) : choisir l'hébergeur (PHP + MariaDB + MongoDB, éventuellement MongoDB Atlas), `APP_DEBUG=false`, HTTPS, `SESSION_SECURE=true`. Noter chaque étape pour la documentation de déploiement.
7. **Livrables documentaires**, dans cet ordre : MCD (comparé à l'annexe du PDF), diagrammes de cas d'utilisation et de séquence, documentation technique (dont la justification de N-12 et de la sécurité), maquettes, charte, manuel avec identifiants, gestion de projet et lien vers l'outil, copie.
8. **Fin du lot 10** : commentaires sur les fichiers restants (N-18).

## 7. Mise à jour du 2026-10-04 : bases de données

- `database/seed.sql` a été **fusionné dans `database/schema.sql`**, qui devient le fichier unique de référence (structure + données, importable directement dans phpMyAdmin). `seed.sql` est supprimé. Import testé deux fois de suite : mêmes données qu'avant (3 comptes avec les mots de passe du README, 9 menus, 32 plats, 10 allergènes, 5 commandes, 10 lignes d'historique, 7 jours d'horaires).
- `database/mongodb-init.js` repart de zéro (collections supprimées puis recréées), utilise le client fixe n° 3 (plus de variable `VG_CLIENT_USER_ID`) et crée l'index de statistiques en unique (N-08).
