# RFID counter (kiosk) — what it is and how to put it on the server

Kept outside `public_html` on purpose: nothing here is reachable from the web.

## What was added

Five **new** files. No file that is already running was changed.

| File | What it is |
|---|---|
| `kiosk.php` | the counter screen — the only page anybody opens |
| `kiosk_api.php` | everything that screen calls. Separate from `api.php` so `api.php` needed no change |
| `zas_link.php` | the shared bits: the card check to ZAS, the pay week, paisa conversion |
| `zas.php` | the four weekly addresses ZAS calls (`charges`, `reserve`, `settle`, `release`) |
| `migration_kiosk.sql` | adds columns and one table. Adds only — changes no existing row |

`api.php`, `lib.php`, `admin.php`, `accounts.php`, `sales.php`, `customer.php`,
`chef.php`, `app.js`, `report.js` are all untouched.

## Putting it on

1. **Database** — Cloudways → Database Manager → SQL COMMAND → paste
   `migration_kiosk.sql` → Execute. Safe while the app is live: it only adds.
2. **Keys** — on the server, run `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`
   twice. Add the four `ZAS_*` lines from `config.sample.php` to the live
   `config.php`, with those two values. The same two values go into ZAS's config.
3. **Files** — upload the five files into `public_html`.
4. **Open** `https://canteen.thqlinen.com/kiosk.php` on the counter PC, sign in
   once as sales/accounts/admin, put the browser in full-screen (F11).

## The four addresses to give ZAS

    https://canteen.thqlinen.com/zas.php?a=charges
    https://canteen.thqlinen.com/zas.php?a=reserve
    https://canteen.thqlinen.com/zas.php?a=settle
    https://canteen.thqlinen.com/zas.php?a=release

All POST, JSON, header `X-Link-Key`. Wrong key → 403 and nothing else.

## Taking it off

Delete `kiosk.php` and `kiosk_api.php`. The counter stops; everything else runs
exactly as before. The columns can stay — they are NULL on every wallet order.

## How a card order differs from a phone order

Only in two ways:

* `orders.zas_emp_id` is set (that is what "card order" means), with
  `week_from`, `settle_state`, `sheet_ref`, `settled_at` alongside it.
* No wallet is touched: no `users.wallet` change and no `wallet_topups` row,
  because the money comes out of ZAS pay instead.

Everything else is identical — `orders` + `order_items`, sales-kitchen stock
down, shows in the sales queue, in Admin and in Reports.

## One thing to know

Cancelling a **card** order from the **Admin** Orders screen would credit a
canteen wallet that was never debited. Cancel and refund card orders from
"Today's card orders" on the kiosk screen, which knows about pay sheets.
Two lines in `api.php` would block the Admin path outright — worth doing
later, but it means re-uploading `api.php`, so it was left out for now.

## Deviations from the ZAS brief, and why

* **Amounts are stored as `DECIMAL(10,2)` rupees, not `BIGINT` paisa.** Changing
  the existing columns would mean touching every screen and report. They are
  converted to exact whole paisa at the link, with no float arithmetic in
  between, so ZAS still sees only integers.
* **Dish names are English.** The canteen database has no Urdu name column. The
  Urdu font is loaded and the screen carries Urdu prompts, so names will render
  if a column is added later.
* **No 10-minute idle sign-out.** The counter is manned all day and a sign-out
  mid-rush is worse than the risk. The screen does return to "Tap your card"
  after 90 seconds, so the next customer can never add to the last one's order.
* **No complimentary (free) items at the counter.** Those are tied to a canteen
  customer's own allocations, which a ZAS employee does not have.

## Tested

82 end-to-end checks against a real copy of the live database, 33 in a real
browser with the reader simulated, plus a two-counters-at-once test proving the
weekly limit holds under a race.
