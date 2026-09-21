-- ================================================================
-- SCHÉMA MARIA DB — Vite & Gourmand
-- Structure relationnelle complète utilisée par le backend.
-- Importer ensuite database/seed.sql.
-- MongoDB est initialisée séparément par database/mongodb-init.js.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `viteetgourmand`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `viteetgourmand`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
    `user_notifications`,
    `email_outbox`,
    `contact_messages`,
    `quote_requests`,
    `order_status_history`,
    `orders`,
    `menu_dishes`,
    `dish_allergens`,
    `dishes`,
    `allergens`,
    `opening_hours`,
    `menus`,
    `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user','employee','admin') NOT NULL DEFAULT 'user',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `gsm` VARCHAR(20) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `reset_token_hash` CHAR(64) NULL,
    `reset_token_expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_active` (`role`,`is_active`),
    KEY `idx_users_reset_token` (`reset_token_hash`)
) ENGINE=InnoDB;

CREATE TABLE `menus` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `theme` VARCHAR(80) NOT NULL,
    `dietary_regime` ENUM('classic','vegetarian','vegan','other') NOT NULL DEFAULT 'classic',
    `min_people` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `base_price` DECIMAL(10,2) NOT NULL,
    `conditions` TEXT NOT NULL,
    `available_stock` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_menus_catalogue` (`is_active`,`available_stock`),
    KEY `idx_menus_theme` (`theme`),
    KEY `idx_menus_regime` (`dietary_regime`),
    KEY `idx_menus_price` (`base_price`),
    KEY `idx_menus_min_people` (`min_people`)
) ENGINE=InnoDB;

CREATE TABLE `dishes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(180) NOT NULL,
    `description` TEXT NOT NULL,
    `category` ENUM('starter','main','dessert') NOT NULL,
    `dietary_regime` ENUM('classic','vegetarian','vegan','other') NOT NULL DEFAULT 'classic',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dishes_category` (`category`,`is_active`),
    KEY `idx_dishes_regime` (`dietary_regime`,`is_active`)
) ENGINE=InnoDB;

CREATE TABLE `allergens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_allergens_code` (`code`),
    UNIQUE KEY `uq_allergens_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE `menu_dishes` (
    `menu_id` INT UNSIGNED NOT NULL,
    `dish_id` INT UNSIGNED NOT NULL,
    `position` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`menu_id`,`dish_id`),
    KEY `idx_menu_dishes_order` (`menu_id`,`position`),
    CONSTRAINT `fk_menu_dishes_menu`
        FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_menu_dishes_dish`
        FOREIGN KEY (`dish_id`) REFERENCES `dishes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `dish_allergens` (
    `dish_id` INT UNSIGNED NOT NULL,
    `allergen_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`dish_id`,`allergen_id`),
    CONSTRAINT `fk_dish_allergens_dish`
        FOREIGN KEY (`dish_id`) REFERENCES `dishes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dish_allergens_allergen`
        FOREIGN KEY (`allergen_id`) REFERENCES `allergens`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `orders` (
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
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending','accepted','preparing','delivering','delivered','awaiting_return','completed','cancelled') NOT NULL DEFAULT 'pending',
    `equipment_loaned` TINYINT(1) NOT NULL DEFAULT 0,
    `cancellation_reason` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_number` (`order_number`),
    KEY `idx_orders_user_date` (`user_id`,`delivery_date`),
    KEY `idx_orders_status_date` (`status`,`delivery_date`),
    KEY `idx_orders_menu` (`menu_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_orders_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `order_status_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending','accepted','preparing','delivering','delivered','awaiting_return','completed','cancelled') NOT NULL,
    `changed_by` INT UNSIGNED NULL,
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_history_order_date` (`order_id`,`changed_at`),
    CONSTRAINT `fk_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `opening_hours` (
    `day_of_week` TINYINT UNSIGNED NOT NULL,
    `is_open` TINYINT(1) NOT NULL DEFAULT 1,
    `opening_time` TIME NULL,
    `closing_time` TIME NULL,
    `opening_time_2` TIME NULL,
    `closing_time_2` TIME NULL,
    PRIMARY KEY (`day_of_week`)
) ENGINE=InnoDB;

CREATE TABLE `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new','processed','closed','trash') NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_status_date` (`status`,`created_at`)
) ENGINE=InnoDB;

CREATE TABLE `email_outbox` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mailbox` VARCHAR(100) NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` MEDIUMTEXT NOT NULL,
    `status` ENUM('draft','queued','sent','failed','trash') NOT NULL DEFAULT 'queued',
    `error_message` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_outbox_mailbox_date` (`mailbox`,`created_at`)
) ENGINE=InnoDB;

CREATE TABLE `quote_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `email` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NULL,
    `last_name` VARCHAR(100) NULL,
    `phone` VARCHAR(30) NULL,
    `company` VARCHAR(150) NULL,
    `event_date` DATE NOT NULL,
    `number_of_people` SMALLINT UNSIGNED NOT NULL,
    `service_type` ENUM('pickup','delivery','on_site') NOT NULL,
    `event_location` VARCHAR(255) NULL,
    `postal_code` VARCHAR(10) NULL,
    `request_details` TEXT NULL,
    `status` ENUM('new','in_review','quoted','accepted','declined','closed') NOT NULL DEFAULT 'new',
    `employee_reply` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_quote_status_date` (`status`,`event_date`),
    KEY `idx_quote_email` (`email`),
    CONSTRAINT `fk_quote_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `user_notifications` (
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
    KEY `idx_notifications_user_read_date` (`user_id`,`is_read`,`created_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notifications_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_notifications_quote` FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;