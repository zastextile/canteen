-- ============================================================================
--  ZAS Canteen — counter token: 26-1, 26-2, restarting each day
-- ============================================================================
--  WHAT IT DOES
--    Adds a day_seq column holding each order's position among that day's
--    orders, so the screens can show 26-1 instead of 5920. The real order id
--    is untouched: order_items, the audit log and every report keep working
--    exactly as they do now, and Admin still shows the id for tracing.
--
--    A separator is used on purpose. Without one, day 26 order 1 and day 2
--    order 61 would both read "261" — and you pass 61 orders in a day often.
--
--  SAFE TO RUN WHILE THE APP IS LIVE
--    It adds one column and fills it in. No existing value is changed and no
--    row is deleted. On 7,500 orders it finishes in well under a second.
--    Running it twice is harmless: the first statement fails with "Duplicate
--    column name" and nothing else happens.
--
--  HOW
--    Database Manager -> SQL command -> paste this whole file -> Execute.
-- ============================================================================

ALTER TABLE `orders` ADD COLUMN `day_seq` SMALLINT UNSIGNED NULL AFTER `status`;

-- Number every order that already exists, oldest first within each day.
UPDATE `orders` o
  JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY DATE(created_at) ORDER BY id) AS rn
          FROM `orders`) x ON x.id = o.id
  SET o.day_seq = x.rn;

-- ---------------------------------------------------------------------------
--  CHECK IT WORKED — the newest orders should read 1, 2, 3 ... for their day.
-- ---------------------------------------------------------------------------
-- SELECT id, DATE(created_at) AS day, day_seq,
--        CONCAT(DAY(created_at),'-',day_seq) AS token
--   FROM orders ORDER BY id DESC LIMIT 15;
