-- ============================================================================
--  ZAS Canteen — one-time timezone correction:  UTC  ->  PKT (UTC+5)
-- ============================================================================
--  WHY
--    PHP was already set to Asia/Karachi (config.php), but MySQL was running on
--    UTC. Every `DEFAULT current_timestamp` column was therefore written 5 hours
--    behind real Pakistan time. This shifts the existing rows forward by 5 hours
--    so history lines up with the clock on the wall.
--
--  RUN THIS ONCE, AND ONLY ONCE.
--    Running it twice would push everything to UTC+10. The CREATE TABLE on line
--    below is a guard: the second time you run this file that statement fails
--    with "table already exists" and nothing is changed.
--
--  ORDER OF WORK  (do it when the canteen is closed and nobody is using the app)
--    1. Back up the database first  (phpMyAdmin -> Export -> Go).
--    2. Import this file.
--    3. Upload the new lib.php immediately afterwards.
--    Between steps 2 and 3 any new order would still be written in UTC and would
--    be missed by the shift — which is why this is done outside opening hours.
--
--  NOT TOUCHED, deliberately:
--    complimentary_allocations.expiry_date — a calendar date an admin typed in,
--        it never came from the server clock.
--    complimentary_allocations.used_on / purchases.purchase_date — DATE columns
--        with no time part, so a 5-hour shift cannot change them. Only a record
--        created between midnight and 05:00 PKT would sit on the wrong day, and
--        the canteen is closed then.
-- ============================================================================

-- Guard — fails harmlessly if this migration has already been applied.
CREATE TABLE `tz_migration_utc_to_pkt` (
  `id`         tinyint(1) NOT NULL,
  `applied_at` datetime   NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

START TRANSACTION;

UPDATE `audit_log`                 SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `categories`                SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `complimentary_allocations` SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `consumption`               SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `inventory`                 SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `inv_allocations`           SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `kitchens`                  SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `menu_items`                SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `orders`                    SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `purchases`                 SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `purchase_items`            SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `sessions`                  SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `users`                     SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `vendors`                   SET `created_at` = `created_at` + INTERVAL 5 HOUR;
UPDATE `wallet_topups`             SET `created_at` = `created_at` + INTERVAL 5 HOUR;

INSERT INTO `tz_migration_utc_to_pkt` (`id`, `applied_at`) VALUES (1, NOW());

COMMIT;

-- ---------------------------------------------------------------------------
--  CHECK IT WORKED — run this afterwards. The busiest hour should now be
--  around 13:00 (lunch), not 08:00.
-- ---------------------------------------------------------------------------
-- SELECT HOUR(created_at) AS hour_of_day, COUNT(*) AS orders
--   FROM orders GROUP BY hour_of_day ORDER BY hour_of_day;
