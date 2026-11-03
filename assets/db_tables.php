<?php
    if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once(__DIR__ . '/functions.php');
    
    /** Safe, additive-only schema helper. Run through an explicit maintenance process. */
    function create_Table($table, array $columns, $idDefinition = 'INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY')
    {
        global $server, $db_name;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Invalid table name');
        }
        foreach ($columns as $name => $definition) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                throw new InvalidArgumentException('Invalid column name');
            }
        }

        $check = $server->prepare('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema=? AND table_name=?');
        $check->bind_param('ss', $db_name, $table);
        $check->execute();
        $exists = (int)$check->get_result()->fetch_assoc()['total'];
        $check->close();

        if (!$exists) {
            $definitions = ["`id` $idDefinition"];
            foreach ($columns as $name => $definition) {
                $definitions[] = "`$name` $definition";
            }
            $definitions[] = '`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $definitions[] = '`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            $sql = "CREATE TABLE `$table` (" . implode(', ', $definitions) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            if (!$server->query($sql)) {
                throw new RuntimeException("Could not create table $table; check server logs");
            }
            return;
        }

        // Existing column types and constraints are NEVER modified automatically.
        $check = $server->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=? AND table_name=?');
        $check->bind_param('ss', $db_name, $table);
        $check->execute();
        $result = $check->get_result();
        $existing = [];
        while ($row = $result->fetch_assoc()) {
            $existing[strtolower($row['column_name'])] = true;
        }
        $check->close();

        foreach ($columns as $name => $definition) {
            if (isset($existing[strtolower($name)])) continue;
            // Avoid inventing values for existing rows when a new NOT NULL field is requested.
            if (preg_match('/\bNOT\s+NULL\b/i', $definition) && !preg_match('/\bDEFAULT\b/i', $definition)) {
                error_log("KashHub migration deferred: $table.$name needs a backfill/default");
                continue;
            }
            // Inline UNIQUE constraints are never added to existing tables automatically.
            // Define and review unique indexes in an explicit migration instead.
            if (preg_match('/\bUNIQUE\b/i', $definition)) {
                error_log("KashHub migration deferred: $table.$name has a UNIQUE constraint");
                continue;
            }
            if (!$server->query("ALTER TABLE `$table` ADD COLUMN `$name` $definition")) {
                error_log("KashHub migration failed for $table.$name: " . $server->error);
                throw new RuntimeException('Database schema update failed; see server logs');
            }
        }
    }

    // create tables
    function cashbook_books()
    {
        $table ='cashbook_books';
        $columns =[
            'name'=>'varchar(255) null',
            'logo' => 'VARCHAR(255) NULL',
            'favicon' => 'VARCHAR(255) NULL',
            'business_id'=>'int(11) null',
            'Details'=>'varchar(255) null',
            'user_id'=>'int(11) null',
            'primary_color' => 'VARCHAR(20) NULL',
            'secondary_color' => 'VARCHAR(20) NULL'
        ];
        create_table($table,$columns);
    }

    function cashbook_users()
    {
        $table ='cashbook_users';
        $columns =[
            'name'=>'varchar(255) null',
            'business_id'=>'int(11) null',
            'role_id'=>'int(11) null',
            'email'=>'varchar(255) null',
            'contact'=>'varchar(255) null',
            'verified'=>'varchar(255) null',
            'password'=>'varchar(255) null'
        ];
        create_table($table,$columns);
    }

    function cashbook_book_users()
    {
        $table ='cashbook_book_users';
        $columns =[
            'user_id'=>'int(11) null',
            'book_id'=>'int(11) null'
        ];
        $foreignKeys= [
            'user_id' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id',
            'book_id' => 'book_id', 'ref_table' => 'cashbooks_books', 'ref_column' => 'id'
        ];
        create_table($table,$columns);
    }

    function cashbook_cashins()
    {
        $table ='cashbook_cashins';
        $columns =[
            'category_id'=>'int(11) null',
            'book_id'=>'int(11) null',
            'item_id'=>'int(11) null',
            'paymode_id'=>'int(11) null',
            'customer_id'=>'int(11) null',
            'creditor_id'=>'int(11) null',
            'amount'=>'float null',
            'details'=>'varchar(255) null',
            'transaction_id'=>'int(11) null',
            'user_id'=>'int(11) null',
            'quantity'=>'int(11) null',
            'rate'=>'int(11) null',
            'type'=>'varchar(255) null'
        ];
        $foreignKeys= [
            'book_id' => 'book_id', 'ref_table' => 'cashbook_books', 'ref_column' => 'id',
            'category_id' => 'category_id', 'ref_table' => 'cashbook_categories', 'ref_column' => 'id',
            'item_id' => 'item_id', 'ref_table' => 'cashbooks_items', 'ref_column' => 'id',
            'user_id' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id',
            'transaction_id' => 'transaction_id', 'ref_table' => 'transactions', 'ref_column' => 'id',
        ];
        create_table($table,$columns);
    }

    function cashbook_cashouts()
    {
        $table ='cashbook_cashouts';
        $columns =[
            'category_id'=>'int(11) null',
            'book_id'=>'int(11) null',
            'item_id'=>'int(11) null',
            'paymode_id'=>'int(11) null',
            'book_id'=>'int(11) null',
            'customer_id'=>'int(11) null',
            'creditor_id'=>'int(11) null',
            'amount'=>'float null',
            'details'=>'varchar(255) null',
            'transaction_id'=>'int(11) null',
            'user_id'=>'int(11) null',
            'quantity'=>'int(11) null',
            'rate'=>'int(11) null',
            'type'=>'varchar(255) null'
        ];
         $foreignKeys= [
            'book_id' => 'book_id', 'ref_table' => 'cashbook_books', 'ref_column' => 'id',
            'category_id' => 'category_id', 'ref_table' => 'cashbook_categories', 'ref_column' => 'id',
            'item_id' => 'item_id', 'ref_table' => 'cashbooks_items', 'ref_column' => 'id',
            'user_id' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id',
            'transaction_id' => 'transaction_id', 'ref_table' => 'transactions', 'ref_column' => 'id',
        ];
        create_table($table,$columns);
    }

    function cashbook_categories()
    {
        $table ='cashbook_categories';
        $columns =[
            'name'=>'varchar(255) null',
            'book_id'=>'int(11) null',
            'Details'=>'varchar(255) null',
            'user_id'=>'int(11) null'
        ];
        create_table($table,$columns);
    }

    function transactions()
    {
        $table ='cashbook_transactions';
        $columns =[
            'category_id'=>'int(11) null',
            'book_id'=>'int(11) null',
            'type'=>'varchar(255) null',
            'item_id'=>'int(11) null',
            'paymode_id'=>'int(11) null',
            'customer_id'=>'int(11) null',
            'creditor_id'=>'int(11) null',
            'quantity'=>'int(11) null',
            'rate'=>'int(11) null',
            'credit_amount'=>'float null',
            'debit_amount'=>'float null',
            'details'=>'varchar(255) null',
            'balance'=>'float null',
            'route_manager_id'=>'int(11) null',
            'user_id'=>'int(11) null',
            'invoice_id'=>'int(11) null'
        ];
        create_table($table,$columns);
    }

    function cashbook_items()
    {
        global $server;

        $table = 'cashbook_items';

        $columns = [
            'name'        => 'varchar(255) null',
            'book_id'     => 'int(11) null',
            'Details'     => 'varchar(255) null',
            'units'       => 'varchar(255) null',
            'user_id'     => 'int(11) null',
            'archived_at' => 'DATETIME NULL DEFAULT NULL'
        ];

        // Create table or add missing columns.
        create_table($table, $columns);

        /*
        |--------------------------------------------------------------------------
        | INDEX FOR ACTIVE AND ARCHIVED ITEMS
        |--------------------------------------------------------------------------
        */
        $indexName = 'ix_cashbook_items_active';
        $check = $server->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.statistics
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND INDEX_NAME = ?
        ");

        $check->bind_param('ss', $table, $indexName);
        $check->execute();

        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ((int)$exists['total'] === 0) {
            $server->query("
                CREATE INDEX ix_cashbook_items_active
                ON cashbook_items (book_id, archived_at)
            ");
        }
    }

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

    function cashbook_paymodes()
    {
        $table ='cashbook_paymodes';
        $columns =[
            'name'=>'varchar(255) null',
            'book_id'=>'int(11) null',
            'Details'=>'varchar(255) null',
            'user_id'=>'int(11) null'
        ];
        create_table($table,$columns);
    }
    function cashbook_customers()
    {
        $table ='cashbook_customers';
        $columns =[
            'name'=>'varchar(255) null',
            'book_id'=>'int(11) null',
            'contact'=>'varchar(255) null',
            'address'=>'varchar(255) null',
            'route_manager_id'=>'int(11) null',
            'user_id'=>'int(11) null',
            'route_id' =>'int(11) null'
        ];
        create_table($table,$columns);
    }

    function cashbook_creditors()
    {
        $table ='cashbook_creditors';
        $columns =[
            'name'=>'varchar(255) null',
            'book_id'=>'int(11) null',
            'contact'=>'varchar(255) null',
            'address'=>'varchar(255) null',
            'user_id'=>'int(11) null'
        ];
        create_table($table,$columns);
    }

    function cashbook_business_profile()
    {
        $table ='cashbook_business_profile';
        $columns =[
            'name'=>'varchar(255) null',
            'address'=>'varchar(255) null',
            'contact1'=>'varchar(255) null',
            'contact2'=>'varchar(255) null',
            'email'=>'varchar(255) null',
            'reg_no'=>'varchar(255) null',
            'profile'=>'varchar(255) null',
            'user_id'=>'int(11) null',
            'business_type_id' => 'INT NULL DEFAULT NULL'
        ];
        create_table($table,$columns);
    }

    function cashbook_roles()
    {
        global $server;
        $table ='cashbook_roles';
        $columns =[
            'name'=>'varchar(255) null',
            'display_name'=>'varchar(255) null'
        ];
        create_table($table,$columns);

        // enter roles
        $roles =[
            'owner'=>'OWNER',
            'partner'=>'PARTNER',
            'staff'=>'STAFF',
            'cashier'=>'CASHIER',
            'stock_manager'=>'STOCK MANAGER',
            'route_manager'=>'ROUTE MANAGER',
            'superadministrator'=>'SUPER ADMIN'
        ];

        $sql = "INSERT INTO cashbook_roles SET name =?, display_name =?";
        // avoid duplicates
        $query = mysqli_query($server,"SELECT * FROM cashbook_roles");
        $dats =[];
        while($r = $query->fetch_assoc())
        {
            $dats[] = $r['name'];
        }
        foreach($roles as $key => $role)
        {
            if(!in_array($key,$dats))
            {
                prepared_statements($sql,'ss',[$key,$role]);
            }
        }
    }

    function cashbook_stocks()
    {
        $table ='cashbook_stocks';
        $columns =[
            'book_id'=>'int(11) null',
            'item_id'=>'int(11) null',
            'suplier_id'=>'int(11) null',
            'transaction_type'=> "ENUM('stock_in','stock_out') NOT NULL",
            'quantity_in'        => 'DECIMAL(10,2) NOT NULL',
            'quantity_out'        => 'DECIMAL(10,2) NOT NULL',
            'balance'         => 'DECIMAL(10,2) NOT NULL',
            'unit_cost'       => 'DECIMAL(10,2) NULL',
            'total_cost'      => 'DECIMAL(12,2) NULL',
            'reference'       => 'varchar(100) NULL',
            'user_id'         => 'int(11) NULL'
        ];
        create_table($table,$columns);
    }

    function cashbook_item_stock_balances()
    {
        $table ='cashbook_item_stock_balances';
        $columns =[
            'book_id'   =>'int(11) NOT  null',
            'item_id'   =>'int(11) NOT null',
            'balance'  => 'DECIMAL(10,2) NOT NULL',
        ];
        create_table($table,$columns);
    }

    function cashbook_transaction_edits()
    {
        $table ='cashbook_transaction_edits';
        $columns =[
            'book_id'=>'int(11) null',
            'transaction_id'=>'int(11) null',
            'user_id'=>'int(11) null',
            'edit_type'=> "ENUM('edit','delete') NOT NULL",
            'previous_data'   => 'TEXT NULL',
            'new_data'        => 'TEXT NULL',
            'user_id'         => 'int(11) NULL'
        ];
        create_table($table,$columns);
    }

    function cashbook_customer_ledger()
    {
        $table = 'cashbook_customer_ledger';

        $columns = [
            'customer_id'   => 'int(11) NOT NULL',
            'book_id'       => 'int(11) NOT NULL',
            'item_id'       => 'int(11) NOT NULL',
            'type'          => "enum('cash_sale','payment','credit_sale','invoice','invoice_return','invoice_payment','invoice_edit','borrowing','repayment','creditorInjection') NOT NULL",
            'debit_amount'  => 'decimal(10,2) DEFAULT 0',   // increases what customer owes
            'credit_amount' => 'decimal(10,2) DEFAULT 0',   // reduces what customer owes
            'transaction_id'  => 'int(11) NULL',
            'paymode_id'   => 'int(11) NULL',
            'quantity'   => 'int(11) NULL',
            'details'   => 'text NULL',
            'balance'       => 'decimal(10,2) DEFAULT 0',
            'user_id'       => 'int(11) NULL',
            'invoice_id'       => 'int(11) NULL',
            'invoice_amount'       => 'decimal (10,2) DEFAULT 0'
        ];

        create_table($table, $columns);
    }

    function cashbook_creditor_ledger()
    {
        $table = 'cashbook_creditor_ledger';

        $columns = [
            'creditor_id'   => 'int(11) NOT NULL',
            'book_id'       => 'int(11) NOT NULL',
            'item_id'       => 'int(11) NOT NULL',
            'type'          => "enum('cash_sale','payment','credit_sale','invoice','invoice_return','invoice_payment','invoice_edit','borrowing','repayment','creditorInjection') NOT NULL",
            'debit_amount'  => 'decimal(10,2) DEFAULT 0',   // increases what customer owes
            'credit_amount' => 'decimal(10,2) DEFAULT 0',   // reduces what customer owes
            'transaction_id'  => 'int(11) NULL',
            'paymode_id'   => 'int(11) NULL',
            'quantity'   => 'int(11) NULL',
            'details'   => 'text NULL',
            'balance'       => 'decimal(10,2) DEFAULT 0',
            'user_id'       => 'int(11) NULL',
            'invoice_id'       => 'int(11) NULL',
            'invoice_amount'       => 'decimal (10,2) DEFAULT 0'
        ];

        create_table($table, $columns);
    }

    function cashbook_invoices()
    {
        $table = 'cashbook_invoices';

        $columns = [
            'customer_id'   => 'int(11) NOT NULL',
            'book_id'       => 'int(11) NOT NULL',
            'invoice_no'    => 'VARCHAR(50) NOT NULL',
            'invoice_date'  => 'DATE',
            'status'      => "ENUM('draft','sent','paid') DEFAULT 'draft'",
            'total'       => 'DECIMAL(10,2) DEFAULT 0',
            'paid_amount' => 'DECIMAL(10,2) DEFAULT 0',
            'returned_amount' => 'DECIMAL(10,2) DEFAULT 0',
            'balance'     => 'decimal(10,2) DEFAULT 0',
            'user_id'     => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_invoice_items()
    {
        $table = 'cashbook_invoice_items';

        $columns = [
            'invoice_id'    => 'int(11) NOT NULL',
            'item_id'       => 'int(11) NOT NULL',
            'quantity'      => 'int(11) NOT NULL',
            'unit_price'    => 'decimal(10,2) DEFAULT 0',
            'total'         => 'decimal(10,2) DEFAULT 0',
            'user_id'       => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_invoice_returns()
    {
        $table = 'cashbook_invoice_returns';

        $columns = [
            'invoice_id'    => 'int(11) NOT NULL',
            'customer_id' => 'int(11) NOT NULL',
            'total'         => 'decimal(10,2) DEFAULT 0',
            'user_id'       => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_invoice_return_items()
    {
        $table = 'cashbook_invoice_return_items';

        $columns = [
            'return_id'=> 'varchar(55) null',
            'invoice_id'    => 'int(11) NOT NULL',
            'invoice_item_id' => 'int(11) NOT NULL',
            'item_id'       => 'int(11) NOT NULL',
            'qty_returned'      => 'int(11) NOT NULL',
            'unit_price'    => 'decimal(10,2) DEFAULT 0',
            'total'         => 'decimal(10,2) DEFAULT 0',
            'user_id'       => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_route_managers()
    {
        $table = 'cashbook_route_managers';

        $columns = [
            'book_id'       => 'int(11) NOT NULL',
            'name'=> 'varchar(255) null',
            'contact1'    => 'varchar(255) NOT NULL',
            'contact2'    => 'varchar(255) NOT NULL',
            'nin'           => 'varchar(255) NULL',
            'residence'       => 'varchar(255) NULL',
            'user_id'       => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    // user can have many routes
    function cashbook_manager_route()
    {
        $table = 'cashbook_manager_route';

        $columns = [
            'route_id' => 'int(11) NOT NULL',
            'route_manager_id'  => 'int(11) NOT NULL',
            'user_id' => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_customer_route()
    {
        $table = 'cashbook_customer_route';

        $columns = [
            'route_id'       => 'int(11) NOT NULL',
            'customer_id'  => 'int(11) NOT NULL'
        ];
        create_table($table, $columns);
    }

    // create routes
    function cashbook_routes()
    {
        $table = 'cashbook_routes';

        $columns = [
            'book_id'  => 'int(11) NOT NULL',
            'name'  => 'varchar(255) NOT NULL',
            'details'  => 'varchar(255) NOT NULL',
            'managers'  => 'varchar(255) NULL',
            'user_id'  => 'int(11) NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_customer_items()
    {
        $table = 'cashbook_customer_items';

        $columns = [
            'customer_id' => 'int(11) NOT NULL',
            'item_id'  => 'int(11) NOT NULL'
        ];

        create_table($table, $columns);
    }

    function cashbook_purchases()
    {
        $table = 'cashbook_purchases';

        $columns = [
            'item_id' => 'int(11) NOT NULL',
            'quantity'  => 'decimal(10,2) NOT NULL',
            'unit_price'  => 'decimal(10,2) NOT NULL',
            'total'  => 'decimal(10,2) NOT NULL',
            'book_id'  => 'int(11) NOT NULL',
            'suplier_id'  => 'int(11) NULL',
            'creditor_id'  => 'int(11) NULL',
            'user_id'  => 'int(11) NOT NULL',
            'transaction_id'  => 'int(11) NULL',
            'type'  => 'varchar(255) null'

        ];

        create_table($table, $columns);
    }

    // supliers
    function cashbook_supliers()
    {
        $table = 'cashbook_supliers';
        $columns = [
            'name' => 'varchar(255) NOT NULL',
            'contact'  => 'varchar(255) NULL',
            'address'  => 'varchar(255) NULL',
            'book_id'  => 'int(11) NOT NULL',
            'user_id'  => 'int(11) NOT NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_customer_balances()
    {
        $table = 'cashbook_customer_balances';
        $columns = [
            'customer_id' => 'int(11) NOT NULL',
            'balance'  => 'decimal(10,2) NOT NULL',
            'date'  => 'date NOT NULL',
            'book_id'  => 'int(11) NOT NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_creditor_balances()
    {
        $table = 'cashbook_creditor_balances';
        $columns = [
            'creditor_id' => 'int(11) NOT NULL',
            'balance'  => 'decimal(10,2) NOT NULL',
            'date'  => 'date NOT NULL',
            'book_id'  => 'int(11) NOT NULL'
        ];
        create_table($table, $columns);
    }

    function cashbook_stocktakes()
    {
        $table = 'cashbook_stocktakes';
        $columns = [
            'book_id'     => 'INT(11) NOT NULL',
            'period_month'=> 'CHAR(7) NOT NULL',
            'count_date'  => 'DATE NOT NULL',
            'status'      => "ENUM('draft','counting','review','approved','posted','cancelled') NOT NULL DEFAULT 'draft'",
            'notes'       => 'TEXT NULL',
            'created_by'  => 'INT(11) NOT NULL',
            'approved_by' => 'INT(11) NULL',
            'posted_by'   => 'INT(11) NULL',
            'approved_at' => 'DATETIME NULL',
            'posted_at'   => 'DATETIME NULL'
        ];
        create_Table($table, $columns);
    }

    function cashbook_stocktake_items()
    {
        $table = 'cashbook_stocktake_items';
        $columns = [
            'stocktake_id'=> 'INT(11) NOT NULL',
            'book_id'     => 'INT(11) NOT NULL',
            'item_id'     => 'INT(11) NOT NULL',
            'expected_qty'=> 'DECIMAL(18,3) NOT NULL DEFAULT 0',
            'counted_qty' => 'DECIMAL(18,3) NULL',
            'unit_cost'   => 'DECIMAL(18,4) NULL',
            'reason'      => 'VARCHAR(255) NULL',
            'counted_by'  => 'INT(11) NULL',
            'counted_at'  => 'DATETIME NULL'
        ];
        create_Table($table, $columns);
    }

    function cashbook_stock_adjustments()
    {
        $table = 'cashbook_stock_adjustments';
        $columns = [
            'stocktake_item_id' => 'INT(11) NOT NULL',
            'stocktake_id'      => 'INT(11) NOT NULL',
            'book_id'           => 'INT(11) NOT NULL',
            'item_id'           => 'INT(11) NOT NULL',
            'variance_qty'      => 'DECIMAL(18,3) NOT NULL',
            'unit_cost'         => 'DECIMAL(18,4) NULL',
            'stock_movement_id' => 'INT(11) NULL',
            'posted_by'         => 'INT(11) NOT NULL',
            'posted_at'         => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ];
        create_Table($table, $columns);
    }

    function cashbook_stock_periods()
    {
        $table = 'cashbook_stock_periods';
        $columns = [
            'book_id'            => 'INT(11) NOT NULL',
            'period_month'       => 'CHAR(7) NOT NULL',
            'stocktake_id'       => 'INT(11) NULL',
            'closing_stock_value'=> 'DECIMAL(18,2) NULL',
            'valuation_complete' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'closed_at'          => 'DATETIME NULL'
        ];
        create_Table($table, $columns);
    }
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

    function cashbook_role_user()
{
    $table = 'cashbook_role_user';
    $columns = [
        'user_id'     => 'INT(11) NOT NULL',
        'role_id'     => 'INT(11) NOT NULL',
        'business_id' => 'INT(11) NOT NULL DEFAULT 0', // 0 = system-wide role
        'assigned_by' => 'INT(11) NOT NULL',
        'assigned_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ];

    create_Table($table, $columns);
}

function cashbook_role_audit()
{
    $table = 'cashbook_role_audit';
    $columns = [
        'actor_user_id'  => 'INT(11) NOT NULL',
        'target_user_id' => 'INT(11) NOT NULL',
        'role_id'        => 'INT(11) NOT NULL',
        'business_id'    => 'INT(11) NOT NULL DEFAULT 0',
        'action'         => 'VARCHAR(16) NOT NULL',
        'reason'         => 'VARCHAR(255) NULL'
    ];

    create_Table($table, $columns);
}


    // ============================================================
    // ROLE-BASED ACCESS CONTROL (RBAC)
    // ============================================================

    function cashbook_permissions()
    {
        create_Table('cashbook_permissions', [
            'permission_key' => 'VARCHAR(100) NOT NULL UNIQUE',
            'module'         => 'VARCHAR(60) NOT NULL',
            'description'    => 'VARCHAR(200) NOT NULL'
        ]);
    }

    function cashbook_role_permissions()
    {
        create_Table('cashbook_role_permissions', [
            'role_id'       => 'INT(11) NOT NULL',
            'permission_id' => 'INT(11) NOT NULL'
        ]);
    }

    /** Add a missing index without altering existing role assignments. */
    function cashbookRbacEnsureIndex($table, $index, $definition)
    {
        global $server, $db_name;

        $check = $server->prepare('SELECT COUNT(*) AS total FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?');
        if (!$check) {
            throw new RuntimeException('Could not check RBAC index.');
        }
        $check->bind_param('sss', $db_name, $table, $index);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();

        if ((int) $result['total'] === 0) {
            if (!$server->query("ALTER TABLE `$table` ADD $definition")) {
                throw new RuntimeException('Could not create RBAC index ' . $index);
            }
        }
    }

    /** Create permission tables and seed permission definitions (not role grants). */
    function createCashbookRbacTables()
    {
        global $server;

        cashbook_permissions();
        cashbook_role_permissions();

        cashbookRbacEnsureIndex('cashbook_role_permissions', 'role_permission',
            'UNIQUE KEY `role_permission` (`role_id`, `permission_id`)');
        cashbookRbacEnsureIndex('cashbook_role_permissions', 'idx_permission',
            'INDEX `idx_permission` (`permission_id`)');

        // Reuse the permission catalog in assets/rbac.php when it is loaded.
        if (!function_exists('khPermissionCatalog')) {
            return;
        }

        $statement = $server->prepare('INSERT INTO cashbook_permissions
            (permission_key, module, description) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE module = VALUES(module), description = VALUES(description)');
        if (!$statement) {
            throw new RuntimeException('Could not prepare RBAC permission seed.');
        }

        foreach (khPermissionCatalog() as $key => $description) {
            $module = explode('.', $key, 2)[0];
            $statement->bind_param('sss', $key, $module, $description);
            if (!$statement->execute()) {
                throw new RuntimeException('Could not seed RBAC permission: ' . $key);
            }
        }
        $statement->close();
    }

    /*
|--------------------------------------------------------------------------
| CREATE CUSTOMER TRANSACTION TRANSFERS TABLE
|--------------------------------------------------------------------------
| Stores the history of transactions transferred between customers.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| CREATE CUSTOMER TRANSACTION TRANSFERS TABLE
|--------------------------------------------------------------------------
| Records transfers of transactions between customers.
| Maintains an audit trail for accountability.
|--------------------------------------------------------------------------
*/

function createCustomerTransfersTable()
{
    global $server;

    $table = "cashbook_customer_transfers";

    $columns = [
        'book_id'          => 'INT NOT NULL',
        'transaction_id'   => 'INT NOT NULL',
        'from_customer_id' => 'INT NOT NULL',
        'to_customer_id'   => 'INT NOT NULL',
        'transferred_by'   => 'INT NOT NULL',
        'reason'           => 'VARCHAR(500) NOT NULL'
    ];

    // Create table or update missing columns.
    create_Table($table, $columns);

    /*
    |--------------------------------------------------------------------------
    | CREATE INDEXES IF THEY DO NOT EXIST
    |--------------------------------------------------------------------------
    */

    $indexes = [
        'idx_transaction' => ['transaction_id'],
        'idx_book_date'   => ['book_id', 'created_at']
    ];

    foreach ($indexes as $indexName => $indexColumns)
    {
        $check = prepared_statements(
            "SELECT INDEX_NAME
             FROM information_schema.statistics
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1",
            'ss',
            [$table, $indexName]
        );

        if ($check->num_rows > 0) {
            continue;
        }

        $columnsSql = implode(
            ', ',
            array_map(
                static function ($column) {
                    return "`{$column}`";
                },
                $indexColumns
            )
        );

        $sql = "ALTER TABLE `{$table}`
                ADD INDEX `{$indexName}` ({$columnsSql})";

        if (!$server->query($sql)) {
            throw new RuntimeException(
                "Failed to create index {$indexName}: " . $server->error
            );
        }
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| KASHHUB - INVOICE SHARING
|--------------------------------------------------------------------------
| Stores secure invoice-sharing tokens for WhatsApp and other sharing
| methods. Links can expire or be revoked.
|--------------------------------------------------------------------------
*/
function cashbook_invoice_shares()
{
    $table = 'cashbook_invoice_shares';
    $columns = [
        'invoice_id' => 'INT(11) NOT NULL',
        'book_id' => 'INT(11) NOT NULL',
        'token_hash' => 'CHAR(64) NOT NULL',
        'created_by' => 'INT(11) NOT NULL',
        'expires_at' => 'DATETIME NOT NULL',
        'revoked_at' => 'DATETIME NULL'
    ];

    create_Table($table, $columns);
}


    // ============================================================
    // BUSINESS TYPES, MODULE CATALOG AND FEATURE REQUESTS
    // Converted from schema.sql. Run through explicit CLI maintenance.
    // ============================================================

    /** Add a named index only when absent. Unique indexes need clean data first. */
    function cashbookFeatureEnsureIndex($table, $index, $definition)
    {
        global $server, $db_name;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $index)) {
            throw new InvalidArgumentException('Invalid index identifier');
        }
        $stmt = $server->prepare('SELECT COUNT(*) AS total FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=?');
        $stmt->bind_param('sss', $db_name, $table, $index);
        $stmt->execute();
        $exists = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        if (!$exists && !$server->query("ALTER TABLE `$table` ADD $definition")) {
            throw new RuntimeException("Cannot create index $index on $table: " . $server->error);
        }
    }

    function cashbook_business_types()
    {
        create_Table('cashbook_business_types', [
            'type_key' => 'VARCHAR(60) NOT NULL',
            'name' => 'VARCHAR(120) NOT NULL',
            'description' => 'VARCHAR(255) NULL',
            'active' => 'TINYINT(1) NOT NULL DEFAULT 1'
        ]);
        cashbookFeatureEnsureIndex('cashbook_business_types', 'uq_business_type_key',
            'UNIQUE KEY `uq_business_type_key` (`type_key`)');
    }

    function cashbook_modules()
    {
        create_Table('cashbook_modules', [
            'module_key' => 'VARCHAR(60) NOT NULL',
            'name' => 'VARCHAR(120) NOT NULL',
            'description' => 'VARCHAR(255) NULL',
            'is_core' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'active' => 'TINYINT(1) NOT NULL DEFAULT 1'
        ]);
        cashbookFeatureEnsureIndex('cashbook_modules', 'uq_module_key',
            'UNIQUE KEY `uq_module_key` (`module_key`)');
    }

    function cashbook_business_type_modules()
    {
        create_Table('cashbook_business_type_modules', [
            'business_type_id' => 'INT NOT NULL',
            'module_key' => 'VARCHAR(60) NOT NULL',
            'enabled' => 'TINYINT(1) NOT NULL DEFAULT 1'
        ]);
        // Existing create_Table() always supplies an auto-increment id.
        // A composite UNIQUE key preserves the source schema's pair uniqueness.
        cashbookFeatureEnsureIndex('cashbook_business_type_modules', 'uq_business_type_module',
            'UNIQUE KEY `uq_business_type_module` (`business_type_id`, `module_key`)');
    }

    function cashbook_business_module_overrides()
    {
        create_Table('cashbook_business_module_overrides', [
            'business_id' => 'INT NOT NULL',
            'module_key' => 'VARCHAR(60) NOT NULL',
            'enabled' => 'TINYINT(1) NOT NULL',
            'approved_by' => 'INT NOT NULL'
        ]);
        cashbookFeatureEnsureIndex('cashbook_business_module_overrides', 'uq_business_module_override',
            'UNIQUE KEY `uq_business_module_override` (`business_id`, `module_key`)');
    }

    function cashbook_business_module_requests()
    {
        create_Table('cashbook_business_module_requests', [
            'business_id' => 'INT NOT NULL',
            'module_key' => 'VARCHAR(60) NOT NULL',
            'reason' => 'TEXT NULL',
            'status' => "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
            'requested_by' => 'INT NOT NULL',
            'reviewed_by' => 'INT NULL',
            'review_note' => 'TEXT NULL',
            'reviewed_at' => 'DATETIME NULL'
        ]);
        cashbookFeatureEnsureIndex('cashbook_business_module_requests', 'idx_business_status',
            'INDEX `idx_business_status` (`business_id`, `status`)');
    }

    function cashbook_feature_audit()
    {
        // Match schema.sql BIGINT primary key for the audit trail.
        create_Table('cashbook_feature_audit', [
            'actor_id' => 'INT NOT NULL',
            'business_id' => 'INT NOT NULL DEFAULT 0',
            'action' => 'VARCHAR(80) NOT NULL',
            'details' => 'TEXT NULL'
        ], 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        cashbookFeatureEnsureIndex('cashbook_feature_audit', 'idx_feature_audit_business',
            'INDEX `idx_feature_audit_business` (`business_id`, `created_at`)');
    }

    /** Seed only the catalog entries provided in schema.sql; never grant access here. */
    function seedCashbookBusinessModules()
    {
        global $server;
        $types = [
            ['general', 'General Business'], ['retail', 'Retail Shop'],
            ['distribution', 'Wholesale & Distribution'], ['restaurant', 'Restaurant'],
            ['services', 'Professional Services'], ['hardware', 'Hardware Store']
        ];
        $stmt = $server->prepare('INSERT IGNORE INTO cashbook_business_types (type_key, name) VALUES (?, ?)');
        foreach ($types as $row) {
            $stmt->bind_param('ss', $row[0], $row[1]);
            if (!$stmt->execute()) throw new RuntimeException('Business type seed failed: ' . $stmt->error);
        }
        $stmt->close();

        $modules = [
            ['cashbook', 'Cashbook', 1], ['reports', 'Reports', 1],
            ['customers', 'Customers', 0], ['routes', 'Routes & Distribution', 0],
            ['stock', 'Stock Management', 0], ['creditors', 'Creditors & Suppliers', 0],
            ['invoices', 'Sales & Invoicing', 0]
        ];
        $stmt = $server->prepare('INSERT IGNORE INTO cashbook_modules (module_key, name, is_core) VALUES (?, ?, ?)');
        foreach ($modules as $row) {
            $stmt->bind_param('ssi', $row[0], $row[1], $row[2]);
            if (!$stmt->execute()) throw new RuntimeException('Module seed failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    function createCashbookBusinessFeatureTables()
    {
        cashbook_business_types();
        cashbook_modules();
        cashbook_business_type_modules();
        cashbook_business_module_overrides();
        cashbook_business_module_requests();
        cashbook_feature_audit();
        // Add nullable column to existing profile; preserve all existing profiles.
        cashbook_business_profile();
        seedCashbookBusinessModules();
    }

    /** Run parent tables before child tables. */
    function createCashbookStocktakeTables()
    {
        cashbook_stocktakes();
        cashbook_stocktake_items();
        cashbook_stock_adjustments();
        cashbook_stock_periods();
        cashbook_stocktake_deletion_audit();
        cashbook_role_audit();
        cashbook_role_user();
    }

    // Explicit CLI maintenance only: php db_tables.php --migrate
    // No .env file or operating-system environment variable is required.
    if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)
        && in_array('--migrate', $argv ?? [], true)) {
        cashbook_invoice_shares();
        createCustomerTransfersTable();
        createCashbookStocktakeTables();
        cashbook_creditor_ledger();
        cashbook_creditor_balances();
        cashbook_creditors();
        cashbook_item_stock_balances();
        cashbook_customer_items();
        cashbook_purchases();
        cashbook_supliers();
        cashbook_customer_balances();
    
        // functions to create
        cashbook_books();
        cashbook_users();
        cashbook_book_users();
        cashbook_cashins();
        cashbook_cashouts();
        cashbook_categories();
        transactions();
        cashbook_items();
        cashbook_paymodes();
        cashbook_customers();
        cashbook_business_profile();
        createCashbookBusinessFeatureTables();
        cashbook_roles();
        createCashbookRbacTables();
        cashbook_stocks();
        cashbook_transaction_edits();
        cashbook_customer_ledger();
        cashbook_invoices();
        cashbook_invoice_items();
        cashbook_invoice_returns();
        cashbook_invoice_return_items();
        cashbook_route_managers();
        cashbook_manager_route();
        cashbook_customer_route();
        cashbook_routes();
        cashbook_stocktake_excluded_items();
    }

// CLI execution must never attempt browser redirects.
if (PHP_SAPI === 'cli') {
    return;
}

// redirect to the home page after checking table creation functions
if(isVerified())
{
    if(checkBusinessProfile())
    {
        // if(hasRole(['owner','partner']))
        // {
        //     redirect('../dashboard/');
        // }else{
        //     redirect('../home/');
        // }
        redirect('../home/');
    }else{
        redirect('../profile/');
    }
    
}else{
    redirect('../login/');
}

?>