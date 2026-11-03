# KashHub — Database Migration Guide

**Applies to:** `db_tables_no_env.php` (or the same file renamed to `db_tables.php`) and the existing `functions.php` database connection.

## 1. How migrations work

KashHub's current schema file declares table functions that call `create_Table()`. The helper creates a missing table and adds certain missing columns to existing tables. Migrations are **not** run automatically on page visits or logins.

To trigger the migration, run the schema file directly with PHP CLI and the `--migrate` argument. **No `.env` file is required.** The file loads `functions.php` from its own directory, so that file must provide the correct database connection (`$server`) and database name (`$db_name`).

**Important:** The existing helper is additive-only, not a complete versioned migration framework. It does not automatically alter existing column types, remove columns, backfill data, or enforce new unique constraints on existing tables. Some additional functions explicitly create indexes or seed lookup data. Review these before every production run.

## 2. Before every migration

1. Back up the **database** (phpMyAdmin Export → SQL, or a verified database backup) and retain a recoverable copy.
2. Back up the existing `db_tables.php` and `functions.php`.
3. Check that the database credentials used by `functions.php` point to the **intended** database (local or production).
4. Test the exact PHP file against a local or staging copy first.
5. Review new table definitions, added columns, indexes, seed data, and any potentially incompatible changes.
6. Plan a maintenance window if the database is large or changes may lock tables.

## 3. Localhost — Windows / XAMPP

1. Place the updated schema file beside `functions.php` (in the same directory, unless you deliberately update its require path).
2. Open Command Prompt and navigate to that directory, for example:

   ```bat
   cd C:\xampp\htdocs\cashbook\assets
   ```

   Adjust this path to your actual installation. If the file is named `db_tables_no_env.php`, run:

   ```bat
   C:\xampp\php\php.exe db_tables_no_env.php --migrate
   ```

   If you renamed it to `db_tables.php`, run:

   ```bat
   C:\xampp\php\php.exe db_tables.php --migrate
   ```

3. Inspect any console errors and PHP error logs.
4. Check the affected tables and columns in phpMyAdmin; then test the related KashHub features.

## 4. Live — Hostinger

**Preferred method: SSH / terminal, if enabled for your hosting account.**

1. Take a fresh backup of the **live** database and files.
2. Upload the tested schema file to the directory containing the live `functions.php`.
3. Connect to the hosting account through its available SSH/terminal interface.
4. Navigate to the **actual** application directory, for example:

   ```bash
   cd /path/to/your/kashhub/assets
   ```

5. Run:

   ```bash
   php db_tables.php --migrate
   ```

   Or use `php db_tables_no_env.php --migrate` if you kept that filename. If `php` invokes the wrong version, use the appropriate PHP CLI binary supplied by the host.

6. Inspect errors, confirm the correct database was changed, and test the affected live modules.

**If SSH is unavailable:** Do not put a public `?migrate=1` URL or an unprotected migration script on the site. A future superadministrator-only maintenance page can be built, but **it is not included in the current CLI-only file**. Until then, use an approved host terminal or carefully reviewed manual SQL migration.

## 5. Adding a new table

Add a clearly named function in `db_tables.php`:

```php
function cashbook_example_settings()
{
    create_Table('cashbook_example_settings', [
        'book_id' => 'INT(11) NOT NULL DEFAULT 0',
        'setting_key' => 'VARCHAR(100) NOT NULL DEFAULT \'\'',
        'setting_value' => 'TEXT NULL'
    ]);
}
```

Then add `cashbook_example_settings();` inside the guarded CLI migration block, preferably after any tables on which it depends. A function declaration alone **does not execute** the migration.

`create_Table()` automatically adds `id`, `created_at`, and `updated_at` to **new** tables. Do not repeat those names in the columns array unless you intentionally redesign the helper.

## 6. Adding a column to an existing table

Add the column to the appropriate function's `$columns` array:

```php
'new_reference' => 'VARCHAR(100) NULL'
```

Then run the migration. Existing rows will have `NULL` for the new column until your application fills it. The helper deliberately defers adding a `NOT NULL` column with no default to an existing table; review the PHP error log and perform an explicit, planned backfill migration when necessary.

## 7. Changes that require a separate reviewed migration

Do **not** assume the helper will safely handle:

- Renaming or dropping tables or columns.
- Changing a column's data type, size, nullability, or default.
- Adding a `UNIQUE` constraint to an existing table that may contain duplicates.
- Backfilling existing records, changing ledger balances, or repairing historical invoice entries.
- Adding foreign keys or composite indexes unless a dedicated helper explicitly handles them.

For such changes, prepare a separate SQL/PHP migration with prechecks, duplicate checks, a backup, and a recovery plan. MySQL DDL may auto-commit, so a PHP database transaction alone does **not** guarantee that schema changes can be rolled back.

## 8. Verification checklist

After running, confirm:

- [ ] Command completed without errors.
- [ ] The intended database was targeted.
- [ ] Expected tables and columns exist (`SHOW TABLES`, `SHOW COLUMNS FROM ...`).
- [ ] Expected indexes exist (`SHOW INDEX FROM ...`).
- [ ] Existing business, customer, invoice, and ledger records remain intact.
- [ ] Login, roles, invoice editing/deletion, customer statements, stocktaking, and business settings still work as expected.
- [ ] No PHP errors or migration deferral warnings were logged.

If the command fails, **stop** and inspect the exact error. Do not repeatedly rerun a partially failed migration without checking which changes already succeeded.

## 9. Suggested migration history

Maintain a simple internal log alongside each release:

| Date | Version | Change | Tested locally | Applied live | Backup reference |
|---|---|---|---|---|---|
| YYYY-MM-DD | 1.0 | Example: business types and module tables | Yes/No | Yes/No | filename |

For more complex updates, a dedicated `cashbook_schema_migrations` table can be added in a later release to record which migration scripts ran successfully. The current file does **not** provide automatic version tracking.

## 10. Security and operational notes

- Never run migrations from ordinary login, dashboard, or page-load requests.
- Keep production database backups outside the public web directory.
- Restrict write access to schema files and never expose database credentials.
- Prefer a dedicated database user with only the privileges required for controlled maintenance, if your hosting configuration allows it.
- A successful PHP syntax check is **not** proof of a successful database migration.
- `functions.php` may contain application/session logic: if CLI execution encounters authentication or session-related errors, inspect and isolate the connection bootstrap before changing production code.

**Quick reference**

Local (XAMPP): `C:\xampp\php\php.exe db_tables.php --migrate`

Live (Hostinger SSH): `php db_tables.php --migrate`

Both commands must be run from the directory containing the file (or with its correct absolute path), and the file must be named as shown in the command.
