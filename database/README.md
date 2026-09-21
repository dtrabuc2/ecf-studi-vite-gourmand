# Base de données

Les scripts de base de données sont séparés entre la structure relationnelle, les données initiales et les mises à jour MongoDB.

## MariaDB

1. Importer `schema.sql`.
2. Importer `seed.sql`.

La base relationnelle reste utilisée par le backend pour les utilisateurs, menus, commandes, horaires et autres données métier structurées.

## MongoDB

Exécuter `mongodb-init.js` avec `mongosh`.

Le script est conçu pour une mise à jour CRUD contrôlée :
- création des collections manquantes ;
- création des index ;
- mise à jour ciblée de documents existants ;
- aucune suppression globale ;
- aucune réinsertion massive de la base.

Le principe est de conserver les données existantes et de privilégier les opérations directement vérifiables dans MongoDB Compass ou mongosh.
