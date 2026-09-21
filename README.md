# Vite & Gourmand — ECF Studi

Application web de commande de menus traiteur réalisée dans le cadre de l'ECF Studi.

Le projet utilise du PHP sans framework Symfony, avec une organisation MVC classique. Le JavaScript sert surtout aux interactions des formulaires, aux filtres et à la prévisualisation, tandis que la logique métier reste traitée côté serveur.

## Stack

- PHP 8+
- Composer
- MariaDB
- MongoDB
- Apache ou serveur PHP intégré
- HTML / CSS / JavaScript
- Architecture MVC maison

## Installation

Cloner le projet puis installer les dépendances :

```bash
composer install
```

Configurer ensuite les paramètres de connexion dans le fichier d'environnement utilisé par le projet.

### Base MariaDB

Les scripts utiles sont :

- `database/schema.sql` : création complète de la structure
- `database/seed.sql` : données de départ et catalogue
- `database/mongodb-init.js` : initialisation et mise à jour de MongoDB

Pour une nouvelle base MariaDB :

```text
database/schema.sql
database/seed.sql
```

Le `schema.sql` recrée la structure. Il est donc destiné à une installation neuve ou à une réinitialisation volontaire.

Le `seed.sql` est conçu pour alimenter une base existante sans supprimer les utilisateurs, commandes, devis, notifications ou historiques métier. Le catalogue des menus et des plats est remis en cohérence avec l'application.

### MongoDB

Depuis la racine du projet :

```bash
mongosh "mongodb://127.0.0.1:27017/viteetgourmand" database/mongodb-init.js
```

Le script crée ou met à jour les collections nécessaires sans suppression globale.

## Lancer le projet

Depuis la racine :

```bash
php -S 127.0.0.1:8080 -t public
```

Autre port possible :

```bash
php -S 127.0.0.1:8888 -t public
```

Avec Apache, le DocumentRoot doit pointer vers `public/`.

## Organisation

```text
App/
├── Controller/
├── Core/
├── Entity/
├── Middleware/
├── Repository/
├── Service/
└── View/

config/
database/
docs/
public/
vendor/
```

- `App/Controller` : gestion des requêtes
- `App/Service` : logique métier
- `App/Repository` : accès aux données
- `App/Entity` : objets métier
- `App/View` : vues PHP
- `public/` : point d'entrée et ressources publiques
- `config/` : routes et configuration
- `database/` : scripts SQL et MongoDB
- `docs/` : documents liés à l'ECF

## Fonctionnalités principales

- inscription et connexion ;
- rôles client, employé et administrateur ;
- consultation et filtrage des menus ;
- détail des menus, plats et allergènes ;
- passage de commande ;
- contrôle du nombre de convives et du minimum par menu ;
- calcul du prix et des remises ;
- calcul des frais de livraison ;
- contrôle des dates et créneaux selon les horaires ;
- suivi des commandes ;
- demandes de devis pour les prestations plus importantes ;
- gestion des horaires ;
- gestion des clients, employés et menus ;
- commentaires et statistiques ;
- utilisation de MongoDB pour les contenus complémentaires prévus par le projet.

## Horaires de référence

Les horaires sont enregistrés directement dans `database/seed.sql`.

Jours avec deux plages :
- 11h30–15h30
- 18h00–23h00

Jeudi et dimanche :
- 12h00–20h00

Lundi :
- fermé

## Vérification PHP

Pour vérifier la syntaxe :

```bash
find App config public -type f -name "*.php" -print0 | xargs -0 -n1 php -l
```

Un workflow GitHub Actions est également présent pour les contrôles automatiques.

## Documentation ECF

Les documents de suivi sont dans `docs/` :

- `docs/enoncer_ecf.md`
- `docs/RAPPORT-V4.MD`
- `docs/RAPPORT-V6.MD`

Le dossier `database/` reste réservé aux scripts de base de données. La documentation générale du projet est à la racine ou dans `docs/`.
