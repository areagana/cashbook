# Integration guide for existing KashHub files

## 1. Navigation

In `nav.php`, after `functions.php` is loaded, include the feature bootstrap **once**:

```php
require_once __DIR__ . '/business_settings/bootstrap.php';
```

Adjust relative path if `nav.php` is in another directory. Existing `functions.php` already includes `nav.php` early; do not create a circular `require` chain.

Superadministrator link:

```php
<?php if (function_exists('khIsSuperAdmin') && khIsSuperAdmin()): ?>
<a href="/cashbook/business_settings/admin.php">Business Types & Modules</a>
<?php endif; ?>
```

Business owner link (replace `$businessId` with the verified active business profile ID):

```php
<?php if (khFeatureOwner((int)$businessId)): ?>
<a href="/cashbook/business_settings/index.php">Business Settings</a>
<?php endif; ?>
```

Optional routes navigation link:

```php
<?php if (khFeatureEnabled('routes', (int)$businessId) && khCan('routes.view', $bookId, $businessId)): ?>
  <!-- Keep your existing routes nav item here -->
<?php endif; ?>
```

`routes.view` is illustrative: use the **actual permission key** in your KashHub permission catalog. Do not replace working RBAC logic.

## 2. Server-side guards

In every routes page and routes AJAX endpoint, after authentication and business/book resolution:

```php
require_once __DIR__ . '/../business_settings/bootstrap.php'; // adjust path
khRequireFeature('routes', (int)$businessId);
// Then keep the existing khCan(...) role/permission check.
```

Repeat for stock, invoices, customers and creditors using the matching module key. Do not guard essential cashbook or reporting functionality (`is_core=1`).

## 3. Registration

After business profile creation, direct the owner to `/cashbook/business_settings/index.php` to select a business type. Do not expose module checkboxes to the owner.

## 4. Deployment checks

- Test superadministrator and owner links separately.
- Test an ordinary staff account cannot access either settings page.
- Test a business without a type retains legacy access.
- Test type selection changes module availability without deleting records.
- Test a request, approval, and role-aware route page access.
- Test direct URLs and AJAX calls return HTTP 403 when a module is unavailable.
- Confirm no new indexes appear on `cashbook_permissions` or `cashbook_invoices`.
