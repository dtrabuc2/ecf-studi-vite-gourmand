// Fichier de référence MongoDB Vite & Gourmand
// À exécuter avec mongosh.

const dbName = "viteetgourmand";
const database = db.getSiblingDB(dbName);

if (!database.getCollectionNames().includes("comments")) {
    database.createCollection("comments");
}

if (!database.getCollectionNames().includes("menu_statistics")) {
    database.createCollection("menu_statistics");
}

if (!database.getCollectionNames().includes("menu_images")) {
    database.createCollection("menu_images");
}

database.comments.createIndex(
    { isValidated: 1, createdAt: -1 },
    { name: "comments_validation_createdAt" }
);

database.comments.createIndex(
    { userId: 1, orderId: 1 },
    { name: "comments_user_order_unique", unique: true }
);

database.menu_statistics.createIndex(
    { menuId: 1, periodIdentifier: 1 },
    { name: "menu_statistics_menu_period_unique", unique: true }
);

print("Base MongoDB initialisee : " + dbName);
