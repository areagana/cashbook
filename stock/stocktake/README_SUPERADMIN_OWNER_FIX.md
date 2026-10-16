KashHub Stocktake — Superadministrator who is also business owner

Fixes owner-only deletion permissions when the business owner has role superadministrator instead of owner. Ownership is verified against cashbook_books.business_id -> cashbook_business_profile.id and cashbook_business_profile.user_id = authenticated user ID. Other superadministrators cannot delete another owner’s sessions. Posted/adjusted/period-linked sessions remain protected.

Install: back up, replace stock/stocktake/session_modal.php and stock/stocktake/save/index.php. Other included module files are unchanged. Ensure deletion audit table is installed. Test with a superadministrator who owns the business and with one who does not.

Long-term: keep cashbook_users.role_id for staff role and add an independent is_superadmin or dedicated system-role assignment with tightly controlled promotion, MFA and audit logging. Do not assign superadministrator automatically to all owners.
