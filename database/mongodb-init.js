// Mise à jour MongoDB non destructive pour Vite & Gourmand.
// Conserve les collections et documents existants.
// Restaure uniquement la collection menu_images à partir des références historiques.
// Les documents comments/menu_statistics sont laissés intacts.

const dbName = "viteetgourmand";
const database = db.getSiblingDB(dbName);

const requiredCollections = [
    "comments",
    "menu_statistics",
    "menu_images"
];

for (const name of requiredCollections) {
    if (!database.getCollectionNames().includes(name)) {
        database.createCollection(name);
        print("Collection créée : " + name);
    }
}

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



// Restauration ciblée : un menu existant est remis à jour uniquement pour
// les positions présentes dans le catalogue historique.
// Aucune suppression : upsert conserve les autres documents.
for (const image of menuImages) {
    database.menu_images.updateOne(
        { menuId: image.menuId, position: image.position },
        { $set: image },
        { upsert: true }
    );
}

database.menu_images.createIndex(
    { menuId: 1, position: 1 },
    { name: "menu_images_menu_position_unique", unique: true }
);

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
    { name: "menu_statistics_menu_period_unique" }
);

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

print("Mise à jour MongoDB terminée sans suppression de données.");
print("Base : " + dbName);
print("menu_images restaurées/vérifiées : " + menuImages.length);
database.getCollectionNames().forEach((name) => print(" - " + name));
