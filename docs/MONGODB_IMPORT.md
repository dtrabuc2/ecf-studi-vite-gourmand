# Import MongoDB avec MongoDB Compass

La base MongoDB utilisée par l'application est `vitegourmand` et contient deux collections :

- `comments`
- `menu_statistics`

MongoDB Compass importe un fichier JSON dans une collection à la fois. Les fichiers de seed du projet sont donc séparés.

## 1. Collection comments

Dans Compass :

1. Ouvre la base `vitegourmand`.
2. Crée ou sélectionne la collection `comments`.
3. Choisis **Add Data > Import JSON or CSV file**.
4. Sélectionne `viteetgourmand_db.json`.
5. Choisis le format **JSON** et lance l'import.

Le fichier est un tableau JSON valide en MongoDB Extended JSON : `$oid` et `$date` seront interprétés comme leurs types MongoDB.

## 2. Collection menu_statistics

Crée ou sélectionne la collection `menu_statistics`, puis importe :

`docs/mongodb-menu-statistics.seed.json`

Ce fichier est également un tableau JSON Extended JSON valide.

## 3. Index

Après l'import, exécute dans l'onglet mongosh de Compass :

```javascript
db.comments.createIndex({ isValidated: 1, createdAt: -1 });
db.comments.createIndex({ userId: 1, orderId: 1 }, { unique: true });
db.menu_statistics.createIndex({ menuId: 1, periodIdentifier: 1 }, { unique: true });
```

Les avis sont des données de démonstration. Les `userId`, `menuId` et `orderId` correspondent aux identifiants utilisés par la base MariaDB de démonstration.

Les statistiques fournies dans le seed sont initialisées à zéro ; elles sont ensuite alimentées par l'application à partir des commandes terminées ou livrées.
