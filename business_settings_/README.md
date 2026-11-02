# KashHub Business Types & Module Allocation — v2

This package **replaces** the earlier owner-toggle business settings model. Do not run the old `business_settings/index.php` alongside this package.

## Installation

1. Back up the production database and PHP files; test on XAMPP first.
2. Copy this folder to the same directory that contains KashHub's `functions.php`, naming it `business_settings`.
3. Run `install/schema.sql` in phpMyAdmin. **IMPORTANT:** If `cashbook_business_profile.business_type_id` already exists, skip the `ALTER TABLE` line. The `CREATE TABLE IF NOT EXISTS` commands do not modify existing tables.
4. Visit `business_settings/admin.php` as a superadministrator to allocate modules to each business type. No allocations are pre-applied.
5. Visit `business_settings/index.php` as a registered business owner to choose the business type and request extra modules.
6. Integrate module checks into `nav.php` and every relevant page/AJAX handler per `INTEGRATION.md`.

## Authorization

Superadmin checks use existing `khIsSuperAdmin()`. Owner checks validate `cashbook_business_profile.user_id` against the logged-in user. Module access is **in addition to** existing `khCan()` role checks, not a replacement.

## Migration behavior

For legacy businesses without a selected type, optional features remain available until the owner chooses a type. Once a type is selected, only core features, allocated features, or superadmin-approved overrides are available. Data is never deleted on deactivation.

## Limitations / follow-up

- The included pages are standalone and do not modify your existing navigation, header/footer, or registration flow; integration requires the current `nav.php` and registration pages.
- `khFeatureEnabled` controls module availability, **not** RBAC authorization. Use both checks.
- The current version does not automatically enforce module dependencies (e.g., Routes requires Customers); allocate dependencies together until dependency validation is added.
- Changes to business type take effect immediately; review active operations before changing a live business.
- Review module-to-page mapping carefully before enabling access guards, particularly routes, stock, invoicing and reporting.
- Module requests are reviewed by the superadmin; approving creates an enabled override. Revocation can be done by a controlled SQL update or a future dedicated admin override screen.
