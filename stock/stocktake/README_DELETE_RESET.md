# KashHub Stock Taking: Owner deletion and reset

## Installation
1. **Back up the database and existing module.**
2. Copy `index.php`, `session_modal.php`, and `save/index.php` into `stock/stocktake/` (keep other existing module files).
3. Add `audit_table_function.php` to your table creation helpers and run `cashbook_stocktake_deletion_audit()` once before using Delete.
4. Confirm the archive table exists. Test with a test book first.

## Behavior
- Only `hasRole(['owner'])` can delete (server enforced); draft/counting/review/approved/cancelled eligible.
- Posted sessions, sessions with posted metadata, any stock adjustment rows, or linked stock periods are blocked.
- Owner must provide a 10-500 character reason and confirm deletion.
- A JSON snapshot of the original header and every line is archived in `cashbook_stocktake_deletion_audit`.
- Session items and header are deleted in a single database transaction, freeing the month for another session.
- Existing approval and print workflows remain intact.

## IMPORTANT production security
`bootstrap.php` now verifies book ownership by joining `cashbook_books.business_id` to `cashbook_business_profile.id` and matching `cashbook_business_profile.user_id` to the logged-in user. Staff/partners must have a matching `cashbook_users.business_id`; superadministrators retain system-wide access, but cannot use owner-only deletion unless they also own the book and have the owner role. Verify these relationships match your production data before deploying.

## Audit / data retention
Do not allow normal users to modify or delete audit records. Store backups and restrict access to audit data. Deleting does not restore a PDF already downloaded or reverse any externally recorded transactions.
