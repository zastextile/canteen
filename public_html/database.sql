-- ============================================================
-- ZAS CANTEEN MANAGEMENT SYSTEM
-- DB:   u630220206_canteen
-- User: u630220206_canteen
-- Pass: Canteen@721
-- Import in phpMyAdmin — drop all tables first if re-importing
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = "+05:00";

USE `u630220206_canteen`;

-- Drop all tables first (safe re-import)
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `consumption`;
DROP TABLE IF EXISTS `inv_allocations`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `purchases`;
DROP TABLE IF EXISTS `purchase_items`;
DROP TABLE IF EXISTS `wallet_topups`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `vendors`;
DROP TABLE IF EXISTS `kitchens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

-- ─── USERS ───────────────────────────────────────────────
CREATE TABLE `users` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` VARCHAR(20) DEFAULT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `username`    VARCHAR(50) NOT NULL,
  `password`    VARCHAR(255) NOT NULL,
  `role`        ENUM('admin','accounts','chef','sales','customer') NOT NULL DEFAULT 'customer',
  `kitchen_id`  INT(11) DEFAULT NULL,
  `department`  VARCHAR(100) DEFAULT NULL,
  `mobile`      VARCHAR(20) DEFAULT NULL,
  `wallet`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── KITCHENS ────────────────────────────────────────────
CREATE TABLE `kitchens` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `type`       ENUM('kitchen','sales') NOT NULL DEFAULT 'kitchen',
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── VENDORS ─────────────────────────────────────────────
CREATE TABLE `vendors` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `mobile`     VARCHAR(20) DEFAULT NULL,
  `address`    VARCHAR(255) DEFAULT NULL,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── PURCHASE ITEM MASTER ─────────────────────────────────
CREATE TABLE `purchase_items` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `unit`       VARCHAR(20) NOT NULL DEFAULT 'KG',
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_purchase_item_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CATEGORIES ──────────────────────────────────────────
CREATE TABLE `categories` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `icon`       VARCHAR(10) DEFAULT '📁',
  `type`       ENUM('menu','purchase','both') NOT NULL DEFAULT 'both',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── MENU ITEMS ──────────────────────────────────────────
CREATE TABLE `menu_items` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `category`   VARCHAR(100) NOT NULL,
  `price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ORDERS ──────────────────────────────────────────────
CREATE TABLE `orders` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id`   INT(11) NOT NULL,
  `total`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`        ENUM('Pending','Preparing','Delivered','Cancelled','Reversed') NOT NULL DEFAULT 'Pending',
  `cancel_reason` VARCHAR(255) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ORDER ITEMS ─────────────────────────────────────────
CREATE TABLE `order_items` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `order_id`     INT(11) NOT NULL,
  `menu_item_id` INT(11) NOT NULL,
  `item_name`    VARCHAR(100) NOT NULL,
  `price`        DECIMAL(10,2) NOT NULL,
  `quantity`     INT(11) NOT NULL DEFAULT 1,
  `subtotal`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── INVENTORY ───────────────────────────────────────────
CREATE TABLE `inventory` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `kitchen_id`  INT(11) NOT NULL,
  `menu_item_id` INT(11) DEFAULT NULL,
  `item_name`   VARCHAR(100) NOT NULL,
  `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `unit`        VARCHAR(20) NOT NULL DEFAULT 'KG',
  `notes`       VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_kitchen_item` (`kitchen_id`,`item_name`),
  KEY `idx_inventory_menu_item` (`kitchen_id`,`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── INV ALLOCATIONS ─────────────────────────────────────
CREATE TABLE `inv_allocations` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `kitchen_id`   INT(11) NOT NULL,
  `item_name`    VARCHAR(100) NOT NULL,
  `quantity`     DECIMAL(10,2) NOT NULL,
  `unit`         VARCHAR(20) NOT NULL DEFAULT 'KG',
  `allocated_by` INT(11) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CONSUMPTION ─────────────────────────────────────────
CREATE TABLE `consumption` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `kitchen_id`   INT(11) NOT NULL,
  `inventory_id` INT(11) NOT NULL,
  `item_name`    VARCHAR(100) NOT NULL,
  `quantity`     DECIMAL(10,2) NOT NULL,
  `unit`         VARCHAR(20) NOT NULL,
  `recorded_by`  INT(11) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── PURCHASES ───────────────────────────────────────────
CREATE TABLE `purchases` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `kitchen_id`    INT(11) NOT NULL,
  `purchase_date` DATE NOT NULL,
  `purchase_item_id` INT(11) DEFAULT NULL,
  `category`      VARCHAR(100) DEFAULT NULL,
  `item_name`     VARCHAR(100) NOT NULL,
  `quantity`      DECIMAL(10,2) NOT NULL,
  `unit`          VARCHAR(20) NOT NULL DEFAULT 'KG',
  `vendor`        VARCHAR(100) DEFAULT NULL,
  `vendor_id`     INT(11) DEFAULT NULL,
  `vendor_name`   VARCHAR(100) DEFAULT NULL,
  `rate`          DECIMAL(10,2) NOT NULL,
  `total_amount`  DECIMAL(10,2) NOT NULL,
  `bill_number`   VARCHAR(50) DEFAULT NULL,
  `bill_image`    VARCHAR(255) DEFAULT NULL,
  `payment_type`  ENUM('Cash','Bank','Credit') NOT NULL DEFAULT 'Cash',
  `remarks`       VARCHAR(255) DEFAULT NULL,
  `created_by`    INT(11) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_kitchen_date` (`kitchen_id`,`purchase_date`),
  KEY `idx_purchases_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── WALLET TOPUPS ───────────────────────────────────────
