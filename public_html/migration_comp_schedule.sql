-- ============================================================
-- MIGRATION 2 — Complimentary item scheduling
-- Run ONCE in phpMyAdmin on u630220206_canteen (AFTER migration_complimentary.sql).
-- Adds: days-of-week, time window, daily-repeat limit. Safe & additive.
-- ============================================================
ALTER TABLE `complimentary_allocations`
  ADD COLUMN `days_of_week` VARCHAR(20) DEFAULT NULL,          -- '1,3,5' (1=Mon … 7=Sun); NULL = every day
  ADD COLUMN `time_from`    TIME DEFAULT NULL,                 -- offer visible from (NULL = all day)
  ADD COLUMN `time_to`      TIME DEFAULT NULL,
  ADD COLUMN `repeat_daily` TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = limit resets every day
  ADD COLUMN `used_on`      DATE DEFAULT NULL;                 -- last usage date (for daily reset)
