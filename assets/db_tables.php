<?php
    session_start();
    require(__dir__.'/functions.php');
    function create_Table($table, array $columns)
    {
            global $server, $db_name,$foreignKeys;
            $query = $server->prepare("SELECT * 
                FROM information_schema.tables 
                WHERE table_schema = ? 
                AND table_name = ?");
            $query->bind_param('ss', $db_name, $table);
            $query->execute();
            $res = $query->get_result();

            $texts = [];
            $edits = [];

            if ($res->num_rows == 0) {
                // TABLE DOES NOT EXIST → CREATE IT
                $sql = "CREATE TABLE IF NOT EXISTS `$table` (";
                $texts[] = "`id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT";

                foreach ($columns as $k => $v) {
                    $texts[] = "`{$k}` {$v}";
                }

                // timestamps
                $texts[] = "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                $texts[] = "`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

                // add foreign keys
                if(isset($foreignKeys) && !empty($foreignKeys))
                {
                    foreach ($foreignKeys as $fkName => $fk) {
                        // expected: ['column' => 'survey_id', 'ref_table' => 'options', 'ref_column' => 'option_id']
                        $texts[] = "CONSTRAINT `$fkName` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['ref_table']}`(`{$fk['ref_column']}`) ON DELETE CASCADE ON UPDATE CASCADE";
                    }
                }

                $tableStructure = $sql . implode(', ', $texts) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                if (!mysqli_query($server, $tableStructure)) {
                    echo mysqli_error($server);
                }
            } else {
                // TABLE EXISTS → check for missing columns & foreign keys

                foreach ($columns as $k => $v) {
                    $checkCol = $server->prepare("SELECT * 
                        FROM information_schema.columns 
                        WHERE table_schema = ? AND table_name = ? AND column_name = ?");
                    $checkCol->bind_param('sss', $db_name, $table, $k);
                    $checkCol->execute();
                    $colRes = $checkCol->get_result();
                    if ($colRes->num_rows == 0) {
                        $edits[] = "ADD COLUMN `$k` $v";
                    }
                }

                // foreign key checks
                if(isset($foreignKeys) && !empty($foreignKeys))
                {
                    foreach ($foreignKeys as $fkName => $fk) {
                        $checkFk = $server->prepare("SELECT * 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE table_schema = ? AND table_name = ? AND constraint_name = ?");
                        $checkFk->bind_param('sss', $db_name, $table, $fkName);
                        $checkFk->execute();
                        $fkRes = $checkFk->get_result();
                        if ($fkRes->num_rows == 0) {
                            $edits[] = "ADD CONSTRAINT `$fkName` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['ref_table']}`(`{$fk['ref_column']}`) ON DELETE CASCADE ON UPDATE CASCADE";
                        }
                    }
                }

                if (!empty($edits)) {
                    $tableStructure = "ALTER TABLE `$table` " . implode(', ', $edits);
                    if (!mysqli_query($server, $tableStructure)) {
                        echo mysqli_error($server);
                    }
                }
            }
    }

    // create tables
    function cashbook_books()
    {
        $table ='cashbook_books';
        $columns =[
            'name'=>'varchar(255) null',
            'business_id'=>'int(11) null',
            'Details'=>'varchar(255) null',
            'user_id'=>'int(11) null'
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
            'amount'=>'float null',
            'details'=>'varchar(255) null',
            'transaction_id'=>'int(11) null',
            'user_id'=>'int(11) null'
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
            'amount'=>'float null',
            'details'=>'varchar(255) null',
            'transaction_id'=>'int(11) null',
            'user_id'=>'int(11) null'
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
            'credit_amount'=>'float null',
            'debit_amount'=>'float null',
            'details'=>'varchar(255) null',
            'balance'=>'float null',
            'user_id'=>'int(11) null'
        ];
        create_table($table,$columns);
    }

    function cashbook_items()
    {
        $table ='cashbook_items';
        $columns =[
            'name'=>'varchar(255) null',
            'book_id'=>'int(11) null',
            'Details'=>'varchar(255) null',
            'user_id'=>'int(11) null'
        ];
        create_table($table,$columns);
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
            'user_id'=>'int(11) null'
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
            'staff'=>'STAFF'
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
    cashbook_roles();

// redirect to the home page after checking table creation functions
if(isVerified())
{
    if(checkBusinessProfile())
    {
        redirect('../home/');
    }else{
        redirect('../profile/');
    }
    
}else{
    redirect('../login/');
}

?>