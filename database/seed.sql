USE `viteetgourmand`;
-- Même jeu de caractères et même collation que les tables (schema.sql).
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Comptes de démonstration de l'ECF.
-- Chaque compte a son propre mot de passe, conforme à la politique
-- (10 caractères min., majuscule, minuscule, chiffre, caractère spécial),
-- stocké en hash bcrypt (coût 12). Identifiants : README, « Comptes de test ».
-- Les téléphones sont en E.164 (+33…), comme ceux enregistrés par l'application.
-- L'upsert se fait par email afin de ne pas écraser les identifiants
-- existants d'autres utilisateurs.
-- ----------------------------------------------------------------

INSERT INTO `users`
    (`email`, `password`, `role`, `first_name`, `last_name`,
     `phone`, `gsm`, `address`, `is_active`)
VALUES
    ('admin@viteetgourmand.com',
     '$2y$12$9IfA44iCxtRAwbf4RjV7duuTrR4X3I0q8EX05cNkI9aPX8D28vhpi',
     'admin', 'Admin', 'Istrator',
     '+33123456789', '+33612345678', '123 Rue de la Paix', 1),
    ('employee@viteetgourmand.com',
     '$2y$12$vZMWibdDGqi4uMI7FmkZZeyrDi5x2zZH0Stfv8MM0rZckAeyII33K',
     'employee', 'Employé', 'Modèle',
     '+33123456789', '+33612345678', '456 Avenue des Champs', 1),
    ('user@viteetgourmand.com',
     '$2y$12$0O7wW0SCX24Ok0ee2sHHcOXFWaKCXK84ebISO7whnOZURMNOsKttq',
     'user', 'Utilisateur', 'Modèle',
     '+33123456789', '+33612345678', '789 Boulevard Saint-Michel', 1)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `role` = VALUES(`role`),
    `first_name` = VALUES(`first_name`),
    `last_name` = VALUES(`last_name`),
    `phone` = VALUES(`phone`),
    `gsm` = VALUES(`gsm`),
    `address` = VALUES(`address`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- ================================================================
-- SEED MARIA DB — Vite & Gourmand
-- Catalogue complet et réexécutable
-- Compatible avec database/schema.sql sur la branche dev.
-- ================================================================

USE `viteetgourmand`;

-- ----------------------------------------------------------------
-- Rafraîchissement non destructif.
-- Ce seed NE SUPPRIME PAS les utilisateurs, commandes, devis,
-- notifications, messages ou historiques existants.
-- Les tables catalogue sont mises à jour par upsert plus bas.
-- ----------------------------------------------------------------

START TRANSACTION;

-- ----------------------------------------------------------------
-- Horaires de référence du site
-- 1 = lundi ... 7 = dimanche
-- ----------------------------------------------------------------

INSERT INTO `opening_hours`
    (`day_of_week`, `is_open`, `opening_time`, `closing_time`, `opening_time_2`, `closing_time_2`)
VALUES
    (1, 0, NULL,         NULL,         NULL,         NULL),
    (2, 1, '11:30:00',   '15:30:00',   '18:00:00', '23:00:00'),
    (3, 1, '11:30:00',   '15:30:00',   '18:00:00', '23:00:00'),
    (4, 1, '12:00:00',   '20:00:00',   NULL,        NULL),
    (5, 1, '11:30:00',   '15:30:00',   '18:00:00', '23:00:00'),
    (6, 1, '11:30:00',   '15:30:00',   '18:00:00', '23:00:00'),
    (7, 1, '12:00:00',   '20:00:00',   NULL,        NULL)
ON DUPLICATE KEY UPDATE
    `is_open` = VALUES(`is_open`),
    `opening_time` = VALUES(`opening_time`),
    `closing_time` = VALUES(`closing_time`),
    `opening_time_2` = VALUES(`opening_time_2`),
    `closing_time_2` = VALUES(`closing_time_2`);

-- ----------------------------------------------------------------
-- Allergènes
-- ----------------------------------------------------------------

INSERT INTO `allergens` (`id`, `name`, `code`) VALUES
    (1, 'Gluten', 'GLU'),
    (2, 'Lait', 'LAIT'),
    (3, 'Œufs', 'OEU'),
    (4, 'Soja', 'SOJA'),
    (5, 'Arachides', 'ARA'),
    (6, 'Fruits à coque', 'FRUITS_COS'),
    (7, 'Poisson', 'POI'),
    (8, 'Crustacés', 'CRU'),
    (9, 'Moutarde', 'MOU'),
    (10, 'Céleri', 'CEL')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `code` = VALUES(`code`);

-- ----------------------------------------------------------------
-- Plats : du plus simple au plus élaboré.
-- Les plats sont réutilisés dans les compositions de menus.
-- ----------------------------------------------------------------

INSERT INTO `dishes`
    (`id`, `name`, `description`, `category`, `dietary_regime`)
VALUES
    (1, 'Salade verte vinaigrette',
     'Salade verte croquante, vinaigrette maison.',
     'starter', 'vegetarian'),

    (2, 'Crudités de saison',
     'Carottes, concombre et tomates avec sauce légère.',
     'starter', 'vegetarian'),

    (3, 'Œuf mayonnaise',
     'Œuf dur, mayonnaise maison et herbes fraîches.',
     'starter', 'vegetarian'),

    (4, 'Tomates mozzarella',
     'Tomates, mozzarella et basilic.',
     'starter', 'vegetarian'),

    (5, 'Velouté de légumes',
     'Velouté de légumes de saison servi chaud.',
     'starter', 'vegan'),

    (6, 'Salade de lentilles',
     'Lentilles, échalote et vinaigrette moutardée.',
     'starter', 'vegan'),

    (7, 'Carottes râpées citronnées',
     'Carottes râpées, citron et herbes.',
     'starter', 'vegan'),

    (8, 'Tarte fine aux légumes',
     'Tarte fine croustillante aux légumes rôtis.',
     'starter', 'vegetarian'),

    (9, 'Steak haché sauce échalote',
     'Steak haché de bœuf avec sauce échalote.',
     'main', 'classic'),

    (10, 'Poulet rôti jus court',
     'Cuisse de poulet rôtie, jus réduit.',
     'main', 'classic'),

    (11, 'Poisson blanc citronné',
     'Filet de poisson blanc, citron et persil.',
     'main', 'classic'),

    (12, 'Lasagnes bolognaises',
     'Lasagnes gratinées à la viande de bœuf.',
     'main', 'classic'),

    (13, 'Boulettes de bœuf tomate',
     'Boulettes de bœuf mijotées dans une sauce tomate.',
     'main', 'classic'),

    (14, 'Saucisse grillée',
     'Saucisse grillée servie avec jus aux herbes.',
     'main', 'classic'),

    (15, 'Curry de légumes',
     'Légumes de saison, pois chiches et sauce coco.',
     'main', 'vegan'),

    (16, 'Dahl de lentilles corail',
     'Lentilles corail, tomate, épices douces et riz.',
     'main', 'vegan'),

    (17, 'Galette végétale',
     'Galette végétale, sauce aux herbes et légumes.',
     'main', 'vegetarian'),

    (18, 'Saumon rôti citron',
     'Pavé de saumon rôti, citron et herbes.',
     'main', 'classic'),

    (19, 'Filet de volaille farci',
     'Filet de volaille farci aux champignons, jus corsé.',
     'main', 'classic'),

    (20, 'Bœuf mijoté au vin',
     'Bœuf mijoté longuement, jus réduit et légumes.',
     'main', 'classic'),

    (21, 'Frites maison',
     'Pommes de terre frites sur place.',
     'main', 'vegan'),

    (22, 'Purée de pommes de terre',
     'Purée onctueuse au lait et beurre.',
     'main', 'vegetarian'),

    (23, 'Haricots verts persillés',
     'Haricots verts, ail et persil.',
     'main', 'vegan'),

    (24, 'Riz basmati',
     'Riz basmati parfumé.',
     'main', 'vegan'),

    (25, 'Mousse au chocolat',
     'Mousse au chocolat noir.',
     'dessert', 'vegetarian'),

    (26, 'Crème caramel',
     'Crème caramel à la vanille.',
     'dessert', 'vegetarian'),

    (27, 'Tarte aux pommes',
     'Tarte fine aux pommes et cannelle.',
     'dessert', 'vegetarian'),

    (28, 'Fromage blanc coulis fruits rouges',
     'Fromage blanc et coulis de fruits rouges.',
     'dessert', 'vegetarian'),

    (29, 'Compote pomme poire',
     'Compote fruitée sans sucres ajoutés.',
     'dessert', 'vegan'),

    (30, 'Salade de fruits frais',
     'Fruits frais de saison.',
     'dessert', 'vegan'),

    (31, 'Fondant chocolat',
     'Gâteau chocolat cœur fondant.',
     'dessert', 'vegetarian'),

    (32, 'Panna cotta vanille',
     'Panna cotta vanillée, coulis de fruits rouges.',
     'dessert', 'vegetarian')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `category` = VALUES(`category`),
    `dietary_regime` = VALUES(`dietary_regime`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

-- ----------------------------------------------------------------
-- Associations catalogue : uniquement les liens des données seedées.
-- Les commandes et utilisateurs ne sont pas touchés.
-- ----------------------------------------------------------------

DELETE FROM `dish_allergens`
WHERE `dish_id` BETWEEN 1 AND 32;

DELETE FROM `menu_dishes`
WHERE `menu_id` BETWEEN 1 AND 9;

-- ----------------------------------------------------------------
-- Allergènes associés aux plats
-- ----------------------------------------------------------------

INSERT INTO `dish_allergens` (`dish_id`, `allergen_id`) VALUES
    (3, 3),
    (3, 9),
    (4, 2),
    (8, 1),
    (8, 2),
    (9, 9),
    (11, 7),
    (12, 1),
    (12, 2),
    (12, 3),
    (12, 10),
    (13, 1),
    (14, 1),
    (17, 2),
    (17, 3),
    (18, 7),
    (18, 2),
    (19, 2),
    (20, 9),
    (20, 10),
    (22, 2),
    (25, 2),
    (25, 3),
    (26, 2),
    (26, 3),
    (27, 1),
    (27, 2),
    (27, 3),
    (28, 2),
    (31, 1),
    (31, 2),
    (31, 3),
    (32, 2);

-- ----------------------------------------------------------------
-- Menus : au moins 3 formules compatibles avec 1 personne.
-- Pour les minimums > 1, une alternative est explicitement fournie.
-- ----------------------------------------------------------------

INSERT INTO `menus`
    (`id`, `title`, `description`, `theme`, `dietary_regime`,
     `min_people`, `base_price`, `conditions`, `available_stock`)
VALUES
    (1,
     'Formule Solo Classique',
     'Entrée, plat, accompagnement et dessert. Pensée pour une personne avec une formule simple et accessible.',
     'Classique',
     'classic',
     1,
     16.90,
     'Minimum 1 personne. Commande directe possible selon le stock disponible.',
     40),

    (2,
     'Formule Solo Végétale',
     'Entrée végétale, plat végétalien, accompagnement et dessert fruité.',
     'Classique',
     'vegan',
     1,
     17.90,
     'Minimum 1 personne. Cuisine 100 % végétale. Commande directe possible selon le stock disponible.',
     30),

    (3,
     'Formule Enfant Gourmande',
     'Plat enfant, accompagnement, dessert et boisson non alcoolisée.',
     'Classique',
     'classic',
     1,
     11.90,
     'Minimum 1 personne. Formule pensée pour un repas enfant.',
     35),

    (4,
     'Menu Duo Convivial',
     'Entrée au choix, plat généreux et dessert pour deux convives.',
     'Classique',
     'classic',
     2,
     34.90,
     'Minimum 2 personnes. Pour 1 personne, choisir la Formule Solo Classique.',
     25),

    (5,
     'Menu Trio Végétal',
     'Entrée, plat végétarien, accompagnement et dessert pour trois convives.',
     'Pâques',
     'vegetarian',
     3,
     52.90,
     'Minimum 3 personnes. Pour 1 personne, choisir la Formule Solo Végétale.',
     20),

    (6,
     'Menu Familial Tradition',
     'Entrée, plats traditionnels, deux accompagnements et dessert familial.',
     'Noël',
     'classic',
     3,
     64.90,
     'Minimum 3 personnes. Pour moins de 3 personnes, choisir une formule Solo ou Duo.',
     18),

    (7,
     'Menu Terroir & Mer',
     'Entrée raffinée, poisson ou viande, accompagnement travaillé et dessert.',
     'Pâques',
     'classic',
     2,
     49.90,
     'Minimum 2 personnes. Pour 1 personne, choisir la Formule Solo Classique.',
     15),

    (8,
     'Menu Végétal Élégance',
     'Entrée gastronomique, plat végétalien travaillé, garniture de saison et dessert.',
     'Évènement',
     'vegan',
     2,
     45.90,
     'Minimum 2 personnes. Pour 1 personne, choisir la Formule Solo Végétale.',
     15),

    (9,
     'Menu Réception Signature',
     'Entrée, deux choix de plats premium, deux garnitures et dessert signature.',
     'Évènement',
     'classic',
     6,
     69.90,
     'Minimum 6 personnes. Pour moins de 6 personnes, une formule Solo, Duo ou Trio est recommandée.',
     12)
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `theme` = VALUES(`theme`),
    `dietary_regime` = VALUES(`dietary_regime`),
    `min_people` = VALUES(`min_people`),
    `base_price` = VALUES(`base_price`),
    `conditions` = VALUES(`conditions`),
    `available_stock` = VALUES(`available_stock`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

-- ----------------------------------------------------------------
-- Composition des menus
-- position : ordre de présentation dans la fiche menu.
-- ----------------------------------------------------------------

INSERT INTO `menu_dishes` (`menu_id`, `dish_id`, `position`) VALUES
    (1, 1, 1),
    (1, 9, 2),
    (1, 21, 3),
    (1, 26, 4),

    (2, 7, 1),
    (2, 16, 2),
    (2, 24, 3),
    (2, 30, 4),

    (3, 3, 1),
    (3, 14, 2),
    (3, 21, 3),
    (3, 29, 4),

    (4, 2, 1),
    (4, 10, 2),
    (4, 22, 3),
    (4, 27, 4),

    (5, 6, 1),
    (5, 17, 2),
    (5, 23, 3),
    (5, 32, 4),

    (6, 4, 1),
    (6, 12, 2),
    (6, 21, 3),
    (6, 23, 4),
    (6, 27, 5),

    (7, 8, 1),
    (7, 18, 2),
    (7, 22, 3),
    (7, 31, 4),

    (8, 5, 1),
    (8, 15, 2),
    (8, 24, 3),
    (8, 30, 4),

    (9, 4, 1),
    (9, 19, 2),
    (9, 20, 3),
    (9, 22, 4),
    (9, 31, 5);

-- ----------------------------------------------------------------
-- Commandes de démonstration terminées (identifiants fixes 1001 à 1005)
-- passées par le compte client user@viteetgourmand.com, retrait sur place.
-- Elles portent les avis et les statistiques de database/mongodb-init.js.
-- INSERT IGNORE : une commande existante portant le même identifiant
-- ou la même référence n'est jamais écrasée.
-- Prix = prix du menu pour son minimum de personnes (pas de remise).
-- ----------------------------------------------------------------

SET @client_id := (SELECT `id` FROM `users` WHERE `email` = 'user@viteetgourmand.com');
SET @employee_id := (SELECT `id` FROM `users` WHERE `email` = 'employee@viteetgourmand.com');

INSERT IGNORE INTO `orders`
    (`id`, `order_number`, `user_id`, `menu_id`, `number_of_people`, `order_date`,
     `delivery_date`, `delivery_time`, `delivery_address`, `delivery_city`, `delivery_postal_code`,
     `delivery_distance_km`, `delivery_cost`, `service_type`, `payment_method`, `contact_phone`,
     `menu_price`, `discount_rate`, `total_price`, `status`, `equipment_loaned`, `created_at`)
SELECT d.id, d.order_number, @client_id, d.menu_id, d.people, d.order_date,
       d.delivery_date, d.delivery_time, '', '', '',
       NULL, 0.00, 'pickup', 'cash_on_site', '+33612345678',
       d.price, 0.00, d.price, 'completed', 0, d.order_date
FROM (
    SELECT 1001 AS id, 'VG-DEMO-001001' AS order_number, 1 AS menu_id, 1 AS people, 16.90 AS price,
           '2026-08-30 10:00:00' AS order_date, '2026-09-02' AS delivery_date, '12:00:00' AS delivery_time
    UNION ALL SELECT 1002, 'VG-DEMO-001002', 2, 1, 17.90, '2026-09-01 09:30:00', '2026-09-05', '19:00:00'
    UNION ALL SELECT 1003, 'VG-DEMO-001003', 4, 2, 34.90, '2026-09-03 14:00:00', '2026-09-08', '19:00:00'
    UNION ALL SELECT 1004, 'VG-DEMO-001004', 7, 2, 49.90, '2026-09-05 11:15:00', '2026-09-10', '20:00:00'
    UNION ALL SELECT 1005, 'VG-DEMO-001005', 9, 6, 69.90, '2026-09-07 16:45:00', '2026-09-12', '19:30:00'
) AS d
WHERE @client_id IS NOT NULL;

-- Historique : création puis fin de prestation (ajouté une seule fois).
INSERT INTO `order_status_history` (`order_id`, `status`, `changed_by`, `changed_at`, `notes`)
SELECT h.order_id, h.status, IF(h.status = 'pending', @client_id, @employee_id), h.changed_at, h.notes
FROM (
    SELECT 1001 AS order_id, 'pending' AS status, '2026-08-30 10:00:00' AS changed_at, 'Commande créée (démonstration)' AS notes
    UNION ALL SELECT 1001, 'completed', '2026-09-02 15:00:00', 'Prestation terminée (démonstration)'
    UNION ALL SELECT 1002, 'pending', '2026-09-01 09:30:00', 'Commande créée (démonstration)'
    UNION ALL SELECT 1002, 'completed', '2026-09-05 22:00:00', 'Prestation terminée (démonstration)'
    UNION ALL SELECT 1003, 'pending', '2026-09-03 14:00:00', 'Commande créée (démonstration)'
    UNION ALL SELECT 1003, 'completed', '2026-09-08 22:00:00', 'Prestation terminée (démonstration)'
    UNION ALL SELECT 1004, 'pending', '2026-09-05 11:15:00', 'Commande créée (démonstration)'
    UNION ALL SELECT 1004, 'completed', '2026-09-10 22:30:00', 'Prestation terminée (démonstration)'
    UNION ALL SELECT 1005, 'pending', '2026-09-07 16:45:00', 'Commande créée (démonstration)'
    UNION ALL SELECT 1005, 'completed', '2026-09-12 23:00:00', 'Prestation terminée (démonstration)'
) AS h
INNER JOIN `orders` o ON o.`id` = h.order_id AND o.`order_number` LIKE 'VG-DEMO-%'
WHERE NOT EXISTS (
    SELECT 1 FROM `order_status_history` x
    WHERE x.`order_id` = h.order_id AND x.`status` = h.status
);

COMMIT;

-- Les comptes de démonstration sont restaurés par ce seed. Les autres comptes
-- peuvent être créés normalement depuis l'application.
-- Identifiant du client de démonstration, à passer à mongodb-init.js
-- s'il ne vaut pas 3 (voir README) :
--   SELECT id FROM users WHERE email = 'user@viteetgourmand.com';
