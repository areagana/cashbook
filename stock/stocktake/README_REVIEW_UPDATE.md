# KashHub stocktake review enhancements

Install these files in `stock/stocktake/`, keeping `save/index.php` in its `save/` subdirectory.

- Owner, partner, superadministrator: approve or return review-stage counts for correction.
- Staff and those roles: edit physical counts while status is draft/counting.
- All active sessions: downloadable provisional PDF marked NOT APPROVED.
- Approved/posted: downloadable final approved PDF.
- Return for corrections: review -> counting, then save counts and resubmit.
- Approved/posted records remain locked. No stock ledger posting is introduced.

**Security note:** `bootstrap.php` retains the prior TODO to enforce user-to-book membership. Implement that check before production use.

**Workflow note:** a returned record does not currently capture a reviewer comment or maintain a historical audit of individual count edits.
