
-- ================================================================
-- IMPORT PHPMYADMIN
-- Ce fichier est l’unique script MariaDB de référence du projet.
-- Compatible MariaDB/MySQL utilisés par phpMyAdmin.
-- Les images, commentaires et statistiques MongoDB sont initialisés
-- séparément par mongodb-init-viteetgourmand.js.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `viteetgourmand`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `viteetgourmand`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Résultat de password_hash(), jamais le mot de passe en clair',
    `role` ENUM('user', 'employee', 'admin') NOT NULL DEFAULT 'user',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `gsm` VARCHAR(20) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `reset_token_hash` CHAR(64) NULL COMMENT 'SHA-256 du jeton de réinitialisation',
    `reset_token_expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_active` (`role`, `is_active`),
    KEY `idx_users_reset_token` (`reset_token_hash`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `theme` VARCHAR(80) NOT NULL COMMENT 'Exemples : Noël, Pâques, classique, événement',
    `dietary_regime` ENUM('classic', 'vegetarian', 'vegan', 'other') NOT NULL DEFAULT 'classic',
    `min_people` SMALLINT UNSIGNED NOT NULL,
    `base_price` DECIMAL(10,2) NOT NULL COMMENT 'Prix TTC pour le nombre minimal de personnes',
    `conditions` TEXT NOT NULL,
    `available_stock` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de commandes encore possibles',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_menus_catalogue` (`is_active`, `available_stock`),
    KEY `idx_menus_theme` (`theme`),
    KEY `idx_menus_regime` (`dietary_regime`),
    KEY `idx_menus_price` (`base_price`),
    KEY `idx_menus_min_people` (`min_people`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `dishes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dishes_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `menu_dishes` (
    `menu_id` INT UNSIGNED NOT NULL,
    `dish_id` INT UNSIGNED NOT NULL,
    `category` ENUM('starter', 'main', 'dessert') NOT NULL,
    `position` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`menu_id`, `dish_id`),
    KEY `idx_menu_dishes_category` (`menu_id`, `category`),
    CONSTRAINT `fk_menu_dishes_menu`
        FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_menu_dishes_dish`
        FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `allergens` (
    `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_allergens_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `dish_allergens` (
    `dish_id` INT UNSIGNED NOT NULL,
    `allergen_id` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`dish_id`, `allergen_id`),
    CONSTRAINT `fk_dish_allergens_dish`
        FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dish_allergens_allergen`
        FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ================================================================
-- COMMANDES ET SUIVI (MariaDB)
-- Les montants sont mémorisés : ils restent exacts si un menu évolue.
-- ================================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(30) NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `menu_id` INT UNSIGNED NOT NULL,
    `number_of_people` INT UNSIGNED NOT NULL,
    `order_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `delivery_date` DATE NOT NULL,
    `delivery_time` TIME NOT NULL,
    `delivery_address` VARCHAR(255) NOT NULL,
    `delivery_city` VARCHAR(100) NULL,
    `delivery_postal_code` VARCHAR(10) NULL,
    `delivery_distance_km` DECIMAL(7,2) NULL,
    `delivery_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `customization` TEXT NULL,
    `service_type` ENUM('delivery','on_site','pickup') NOT NULL DEFAULT 'delivery',
    `payment_method` ENUM('cash_on_site') NOT NULL DEFAULT 'cash_on_site',
    `delivery_instructions` TEXT NULL,
    `contact_phone` VARCHAR(20) NOT NULL,
    `menu_price` DECIMAL(10,2) NOT NULL,
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Pourcentage, par exemple 10.00',
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `equipment_loaned` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 si du matériel a été prêté pour cette commande',
    `cancellation_reason` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_number` (`order_number`),
    KEY `idx_orders_user_date` (`user_id`, `delivery_date`),
    KEY `idx_orders_status_date` (`status`, `delivery_date`),
    KEY `idx_orders_menu` (`menu_id`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_orders_menu`
        FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return', 'completed', 'cancelled') NOT NULL,
    `changed_by` INT UNSIGNED NULL COMMENT 'Utilisateur ayant effectué le changement',
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_history_order_date` (`order_id`, `changed_at`),
    CONSTRAINT `fk_history_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_user`
        FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `opening_hours` (
    `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '1 = lundi, 7 = dimanche',
    `is_open` TINYINT(1) NOT NULL DEFAULT 1,
    `opening_time` TIME NULL,
    `closing_time` TIME NULL,
    PRIMARY KEY (`day_of_week`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'processed', 'closed', 'trash') NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_status_date` (`status`, `created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `email_outbox` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mailbox` VARCHAR(100) NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` MEDIUMTEXT NOT NULL,
    `status` ENUM('draft', 'queued', 'sent', 'failed', 'trash') NOT NULL DEFAULT 'queued',
    `error_message` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_outbox_mailbox_date` (`mailbox`, `created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `quote_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `email` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NULL,
    `last_name` VARCHAR(100) NULL,
    `phone` VARCHAR(30) NULL,
    `company` VARCHAR(150) NULL,
    `event_date` DATE NOT NULL,
    `number_of_people` SMALLINT UNSIGNED NOT NULL,
    `service_type` ENUM('pickup', 'delivery', 'on_site') NOT NULL,
    `event_location` VARCHAR(255) NULL,
    `postal_code` VARCHAR(10) NULL,
    `request_details` TEXT NULL,
    `status` ENUM('new', 'in_review', 'quoted', 'accepted', 'declined', 'closed') NOT NULL DEFAULT 'new',
    `employee_reply` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_quote_status_date` (`status`, `event_date`),
    KEY `idx_quote_email` (`email`),
    CONSTRAINT `fk_quote_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `user_notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NULL,
    `quote_request_id` INT UNSIGNED NULL,
    `type` VARCHAR(40) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_read_date` (`user_id`, `is_read`, `created_at`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notifications_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_notifications_quote`
        FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

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

INSERT INTO `dishes` (`id`, `name`, `description`) VALUES
    (10, 'Foie gras maison, chutney de figues', 'Foie gras de canard et chutney de figues.'),
    (11, 'Saint-Jacques rôties, agrumes et herbes', 'Noix de Saint-Jacques snackées, agrumes et herbes fraîches.'),
    (12, 'Filet de bœuf sauce truffée', 'Filet de bœuf rôti, jus réduit et truffe.'),
    (13, 'Homard rôti, légumes de saison', 'Homard rôti accompagné de légumes frais.'),
    (14, 'Sphère chocolat noir, cœur praliné', 'Dessert au chocolat noir et praliné.'),
    (15, 'Paris-Brest pistache', 'Pâte à choux et crème légère à la pistache.'),
    (16, 'Millefeuille vanille bourbon', 'Feuilletage croustillant et crème vanille.'),
    (17, 'Gaspacho de tomates anciennes', 'Tomates anciennes, huile d’olive et herbes.'),
    (18, 'Tartare avocat, mangue et citron vert', 'Avocat, mangue, citron vert et herbes fraîches.'),
    (19, 'Risotto crémeux aux cèpes', 'Risotto aux cèpes, parmesan et persil.'),
    (20, 'Tarte fine aux légumes du soleil', 'Courgette, tomate, poivron et herbes.'),
    (21, 'Pavlova aux fruits exotiques', 'Meringue, crème végétale et fruits exotiques.'),
    (22, 'Moelleux chocolat et fruits rouges', 'Gâteau chocolat fondant accompagné de fruits rouges.'),
    (23, 'Mini burgers gourmets au foie gras', 'Mini burgers briochés, foie gras et condiment fruité.'),
    (24, 'Verrine de saumon gravlax et aneth', 'Saumon mariné, crème citronnée et aneth.'),
    (25, 'Brochette de poulet yakitori', 'Poulet mariné et grillé, sauce soja douce.'),
    (26, 'Gambas croustillantes, sauce aigre-douce', 'Gambas en tempura et sauce aigre-douce.'),
    (27, 'Assortiment de macarons', 'Sélection de macarons aux parfums variés.'),
    (28, 'Mini éclairs chocolat, café et vanille', 'Petits éclairs garnis de crèmes parfumées.'),
    (29, 'Roulé jambon fromage', 'Roulé moelleux au jambon et fromage.'),
    (30, 'Velouté doux de potimarron', 'Potimarron et crème pour une soupe douce.'),
    (31, 'Taboulé coloré aux herbes', 'Semoule, légumes croquants et herbes fraîches.'),
    (32, 'Nuggets de poulet maison', 'Bouchées de poulet panées maison.'),
    (33, 'Pâtes bolognaise', 'Pâtes avec sauce tomate et viande mijotée.'),
    (34, 'Poisson pané et purée maison', 'Poisson croustillant et purée de pommes de terre.'),
    (35, 'Brownie chocolat', 'Brownie moelleux au chocolat noir.'),
    (36, 'Salade de fruits frais', 'Assortiment de fruits de saison.'),
    (37, 'Couscous de légumes rôtis', 'Semoule, pois chiches et légumes rôtis.'),
    (38, 'Falafels aux herbes, sauce tahini', 'Falafels maison et sauce au sésame.'),
    (39, 'Tofu grillé au gingembre', 'Tofu mariné, gingembre et citron vert.'),
    (40, 'Ratatouille provençale', 'Légumes du soleil mijotés à l’huile d’olive.'),
    (41, 'Salade niçoise végétale', 'Tomates, haricots verts, pommes de terre, olives et pois chiches.'),
    (42, 'Tartelette citron meringuée', 'Crème de citron et meringue légère.'),
    (43, 'Compote pomme-poire maison', 'Compote de fruits de saison.'),
    (44, 'Pommes rôties à la cannelle', 'Pommes rôties et parfumées à la cannelle.'),
    (45, 'Moules marinières', 'Moules cuisinées dans une sauce marinière.'),
    (46, 'Paella aux fruits de mer', 'Riz safrané, fruits de mer et légumes.'),
    (47, 'Côte de bœuf grillée', 'Pièce de bœuf grillée, jus réduit et garniture.'),
    (48, 'Quinoa, tofu fumé et légumes croquants', 'Quinoa, tofu fumé et légumes frais.'),
    (50, 'Gratin de légumes au lait de coco', 'Légumes rôtis et sauce douce au lait de coco.'),
    (51, 'Penne crémeuses aux champignons', 'Penne, champignons et crème végétale.'),
    (52, 'Buddha bowl quinoa et légumes rôtis', 'Quinoa, légumes rôtis, pois chiches et sauce citronnée.'),
    (53, 'Burger végétal aux champignons', 'Pain brioché, galette végétale, champignons et salade.'),
    (54, 'Tiramisu revisité au café', 'Dessert au café et crème mascarpone.'),
    (55, 'Crème chocolat et noisette', 'Crème chocolatée avec éclats de noisette.'),
    (56, 'Panna cotta fruits rouges', 'Panna cotta vanille et coulis de fruits rouges.'),
    (57, 'Crêpe fine au sucre', 'Crêpe légère servie avec sucre.'),
    (58, 'Salade de fruits exotiques', 'Mangue, ananas, kiwi et fruits de saison.'),
    (59, 'Jus de pomme artisanal', 'Jus de pomme servi frais.'),
    (60, 'Citronnade maison', 'Citronnade fraîchement préparée.'),
    (61, 'Thé glacé pêche', 'Thé glacé parfum pêche.'),
    (62, 'Café expresso', 'Café expresso.'),
    (63, 'Armagnac XO', 'Armagnac de caractère servi en digestif.'),
    (64, 'Limoncello', 'Liqueur italienne de citron servie fraîche.')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `menu_dishes` (`menu_id`, `dish_id`, `category`, `position`) VALUES
    (1,10,'starter',1),(1,11,'starter',2),(1,12,'main',1),(1,13,'main',2),(1,14,'dessert',1),(1,15,'dessert',2),(1,16,'dessert',3),
    (2,17,'starter',1),(2,18,'starter',2),(2,19,'main',1),(2,20,'main',2),(2,21,'dessert',1),(2,22,'dessert',2),
    (3,23,'starter',1),(3,24,'starter',2),(3,25,'main',1),(3,26,'main',2),(3,27,'dessert',1),(3,28,'dessert',2),
    (4,29,'starter',1),(4,30,'starter',2),(4,31,'starter',3),(4,32,'main',1),(4,33,'main',2),(4,34,'main',3),(4,35,'dessert',1),(4,36,'dessert',2),
    (5,37,'starter',1),(5,38,'starter',2),(5,39,'main',1),(5,40,'main',2),(5,41,'main',3),(5,42,'dessert',1),(5,43,'dessert',2),(5,44,'dessert',3),
    (6,24,'starter',1),(6,45,'main',1),(6,46,'main',2),(6,47,'main',3),(6,54,'dessert',1),(6,56,'dessert',2)
ON DUPLICATE KEY UPDATE
    `category` = VALUES(`category`),
    `position` = VALUES(`position`);

INSERT INTO `allergens` (`id`, `name`) VALUES
    (1,'Gluten'),
    (2,'Lait'),
    (3,'Œufs'),
    (4,'Crustacés'),
    (5,'Fruits à coque'),(6,'Soja'),(7,'Sésame'),(8,'Moutarde')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `dish_allergens` (`dish_id`, `allergen_id`) VALUES
    (10,1),(10,2),(11,4),(12,2),(13,2),(14,1),(14,2),(14,3),(15,1),(15,2),(15,3),
    (16,1),(16,2),(16,3),(18,7),(19,2),(22,2),(22,3),(23,1),(23,2),(24,4),
    (25,6),(26,4),(27,3),(28,1),(28,2),(28,3),(29,1),(29,2),(30,2),(31,1),
    (32,1),(32,3),(33,1),(33,2),(34,1),(34,3),(38,7),(39,6),(42,3),(45,4),(46,4),
    (47,2),(51,2),(52,7),(53,1),(54,2),(54,3),(55,2),(55,5),(56,2)
ON DUPLICATE KEY UPDATE `allergen_id` = VALUES(`allergen_id`);

-- Les employés sont créés et gérés depuis l’espace administrateur.
-- Les clients peuvent créer leur compte via /register.
