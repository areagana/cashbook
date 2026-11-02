# KashHub integration checklist

## Load helpers once
After existing `functions.php` is loaded, in shared bootstrap or `nav.php`:

```php
require_once __DIR__ . '/business_settings/bootstrap.php'; // adjust relative path
```

Do not include `install/install.php` on normal requests.

## Business navigation
Add an owner-only settings link:

```php
if (khSettingsIsOwner(khSettingsBusinessId())) {
    echo '<a href="/cashbook/business_settings/">Business Settings</a>';
}
```

For each module nav section, combine feature and permission checks:

```php
if (khModuleEnabled('routes', $businessId) && khCan('routes.view', $bookId, $businessId)) {
    // render route navigation; replace routes.view with your actual permission key
}
```

## Controller/page access — REQUIRED
Immediately after authentication and resolving the current business/book context:

```php
require_once __DIR__ . '/../business_settings/bootstrap.php';
khRequireModule('routes', $businessId);
// Keep your existing khCan() check here too.
```

Apply to all route pages, route manager pages, route exports, AJAX endpoints, and write handlers. Repeat for `stock`, `customers`, `creditors`, and `invoices`. **Do not rely only on hiding links.**

## Security
Always resolve `$businessId` from an authenticated, authorised book/business mapping; do not use a GET `business_id` directly. Check the book belongs to that business before checking `khModuleEnabled`. Do not allow a user to switch to an unrelated business by changing the session variable.

## Presets
Presets are suggested defaults; selecting a preset in the UI does not delete records. Owners can customize any preset. Core finance functionality is not controlled by these optional module settings.

## Tests
1. New installation on XAMPP, run installer twice; verify only one `uq_business_setting` index.
2. Log in as registered owner, save and reload settings.
3. Log in as staff, verify settings page returns 403.
4. Disable routes; verify nav disappears and **every** route endpoint returns 403 after guard integration.
5. Re-enable routes; verify existing route records remain intact.
6. Attempt to enable routes without customers; save must fail.
7. Confirm invoice, stocktake, customer ledger and core cashbook transactions remain functional.
8. Confirm no new indexes are created during login.
