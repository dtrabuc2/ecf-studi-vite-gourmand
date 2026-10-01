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

// ----------------------------------------------------------------
// Avis et statistiques alignés sur database/seed.sql :
// commandes terminées 1001 à 1005 du compte user@viteetgourmand.com.
// Son identifiant SQL vaut 3 sur une installation neuve (schema.sql puis
// seed.sql). Sinon, le passer dans la variable d'environnement
// VG_CLIENT_USER_ID (voir README).
// ----------------------------------------------------------------

const clientUserId = Number(process.env.VG_CLIENT_USER_ID || 3);

if (!Number.isInteger(clientUserId) || clientUserId < 1) {
    throw new Error("VG_CLIENT_USER_ID doit être l'identifiant SQL du compte user@viteetgourmand.com.");
}

// [orderId, menuId, titre du menu, total de la commande, note, commentaire, date]
const demoOrders = [
    [1001, 1, "Formule Solo Classique", 16.90, 5,
        "Formule simple, portions correctes et commande facile pour une personne.", "2026-09-02T12:15:00Z"],
    [1002, 2, "Formule Solo Végétale", 17.90, 5,
        "Très bonne formule végétale, fraîche et suffisamment copieuse.", "2026-09-05T18:30:00Z"],
    [1003, 4, "Menu Duo Convivial", 34.90, 4,
        "Le menu duo fonctionne bien pour un repas familial sans complication.", "2026-09-08T19:10:00Z"],
    [1004, 7, "Menu Terroir & Mer", 49.90, 5,
        "Très bonne présentation et poisson bien préparé. Prestation soignée.", "2026-09-10T20:05:00Z"],
    [1005, 9, "Menu Réception Signature", 69.90, 5,
        "Nous avons choisi le menu réception pour un petit événement professionnel. Très pratique.", "2026-09-12T19:45:00Z"]
];

for (const [orderId, menuId, , , rating, comment, date] of demoOrders) {
    database.comments.updateOne(
        { orderId },
        {
            $set: {
                userId: clientUserId,
                menuId,
                orderId,
                rating,
                comment,
                isValidated: true,
                createdAt: new Date(date),
                updatedAt: new Date()
            },
            // Ancien champ du seed, remplacé par le nom du compte SQL.
            $unset: { authorName: "" }
        },
        { upsert: true }
    );
}

// Statistiques "all_time" lues par le tableau de bord : même calcul que
// MenuStatisticsService::aggregateAndStore() (commandes terminées uniquement).
database.menu_statistics.deleteMany({ periodIdentifier: "2026-09" });

const statistics = new Map();
for (const [, menuId, menuTitle, total] of demoOrders) {
    const current = statistics.get(menuId) || { menuTitle, orderCount: 0, revenue: 0 };
    current.orderCount += 1;
    current.revenue = Math.round((current.revenue + total) * 100) / 100;
    statistics.set(menuId, current);
}

for (const [menuId, stat] of statistics) {
    database.menu_statistics.updateOne(
        { menuId, periodIdentifier: "all_time" },
        {
            $set: {
                menuId,
                menuTitle: stat.menuTitle,
                periodStart: null,
                periodEnd: null,
                periodIdentifier: "all_time",
                orderCount: stat.orderCount,
                revenue: stat.revenue,
                updatedAt: new Date()
            }
        },
        { upsert: true }
    );
}

print("MongoDB Vite & Gourmand : galeries, avis et statistiques chargés.");
print("Images : " + menuImages.length);
print("Avis validés (client SQL #" + clientUserId + ") : " + demoOrders.length);
print("Statistiques all_time : " + statistics.size + " menus");
