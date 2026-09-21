-- ================================================================
-- SEED MARIA DB — Vite & Gourmand
-- Ce fichier suppose que database/schema.sql a déjà créé les tables.
-- Il contient les données initiales du catalogue et des horaires.
-- ================================================================

USE `viteetgourmand`;

-- ================================================================
-- Horaires d'ouverture de référence
-- ================================================================

INSERT INTO `opening_hours` (`day_of_week`, `is_open`, `opening_time`, `closing_time`) VALUES
    (1, 1, '09:00:00', '18:00:00'),
    (2, 1, '09:00:00', '18:00:00'),
    (3, 1, '09:00:00', '18:00:00'),
    (4, 1, '09:00:00', '18:00:00'),
    (5, 1, '09:00:00', '19:00:00'),
    (6, 1, '10:00:00', '16:00:00'),
    (7, 0, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `is_open` = VALUES(`is_open`),
    `opening_time` = VALUES(`opening_time`),
    `closing_time` = VALUES(`closing_time`);

INSERT INTO `menus`
    (`id`, `title`, `description`, `theme`, `dietary_regime`, `min_people`, `base_price`, `conditions`, `available_stock`)
VALUES
    (1, 'Menu Prestige Signature', 'Formule traiteur haut de gamme pour mariages, réceptions privées et événements professionnels.', 'Gastronomique', 'classic', 12, 74.00, 'Réservation recommandée 7 jours à l’avance. Livraison, dressage et service selon option.', 20),
    (2, 'Menu Végétal Élégance', 'Menu gastronomique végétarien inspiré de la cuisine végétale contemporaine.', 'Végétal', 'vegetarian', 8, 46.00, 'Réservation recommandée 5 jours à l’avance. Adaptation vegan possible selon composition.', 20),
    (3, 'Menu Cocktail Signature', 'Sélection de bouchées et pièces cocktail pour réceptions et événements professionnels.', 'Cocktail', 'classic', 15, 52.00, 'Réservation recommandée 7 jours à l’avance. Service et verrerie selon option.', 20),
    (4, 'Menu Enfant Gourmand', 'Formule enfant avec plat, accompagnement, boisson et dessert.', 'Famille', 'classic', 5, 10.90, 'Réservé aux enfants. Composition adaptée aux goûts des plus jeunes.', 30),
    (5, 'Menu Méditerranéen Vegan', 'Formule 100 % végétale inspirée des saveurs méditerranéennes.', 'Méditerranéen', 'vegan', 6, 42.00, 'Réservation recommandée 4 jours à l’avance. Préparation entièrement végétale.', 15),
    (6, 'Menu Terre & Mer', 'Formule festive associant produits de la mer et pièce de viande.', 'Tradition', 'classic', 8, 55.00, 'Réservation recommandée 5 jours à l’avance. Selon arrivage et disponibilité.', 12)
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





-- Les employés sont créés et gérés depuis l’espace administrateur.
-- Les clients peuvent créer leur compte via /register.
