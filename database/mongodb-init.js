// Mise à jour MongoDB contrôlée pour Vite & Gourmand.
// Ce script ne supprime ni ne recrée la base.
// Il applique uniquement des opérations CRUD ciblées et idempotentes.
//
// Utilisation :
// mongosh "mongodb://..." database/mongodb-init.js
//
// Principe :
// - créer les collections manquantes si nécessaire ;
// - créer les index utiles avec createIndex ;
// - conserver les documents existants ;
// - mettre à jour seulement les documents explicitement ciblés avec updateMany/updateOne ;
// - ne jamais utiliser deleteMany()/drop()/insertMany() pour réinitialiser la base.

const dbName = "viteetgourmand";
const database = db.getSiblingDB(dbName);

// -----------------------------------------------------------------------------
// Collections nécessaires
// -----------------------------------------------------------------------------
const requiredCollections = [
    "comments",
    "menu_statistics"
];

for (const name of requiredCollections) {
    if (!database.getCollectionNames().includes(name)) {
        database.createCollection(name);
        print("Collection créée : " + name);
    }
}

// -----------------------------------------------------------------------------
// Index des avis
// -----------------------------------------------------------------------------
database.comments.createIndex(
    { isValidated: 1, createdAt: -1 },
    { name: "comments_validation_createdAt" }
);

database.comments.createIndex(
    { userId: 1, orderId: 1 },
    { name: "comments_user_order_unique", unique: true }
);

// -----------------------------------------------------------------------------
// Index des statistiques
// -----------------------------------------------------------------------------
database.menu_statistics.createIndex(
    { menuId: 1, periodIdentifier: 1 },
    { name: "menu_statistics_menu_period_unique" }
);

// -----------------------------------------------------------------------------
// Migration CRUD exemple : normalisation des avis existants.
// Aucun document n'est supprimé.
// -----------------------------------------------------------------------------
database.comments.updateMany(
    {
        content: { $exists: true },
        comment: { $exists: false }
    },
    [
        {
            $set: {
                comment: "$content"
            }
        }
    ]
);

// On peut ensuite supprimer l'ancien champ uniquement après vérification manuelle.
// L'opération reste volontairement désactivée pour éviter toute perte de données.
// database.comments.updateMany(
//     { comment: { $exists: true }, content: { $exists: true } },
//     { $unset: { content: "" } }
// );

print("Mise à jour MongoDB terminée.");
print("Base : " + dbName);
print("Collections présentes :");
database.getCollectionNames().forEach((name) => print(" - " + name));
