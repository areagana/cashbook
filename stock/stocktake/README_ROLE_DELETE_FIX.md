# KashHub Stock Taking — Delete Permission Fix

## Root cause
The modal previously displayed Delete only if the signed-in user matched `cashbook_business_profile.user_id`. A Superadministrator assignment in `cashbook_role_user` did not meet this ownership-only condition.

## Updated files
- `stock/stocktake/bootstrap.php`: new `stocktakeCanDeleteSession()` authorization helper.
- `stock/stocktake/session_modal.php`: same permission used to show Delete button.
- `stock/stocktake/save/index.php`: same permission enforced for POST delete_session.

## Installation
Back up these three files and replace them from the ZIP. The ZIP includes other stocktake files for completeness; leave your locally modified versions of those untouched.

**Prerequisite:** `assets/functions.php` must include the updated `hasRole()` function that recognizes `cashbook_role_user` system-wide Superadministrator assignments (`business_id = 0`). If it does not, a newly assigned Superadministrator will not be recognized.

## Safeguards
Posted sessions or sessions with stock adjustment/period references remain protected. A reason and audit snapshot are still required. Do not expose this permission to ordinary staff or business partners.

Syntax checks are not a substitute for testing with the live database and session.
