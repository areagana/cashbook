<?php
/** Add this function alongside your other create_Table() helpers and run it once. */
function cashbook_stocktake_deletion_audit()
{
    create_Table('cashbook_stocktake_deletion_audit', [
        'book_id' => 'INT(11) NOT NULL',
        'stocktake_id' => 'INT(11) NOT NULL',
        'period_month' => 'CHAR(7) NOT NULL',
        'original_status' => 'VARCHAR(30) NOT NULL',
        'deleted_by' => 'INT(11) NOT NULL',
        'reason' => 'VARCHAR(500) NOT NULL',
        'snapshot_json' => 'LONGTEXT NOT NULL',
        'deleted_at' => 'DATETIME NOT NULL'
    ]);
}