CREATE TABLE `wallet_topups` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `amount`      DECIMAL(10,2) NOT NULL,
  `type`        ENUM('topup','deduct','refund','order') NOT NULL DEFAULT 'topup',
  `note`        VARCHAR(255) DEFAULT NULL,
  `created_by`  INT(11) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── AUDIT LOG ───────────────────────────────────────────
CREATE TABLE `audit_log` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) DEFAULT NULL,
  `user_name`  VARCHAR(100) DEFAULT NULL,
  `action`     VARCHAR(255) NOT NULL,
  `target`     VARCHAR(100) DEFAULT NULL,
  `target_id`  INT(11) DEFAULT NULL,
  `reason`     VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SESSIONS ────────────────────────────────────────────
CREATE TABLE `sessions` (
  `id`         VARCHAR(128) NOT NULL,
  `user_id`    INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════
-- SEED DATA
-- ════════════════════════════════════════════════════════

INSERT INTO `kitchens` (`id`,`name`,`type`,`status`) VALUES
(1,'Kitchen 1','kitchen','active'),
(2,'Kitchen 2','kitchen','active'),
(3,'Sales Kitchen','sales','active');

INSERT INTO `vendors` (`name`,`mobile`,`status`) VALUES
('City Supplier','03001111111','active'),
('Daily Mart','03002222222','active');

INSERT INTO `categories` (`name`,`icon`,`type`) VALUES
('Breakfast','🍳','menu'),
('Lunch','🍱','menu'),
('Snacks','🥪','menu'),
('Beverages','☕','menu'),
('Grocery','🛒','purchase'),
('Meat','🥩','purchase'),
('Vegetables','🥦','purchase'),
('Dairy','🥛','purchase'),
('Cleaning','🧹','purchase');

INSERT INTO `purchase_items` (`name`,`unit`,`status`) VALUES
('Basmati Rice','KG','active'),
('Cooking Oil','Liters','active'),
('Chicken','KG','active'),
('Beef','KG','active'),
('Potatoes','KG','active'),
('Onions','KG','active'),
('Milk','Liters','active'),
('Tea','KG','active'),
('Sugar','KG','active'),
('Disposable Cups','Pcs','active');

INSERT INTO `menu_items` (`name`,`category`,`price`,`status`) VALUES
('Paratha','Breakfast',15.00,'active'),
('Egg Omelette','Breakfast',20.00,'active'),
('Dal Chawal','Lunch',60.00,'active'),
('Biryani','Lunch',80.00,'active'),
('Roti','Lunch',5.00,'active'),
('Samosa','Snacks',10.00,'active'),
('Tea','Beverages',15.00,'active'),
('Water Bottle','Beverages',20.00,'active');

-- Users: passwords stored as MD5, auto-upgraded to bcrypt on first login
INSERT INTO `users` (`id`,`employee_id`,`name`,`username`,`password`,`role`,`kitchen_id`,`department`,`mobile`,`wallet`) VALUES
(1,'EMP001','Super Admin','admin','0192023a7bbd73250516f069df18b500','admin',NULL,'','',0),
(2,'EMP002','Accounts Mgr','accounts','7f4bba20acd3fc4168e3a6576d13adcb','accounts',NULL,'','',0),
(3,'EMP003','Chef Hassan','chef1','677dbf3b047f16c7c5b5554a8259f2eb','chef',1,'','',0),
(4,'EMP004','Chef Bilal','chef2','0a0d8b31fba3be8f6e7ac5f857e7c394','chef',2,'','',0),
(5,'EMP005','Sales Staff','sales','0ad80eb119d9bf7775aa23786b05b391','sales',NULL,'','',0),
(6,'EMP006','Ahmed Khan','ahmed','32aa2fd87338e241978c48ab319641bc','customer',NULL,'Weaving','03001234567',5000),
(7,'EMP007','Sara Malik','sara','312dc6ec7c900fb9017bf43c6b1f81bb','customer',NULL,'Finishing','03007654321',3000);

ALTER TABLE `users`      AUTO_INCREMENT = 10;
ALTER TABLE `kitchens`   AUTO_INCREMENT = 10;
ALTER TABLE `vendors`    AUTO_INCREMENT = 10;
ALTER TABLE `purchase_items` AUTO_INCREMENT = 20;
ALTER TABLE `menu_items` AUTO_INCREMENT = 10;
ALTER TABLE `categories` AUTO_INCREMENT = 10;

-- ════════════════════════════════════════════════════════
-- LOGIN CREDENTIALS
-- ════════════════════════════════════════════════════════
-- admin    / admin123   → Admin (Full PC Dashboard)
-- accounts / acc123     → Accounts Manager (Mobile)
-- chef1    / chef123    → Chef Kitchen 1 (Mobile)
-- chef2    / chef456    → Chef Kitchen 2 (Mobile)
-- sales    / sales123   → Sales Staff (Mobile)
-- ahmed    / ahmed123   → Customer (Mobile)
-- sara     / sara123    → Customer (Mobile)
-- ════════════════════════════════════════════════════════
