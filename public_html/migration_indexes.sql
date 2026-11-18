-- ============================================================================
--  ZAS Canteen — indexes for the slow queries in the PHP slow log
-- ============================================================================
--  WHY
--    The slow log showed /api.php?a=orders_list taking up to 360 seconds and
--    audit-log inserts up to 120. The orders table has indexes on
--    (customer_id, created_at) and (status, created_at), but NOTHING on
--    created_at by itself — and the admin Orders screen runs:
--
--        SELECT ... FROM orders o JOIN users c ON c.id = o.customer_id
--        ORDER BY o.created_at DESC LIMIT 200
--
--    with no WHERE clause. Neither existing index can serve that, so MySQL
--    reads every order row and sorts the lot to return 200. While that runs it
--    holds a PHP worker and a database connection; enough of them at once and
--    the pool is exhausted, which is why ordinary audit inserts then took two
--    minutes and new connections failed outright.
--
--  SAFE TO RUN WHILE THE APP IS LIVE
--    Adding an index does not change any data. On a table of a few thousand
--    rows each statement finishes in well under a second. Running it twice is
--    harmless: the second run fails with "Duplicate key name" and changes
--    nothing.
--
--  HOW
--    phpMyAdmin -> select the database -> Import -> choose this file -> Go.
-- ============================================================================

-- The admin Orders screen, newest first. Turns a full scan plus filesort into
-- reading 200 rows straight off the end of the index.
ALTER TABLE `orders` ADD KEY `idx_orders_created` (`created_at`);

-- attempt_login() looks every user up by username on each login attempt.
-- Non-unique on purpose: a UNIQUE key would abort this migration if any
-- duplicate username exists, and the lookup is just as fast either way.
ALTER TABLE `users` ADD KEY `idx_users_username` (`username`);

-- inv_allocations had no index at all beyond its primary key. Every - / + tap
-- on Sales Stock writes a row here, and the Daily Stock Movement report reads
-- it by kitchen and date.
ALTER TABLE `inv_allocations` ADD KEY `idx_inv_alloc_kitchen` (`kitchen_id`, `created_at`);

-- ---------------------------------------------------------------------------
--  CHECK IT WORKED — run this afterwards. "Extra" should no longer say
--  "Using filesort", and "key" should read idx_orders_created.
-- ---------------------------------------------------------------------------
-- EXPLAIN SELECT o.id, o.total, o.created_at, c.name
--   FROM orders o JOIN users c ON c.id = o.customer_id
--   ORDER BY o.created_at DESC LIMIT 200;
