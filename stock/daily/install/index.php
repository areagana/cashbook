<?php
/** One-time installation, using the same create_Table($table,$columns) convention as db_tables.php.
 * The shared db_tables.php is intentionally NOT included because it performs redirects and creates other tables.
 */
require_once __DIR__ . '/../../../assets/functions.php';
header('Content-Type: text/plain; charset=utf-8');
if (!isVerified() || !function_exists('khIsSuperAdmin') || !khIsSuperAdmin()) {
    http_response_code(403); exit('Only the system superadministrator can install the daily stock module.');
}
/** Compatible with the create_Table approach in your supplied db_tables.php. */
if (!function_exists('create_Table')) {
    function create_Table($table, array $columns) {
        global $server;
        if (!preg_match('/^[a-z][a-z0-9_]+$/i',$table)) throw new RuntimeException('Invalid table name.');
        $parts=['`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY'];
        foreach ($columns as $column=>$definition) {
            if (!preg_match('/^[a-z][a-z0-9_]+$/i',$column)) throw new RuntimeException('Invalid column name.');
            $parts[]="`$column` $definition";
        }
        $parts[]='`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        $parts[]='`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $sql="CREATE TABLE IF NOT EXISTS `$table` (".implode(',',$parts).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        if (!$server->query($sql)) throw new RuntimeException($server->error);
        // Same additive column migration behavior as the shared table helper.
        $existing=[];
        $result=$server->query("SHOW COLUMNS FROM `$table`");
        while ($row=$result->fetch_assoc()) $existing[$row['Field']]=true;
        foreach ($columns as $column=>$definition) {
            if (!isset($existing[$column]) && !$server->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
                throw new RuntimeException($server->error);
            }
        }
    }
}
try {
    create_Table('cashbook_daily_stocktakes',[
        'book_id'=>'INT(11) NOT NULL',
        'count_date'=>'DATE NOT NULL',
        'status'=>"ENUM('draft','counting','review','approved') NOT NULL DEFAULT 'draft'",
        'created_by'=>'INT(11) NOT NULL',
        'submitted_by'=>'INT(11) NULL',
        'submitted_at'=>'DATETIME NULL',
        'reviewed_by'=>'INT(11) NULL',
        'reviewed_at'=>'DATETIME NULL',
        'review_notes'=>'TEXT NULL'
    ]);
    create_Table('cashbook_daily_stocktake_items',[
        'daily_id'=>'INT(11) NOT NULL',
        'book_id'=>'INT(11) NOT NULL',
        'item_id'=>'INT(11) NOT NULL',
        'expected_qty'=>'DECIMAL(18,3) NOT NULL DEFAULT 0',
        'counted_qty'=>'DECIMAL(18,3) NULL',
        'remarks'=>'VARCHAR(255) NULL',
        'counted_by'=>'INT(11) NULL',
        'counted_at'=>'DATETIME NULL'
    ]);
    global $server;
    // Guard against duplicate daily sessions and duplicate item rows.
    foreach ([
        ['cashbook_daily_stocktakes','uq_daily_book_date','(book_id,count_date)'],
        ['cashbook_daily_stocktake_items','uq_daily_item','(daily_id,item_id)'],
        ['cashbook_daily_stocktake_items','ix_daily_book','(book_id,daily_id)']
    ] as [$table,$index,$columns]) {
        $check=$server->query("SHOW INDEX FROM `$table` WHERE Key_name='$index'");
        if (!$check || !$check->num_rows) {
            if (!$server->query("ALTER TABLE `$table` ADD ".(str_starts_with($index,'uq_')?'UNIQUE ':'')."INDEX `$index` $columns")) {
                throw new RuntimeException($server->error);
            }
        }
    }
    echo "Daily stock tables installed successfully.\nRemove or restrict the install directory after installation.\n";
} catch (Throwable $e) { http_response_code(500); echo 'Installation failed: '.$e->getMessage(); }
