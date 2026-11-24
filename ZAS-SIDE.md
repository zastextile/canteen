# The ZAS side of the canteen link

For `zastextile/zas` (invoice.thqlinen.com). Written and tested here, but this
session has **read-only** access to that repository, so these files are handed
over rather than pushed.

## What was added

**4 new files** — nothing that is already running was rewritten.

| File | What it is |
|---|---|
| `public_html/includes/canteen.php` | all the logic: settings, the card answer, the weekly posting |
| `public_html/canteen_api.php` | the one address the canteen calls, once per card tap |
| `public_html/canteen_cards.php` | Production → **Canteen Cards**: the two keys, and a card + weekly limit per worker |
| `public_html/canteen_charges.php` | Production → **Canteen Charges**: the weekly button |

**2 existing files, small amendments**

| File | Change | If you skip it |
|---|---|---|
| `public_html/includes/menu.php` | two menu lines, both wrapped in `is_file()` | the screens work, you just have to type the URL |
| `public_html/includes/zprod.php` | `zp_worker_ledger()` labels a canteen line "Canteen" instead of "Cash paid", and leaves it without a Cancel button | the ledger still balances; the line just says "Cash paid" and can be cancelled from the wrong screen |

Neither amendment can break anything if the other file is missing: the menu
lines check the file exists, and the ledger falls back to its original query if
the `source` column is not there.

`config/config.php` is **not** touched. The keys live in `zp_meta`, the same
key/value table the HR sync already keeps its address and token in, so they are
set from the Cards screen with nothing to edit by hand on the server.

## Putting it on

1. Upload the 4 new files and the 2 amended ones.
2. Open **Production → Canteen Cards** (admin only). Fill in:
   - **Canteen address** — `https://canteen.thqlinen.com/zas.php`
   - **Card key** and **Charges key** — the same two values as the canteen's
     `config.php`, each made with
     `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`
   - tick **Canteen link is ON**
3. Press **Test the link**. It should say the canteen answered.
4. For each worker: press **Register card**, tap their card on a reader plugged
   into that PC. Set a weekly limit only if it differs from the standard.

The database changes itself on first open — three columns on `zp_workers`, one
column on `zp_pay`, one new table `zp_canteen_sheets`. All ADD only, each in its
own try/catch, exactly the way the rest of this app does it.

## Where the money lands

One row in `zp_pay` per worker per week, with `source='canteen'` and a note:

    Canteen 18 Nov-24 Nov - 3 Tea, 1 Roti (2 orders)

That is the whole reason this is safe. `balance = earned - paid`, and
`zp_paid_map()` already sums `zp_pay`, so the balance is right the moment the
row exists — **no existing function had to be changed**, and there is no second
place for the figure to disagree.

Food *is* a payment: the worker received value, it simply was not cash.

## The weekly routine

**Production → Canteen Charges** → pick the Thursday → **Get canteen charges** →
look at it → **Post to Worker Pay**.

Pressing it twice charges once. The week is claimed under one reference
(`CT-20261124`), which is UNIQUE here and is what the canteen stamps on the
orders it hands over.

If anything fails, the week is put back untouched and comes again next time. The
only case needing a human is a failure at the very last step — the pay rows are
written but the canteen was not told. That charges nobody twice; it leaves one
**Confirm with the canteen** button on the screen.

## Taking it off

Delete the 4 new files. Production pay carries on exactly as before: the extra
columns are NULL on every worker and `'cash'` on every payment ever made.

## Two things to know

**A canteen line has no Cancel button** on the Worker Pay ledger. That is on
purpose — it is undone at the canteen, so the two apps go on agreeing about what
was settled.

**An employee the canteen knows but ZAS does not** stops the posting and is
named on screen, rather than being quietly dropped. That can only happen if a
worker is deleted here after they have eaten.

## A pre-existing bug, found while testing — not touched

`production_dashboard.php` returns **500** when any worker has a NULL `stage`:

    Uncaught TypeError: zp_is_cutting(): Argument #1 ($stage) must be of type
    string, null given — includes/zprod.php line 1375, defined at line 524

Confirmed on the **untouched** repository, before any of this work. Nothing to
do with the canteen link, and left alone as asked. Worth a one-line fix
(`(string)$stage`, or a null guard) when convenient.

## Tested

51 checks with both apps running against real MariaDB copies and talking to each
other over real HTTP — card taps, per-person limits, a refund, the weekly
posting, a double-click on the button, the worker's ledger, and every other ZAS
screen still loading. Two real bugs were caught and fixed on the way:

* `zp_meta` does not exist until something creates it, so saving the link
  settings on a fresh server would have silently saved nothing while the screen
  said "saved".
* The card-tap panel's `display:grid` beat its own `hidden` attribute, so an
  invisible overlay covered the Cards screen and swallowed every click.
