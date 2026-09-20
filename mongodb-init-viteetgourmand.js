// Initialisation idempotente de MongoDB pour Vite & Gourmand.
const database = db.getSiblingDB("viteetgourmand");

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

["comments", "menu_statistics", "menu_images"].forEach((name) => {
    if (!database.getCollectionNames().includes(name)) {
        database.createCollection(name);
    }
});

const imageCollection = database.menu_images;

imageCollection.deleteMany({
    menuId: { $in: menuImages.map((image) => image.menuId) }
});

if (menuImages.length > 0) {
    imageCollection.insertMany(menuImages);
}

database.comments.createIndex(
    { isValidated: 1, createdAt: -1 },
    { name: "comments_validation_createdAt" }
);

database.comments.createIndex(
    { userId: 1, orderId: 1 },
    { name: "comments_user_order_unique", unique: true }
);

imageCollection.createIndex(
    { menuId: 1, position: 1 },
    { name: "menu_images_menu_position_unique", unique: true }
);

database.menu_statistics.createIndex(
    { menuId: 1, periodIdentifier: 1 },
    { name: "menu_statistics_menu_period_unique" }
);

print("MongoDB viteetgourmand initialisee.");
print("Images de menus : " + imageCollection.countDocuments({ menuId: { $in: menuImages.map((image) => image.menuId) } }));