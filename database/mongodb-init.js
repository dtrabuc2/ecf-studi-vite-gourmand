// ================================================================
// INITIALISATION MONGODB — Vite & Gourmand
// Galeries menus, avis clients et statistiques.
// Les données restent alignées sur les menus SQL 1 à 9.
// ================================================================

const dbName = "viteetgourmand";
const database = db.getSiblingDB(dbName);

const collections = ["menu_images", "comments", "menu_statistics"];
for (const name of collections) {
    if (!database.getCollectionNames().includes(name)) {
        database.createCollection(name);
    }
}

database.menu_images.createIndex(
    { menuId: 1, position: 1 },
    { name: "menu_images_menu_position_unique", unique: true }
);
database.comments.createIndex(
    { userId: 1, orderId: 1 },
    { name: "comments_user_order_unique", unique: true }
);
database.comments.createIndex(
    { isValidated: 1, createdAt: -1 },
    { name: "comments_validation_createdAt" }
);
database.comments.createIndex(
    { menuId: 1, createdAt: -1 },
    { name: "comments_menu_createdAt" }
);
database.menu_statistics.createIndex(
    { menuId: 1, periodIdentifier: 1 },
    { name: "menu_statistics_menu_period_unique" }
);

const menuImages = [
    [1,1,"https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop","Velouté de légumes et formule solo classique"],
    [1,2,"https://images.unsplash.com/photo-1543353071-873f17a7a088?w=1200&auto=format&fit=crop","Steak haché et accompagnement"],
    [2,1,"https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop","Assiette végétale"],
    [2,2,"https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=1200&auto=format&fit=crop","Curry végétal et riz"],
    [3,1,"https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&auto=format&fit=crop","Menu enfant"],
    [3,2,"https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=1200&auto=format&fit=crop","Dessert enfant"],
    [4,1,"https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&auto=format&fit=crop","Menu duo convivial"],
    [4,2,"https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?w=1200&auto=format&fit=crop","Plat familial"],
    [5,1,"https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=1200&auto=format&fit=crop","Menu végétal pour trois"],
    [5,2,"https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop","Assiette végétarienne"],
    [6,1,"https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop","Menu familial traditionnel"],
    [6,2,"https://images.unsplash.com/photo-1543353071-873f17a7a088?w=1200&auto=format&fit=crop","Plat familial généreux"],
    [7,1,"https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&auto=format&fit=crop","Poisson et garniture"],
    [7,2,"https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&auto=format&fit=crop","Dessert gourmand"],
    [8,1,"https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&auto=format&fit=crop","Menu végétal élégant"],
    [8,2,"https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop","Composition végétale"],
    [9,1,"https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&auto=format&fit=crop","Réception gastronomique"],
    [9,2,"https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop","Présentation événementielle"]
];

for (const [menuId, position, url, altText] of menuImages) {
    database.menu_images.updateOne(
        { menuId, position },
        { $set: { menuId, position, url, altText, source: "Unsplash", updatedAt: new Date() } },
        { upsert: true }
    );
}

const reviews = [
    {
        userId: 1, menuId: 1, orderId: 1001, rating: 5,
        comment: "Formule simple, portions correctes et commande facile pour une personne.",
        isValidated: true, authorName: "Sophie Martin", createdAt: new Date("2026-09-02T12:15:00Z")
    },
    {
        userId: 2, menuId: 2, orderId: 1002, rating: 5,
        comment: "Très bonne formule végétale, fraîche et suffisamment copieuse.",
        isValidated: true, authorName: "Thomas Bernard", createdAt: new Date("2026-09-05T18:30:00Z")
    },
    {
        userId: 3, menuId: 4, orderId: 1003, rating: 4,
        comment: "Le menu duo fonctionne bien pour un repas familial sans complication.",
        isValidated: true, authorName: "Camille Leroy", createdAt: new Date("2026-09-08T19:10:00Z")
    },
    {
        userId: 4, menuId: 7, orderId: 1004, rating: 5,
        comment: "Très bonne présentation et poisson bien préparé. Prestation soignée.",
        isValidated: true, authorName: "Julien Moreau", createdAt: new Date("2026-09-10T20:05:00Z")
    },
    {
        userId: 5, menuId: 9, orderId: 1005, rating: 5,
        comment: "Nous avons choisi le menu réception pour un petit événement professionnel. Très pratique.",
        isValidated: true, authorName: "Claire Petit", createdAt: new Date("2026-09-12T19:45:00Z")
    }
];

for (const review of reviews) {
    database.comments.updateOne(
        { orderId: review.orderId },
        { $set: { ...review, updatedAt: new Date() } },
        { upsert: true }
    );
}

const statistics = [
    [1,"2026-09",14,236.60,4.7],
    [2,"2026-09",11,196.90,4.8],
    [3,"2026-09",9,107.10,4.4],
    [4,"2026-09",8,279.20,4.3],
    [5,"2026-09",6,317.40,4.6],
    [6,"2026-09",5,324.50,4.5],
    [7,"2026-09",4,199.60,4.9],
    [8,"2026-09",3,137.70,4.7],
    [9,"2026-09",2,139.80,4.8]
];

for (const [menuId, periodIdentifier, orderCount, revenue, averageRating] of statistics) {
    database.menu_statistics.updateOne(
        { menuId, periodIdentifier },
        {
            $set: {
                menuId,
                periodIdentifier,
                orderCount,
                revenue,
                averageRating,
                updatedAt: new Date()
            }
        },
        { upsert: true }
    );
}

print("MongoDB Vite & Gourmand : galeries, avis et statistiques chargés.");
print("Images : " + menuImages.length);
print("Avis validés : " + reviews.length);
print("Statistiques : " + statistics.length);
