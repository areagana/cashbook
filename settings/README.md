# KashHub Business Settings — modular business configuration

## What is included
- `bootstrap.php`: business profile presets, settings helpers, dependency enforcement, save validation and access guards.
- `index.php`: responsive owner-only business settings page with CSRF protection.
- `assets/settings.css`: standalone styles.
- `install/install.php`: CLI-only, additive table installer using the existing `create_Table()` convention.
- `INTEGRATION.md`: navigation, controller and permission integration examples.

## Installation
1. **Back up your production database and PHP files.** Test on XAMPP first.
2. Copy the entire `business_settings` directory into your KashHub application directory, alongside `functions.php` and `db_tables.php`. Rename the downloaded directory to `business_settings` if needed.
3. Ensure `functions.php` initializes your existing session and database connection, and `db_tables.php` is in the parent directory.
4. Run **from a terminal**, in the KashHub directory: `php business_settings/install/install.php`. This does not run on login or ordinary requests. On shared hosting without CLI, use the SQL in `install/schema.sql` manually instead.
5. Open `/cashbook/business_settings/` (adjust for your deployment root). Only the registered business profile owner can manage settings.
6. Follow `INTEGRATION.md` to add a navigation link and guards to **all** affected pages and API endpoints. Without these integration changes, settings are saved but existing route URLs are **not yet blocked**.

## Important safety behaviour
- Default for legacy businesses: existing modules are **enabled** until the owner configures them, to prevent accidental disruption.
- Unknown modules are denied; disabled modules are denied by `khRequireModule()`.
- No data is deleted when disabling a module.
- Only the registered business owner can save settings, even if a user has other role permissions.
- Business identity comes from authenticated session/profile, not POST or GET.
- The existing `khCan()` RBAC still applies: integrate both checks.
- Core cashbook/transactions/finance reports remain always available.
- Dependencies: routes and invoices require customers.
- PHP 8.0+ required (`str_starts_with`).

## Deployment note
Business ID resolution assumes `$_SESSION['business_id']` stores a **numeric business profile ID**. If your deployment stores an encrypted ID or uses a different session convention, adapt `khSettingsBusinessId()` to your established authoritative business context before deployment. Do not trust arbitrary request IDs.
