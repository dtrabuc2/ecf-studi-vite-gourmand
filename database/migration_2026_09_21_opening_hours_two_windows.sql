-- Migration MariaDB : ajoute une seconde plage horaire quotidienne.
-- À exécuter sur une base existante avant database/seed.sql.

USE `viteetgourmand`;

SET @has_opening_time_2 := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'opening_hours'
      AND COLUMN_NAME = 'opening_time_2'
);

SET @sql := IF(
    @has_opening_time_2 = 0,
    'ALTER TABLE opening_hours ADD COLUMN opening_time_2 TIME NULL AFTER closing_time',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_closing_time_2 := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'opening_hours'
      AND COLUMN_NAME = 'closing_time_2'
);

SET @sql := IF(
    @has_closing_time_2 = 0,
    'ALTER TABLE opening_hours ADD COLUMN closing_time_2 TIME NULL AFTER opening_time_2',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Puis exécuter database/seed.sql pour appliquer les horaires de référence.
