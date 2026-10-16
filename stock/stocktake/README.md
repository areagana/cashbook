# KashHub Stock Taking v2

Install under `stock/stocktake/`:

```
stock/stocktake/
  index.php
  bootstrap.php
  session_modal.php
  print.php
  save/index.php
```

Requires your existing `../../assets/functions.php`, `pageHeaderLevel2()` and `pageFooterLevel2()`, and the four stocktake tables previously defined with `create_Table()`.

**Critical before production:** `bootstrap.php` has a marked integration point for the real book membership/access check. `hasRole()` is not sufficient to authorize an arbitrary `bkid`. Replace this with your application's book access helper. Enforce CSRF and authenticated permissions in the save endpoint as included.

**Report:** `print.php` renders a direct PDF if Dompdf is installed at either supported vendor path. Otherwise it renders an A4 landscape HTML report with Print / Save as PDF. Install Dompdf if direct PDF output is required on your hosting environment.

**Behaviour:** open = draft, counting, review; closed = approved, posted, cancelled. Closed records are hidden by default and accessed with the Closed tab. Session details are AJAX-loaded into a central modal. All writes use `save/index.php`; no forms POST to the dashboard.

**Inventory accounting:** approval does not post adjustment movements. Expected quantities are a snapshot at session creation and not historical balances. Costs must be entered for full valuation. This release does not compute cost of goods sold or profitability.

**Schema safeguards:** add unique indexes `(book_id, period_month)` to `cashbook_stocktakes` and `(stocktake_id, item_id)` to `cashbook_stocktake_items` after verifying there are no duplicates. Your `create_Table()` helper does not create these indexes.

**Browser testing:** verify modal open/close, sidebar toggle, create/save/review/approve, default Open tab, Closed tab, and approved PDF with real book data before production.
