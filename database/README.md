# Base de données

Les scripts de base de données sont séparés pour distinguer la structure, les données initiales et l'initialisation MongoDB.

## MariaDB

1. Importer `schema.sql`.
2. Importer `seed.sql`.

Les deux scripts ciblent la base `viteetgourmand` et sont compatibles avec MariaDB/MySQL via phpMyAdmin.

## MongoDB

Exécuter `mongodb-init.js` avec `mongosh` ou l'importer selon l'outil utilisé.

MongoDB est utilisé pour les images de menus, les avis et les statistiques. MariaDB reste la source des menus, plats, régimes, allergènes et commandes.
