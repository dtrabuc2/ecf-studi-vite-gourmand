// Initialisation MongoDB Vite & Gourmand
// Les images des menus sont stockées exclusivement dans MongoDB.
// MariaDB ne contient aucune table menu_images.

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

// Images récupérées depuis l'ancien frontend.
// Elles sont liées aux vrais identifiants des menus MariaDB.
const menuImages = [
    {
        menuId: 1,
        position: 1,
        url: "https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&auto=format&fit=crop",
        altText: "Présentation gastronomique du Menu de Noël"
    },
    {
        menuId: 2,
        position: 1,
        url: "https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1200&auto=format&fit=crop",
        altText: "Présentation gourmande du Menu de Pâques"
    },
    {
        menuId: 3,
        position: 1,
        url: "https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop",
        altText: "Présentation du Menu végétarien"
    }
];

// Réinitialise uniquement les images gérées par ce script.
database.menu_images.deleteMany({
    menuId: { $in: menuImages.map((image) => image.menuId) }
});

if (menuImages.length > 0) {
    database.menu_images.insertMany(menuImages);
}

database.comments.createIndex(
    { isValidated: 1, createdAt: -1 },
    { name: "comments_validation_createdAt" }
);

database.comments.createIndex(
    { userId: 1, orderId: 1 },
    { name: "comments_user_order_unique", unique: true }
);

database.menu_images.createIndex(
    { menuId: 1, position: 1 },
    { name: "menu_images_menu_position_unique", unique: true }
);

database.menu_statistics.createIndex(
    { menuId: 1, periodIdentifier: 1 },
    { name: "menu_statistics_menu_period_unique", unique: true }
);

print("Base MongoDB initialisee : " + dbName);
print(menuImages.length + " image(s) de menu importee(s).");
