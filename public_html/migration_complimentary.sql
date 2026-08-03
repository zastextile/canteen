-- ============================================================
-- MIGRATION for EXISTING installs (fresh installs: use database.sql)
-- Adds: Complimentary Menu Allocation feature + performance indexes.
-- Run ONCE in phpMyAdmin on the live database. Safe & additive —
-- no existing tables, columns or data are modified or removed.
-- ============================================================

-- New feature table
CREATE TABLE IF NOT EXISTS `complimentary_allocations` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id`  INT(11) NOT NULL,
  `menu_item_id` INT(11) NOT NULL,
  `qty_allowed`  INT(11) NOT NULL DEFAULT 1,
  `qty_used`     INT(11) NOT NULL DEFAULT 0,
  `expiry_date`  DATE DEFAULT NULL,
  `status`       ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `assigned_by`  INT(11) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comp_customer` (`customer_id`,`status`),
  KEY `idx_comp_item` (`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Flag complimentary lines inside orders (0 = paid, 1 = free)
ALTER TABLE `order_items` ADD COLUMN `is_comp` TINYINT(1) NOT NULL DEFAULT 0;

-- Performance indexes (speed up queue, my_orders, reports, audit under load)
ALTER TABLE `orders`        ADD KEY `idx_orders_customer` (`customer_id`,`created_at`);
ALTER TABLE `orders`        ADD KEY `idx_orders_status` (`status`,`created_at`);
ALTER TABLE `order_items`   ADD KEY `idx_order_items_order` (`order_id`);
ALTER TABLE `order_items`   ADD KEY `idx_order_items_menu` (`menu_item_id`);
ALTER TABLE `wallet_topups` ADD KEY `idx_topups_customer` (`customer_id`,`created_at`);
ALTER TABLE `audit_log`     ADD KEY `idx_audit_created` (`created_at`);
ALTER TABLE `users`         ADD KEY `idx_users_role` (`role`,`status`);
