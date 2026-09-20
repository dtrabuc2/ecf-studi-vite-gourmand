// Initialisation idempotente de MongoDB pour Vite & Gourmand.
// MariaDB reste la source des menus, plats, thèmes, régimes, allergènes et commandes.
// MongoDB stocke les galeries d'images, les avis validés/en attente et les statistiques.

const database = db.getSiblingDB("viteetgourmand");

[
    "comments",
    "menu_statistics",
    "menu_images"
].forEach((name) => {
    if (!database.getCollectionNames().includes(name)) {
        database.createCollection(name);
    }
});

const menuImages = [
    {
        menuId: 1,
        position: 1,
        url: "https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&auto=format&fit=crop",
        href: "https://www.pexels.com/photo/meat-dish-on-plate-in-restaurant-16021237/",
        altText: "Présentation gastronomique du Menu Prestige"
    },
    {
        menuId: 1,
        position: 2,
        url: "https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&auto=format&fit=crop",
        altText: "Assiette gastronomique du Menu Prestige"
    },
    {
        menuId: 2,
        position: 1,
        url: "https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop",
        href: "https://unsplash.com/s/photos/vegan-food",
        altText: "Présentation du Menu Végétarien Délice"
    },
    {
        menuId: 2,
        position: 2,
        url: "https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop",
        altText: "Plat végétarien du Menu Végétarien Délice"
    },
    {
        menuId: 3,
        position: 1,
        url: "https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1200&auto=format&fit=crop",
        href: "https://unsplash.com/s/photos/cocktail",
        altText: "Présentation du Menu Cocktail Chic"
    },
    {
        menuId: 3,
        position: 2,
        url: "https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&auto=format&fit=crop",
        altText: "Assortiment du Menu Cocktail Chic"
    },
    {
        menuId: 4,
        position: 1,
        url: "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1200&auto=format&fit=crop",
        href: "https://www.pexels.com/search/burger%20fries/",
        altText: "Présentation du Menu Enfant Gourmand"
    },
    {
        menuId: 4,
        position: 2,
        url: "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&auto=format&fit=crop",
        altText: "Plat du Menu Enfant Gourmand"
    },
    {
        menuId: 5,
        position: 1,
        url: "https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop",
        href: "https://unsplash.com/s/photos/plant-based-food",
        altText: "Présentation du Menu Méditerranéen Végétal"
    },
    {
        menuId: 5,
        position: 2,
        url: "https://images.unsplash.com/photo-1512058564366-18510be2db19?w=1200&auto=format&fit=crop",
        altText: "Plat végétal du Menu Méditerranéen"
    },
    {
        menuId: 6,
        position: 1,
        url: "https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&auto=format&fit=crop",
        href: "https://unsplash.com/s/photos/seafood-dish",
        altText: "Présentation du Menu Saveurs de la Mer"
    },
    {
        menuId: 6,
        position: 2,
        url: "https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&auto=format&fit=crop",
        altText: "Poisson et fruits de mer du menu"
    }
];

const imageCollection = database.menu_images;

imageCollection.deleteMany({
    menuId: { $in: [1, 2, 3, 4, 5, 6] }
});

imageCollection.insertMany(menuImages);

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
print("Galeries de menus : " + menuImages.length + " images.");
