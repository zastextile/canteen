-- ============================================================================
--  ZAS Canteen — RFID kiosk / POS counter
-- ============================================================================
--  WHAT IT DOES
--    Adds the columns and one table the card counter needs. Nothing existing is
--    changed, renamed or dropped, and no existing row is touched:
--
--      users.zas_emp_id    the ZAS employee this canteen customer row stands
--                          for. NULL on every customer you already have, so
--                          your own wallet customers are untouched.
--      orders.zas_emp_id   the ZAS employee id exactly as the card check
--                          returned it. NULL on every order placed from a
--                          phone, so "card order" simply means NOT NULL.
--      orders.week_from    the Friday that starts the order's pay week.
--      orders.settle_state unsettled -> reserved -> settled. NULL on all
--                          wallet orders, so the charges feed that ZAS reads
--                          ignores your 7,500 existing orders with no backfill.
--      orders.sheet_ref    the pay sheet a reserved/settled order sits on.
--      orders.settled_at   when it was settled.
--      order_refunds       a refund against an order already on a pay sheet.
--                          A refund is a new row; the order is never edited.
--
--  SAFE TO RUN WHILE THE APP IS LIVE
--    Only ADD COLUMN / ADD KEY / CREATE TABLE. On 7,500 orders it finishes in
--    well under a second. Running it twice is harmless: the second run stops
--    with "Duplicate column name" and changes nothing.
--
--  HOW
--    Cloudways -> Database Manager -> SQL COMMAND -> paste this whole file ->
--    Execute. (phpMyAdmin: select the database -> SQL -> paste -> Go.)
-- ============================================================================

-- ─── Which canteen customer row stands for which ZAS employee ───────────────
-- One row per employee, created by the kiosk the first time their card is
-- accepted. The card NUMBER is never stored here or anywhere else — only this
-- id, which ZAS itself returned.
ALTER TABLE `users`
  ADD COLUMN `zas_emp_id` INT UNSIGNED NULL AFTER `employee_id`,
  ADD UNIQUE KEY `uniq_users_zas_emp` (`zas_emp_id`);

-- ─── Card orders ────────────────────────────────────────────────────────────
ALTER TABLE `orders`
  ADD COLUMN `zas_emp_id`   INT UNSIGNED NULL AFTER `customer_id`,
  ADD COLUMN `week_from`    DATE NULL AFTER `zas_emp_id`,
  ADD COLUMN `settle_state` ENUM('unsettled','reserved','settled') NULL AFTER `cancel_reason`,
  ADD COLUMN `sheet_ref`    VARCHAR(40) NULL AFTER `settle_state`,
  ADD COLUMN `settled_at`   DATETIME NULL AFTER `sheet_ref`;

-- The weekly-limit check on every card tap: this employee, this pay week.
ALTER TABLE `orders` ADD KEY `idx_orders_zas_week` (`zas_emp_id`, `week_from`);
-- The charges feed ZAS reads once a week: everything still unsettled.
ALTER TABLE `orders` ADD KEY `idx_orders_settle` (`settle_state`, `created_at`);

-- ─── Refunds (for orders already on a pay sheet) ────────────────────────────
CREATE TABLE IF NOT EXISTS `order_refunds` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `order_id`     INT(11) NOT NULL,
  `zas_emp_id`   INT UNSIGNED NOT NULL,
  `amount`       DECIMAL(10,2) NOT NULL,
  `reason`       VARCHAR(255) NOT NULL,
  `created_by`   INT(11) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settle_state` ENUM('unsettled','reserved','settled') NOT NULL DEFAULT 'unsettled',
  `sheet_ref`    VARCHAR(40) DEFAULT NULL,
  `settled_at`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_refunds_order` (`order_id`),
  KEY `idx_refunds_emp` (`zas_emp_id`, `created_at`),
  KEY `idx_refunds_settle` (`settle_state`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
--  CHECK IT WORKED — all three should return rows, and the count should be 0.
-- ---------------------------------------------------------------------------
-- SHOW COLUMNS FROM `orders` LIKE 'settle_state';
-- SHOW COLUMNS FROM `users`  LIKE 'zas_emp_id';
-- SELECT COUNT(*) AS should_be_zero FROM `orders` WHERE settle_state IS NOT NULL;
