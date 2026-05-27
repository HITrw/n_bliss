-- Migration: add waiter support to orders and make table_number nullable
-- Run once on the live database.

ALTER TABLE `orders` ADD COLUMN `waiter_id` INT NULL AFTER `employee_id`;
ALTER TABLE `orders` ADD COLUMN `waiter_name` VARCHAR(100) NULL AFTER `waiter_id`;
ALTER TABLE `orders` MODIFY COLUMN `table_number` VARCHAR(10) NULL DEFAULT NULL;

-- Drop FK on cart.table_number — cart now uses waiter_id as session key,
-- not a real table number, so the tables reference constraint must be removed.
ALTER TABLE `cart` DROP FOREIGN KEY `cart_ibfk_1`;
