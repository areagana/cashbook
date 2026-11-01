<?php
/** Add alongside your existing create_Table() helpers, then run once. */
function cashbook_stocktake_excluded_items()
{
    create_Table('cashbook_stocktake_excluded_items', [
        'book_id'      => 'INT(11) NOT NULL',
        'stocktake_id' => 'INT(11) NOT NULL',
        'item_id'      => 'INT(11) NOT NULL',
        'excluded_by'  => 'INT(11) NOT NULL',
        'excluded_at'  => 'DATETIME NOT NULL'
    ]);

    // Required for ON DUPLICATE KEY UPDATE; run separately if your
    // create_Table helper does not support composite unique indexes.
    global $server;
    $exists = $server->query("SELECT 1 FROM information_schema.statistics
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cashbook_stocktake_excluded_items'
          AND INDEX_NAME='ux_stocktake_excluded_item' LIMIT 1");
    if ($exists && $exists->num_rows === 0) {
        $server->query('ALTER TABLE cashbook_stocktake_excluded_items
            ADD UNIQUE KEY ux_stocktake_excluded_item (book_id,stocktake_id,item_id)');
    }
}

cashbook_stocktake_excluded_items();
