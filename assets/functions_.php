<?php
    session_start();
    require(__dir__.'/db_connect.php');
    date_default_timezone_set('Africa/Kampala');
    if(isset($_GET['logout']))
    {
        session_destroy();
    }

    $styleVersion = file_exists(__DIR__ . '/../assets/style.css')
        ? filemtime(__DIR__ . '/../assets/style.css')
        : time();

    $scriptVersion = file_exists(__DIR__ . '/../assets/script.js')
        ? filemtime(__DIR__ . '/../assets/script.js')
        : time();

    
function prepared_statements($stat,$binds='',$vars=[])
{
    global $server;

    if(trim($stat)===''){
        throw new InvalidArgumentException('SQL statement cannot be empty.');
    }

    if(!$server || $server->connect_errno){
        throw new RuntimeException('Database connection is unavailable.');
    }

    $binds = (string)$binds;
    $vars = array_values($vars);

    if(strlen($binds)!==count($vars)){
        throw new InvalidArgumentException(
            'Parameter mismatch: '.strlen($binds).' types for '.count($vars).' values.'
        );
    }

    if($binds!=='' && preg_match('/[^idsb]/',$binds)){
        throw new InvalidArgumentException('Invalid parameter binding type.');
    }

    $stmt = null;

    try{
        $stmt = $server->prepare($stat);

        if(!$stmt){
            throw new RuntimeException('SQL prepare failed: '.$server->error);
        }

        if($binds!==''){
            if(!$stmt->bind_param($binds,...$vars)){
                throw new RuntimeException('Parameter binding failed: '.$stmt->error);
            }
        }

        if(!$stmt->execute()){
            throw new RuntimeException('SQL execution failed: '.$stmt->error);
        }

        $query = ltrim($stat);

        // Remove leading SQL comments
        $query = preg_replace(
            '/\A(?:(?:--[^\r\n]*|#[^\r\n]*)(?:\r?\n|$)|\/\*.*?\*\/\s*)*/s',
            '',
            $query
        );

        $queryType = strtoupper(strtok(ltrim($query)," \t\r\n"));

        if(in_array($queryType,['SELECT','SHOW','DESCRIBE','EXPLAIN'],true)){
            $result = $stmt->get_result();

            if($result===false){
                throw new RuntimeException('Unable to retrieve query results: '.$stmt->error);
            }

            return $result;
        }

        if($queryType==='INSERT' || $queryType==='REPLACE'){
            return (int)$stmt->insert_id;
        }

        // Successful UPDATE / DELETE / other write statements
        return true;

    }catch(Throwable $e){
        error_log('prepared_statements error: '.$e->getMessage());
        throw $e;
    }finally{
        if($stmt instanceof mysqli_stmt){
            $stmt->close();
        }
    }
}


    function redirect($link)
    {
        header('location:'.$link);
    }

    function back()
	{
		$previousUrl = $_SERVER['HTTP_REFERER'];
		return $previousUrl;
	}

    function request($key,$default=null)
    {
        if(!is_string($key) || $key==='') return $default;

        $value=$_POST[$key] ?? $_GET[$key] ?? $default;

        if(is_string($value)) return trim($value);

        return $value;
    }

    function e($var)
    {
        global $server;
        $t = mysqli_real_escape_string($server,$var);
        $t = trim($var);
        return $t;
    }

    function bookFind($id)
    {
        global $server;
        $stmt = "SELECT * FROM cashbook_books WHERE id = ?";
        $data = prepared_statements($stmt,'i',[$id]);
        $row = $data->fetch_assoc();
        return json_decode(json_encode($row));
    }

    function transactionFind($id)
    {
        global $server;
        $stmt = "SELECT ct.*,c.name as category,p.name as paymode,i.name as item_name,cust.name as customer_name,ci.id as invoice_id,ci.invoice_no,cred.name as creditor_name FROM cashbook_transactions ct 
                    LEFT JOIN cashbook_categories c ON c.id = ct.category_id
                    LEFT JOIN cashbook_paymodes p ON p.id = ct.paymode_id
                    LEFT JOIN cashbook_items i ON i.id = ct.item_id
                    LEFT JOIN cashbook_customers cust ON cust.id = ct.customer_id
                    LEFT JOIN cashbook_creditors cred ON cred.id = ct.creditor_id
                    LEFT JOIN cashbook_invoices ci ON ci.id = ct.invoice_id
                WHERE ct.id = ?";
        $data = prepared_statements($stmt,'i',[$id]);
        $row = $data->fetch_assoc();
        return json_decode(json_encode($row));
    }

    function auth()
    {
        $user = $_SESSION['auth'];
        return $user;
    }
    
    function myObject($array)
    {
        return json_decode(json_encode($array));
    }

    function businessFind($owner)
    {
        global $server;
        $stmt = "SELECT * FROM cashbook_business_profile WHERE user_id = ?";
        $res = prepared_statements($stmt,'i',[$owner->id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }

    function businessFindId($id)
    {
        global $server;
        $stmt = "SELECT * FROM cashbook_business_profile WHERE id = ?";
        $res = prepared_statements($stmt,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }

    // find customer
    function getCustomer($id)
    {
        global $server;
        $stmt = "SELECT cc.*,cr.name as route,crm.id as route_manager_id,crm.name as route_manager FROM cashbook_customers cc 
                    LEFT JOIN cashbook_routes cr ON cr.id = cc.route_id 
                    LEFT JOIN cashbook_route_managers crm ON crm.id = cc.route_manager_id
                WHERE cc.id = ?";
        $res = prepared_statements($stmt,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }

    // find category
    function categoryFind($id)
    {
        global $server;
        $sql = "SELECT * FROM cashbook_categories WHERE id = ?";
        $res = prepared_statements($sql,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }

    // find category
    function routeFind($id)
    {
        global $server;
        $sql = "SELECT cr.*,crm.name as manager FROM cashbook_routes cr
                    LEFT JOIN cashbook_route_managers crm ON crm.id = cr.managers
                WHERE cr.id = ?";
        $res = prepared_statements($sql,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }


    // find category
    function routeManagerFind($id)
    {
        global $server;
        $sql = "SELECT * FROM cashbook_route_managers WHERE id = ?";
        $res = prepared_statements($sql,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }

    // find category
    function memberFind($id)
    {
        global $server;
        $sql = "SELECT cu.*, cr.name as role_name FROM cashbook_users cu
                    LEFT JOIN cashbook_roles cr ON cr.id = cu.role_id
                WHERE cu.id = ?";
        $res = prepared_statements($sql,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
    }

    function customerAttachItem($customer_id,$item_id)
    {
        global $server;
        $sql = "INSERT INTO cashbook_customer_items SET customer_id = ?, item_id = ?";
        prepared_statements($sql,'ii',[$customer_id,$item_id]);
    }

    function customerDettachItem($customer_id,$item_id)
    {
        global $server;
        $sql = "DELETE FROM cashbook_customer_items WHERE customer_id = ? AND item_id = ?";
        prepared_statements($sql,'ii',[$customer_id,$item_id]);
    }

    // find customer items
    function customerItems($customer_id)
    {
        global $server;
        $sql = "SELECT cci.*,ci.name as item_name FROM cashbook_customer_items cci 
                    LEFT JOIN cashbook_items ci ON ci.id = cci.item_id
                WHERE cci.customer_id = ?";
        $res = prepared_statements($sql,'i',[$customer_id]);
        return $res;
    }

    // remove invoice item
    function removeInvoiceItem($id)
    {
        global $server;
        $sql = "DELETE FROM cashbook_invoice_items WHERE id = ?";
        if(prepared_statements($sql,'i',[$id]))
        {
            return true;
        }
        return false;
    }

    // encryptor function
    function encryptor($action, $string) 
	{
        $output = false;
        $encrypt_method = "AES-256-CBC";
        //pls set your unique hashing key
        $secret_key = 'raytechnologies';
        $secret_iv = 'raytech2025';
    
        // hash
        $key = hash('sha1', $secret_key);
    
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha1', $secret_iv), 0, 16);
    
        //do the encyption given text/string/number
        if( $action == 'encrypt' ) {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        }
        else if( $action == 'decrypt' ){
            //decrypt the given text/string/number
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }

    // auth functions
    function isVerified()
    {
        if(isset($_SESSION['verified']) && !empty($_SESSION['verified']))
        {
            return true;
        }else{
            return false;
        }
    }

    // check business profile on Login
    function checkBusinessProfile()
    {
    global $server;
    $user = auth();
    $sql = "SELECT * FROM cashbook_business_profile WHERE user_id = ?";
    $res = prepared_statements($sql,'i',[$user->id]);

    // check if beongs to a business
    $stmt = "SELECT business_id FROM cashbook_users WHERE id = ?";
    $ress = prepared_statements($stmt,'i',[$user->id]);
    $r = $ress->fetch_assoc();

    if($res->num_rows > 0 || !empty($r['business_id']) > 0)
    {
        $_SESSION['hasbusiness'] = true;
        return true;
    }else{
        return false;
    }
    }
    // check roles
    // function hasRole($array)
    // {
    //     global $server;
    //     $auth = auth();
    //     $roles = mysqli_query($server,"SELECT * FROM cashbook_roles");
    //     $dat =[];
    //     print_r($auth);

    //     while($r = $roles->fetch_assoc())
    //     {
    //         if($r['id'] == $auth->role_id)
    //         {
    //             $dat[] = $r['name'];
    //         }
    //     }

    //     foreach($array as $role)
    //     {
    //         if(in_array($role,$dat))
    //         {
    //             return true;
    //         }else{
    //             return false;
    //         }
    //     }
    // }
    function hasRole($array)
    {
        global $server;

        $auth = auth();

        $query = mysqli_query($server, "
            SELECT name 
            FROM cashbook_roles 
            WHERE id = '{$auth->role_id}'
            LIMIT 1
        ");

        if(!$query || mysqli_num_rows($query) == 0)
        {
            return false;
        }

        $role = mysqli_fetch_assoc($query);
        return in_array($role['name'], $array);
    }

    // display session message
    function sessionMessage()
    {
        if(isset($_SESSION['success']))
        {
            ?>
                <div class="p-2 success-message" style='position:absolute;background: green !important;
                            color: white !important;padding: 15px;z-index: 99999;'>
                    <i class="fa fa-check-circle"></i> <?php echo $_SESSION['success'];?>
                </div>
            <?php
            unset($_SESSION['success']);
        }
        if(isset($_SESSION['error']))
        {
            ?>
                <div class="p-2 shadow success-message" style='position:absolute;background: red !important;
                            color: white !important;padding: 15px;z-index: 99999;'>
                    <i class="fa fa-times-circle text-dark"></i> <?php echo $_SESSION['error'];?>
                </div>
            <?php
                unset($_SESSION['error']);
        }
    }

    // find item
    function itemFind($id)
    {
        global $server;
        $sql = "SELECT * FROM cashbook_items WHERE id =?";
        $res = prepared_statements($sql,'i',[$id]);
        $rw = $res->fetch_assoc();
        return myObject($rw);
    }

    function fetchBusiness()
    {
        global $server;
        // $business = 
    }

    // track transaction edits
    function trackTransactionEdits($transaction_id,$type)
    {
        // fetch transaction data before edit
        $sql_fetch = "SELECT * FROM cashbook_transactions WHERE id = ?";
        $res_fetch = prepared_statements($sql_fetch, 'i', [$transaction_id]);
        $row = $res_fetch->fetch_assoc();
        $old_value = json_encode($row);
        $user_id = auth()->id;
        $book_id = $row['book_id'];
        
        $sql = "INSERT INTO cashbook_transaction_edits (book_id,transaction_id, user_id, previous_data, edit_type) VALUES (?, ?, ?, ?, ?)";
        prepared_statements($sql, 'iiiss', [$book_id,$transaction_id, $user_id, $old_value,$type]);
    }

    // customer ledger update function
    function customerLedgerUpdate($customer_id, $credit, $debit, $category_id, $details, $book_id, $payment_mode, $trans_id, $date, $user_id, $item_id, $qty, $type = false)
    {
        global $server;

        // 1. Normalize inputs.
        $customer_id = (int) $customer_id;
        $book_id = (int) $book_id;
        $trans_id = (int) $trans_id;
        $user_id = (int) $user_id;
        $item_id = (int) $item_id;
        $category_id = (int) $category_id;
        $payment_mode = (int) $payment_mode;
        $qty = (float) $qty;
        $credit = round((float) $credit, 2);
        $debit = round((float) $debit, 2);
        $details = (string) $details;
        $type = (string) $type;

        if ($customer_id <= 0 || $book_id <= 0 || $trans_id <= 0) {
            throw new Exception('Invalid customer ledger transaction');
        }
        if ($credit < 0 || $debit < 0 || $qty < 0) {
            throw new Exception('Customer ledger amounts and quantity cannot be negative');
        }
        if (trim((string) $date) === '') {
            throw new Exception('Transaction date is required');
        }

        // 2. Normalize date; retain the supplied time if present.
        $timestamp = strtotime((string) $date);
        if ($timestamp === false) {
            throw new Exception('Invalid transaction date');
        }
        $date = date('Y-m-d H:i:s', $timestamp);

        // 3. Verify that the customer belongs to this business book.
        $customer = prepared_statements(
            'SELECT id FROM cashbook_customers WHERE id=? AND book_id=? LIMIT 1',
            'ii',
            [$customer_id, $book_id]
        );
        if ($customer === false || $customer->num_rows === 0) {
            throw new Exception('Customer does not belong to this business book');
        }

        // 4. Find an existing ledger entry for this transaction.
        $check = prepared_statements(
            'SELECT id, customer_id FROM cashbook_customer_ledger WHERE transaction_id=? AND book_id=? LIMIT 1',
            'ii',
            [$trans_id, $book_id]
        );
        if ($check === false) {
            throw new Exception('Failed to check customer ledger');
        }

        $old_customer_id = 0;

        if ($check->num_rows > 0) {
            // 5A. Update an existing ledger entry, including its transaction date.
            $existing = $check->fetch_assoc();
            $old_customer_id = (int) $existing['customer_id'];

            $sql = 'UPDATE cashbook_customer_ledger SET
                        customer_id=?, type=?, credit_amount=?, debit_amount=?,
                        details=?, paymode_id=NULLIF(?,0), created_at=?,
                        user_id=?, item_id=?, quantity=?
                    WHERE id=? AND book_id=?';

            $result = prepared_statements(
                $sql,
                'isddsisiidii', // 12 types for 12 placeholders and values.
                [
                    $customer_id,
                    $type,
                    $credit,
                    $debit,
                    $details,
                    $payment_mode,
                    $date,
                    $user_id,
                    $item_id,
                    $qty,
                    (int) $existing['id'],
                    $book_id
                ]
            );
        } else {
            // 5B. Insert a new ledger entry.
            $sql = 'INSERT INTO cashbook_customer_ledger SET
                        type=?, customer_id=?, credit_amount=?, debit_amount=?,
                        details=?, book_id=?, paymode_id=NULLIF(?,0),
                        transaction_id=?, created_at=?, user_id=?, item_id=?, quantity=?';

            $result = prepared_statements(
                $sql,
                'siddsiiisiid', // 12 types for 12 placeholders and values.
                [
                    $type,
                    $customer_id,
                    $credit,
                    $debit,
                    $details,
                    $book_id,
                    $payment_mode,
                    $trans_id,
                    $date,
                    $user_id,
                    $item_id,
                    $qty
                ]
            );
        }

        if ($result === false) {
            throw new Exception('Failed to save customer ledger');
        }

        // 6. Rebuild the previous customer's ledger after reassignment.
        if ($old_customer_id > 0 && $old_customer_id !== $customer_id) {
            if (rebuildCustomerLedgerBalances($old_customer_id, $book_id) === false) {
                throw new Exception('Failed to rebuild previous customer balance');
            }
        }

        // 7. Rebuild the current customer's running balances.
        if (rebuildCustomerLedgerBalances($customer_id, $book_id) === false) {
            throw new Exception('Failed to rebuild customer ledger balances');
        }

        return true;
    }

    // customer ledger update function
    function creditorLedgerUpdate(
        $creditor_id,
        $credit,
        $debit,
        $category_id,
        $details,
        $book_id,
        $payment_mode,
        $trans_id,
        $date,
        $user_id,
        $item_id,
        $qty,
        $type = false
    )
    {
        $creditor_id = (int)$creditor_id;
        $credit      = (float)$credit;
        $debit       = (float)$debit;
        $trans_id    = (int)$trans_id;

        if($creditor_id <= 0 || $trans_id <= 0)
        {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING LEDGER TRANSACTION
        |--------------------------------------------------------------------------
        */

        $check_sql = "
            SELECT *
            FROM cashbook_creditor_ledger
            WHERE transaction_id = ?
            LIMIT 1
        ";

        $check_res = prepared_statements(
            $check_sql,
            'i',
            [$trans_id]
        );


        if($check_res && $check_res->num_rows > 0)
        {
            $oldLedger = $check_res->fetch_assoc();

            $old_creditor_id = (int)$oldLedger['creditor_id'];


            /*
            |--------------------------------------------------------------------------
            | SAME CREDITOR
            |--------------------------------------------------------------------------
            */

            if($old_creditor_id === $creditor_id)
            {
                /*
                * Get balance immediately before this transaction.
                */
                $balance = getCreditorBalanceBeforeTransaction(
                    $creditor_id,
                    $trans_id
                );

                $newBalance =
                    $balance +
                    $credit -
                    $debit;


                $update_sql = "
                    UPDATE cashbook_creditor_ledger SET
                        type = ?,
                        creditor_id = ?,
                        credit_amount = ?,
                        debit_amount = ?,
                        details = ?,
                        book_id = ?,
                        paymode_id = ?,
                        created_at = ?,
                        user_id = ?,
                        item_id = ?,
                        quantity = ?,
                        balance = ?
                    WHERE transaction_id = ?
                ";

                prepared_statements(
                    $update_sql,
                    'siddsiisiiidi',
                    [
                        $type,
                        $creditor_id,
                        $credit,
                        $debit,
                        $details,
                        $book_id,
                        $payment_mode,
                        $date,
                        $user_id,
                        $item_id,
                        $qty,
                        $newBalance,
                        $trans_id
                    ]
                );


                /*
                * Save current creditor balance.
                */
                saveCreditorBalance(
                    $creditor_id,
                    $newBalance,
                    $date
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CREDITOR CHANGED
            |--------------------------------------------------------------------------
            */

            else
            {
                /*
                * Remove this transaction from the old creditor first.
                */
                $delete_sql = "
                    DELETE FROM cashbook_creditor_ledger
                    WHERE transaction_id = ?
                ";

                prepared_statements(
                    $delete_sql,
                    'i',
                    [$trans_id]
                );


                /*
                * Recalculate old creditor's current balance.
                */
                $oldBalance = getCreditorBalance($old_creditor_id);

                saveCreditorBalance(
                    $old_creditor_id,
                    $oldBalance,
                    $date
                );


                /*
                * Now create the transaction against the newly selected creditor.
                */
                $balance = getCreditorBalance($creditor_id);

                $newBalance =
                    $balance +
                    $credit -
                    $debit;


                $insert_sql = "
                    INSERT INTO cashbook_creditor_ledger SET
                        type = ?,
                        creditor_id = ?,
                        credit_amount = ?,
                        debit_amount = ?,
                        details = ?,
                        book_id = ?,
                        paymode_id = ?,
                        transaction_id = ?,
                        created_at = ?,
                        user_id = ?,
                        item_id = ?,
                        quantity = ?,
                        balance = ?
                ";

                prepared_statements(
                    $insert_sql,
                    'siddsiiisiiid',
                    [
                        $type,
                        $creditor_id,
                        $credit,
                        $debit,
                        $details,
                        $book_id,
                        $payment_mode,
                        $trans_id,
                        $date,
                        $user_id,
                        $item_id,
                        $qty,
                        $newBalance
                    ]
                );


                saveCreditorBalance(
                    $creditor_id,
                    $newBalance,
                    $date
                );
            }


            $_SESSION['success'] = "Creditor transaction updated successfully.";

            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | NEW CREDITOR LEDGER TRANSACTION
        |--------------------------------------------------------------------------
        */

        $balance = getCreditorBalance($creditor_id);

        $newBalance =
            $balance +
            $credit -
            $debit;


        $stmt = "
            INSERT INTO cashbook_creditor_ledger SET
                type = ?,
                creditor_id = ?,
                credit_amount = ?,
                debit_amount = ?,
                details = ?,
                book_id = ?,
                paymode_id = ?,
                transaction_id = ?,
                created_at = ?,
                user_id = ?,
                item_id = ?,
                quantity = ?,
                balance = ?
        ";

        prepared_statements(
            $stmt,
            'siddsiiisiiid',
            [
                $type,
                $creditor_id,
                $credit,
                $debit,
                $details,
                $book_id,
                $payment_mode,
                $trans_id,
                $date,
                $user_id,
                $item_id,
                $qty,
                $newBalance
            ]
        );


        saveCreditorBalance(
            $creditor_id,
            $newBalance,
            $date
        );

        $_SESSION['success'] = "Creditor transaction saved successfully.";

        return true;
    }

    // set book function
    function setBook($book)
    {
        $_SESSION['book_id'] = encryptor('encrypt',$book->id);
    }

    //header and footer functions
    function pageHeader($header = false)
    {
        global $styleVersion;
        /*
        ==========================================================
        DETERMINE SIDEBAR
        ==========================================================
        */

        $hasBookContext =
            isset($_REQUEST['book_id']) ||
            isset($_REQUEST['bkid']) ||
            isset($_REQUEST['bsid']);

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <meta
                name="theme-color"
                content="#198754"
            >

            <meta
                name="mobile-web-app-capable"
                content="yes"
            >

            <meta
                name="apple-mobile-web-app-capable"
                content="yes"
            >

            <meta
                name="apple-mobile-web-app-status-bar-style"
                content="default"
            >

            <meta
                name="apple-mobile-web-app-title"
                content="KashHub"
            >


            <!-- ICONS -->

            <link
                rel="apple-touch-icon"
                href="/cashbook/icons/icon-192.png"
            >

            <link
                rel="apple-touch-icon"
                sizes="180x180"
                href="/cashbook/icons/apple-touch-icon.png"
            >

            <link
                rel="icon"
                type="image/png"
                sizes="32x32"
                href="/cashbook/icons/favicon-32x32.png"
            >

            <link
                rel="icon"
                type="image/png"
                sizes="16x16"
                href="/cashbook/icons/favicon-16x16.png"
            >


            <title>
                <?= $header ? htmlspecialchars($header) . ' | KashHub' : 'KashHub';
                ?>
            </title>


            <link
                rel="manifest"
                href="/cashbook/manifest.json"
            >


            <!-- BOOTSTRAP -->

            <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css"
            >

            <link
                rel="stylesheet"
                href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
            >

            <link
                rel="stylesheet"
                href="../assets/css/bootstrap.min.css"
            >


            <!-- FONT AWESOME -->

            <link
                rel="stylesheet"
                href="../assets/fontawesome-free-5.14.0-web/css/all.css"
            >


            <!-- CUSTOM CSS -->

            <link
                rel="stylesheet"
                href="../assets/css/custom.css"
            >

            <link
                rel="stylesheet"
                href="../assets/style.css?v=<?=$styleVersion;?>"
            >

            <link
                rel="stylesheet"
                href="../assets/css/xdialog.3.4.0.min.css"
            >


            <!-- LINE ICONS -->

            <link
                rel="stylesheet"
                href="https://cdn.lineicons.com/5.1/line/lineicons.css"
            >

            <link
                rel="stylesheet"
                href="https://cdn.lineicons.com/5.1/solid/lineicons-solid.css"
            >


            <!-- DATATABLES -->

            <link
                rel="stylesheet"
                href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css"
            >


            <!-- SELECT2 -->

            <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css"
            >

        </head>


        <body>

            <div class="wrapper">

                <?php

                /*
                ======================================================
                SIDEBAR SELECTION
                ======================================================

                IMPORTANT:

                Book context has priority.

                This allows a superadministrator to enter a book for
                support and still receive the normal book sidebar.

                ======================================================
                */

                // Book takes highest priority.
                if ($hasBookContext) {
                    sideBar();
                }
                // System administration.
                elseif (hasRole(['superadministrator'])) {
                    superAdminSideBar();
                }
                // Normal authenticated business workspace.
                else {
                    businessSideBar();
                }

                ?>
                <div class="main">
                    <!-- =============================================
                        TOP NAVIGATION
                    ============================================== -->

                    <nav class="navbar navbar-expand-lg navbar-dark bg-success">

                        <div class="container-fluid">


                            <!-- BRAND -->

                            <a
                                class="navbar-brand text-white"
                                href="../"
                            >
                                <strong>KashHub</strong>
                            </a>


                            <?php if (!$hasBookContext && hasRole(['superadministrator'])): ?>

                                <span
                                    class="badge badge-light ml-2 d-none d-md-inline"
                                >

                                    <i class="fa fa-shield-alt mr-1"></i>

                                    SYSTEM ADMINISTRATION

                                </span>

                            <?php endif; ?>


                            <div class="ml-auto">


                                <?php if (
                                    isset($_SESSION['auth']) &&
                                    isset($_SESSION['auth']->name)
                                ): ?>


                                    <div class="dropdown">


                                        <button
                                            class="btn btn-sm btn-outline-light dropdown-toggle"
                                            type="button"
                                            id="userDropdown"
                                            data-toggle="dropdown"
                                            aria-haspopup="true"
                                            aria-expanded="false"
                                        >

                                            <i class="fa fa-user-circle mr-1"></i>

                                            <?=htmlspecialchars(
                                                $_SESSION['auth']->name
                                            );?>

                                        </button>


                                        <div
                                            class="dropdown-menu dropdown-menu-right"
                                            aria-labelledby="userDropdown"
                                        >


                                            <div class="px-3 py-2">

                                                <small class="text-muted">
                                                    Signed in as
                                                </small>

                                                <br>

                                                <strong>

                                                    <?=htmlspecialchars(
                                                        $_SESSION['auth']->name
                                                    );?>

                                                </strong>

                                            </div>


                                            <div class="dropdown-divider"></div>


                                            <a
                                                class="dropdown-item"
                                                href="../profile/"
                                            >

                                                <i class="fa fa-user mr-2"></i>

                                                Profile

                                            </a>


                                            <?php if (
                                                hasRole(['superadministrator'])
                                            ): ?>

                                                <a
                                                    class="dropdown-item"
                                                    href="../settings/"
                                                >

                                                    <i class="fa fa-cog mr-2"></i>

                                                    Settings

                                                </a>

                                            <?php endif; ?>


                                            <div class="dropdown-divider"></div>


                                            <a
                                                class="dropdown-item text-danger"
                                                href="../?logout=1"
                                            >

                                                <i class="fa fa-sign-out-alt mr-2"></i>

                                                <strong>Logout</strong>

                                            </a>


                                        </div>

                                    </div>


                                <?php endif; ?>


                            </div>

                        </div>

                    </nav>


                    <?php sessionMessage(); ?>

        <?php
    }

    function pageFooter()
    {
        global $scriptVersion;
        ?>

                </div>
                <!-- END MAIN -->

            </div>
            <!-- END WRAPPER -->


            <!-- =====================================================
                CORE JAVASCRIPT
            ====================================================== -->

            <script src="../assets/js/jquery-3.5.1.min.js"></script>

            <script src="../assets/js/bootstrap.min.js"></script>


            <!-- =====================================================
                CHART JS
            ====================================================== -->

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


            <!-- =====================================================
                DATATABLES
            ====================================================== -->

            <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>


            <!-- =====================================================
                XDIALOG
            ====================================================== -->

            <script src="../assets/js/xdialog.3.4.0.min.js"></script>


            <!-- =====================================================
                SELECT2
            ====================================================== -->

            <script
                src="../assets/js/select2.min.js"
                defer
            ></script>


            <!-- =====================================================
                APPLICATION JS
            ====================================================== -->

            <script src="../assets/js/custom.js"></script>

            <script src="../assets/script.js?v=<?=$scriptVersion;?>"></script>


            <!-- =====================================================
                SERVICE WORKER
            ====================================================== -->

            <script>

                if ('serviceWorker' in navigator) {

                    window.addEventListener(
                        'load',
                        function ()
                        {

                            navigator.serviceWorker
                                .register(
                                    '/cashbook/service-worker.js'
                                )

                                .then(
                                    function (registration)
                                    {

                                        console.log(
                                            'Cashbook Service Worker registered:',
                                            registration.scope
                                        );

                                    }
                                )

                                .catch(
                                    function (error)
                                    {

                                        console.error(
                                            'Cashbook Service Worker registration failed:',
                                            error
                                        );

                                    }
                                );

                        }
                    );

                }

            </script>


        </body>

        </html>

        <?php
    }

    function pageHeaderLevel2($header = false)
    {
        global $styleVersion;
        /*
        ==========================================================
        DETERMINE SIDEBAR
        ==========================================================
        */

        $hasBookContext =
            isset($_REQUEST['book_id']) ||
            isset($_REQUEST['bkid']) ||
            isset($_REQUEST['bsid']);

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <meta
                name="theme-color"
                content="#198754"
            >

            <meta
                name="mobile-web-app-capable"
                content="yes"
            >

            <meta
                name="apple-mobile-web-app-capable"
                content="yes"
            >

            <meta
                name="apple-mobile-web-app-status-bar-style"
                content="default"
            >

            <meta
                name="apple-mobile-web-app-title"
                content="KashHub"
            >


            <!-- ICONS -->

            <link
                rel="apple-touch-icon"
                href="/cashbook/icons/icon-192.png"
            >

            <link
                rel="apple-touch-icon"
                sizes="180x180"
                href="/cashbook/icons/apple-touch-icon.png"
            >

            <link
                rel="icon"
                type="image/png"
                sizes="32x32"
                href="/cashbook/icons/favicon-32x32.png"
            >

            <link
                rel="icon"
                type="image/png"
                sizes="16x16"
                href="/cashbook/icons/favicon-16x16.png"
            >


            <title>
                <?= $header ? htmlspecialchars($header) . ' | KashHub' : 'KashHub';
                ?>
            </title>


            <link
                rel="manifest"
                href="/cashbook/manifest.json"
            >


            <!-- BOOTSTRAP -->

            <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css"
            >

            <link
                rel="stylesheet"
                href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
            >

            <link
                rel="stylesheet"
                href="../../assets/css/bootstrap.min.css"
            >


            <!-- FONT AWESOME -->

            <link
                rel="stylesheet"
                href="../../assets/fontawesome-free-5.14.0-web/css/all.css"
            >


            <!-- CUSTOM CSS -->

            <link
                rel="stylesheet"
                href="../../assets/css/custom.css"
            >

            <link
                rel="stylesheet"
                href="../../assets/style.css?v=<?=$styleVersion;?>"
            >

            <link
                rel="stylesheet"
                href="../../assets/css/xdialog.3.4.0.min.css"
            >


            <!-- LINE ICONS -->

            <link
                rel="stylesheet"
                href="https://cdn.lineicons.com/5.1/line/lineicons.css"
            >

            <link
                rel="stylesheet"
                href="https://cdn.lineicons.com/5.1/solid/lineicons-solid.css"
            >


            <!-- DATATABLES -->

            <link
                rel="stylesheet"
                href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css"
            >


            <!-- SELECT2 -->

            <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css"
            >

        </head>


        <body>

            <div class="wrapper">

                <?php

                /*
                ======================================================
                SIDEBAR SELECTION
                ======================================================

                IMPORTANT:

                Book context has priority.

                This allows a superadministrator to enter a book for
                support and still receive the normal book sidebar.

                ======================================================
                */

                // Book takes highest priority.
                if ($hasBookContext) {
                    sideBarLevel2();
                }
                // System administration.
                elseif (hasRole(['superadministrator'])) {
                    superAdminSideBar();
                }
                // Normal authenticated business workspace.
                else {
                    businessSideBar();
                }

                ?>
                <div class="main">
                    <!-- =============================================
                        TOP NAVIGATION
                    ============================================== -->

                    <nav class="navbar navbar-expand-lg navbar-dark bg-success">

                        <div class="container-fluid">


                            <!-- BRAND -->

                            <a
                                class="navbar-brand text-white"
                                href="../../"
                            >
                                <strong>KashHub</strong>
                            </a>


                            <?php if (!$hasBookContext && hasRole(['superadministrator'])): ?>

                                <span
                                    class="badge badge-light ml-2 d-none d-md-inline"
                                >

                                    <i class="fa fa-shield-alt mr-1"></i>

                                    SYSTEM ADMINISTRATION

                                </span>

                            <?php endif; ?>


                            <div class="ml-auto">


                                <?php if (
                                    isset($_SESSION['auth']) &&
                                    isset($_SESSION['auth']->name)
                                ): ?>


                                    <div class="dropdown">


                                        <button
                                            class="btn btn-sm btn-outline-light dropdown-toggle"
                                            type="button"
                                            id="userDropdown"
                                            data-toggle="dropdown"
                                            aria-haspopup="true"
                                            aria-expanded="false"
                                        >

                                            <i class="fa fa-user-circle mr-1"></i>

                                            <?=htmlspecialchars(
                                                $_SESSION['auth']->name
                                            );?>

                                        </button>


                                        <div
                                            class="dropdown-menu dropdown-menu-right"
                                            aria-labelledby="userDropdown"
                                        >


                                            <div class="px-3 py-2">

                                                <small class="text-muted">
                                                    Signed in as
                                                </small>

                                                <br>

                                                <strong>

                                                    <?=htmlspecialchars(
                                                        $_SESSION['auth']->name
                                                    );?>

                                                </strong>

                                            </div>


                                            <div class="dropdown-divider"></div>


                                            <a
                                                class="dropdown-item"
                                                href="../../profile/"
                                            >

                                                <i class="fa fa-user mr-2"></i>

                                                Profile

                                            </a>


                                            <?php if (
                                                hasRole(['superadministrator'])
                                            ): ?>

                                                <a
                                                    class="dropdown-item"
                                                    href="../../settings/"
                                                >

                                                    <i class="fa fa-cog mr-2"></i>

                                                    Settings

                                                </a>

                                            <?php endif; ?>


                                            <div class="dropdown-divider"></div>


                                            <a
                                                class="dropdown-item text-danger"
                                                href="../../?logout=1"
                                            >

                                                <i class="fa fa-sign-out-alt mr-2"></i>

                                                <strong>Logout</strong>

                                            </a>


                                        </div>

                                    </div>


                                <?php endif; ?>


                            </div>

                        </div>

                    </nav>


                    <?php sessionMessage(); ?>

        <?php
    }

    function pageFooterLevel2()
    {
        global $scriptVersion;
        ?>

                </div>
                <!-- END MAIN -->

            </div>
            <!-- END WRAPPER -->


            <!-- =====================================================
                CORE JAVASCRIPT
            ====================================================== -->

            <script src="../../assets/js/jquery-3.5.1.min.js"></script>
            <script src="../../assets/js/bootstrap.min.js"></script>


            <!-- =====================================================
                CHART JS
            ====================================================== -->

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <!-- =====================================================
                DATATABLES
            ====================================================== -->
            <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>


            <!-- =====================================================
                XDIALOG
            ====================================================== -->

            <script src="../../assets/js/xdialog.3.4.0.min.js"></script>
            <!-- =====================================================
                SELECT2
            ====================================================== -->
            <script src="../../assets/js/select2.min.js" defer></script>


            <!-- =====================================================
                APPLICATION JS
            ====================================================== -->

            <script src="../../assets/js/custom.js"></script>
            <script src="../../assets/script.js?v=<?=$scriptVersion;?>"></script>

            <!-- =====================================================
                SERVICE WORKER
            ====================================================== -->
            <script>

                if ('serviceWorker' in navigator) {
                    window.addEventListener(
                        'load',
                        function ()
                        {
                            navigator.serviceWorker
                                .register(
                                    '/cashbook/service-worker.js'
                                )
                                .then(
                                    function (registration)
                                    {
                                        console.log(
                                            'Cashbook Service Worker registered:',
                                            registration.scope
                                        );

                                    }
                                )

                                .catch(
                                    function (error)
                                    {

                                        console.error(
                                            'Cashbook Service Worker registration failed:',
                                            error
                                        );

                                    }
                                );

                        }
                    );

                }

            </script>


        </body>

        </html>

        <?php
    }


    /** Only the registered business owner may manage business role assignments. */
    function canManageBusinessRolesNav($businessId)
    {
        global $server;
        $businessId = (int)$businessId;
        $userId = (int)($_SESSION['auth']->id ?? 0);
        if ($businessId <= 0 || $userId <= 0) return false;

        $stmt = $server->prepare('SELECT id FROM cashbook_business_profile WHERE id=? AND user_id=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('ii', $businessId, $userId);
        $stmt->execute();
        $allowed = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $allowed;
    }

    /** System role assignments: legacy superadministrator until multi-role rollout. */
    function canManageSystemRolesNav()
    {
        if (!empty($_SESSION['auth']->id) && hasRole(['superadministrator'])) return true;
        global $server;
        $userId = (int)($_SESSION['auth']->id ?? 0);
        if ($userId <= 0) return false;
        // New role assignments may not yet be installed: avoid fatal SQL errors.
        $exists = $server->query("SHOW TABLES LIKE 'cashbook_role_user'");
        if (!$exists || !$exists->num_rows) return false;
        $stmt = $server->prepare("SELECT ru.id FROM cashbook_role_user ru JOIN cashbook_roles r ON r.id=ru.role_id WHERE ru.user_id=? AND ru.business_id=0 AND LOWER(r.name)='superadministrator' LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $allowed = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $allowed;
    }

    function sideBar()
    {
        global $book;

        /*
        |--------------------------------------------------------------------------
        | GET BOOK ID
        |--------------------------------------------------------------------------
        */

        $encryptedBookId =
            $_REQUEST['bkid']
            ?? $_REQUEST['bsid']
            ?? $_SESSION['book_id']
            ?? null;

        if (empty($encryptedBookId)) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | DECRYPT BOOK ID
        |--------------------------------------------------------------------------
        */

        $book_id = encryptor(
            'decrypt',
            $encryptedBookId
        );


        /*
        |--------------------------------------------------------------------------
        | FIND BOOK
        |--------------------------------------------------------------------------
        */

        $book = $book ?? bookFind($book_id);

        if (!$book) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND BUSINESS THAT OWNS THIS BOOK
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | The company logo belongs to cashbook_business_profile,
        | NOT cashbook_books.
        |
        */

        $business = null;

        if (!empty($book->business_id)) {
            $business = businessFindId($book->business_id);
        }


        /*
        |--------------------------------------------------------------------------
        | BUSINESS INFORMATION
        |--------------------------------------------------------------------------
        */

        $businessName =
            !empty($business->name)
            ? $business->name
            : 'Business';


        $bookName =
            !empty($book->name)
            ? $book->name
            : 'Business Book';


        /*
        |--------------------------------------------------------------------------
        | BUSINESS LOGO
        |--------------------------------------------------------------------------
        */

        $businessLogo = businessLogo($business);


        /*
        |--------------------------------------------------------------------------
        | ENCRYPT BOOK ID ONCE
        |--------------------------------------------------------------------------
        */

        $bsid = encryptor(
            'encrypt',
            $book->id
        );

        ?>

        <!-- =============================================================
            MOBILE MENU
        ============================================================== -->

        <button
            id="mobile-menu-btn"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="fa fa-bars"></i>
        </button>


        <!-- =============================================================
            MOBILE OVERLAY
        ============================================================== -->

        <div id="sidebar-overlay"></div>


        <!-- =============================================================
            SIDEBAR
        ============================================================== -->

        <aside id="sidebar" class="expand">


            <!-- =========================================================
                SIDEBAR HEADER
            ========================================================== -->

            <div class="sidebar-header">


                <!-- DESKTOP TOGGLE -->

                <button
                    id="toggle-btn"
                    type="button"
                    aria-label="Toggle navigation"
                    aria-expanded="true"
                >
                    <i class="fa fa-bars"></i>
                </button>


                <!-- =====================================================
                    COMPANY BRAND
                ====================================================== -->

                <div class="sidebar-logo">

                    <a
                        href="../dashboard/?bkid=<?=$bsid;?>"
                        class="sidebar-company-brand"
                        title="<?=htmlspecialchars($businessName);?>"
                    >

                        <div class="sidebar-company-logo-wrap">

                            <img
                                src="<?=htmlspecialchars($businessLogo);?>"
                                alt="<?=htmlspecialchars($businessName);?>"
                                class="sidebar-company-logo"
                            >

                        </div>


                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                <?=htmlspecialchars($businessName);?>
                            </span>

                            <span class="sidebar-book-name">
                                <?=htmlspecialchars($bookName);?>
                            </span>

                        </div>

                    </a>

                </div>


                <!-- MOBILE CLOSE -->

                <button
                    id="mobile-close-btn"
                    type="button"
                    aria-label="Close navigation"
                >
                    <i class="fa fa-times"></i>
                </button>

            </div>


            <!-- =========================================================
                NAVIGATION
            ========================================================== -->

            <ul class="sidebar-nav">


                <!-- DASHBOARD -->
                <?php if(hasRole(['superadministrator'])):?>
                    <li class="sidebar-item">
                        <a  href="../super/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-shield-alt"></i>
                            <span>Super Admin Dashboard</span>
                        </a>
                    </li>
                <?php endif;?>
                <?php if(hasRole(['owner','partner','superadministrator'])):?>
                    <li class="sidebar-item">
                        <a  href="../dashboard/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                <?php endif;?>
                <li class="sidebar-item">
                    <a href="../home/?bkid=<?=$bsid;?>" class="sidebar-link">
                        <i class="fa fa-building"></i>
                        <span>Home</span>
                    </a>
                </li>


                <!-- CUSTOMERS -->

                <li class="sidebar-item">

                    <a
                        href="../customers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-user-friends"></i>

                        <span>Customers</span>
                    </a>

                </li>


                <!-- CREDITORS -->

                <li class="sidebar-item">

                    <a
                        href="../creditors/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-hand-holding-usd"></i>
                        <span>Creditors</span>
                    </a>

                </li>


                <!-- STOCK -->

                <li class="sidebar-item">

                    <a
                        href="../stock/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-warehouse"></i>

                        <span>Stock</span>
                    </a>

                </li>


                <!-- ITEMS -->

                <li class="sidebar-item">

                    <a
                        href="../items/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-box-open"></i>

                        <span>Items</span>
                    </a>

                </li>


                <!-- PURCHASES -->

                <li class="sidebar-item">

                    <a
                        href="../purchases/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-shopping-cart"></i>

                        <span>Purchases</span>
                    </a>

                </li>


                <!-- USERS -->

                <li class="sidebar-item">

                    <a
                        href="../members/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users"></i>

                        <span>Users</span>
                    </a>

                </li>


                <!-- ROLE MANAGEMENT: registered owner only -->
                <?php if (canManageBusinessRolesNav((int)($book->business_id ?? 0))): ?>
                    <li class="sidebar-item">
                        <a href="../roles/?business_id=<?= (int)$book->business_id; ?>" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>Roles &amp; Access</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- CATEGORIES -->

                <li class="sidebar-item">

                    <a
                        href="../category/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-tags"></i>

                        <span>Categories</span>
                    </a>

                </li>


                <!-- PAY MODES -->

                <li class="sidebar-item">

                    <a
                        href="../modes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-handshake"></i>

                        <span>Pay Modes</span>
                    </a>

                </li>


                <!-- INVOICES -->

                <li class="sidebar-item">

                    <a
                        href="../invoices/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-file-invoice"></i>

                        <span>Invoices</span>
                    </a>

                </li>


                <!-- REPORT -->

                <li class="sidebar-item">

                    <a
                        href="../report/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-chart-bar"></i>

                        <span>Reports</span>
                    </a>

                </li>


                <!-- ROUTES -->

                <li class="sidebar-item">

                    <a
                        href="../routes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-route"></i>

                        <span>Routes</span>
                    </a>

                </li>


                <!-- ROUTE MANAGERS -->

                <li class="sidebar-item">

                    <a
                        href="../route_managers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users-cog"></i>

                        <span>Route Managers</span>
                    </a>

                </li>

            </ul>


            <!-- =========================================================
                SIDEBAR FOOTER / KASHHUB BRAND
            ========================================================== -->

            <div class="sidebar-footer">

                <div class="sidebar-powered-by">

                    <span>Powered by</span>

                    <strong>KashHub</strong>

                </div>


                <a href="../?logout=1">

                    <i class="fa fa-sign-out-alt"></i>

                    <span>Logout</span>

                </a>

            </div>

        </aside>

        <?php
    }

    function sideBarLevel2()
    {
        global $book;

        /*
        |--------------------------------------------------------------------------
        | GET BOOK ID
        |--------------------------------------------------------------------------
        */

        $encryptedBookId =
            $_REQUEST['bkid']
            ?? $_REQUEST['bsid']
            ?? $_SESSION['book_id']
            ?? null;

        if (empty($encryptedBookId)) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | DECRYPT BOOK ID
        |--------------------------------------------------------------------------
        */

        $book_id = encryptor(
            'decrypt',
            $encryptedBookId
        );


        /*
        |--------------------------------------------------------------------------
        | FIND BOOK
        |--------------------------------------------------------------------------
        */

        $book = $book ?? bookFind($book_id);

        if (!$book) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND BUSINESS THAT OWNS THIS BOOK
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | The company logo belongs to cashbook_business_profile,
        | NOT cashbook_books.
        |
        */

        $business = null;

        if (!empty($book->business_id)) {
            $business = businessFindId($book->business_id);
        }


        /*
        |--------------------------------------------------------------------------
        | BUSINESS INFORMATION
        |--------------------------------------------------------------------------
        */

        $businessName =
            !empty($business->name)
            ? $business->name
            : 'Business';


        $bookName =
            !empty($book->name)
            ? $book->name
            : 'Business Book';


        /*
        |--------------------------------------------------------------------------
        | BUSINESS LOGO
        |--------------------------------------------------------------------------
        */

        $businessLogo = businessLogo($business);


        /*
        |--------------------------------------------------------------------------
        | ENCRYPT BOOK ID ONCE
        |--------------------------------------------------------------------------
        */

        $bsid = encryptor(
            'encrypt',
            $book->id
        );

        ?>

        <!-- =============================================================
            MOBILE MENU
        ============================================================== -->

        <button
            id="mobile-menu-btn"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="fa fa-bars"></i>
        </button>


        <!-- =============================================================
            MOBILE OVERLAY
        ============================================================== -->

        <div id="sidebar-overlay"></div>


        <!-- =============================================================
            SIDEBAR
        ============================================================== -->

        <aside id="sidebar">


            <!-- =========================================================
                SIDEBAR HEADER
            ========================================================== -->

            <div class="sidebar-header">


                <!-- DESKTOP TOGGLE -->

                <button
                    id="toggle-btn"
                    type="button"
                    aria-label="Toggle navigation"
                    aria-expanded="false"
                >
                    <i class="fa fa-bars"></i>
                </button>


                <!-- =====================================================
                    COMPANY BRAND
                ====================================================== -->

                <div class="sidebar-logo">

                    <a
                        href="../../dashboard/?bkid=<?=$bsid;?>"
                        class="sidebar-company-brand"
                        title="<?=htmlspecialchars($businessName);?>"
                    >

                        <div class="sidebar-company-logo-wrap">

                            <img
                                src="<?=htmlspecialchars($businessLogo);?>"
                                alt="<?=htmlspecialchars($businessName);?>"
                                class="sidebar-company-logo"
                            >

                        </div>


                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                <?=htmlspecialchars($businessName);?>
                            </span>

                            <span class="sidebar-book-name">
                                <?=htmlspecialchars($bookName);?>
                            </span>

                        </div>

                    </a>

                </div>


                <!-- MOBILE CLOSE -->

                <button
                    id="mobile-close-btn"
                    type="button"
                    aria-label="Close navigation"
                >
                    <i class="fa fa-times"></i>
                </button>

            </div>


            <!-- =========================================================
                NAVIGATION
            ========================================================== -->

            <ul class="sidebar-nav">


                <!-- DASHBOARD -->
                <?php if(hasRole(['superadministrator'])):?>
                    <li class="sidebar-item">
                        <a  href="../../super/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-shield-alt"></i>
                            <span>Super Admin Dashboard</span>
                        </a>
                    </li>
                <?php endif;?>
                <?php if(hasRole(['owner','partner','superadministrator'])):?>
                    <li class="sidebar-item">
                        <a  href="../../dashboard/?bkid=<?=$bsid;?>" class="sidebar-link">
                            <i class="fa fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                <?php endif;?>
                <li class="sidebar-item">
                    <a href="../../home/?bkid=<?=$bsid;?>" class="sidebar-link">
                        <i class="fa fa-building"></i>
                        <span>Home</span>
                    </a>
                </li>


                <!-- CUSTOMERS -->

                <li class="sidebar-item">

                    <a
                        href="../../customers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-user-friends"></i>

                        <span>Customers</span>
                    </a>

                </li>


                <!-- CREDITORS -->

                <li class="sidebar-item">

                    <a
                        href="../../creditors/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-hand-holding-usd"></i>
                        <span>Creditors</span>
                    </a>

                </li>


                <!-- STOCK -->

                <li class="sidebar-item">

                    <a
                        href="../../stock/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-warehouse"></i>

                        <span>Stock</span>
                    </a>

                </li>


                <!-- ITEMS -->

                <li class="sidebar-item">

                    <a
                        href="../../items/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-box-open"></i>

                        <span>Items</span>
                    </a>

                </li>


                <!-- PURCHASES -->

                <li class="sidebar-item">

                    <a
                        href="../../purchases/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-shopping-cart"></i>

                        <span>Purchases</span>
                    </a>

                </li>


                <!-- USERS -->

                <li class="sidebar-item">

                    <a
                        href="../../members/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users"></i>

                        <span>Users</span>
                    </a>

                </li>


                <!-- ROLE MANAGEMENT: registered owner only -->
                <?php if (canManageBusinessRolesNav((int)($book->business_id ?? 0))): ?>
                    <li class="sidebar-item">
                        <a href="../../roles/?business_id=<?= (int)$book->business_id; ?>" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>Roles &amp; Access</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- CATEGORIES -->

                <li class="sidebar-item">

                    <a
                        href="../../category/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-tags"></i>

                        <span>Categories</span>
                    </a>

                </li>


                <!-- PAY MODES -->

                <li class="sidebar-item">

                    <a
                        href="../../modes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-handshake"></i>

                        <span>Pay Modes</span>
                    </a>

                </li>


                <!-- INVOICES -->

                <li class="sidebar-item">

                    <a
                        href="../../invoices/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-file-invoice"></i>

                        <span>Invoices</span>
                    </a>

                </li>


                <!-- REPORT -->

                <li class="sidebar-item">

                    <a
                        href="../../report/?bkid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-chart-bar"></i>

                        <span>Reports</span>
                    </a>

                </li>


                <!-- ROUTES -->

                <li class="sidebar-item">

                    <a
                        href="../../routes/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-route"></i>

                        <span>Routes</span>
                    </a>

                </li>


                <!-- ROUTE MANAGERS -->

                <li class="sidebar-item">

                    <a
                        href="../../route_managers/?bsid=<?=$bsid;?>"
                        class="sidebar-link"
                    >
                        <i class="fa fa-users-cog"></i>

                        <span>Route Managers</span>
                    </a>

                </li>

            </ul>


            <!-- =========================================================
                SIDEBAR FOOTER / KASHHUB BRAND
            ========================================================== -->

            <div class="sidebar-footer">

                <div class="sidebar-powered-by">

                    <span>Powered by</span>

                    <strong>KashHub</strong>

                </div>


                <a href="../../?logout=1">

                    <i class="fa fa-sign-out-alt"></i>

                    <span>Logout</span>

                </a>

            </div>

        </aside>

        <?php
    }

    function superAdminSideBar()
    {
        /*
        |--------------------------------------------------------------------------
        | KASHHUB PLATFORM BRANDING
        |--------------------------------------------------------------------------
        | Super Admin represents KashHub itself.
        | Do not resolve a business or book logo here.
        */

        $kashHubLogo = '/cashbook/assets/images/kashhub/logo.png';

        ?>

        <!-- Mobile menu button -->
        <button
            id="mobile-menu-btn"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="fa fa-bars"></i>
        </button>

        <!-- Mobile overlay -->
        <div id="sidebar-overlay"></div>


        <!-- ============================================================
            SUPER ADMIN SIDEBAR
        ============================================================= -->
        <aside id="sidebar" class="expand">

            <!-- ========================================================
                HEADER
            ========================================================= -->
            <div class="sidebar-header">

                <!-- Desktop toggle -->
                <button
                    id="toggle-btn"
                    type="button"
                    aria-label="Toggle navigation"
                    aria-expanded="true"
                >
                    <i class="fa fa-bars"></i>
                </button>


                <!-- KashHub Brand -->
                <div class="sidebar-logo">

                    <a
                        href="../super/"
                        class="sidebar-company-brand"
                        title="KashHub Administration"
                    >

                        <div class="sidebar-company-logo-wrap">
                            <img
                                src="<?= htmlspecialchars($kashHubLogo); ?>"
                                alt="KashHub"
                                class="sidebar-company-logo"
                            >
                        </div>

                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                KashHub
                            </span>

                            <span class="sidebar-book-name">
                                System Administration
                            </span>

                        </div>

                    </a>

                </div>


                <!-- Mobile close -->
                <button
                    id="mobile-close-btn"
                    type="button"
                    aria-label="Close navigation"
                >
                    <i class="fa fa-times"></i>
                </button>

            </div>


            <!-- ========================================================
                NAVIGATION
            ========================================================= -->
            <ul class="sidebar-nav">

                <li class="sidebar-item">
                    <a
                        href="../super/"
                        class="sidebar-link"
                        title="Dashboard"
                    >
                        <i class="fa fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a
                        href="../books/"
                        class="sidebar-link"
                        title="Books"
                    >
                        <i class="fa fa-book"></i>
                        <span>Books</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a
                        href="../businesses/"
                        class="sidebar-link"
                        title="Businesses"
                    >
                        <i class="fa fa-building"></i>
                        <span>Businesses</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a
                        href="../users/"
                        class="sidebar-link"
                        title="System Users"
                    >
                        <i class="fa fa-users"></i>
                        <span>System Users</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a
                        href="../support/"
                        class="sidebar-link"
                        title="Support"
                    >
                        <i class="fa fa-headphones"></i>
                        <span>Support</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a
                        href="../activity/"
                        class="sidebar-link"
                        title="System Activity"
                    >
                        <i class="fa fa-history"></i>
                        <span>System Activity</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a
                        href="../settings/"
                        class="sidebar-link"
                        title="System Settings"
                    >
                        <i class="fa fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>

                <!-- SYSTEM ROLE MANAGEMENT -->
                <?php if (canManageSystemRolesNav()): ?>
                    <li class="sidebar-item">
                        <a href="../roles/" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>System Roles</span>
                        </a>
                    </li>
                <?php endif; ?>

            </ul>


            <!-- ========================================================
                FOOTER
            ========================================================= -->
            <div class="sidebar-footer">

                <div class="sidebar-admin-context">

                    <div class="sidebar-admin-icon">
                        <i class="fa fa-shield-alt"></i>
                    </div>

                    <div class="sidebar-admin-details">
                        <span>Administration</span>
                        <strong>KashHub Platform</strong>
                    </div>

                </div>


                <a
                    href="../?logout=1"
                    class="sidebar-logout"
                    title="Logout"
                >
                    <i class="fa fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>

            </div>

        </aside>

        <?php
    }

    function businessSideBar()
    {
        /*
        |--------------------------------------------------------------------------
        | FIND CURRENT BUSINESS
        |--------------------------------------------------------------------------
        */

        $business = businessFind(auth());

        if (
            !$business &&
            isset(auth()->business_id) &&
            !empty(auth()->business_id)
        ) {
            $business = businessFindId(
                auth()->business_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | BUSINESS BRANDING
        |--------------------------------------------------------------------------
        */

        $businessName =
            !empty($business->name)
            ? $business->name
            : 'My Business';


        $businessLogo =
            businessLogo($business);

        ?>

        <!-- =============================================================
            MOBILE MENU BUTTON
        ============================================================== -->

        <button
            id="mobile-menu-btn"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="fa fa-bars"></i>
        </button>


        <!-- MOBILE OVERLAY -->

        <div id="sidebar-overlay"></div>


        <!-- =============================================================
            BUSINESS SIDEBAR
        ============================================================== -->

        <aside id="sidebar" class="expand">


            <!-- =========================================================
                HEADER
            ========================================================== -->

            <div class="sidebar-header">


                <!-- DESKTOP TOGGLE -->

                <button
                    id="toggle-btn"
                    type="button"
                    aria-label="Toggle navigation"
                    aria-expanded="true"
                >
                    <i class="fa fa-bars"></i>
                </button>


                <!-- =====================================================
                    BUSINESS BRAND
                ====================================================== -->

                <div class="sidebar-logo">

                    <a
                        href="../"
                        class="sidebar-company-brand"
                        title="<?=htmlspecialchars($businessName);?>"
                    >

                        <div class="sidebar-company-logo-wrap">

                            <img
                                src="<?=htmlspecialchars($businessLogo);?>"
                                alt="<?=htmlspecialchars($businessName);?>"
                                class="sidebar-company-logo"
                            >

                        </div>


                        <div class="sidebar-company-details">

                            <span class="sidebar-company-name">
                                <?=htmlspecialchars($businessName);?>
                            </span>

                            <span class="sidebar-book-name">
                                KashHub Business
                            </span>

                        </div>

                    </a>

                </div>


                <!-- MOBILE CLOSE -->

                <button
                    id="mobile-close-btn"
                    type="button"
                    aria-label="Close navigation"
                >
                    <i class="fa fa-times"></i>
                </button>

            </div>


            <!-- =========================================================
                NAVIGATION
            ========================================================== -->

            <ul class="sidebar-nav">


                <!-- HOME -->

                <li class="sidebar-item">

                    <a
                        href="../"
                        class="sidebar-link"
                    >

                        <i class="fa fa-home"></i>

                        <span>Home</span>

                    </a>

                </li>


                <!-- BUSINESS PROFILE -->

                <li class="sidebar-item">

                    <a
                        href="#"
                        class="sidebar-link new-profile"
                        data-id="<?=
                            !empty($business->id)
                            ? (int)$business->id
                            : '';
                        ?>"
                    >

                        <i class="fa fa-building"></i>

                        <span>Business Profile</span>

                    </a>

                </li>


                <!-- TEAM -->

                <li class="sidebar-item">

                    <a
                        href="#"
                        class="sidebar-link new-team"
                    >

                        <i class="fa fa-users"></i>

                        <span>Team</span>

                    </a>

                </li>


                <!-- BUSINESS ROLE MANAGEMENT: owner only -->
                <?php if (!empty($business->id) && canManageBusinessRolesNav((int)$business->id)): ?>
                    <li class="sidebar-item">
                        <a href="../roles/?business_id=<?= (int)$business->id; ?>" class="sidebar-link">
                            <i class="fa fa-user-shield"></i>
                            <span>Roles &amp; Access</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- SETTINGS -->

                <li class="sidebar-item">

                    <a
                        href="#"
                        class="sidebar-link new-settings"
                    >

                        <i class="fa fa-cog"></i>

                        <span>Settings</span>

                    </a>

                </li>


            </ul>


            <!-- =========================================================
                FOOTER
            ========================================================== -->

            <div class="sidebar-footer">


                <!-- KASHHUB PLATFORM BRAND -->

                <div class="sidebar-powered-by">

                    <span>Powered by</span>

                    <strong>KashHub</strong>

                </div>


                <!-- LOGOUT -->

                <a href="../?logout=1">

                    <i class="fa fa-sign-out-alt"></i>

                    <span>Logout</span>

                </a>


            </div>


        </aside>

        <?php
    }

    function businessLogo($business = null)
    {
        /*
        |--------------------------------------------------------------------------
        | DEFAULT LOGO
        |--------------------------------------------------------------------------
        */

        $defaultLogo = '/cashbook/assets/images/kashhub/logo.png';


        /*
        |--------------------------------------------------------------------------
        | NO BUSINESS / NO CUSTOM LOGO
        |--------------------------------------------------------------------------
        */

        if (!$business || empty($business->profile)) {
            return $defaultLogo;
        }


        /*
        |--------------------------------------------------------------------------
        | BUSINESS LOGO
        |--------------------------------------------------------------------------
        */

        $filename = basename($business->profile);

        return '/cashbook/assets/uploads/business_logos/'
            . rawurlencode($filename);
    }

    // invoice find function
    // function invoiceFind($id)
    // {
    //     $stmt = "SELECT ci.*,cc.name as customer,cc.contact FROM cashbook_invoices ci 
    //                 LEFT JOIN cashbook_customers cc ON cc.id = ci.customer_id
    //             WHERE ci.id = ?";
    //     $res = prepared_statements($stmt,'i',[$id]);
    //     return myObject($res->fetch_assoc());
    // }
    
    function invoiceFind($id)
    {
        $stmt = "SELECT ci.*,cc.name AS customer,cc.contact
                FROM cashbook_invoices ci
                LEFT JOIN cashbook_customers cc
                    ON cc.id=ci.customer_id
                    AND cc.book_id=ci.book_id
                WHERE ci.id=? LIMIT 1";

        $res = prepared_statements($stmt,'i',[(int)$id]);

        if(!$res || !$res->num_rows) return null;

        return myObject($res->fetch_assoc());
    }

    function kashHubLogo()
    {
        return '/assets/images/kashhub/logo.png';
    }

    function customerFind($id)
    {
        $stmt = "SELECT * FROM cashbook_customers WHERE id = ?";
        $res = prepared_statements($stmt,'i',[$id]);
        return myObject($res->fetch_assoc());
    }

    function creditorFind($id)
    {
        $stmt = "SELECT * FROM cashbook_creditors WHERE id = ?";
        $res = prepared_statements($stmt,'i',[$id]);
        return myObject($res->fetch_assoc());
    }

    // function getCustomerBalance($customer_id)
    // {
    //     global $server;

    //     $stmt = $server->prepare("
    //         SELECT COALESCE(balance, 0) AS balance
    //         FROM cashbook_customer_ledger
    //         WHERE customer_id = ?
    //         ORDER BY id DESC
    //         LIMIT 1
    //     ");

    //     if (!$stmt) {
    //         return 0;
    //     }

    //     $stmt->bind_param('i', $customer_id);
    //     $stmt->execute();

    //     $result = $stmt->get_result();
    //     $row = $result->fetch_assoc();

    //     $stmt->close();

    //     return $row ? (float)$row['balance'] : 0;
    // }
    
function getCustomerBalance($customer_id)
{
    global $server;

    $customer_id = (int)$customer_id;

    $stmt = $server->prepare("
        SELECT COALESCE(balance,0) AS balance
        FROM cashbook_customer_balances
        WHERE customer_id=?
        LIMIT 1
    ");

    if(!$stmt) throw new Exception($server->error);

    $stmt->bind_param('i',$customer_id);

    if(!$stmt->execute()){
        $error = $stmt->error;
        $stmt->close();
        throw new Exception($error);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? (float)$row['balance'] : 0;
}


    function getCreditorBalance($creditor_id)
    {
        global $server;

        $stmt = $server->prepare("
            SELECT COALESCE(balance, 0) AS balance
            FROM cashbook_creditor_ledger
            WHERE creditor_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $creditor_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? (float)$row['balance'] : 0;
    }

    function getCustomerBalanceBeforeTransaction($customer_id, $transaction_id)
    {
        global $server;

        $stmt = $server->prepare("SELECT COALESCE(balance, 0) AS balance
                FROM cashbook_customer_ledger
                    WHERE customer_id = ? AND id < (
                        SELECT id FROM cashbook_customer_ledger WHERE transaction_id = ? LIMIT 1
                    )
                ORDER BY id DESC LIMIT 1
            ");

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('ii', $customer_id, $transaction_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? (float)$row['balance'] : 0;
    }

    function getCreditorBalanceBeforeTransaction($creditor_id, $transaction_id)
    {
        global $server;

        $stmt = $server->prepare("SELECT COALESCE(balance, 0) AS balance
                FROM cashbook_creditor_ledger
                    WHERE customer_id = ? AND id < (
                        SELECT id FROM cashbook_creditor_ledger WHERE transaction_id = ? LIMIT 1
                    )
                ORDER BY id DESC LIMIT 1
            ");

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('ii', $creditor_id, $transaction_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? (float)$row['balance'] : 0;
    }

    // function insertCustomerLedgerInvoice($customer_id, $type,$amount,$invoice_id,$trans_id,$book_id,$invoice_no)
    // {
    //     global $server;

    //     $currentBalance = getCustomerBalance($customer_id);
    //     $invoice = invoiceFind($invoice_id);
    //     $user_id = auth()->id;
    //     // get new balance
    //     $newBalance = $currentBalance + $amount;

    //     $stmt = $server->prepare("INSERT INTO cashbook_customer_ledger(customer_id,type,debit_amount,balance,invoice_id,invoice_amount,transaction_id,book_id,user_id,details,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    //     $stmt->bind_param('isddidiiiss',$customer_id,$type,$amount,$newBalance,$invoice_id,$amount,$trans_id,$book_id,$user_id,$invoice_no,$invoice->created_at);

    //     // update customer balance
    //     saveCustomerBalance($customer_id,$newBalance,$invoice->created_at); 
    //     return $stmt->execute();
    // }
    
    function insertCustomerLedgerInvoice($customer_id,$type,$amount,$invoice_id,$trans_id,$book_id,$invoice_no)
    {
        global $server;

        $customer_id = (int)$customer_id;
        $invoice_id = (int)$invoice_id;
        $trans_id = (int)$trans_id;
        $book_id = (int)$book_id;
        $user_id = (int)auth()->id;
        $amount = round((float)$amount,2);

        if($amount <= 0) return false;

        $invoice = invoiceFind($invoice_id);
        if(!$invoice || (int)$invoice->book_id !== $book_id) return false;

        $currentBalance = (float)getCustomerBalance($customer_id);
        $newBalance = round($currentBalance+$amount,2);

        $stmt = $server->prepare("
            INSERT INTO cashbook_customer_ledger
            (customer_id,type,debit_amount,balance,invoice_id,
            invoice_amount,transaction_id,book_id,user_id,details,created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");

        if(!$stmt) return false;

        $date = $invoice->created_at;

        $stmt->bind_param(
            'isddidiiiss',
            $customer_id,$type,$amount,$newBalance,$invoice_id,
            $amount,$trans_id,$book_id,$user_id,$invoice_no,$date
        );

        $saved = $stmt->execute();
        $stmt->close();

        if(!$saved) return false;

        return (bool)saveCustomerBalance($customer_id,$newBalance,$date);
    }

    // update customer balances table
    function saveCustomerBalance($customer_id,$balance,$date)
    {
        global $server;
        //   check if the customer  exists in the cashbook_customer_balances table
        $check_stmt = "SELECT * FROM cashbook_customer_balances WHERE customer_id = ?";
        $check = prepared_statements($check_stmt,'i',[$customer_id]);
        $book_id = customerFind($customer_id)->book_id;
        
        if($check->num_rows > 0)
        {
            $stmt = "UPDATE cashbook_customer_balances SET balance = ?,date = ? WHERE customer_id = ?";
            return prepared_statements($stmt,'dis',[$balance,$date,$customer_id]);
        }else
        {
            $stmt = "INSERT INTO cashbook_customer_balances SET customer_id = ?, balance = ?,date = ?,book_id = ?";
            return prepared_statements($stmt,'idsi',[$customer_id,$balance,$date,$book_id]);
        }
    }

    function saveCreditorBalance($creditor_id, $balance, $date)
    {
        $creditor_id = (int)$creditor_id;
        $balance = (float)$balance;

        $creditor = creditorFind($creditor_id);

        if(!$creditor)
        {
            return false;
        }

        $book_id = (int)$creditor->book_id;

        $check_stmt = "
            SELECT id
            FROM cashbook_creditor_balances
            WHERE creditor_id = ?
            AND book_id = ?
            LIMIT 1
        ";

        $check = prepared_statements(
            $check_stmt,
            'ii',
            [$creditor_id,$book_id]
        );

        if(!$check)
        {
            return false;
        }


        if($check->num_rows > 0)
        {
            $stmt = "
                UPDATE cashbook_creditor_balances
                SET
                    balance = ?,
                    date = ?
                WHERE creditor_id = ?
                AND book_id = ?
            ";

            return prepared_statements(
                $stmt,
                'dsii',
                [
                    $balance,
                    $date,
                    $creditor_id,
                    $book_id
                ]
            );
        }


        $stmt = "
            INSERT INTO cashbook_creditor_balances
            (
                creditor_id,
                balance,
                date,
                book_id
            )
            VALUES (?, ?, ?, ?)
        ";

        return prepared_statements(
            $stmt,
            'idsi',
            [
                $creditor_id,
                $balance,
                $date,
                $book_id
            ]
        );
    }

    function recalculateCreditorLedgerFromTransaction(
        $creditor_id,
        $transaction_id
    )
    {
        $creditor_id   = (int)$creditor_id;
        $transaction_id = (int)$transaction_id;

        if($creditor_id <= 0 || $transaction_id <= 0)
        {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND EDITED TRANSACTION
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT
                id,
                created_at
            FROM cashbook_creditor_ledger
            WHERE creditor_id = ?
            AND transaction_id = ?
            LIMIT 1
        ";

        $res = prepared_statements(
            $sql,
            'ii',
            [
                $creditor_id,
                $transaction_id
            ]
        );


        if(!$res || $res->num_rows <= 0)
        {
            return false;
        }


        $editedTransaction = $res->fetch_assoc();

        $ledger_id = (int)$editedTransaction['id'];
        $created_at = $editedTransaction['created_at'];


        /*
        |--------------------------------------------------------------------------
        | GET BALANCE IMMEDIATELY BEFORE EDITED TRANSACTION
        |--------------------------------------------------------------------------
        |
        | This is the important part.
        |
        | We DO NOT start from zero.
        |--------------------------------------------------------------------------
        */

        $previousSql = "
            SELECT balance
            FROM cashbook_creditor_ledger
            WHERE creditor_id = ?
            AND (
                created_at < ?
                OR
                (
                    created_at = ?
                    AND id < ?
                )
            )
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ";

        $previous = prepared_statements(
            $previousSql,
            'issi',
            [
                $creditor_id,
                $created_at,
                $created_at,
                $ledger_id
            ]
        );


        $runningBalance = 0;


        if($previous && $previous->num_rows > 0)
        {
            $previousRow =
                $previous->fetch_assoc();

            $runningBalance =
                (float)$previousRow['balance'];
        }


        /*
        |--------------------------------------------------------------------------
        | GET EDITED TRANSACTION + EVERYTHING AFTER IT
        |--------------------------------------------------------------------------
        */

        $ledgerSql = "
            SELECT
                id,
                credit_amount,
                debit_amount,
                created_at
            FROM cashbook_creditor_ledger
            WHERE creditor_id = ?
            AND (
                created_at > ?
                OR
                (
                    created_at = ?
                    AND id >= ?
                )
            )
            ORDER BY created_at ASC, id ASC
        ";

        $ledger = prepared_statements(
            $ledgerSql,
            'issi',
            [
                $creditor_id,
                $created_at,
                $created_at,
                $ledger_id
            ]
        );


        if(!$ledger)
        {
            return false;
        }


        $lastDate = $created_at;


        while($row = $ledger->fetch_assoc())
        {
            $credit =
                (float)$row['credit_amount'];

            $debit =
                (float)$row['debit_amount'];


            /*
            |--------------------------------------------------------------------------
            | RUNNING BALANCE
            |--------------------------------------------------------------------------
            */

            $runningBalance =
                $runningBalance
                + $credit
                - $debit;


            /*
            |--------------------------------------------------------------------------
            | UPDATE THIS LEDGER ROW
            |--------------------------------------------------------------------------
            */

            $updateSql = "
                UPDATE cashbook_creditor_ledger
                SET balance = ?
                WHERE id = ?
            ";

            prepared_statements(
                $updateSql,
                'di',
                [
                    $runningBalance,
                    $row['id']
                ]
            );


            $lastDate = $row['created_at'];
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE CURRENT CREDITOR BALANCE
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Do NOT call saveCreditorBalance() here because your current version
        | also updates the latest ledger row.
        |
        | We already updated every affected ledger row above.
        |--------------------------------------------------------------------------
        */

        $balanceCheckSql = "
            SELECT id
            FROM cashbook_creditor_balances
            WHERE creditor_id = ?
            LIMIT 1
        ";

        $balanceCheck = prepared_statements(
            $balanceCheckSql,
            'i',
            [$creditor_id]
        );


        if($balanceCheck && $balanceCheck->num_rows > 0)
        {
            $updateBalanceSql = "
                UPDATE cashbook_creditor_balances
                SET
                    balance = ?,
                    date = ?
                WHERE creditor_id = ?
            ";

            prepared_statements(
                $updateBalanceSql,
                'dsi',
                [
                    $runningBalance,
                    $lastDate,
                    $creditor_id
                ]
            );
        }
        else
        {
            $creditor = creditorFind($creditor_id);

            if(!$creditor)
            {
                return false;
            }

            $book_id = (int)$creditor->book_id;


            $insertBalanceSql = "
                INSERT INTO cashbook_creditor_balances
                (
                    creditor_id,
                    balance,
                    date,
                    book_id
                )
                VALUES (?, ?, ?, ?)
            ";

            prepared_statements(
                $insertBalanceSql,
                'idsi',
                [
                    $creditor_id,
                    $runningBalance,
                    $lastDate,
                    $book_id
                ]
            );
        }


        return $runningBalance;
    }

    function stockIn($item_id, $qty, $trans_id = false)
    {
        global $server;

        $user_id = auth()->id;
        $qty = (float) $qty;

        if ($item_id <= 0 || $qty <= 0) {
            return false;
        }

        // Get item
        $item = itemFind($item_id);

        if (!$item) {
            return false;
        }

        $book_id = $item->book_id;
        $type = 'stock_in';

        // Normalize transaction/reference
        $reference = !empty($trans_id) ? $trans_id : null;

        /*
        =====================================================
        START DATABASE TRANSACTION
        =====================================================
        */

        mysqli_begin_transaction($server);

        try {

            /*
            =====================================================
            GET CURRENT STOCK BALANCE
            =====================================================
            */

            $bal = (float) getItemStockBalance($item_id);

            /*
            =====================================================
            CHECK WHETHER THIS TRANSACTION ALREADY EXISTS
            =====================================================
            */

            $existing = null;

            if ($reference !== null) {

                $stmt = "
                    SELECT id, quantity_in
                    FROM cashbook_stocks
                    WHERE reference = ?
                    AND transaction_type = ?
                    AND item_id = ?
                    AND book_id = ?
                    LIMIT 1
                ";

                $result = prepared_statements(
                    $stmt,
                    'ssii',
                    [$reference, $type, $item_id, $book_id]
                );

                if ($result && $result->num_rows > 0) {
                    $existing = $result->fetch_assoc();
                }
            }

            /*
            =====================================================
            UPDATE EXISTING STOCK-IN
            =====================================================
            */

            if ($existing) {

                $oldQty = (float) $existing['quantity_in'];

                /*
                * Remove the old quantity first,
                * then add the new quantity.
                */
                $newBalance = $bal - $oldQty + $qty;

                $stmt = "
                    UPDATE cashbook_stocks
                    SET
                        quantity_in = ?,
                        balance = ?,
                        user_id = ?
                    WHERE id = ?
                ";

                $result = prepared_statements(
                    $stmt,
                    'ddii',
                    [$qty, $newBalance, $user_id, $existing['id']]
                );

                if (!$result) {
                    throw new Exception("Failed to update stock record.");
                }

            }

            /*
            =====================================================
            INSERT NEW STOCK-IN
            =====================================================
            */

            else {

                $newBalance = $bal + $qty;

                $stmt = "
                    INSERT INTO cashbook_stocks
                    (
                        item_id,
                        book_id,
                        transaction_type,
                        quantity_in,
                        balance,
                        reference,
                        user_id
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ";

                $result = prepared_statements(
                    $stmt,
                    'iisddsi',
                    [
                        $item_id,
                        $book_id,
                        $type,
                        $qty,
                        $newBalance,
                        $reference,
                        $user_id
                    ]
                );

                if (!$result) {
                    throw new Exception("Failed to insert stock record.");
                }
            }

            /*
            =====================================================
            UPDATE CURRENT ITEM BALANCE
            =====================================================
            */

            $balanceUpdated = updateStockItemBalance(
                $item_id,
                $newBalance
            );

            if ($balanceUpdated === false) {
                throw new Exception("Failed to update stock balance.");
            }

            /*
            =====================================================
            COMMIT
            =====================================================
            */

            mysqli_commit($server);

            return true;

        } catch (Throwable $e) {

            /*
            =====================================================
            ROLLBACK EVERYTHING
            =====================================================
            */

            mysqli_rollback($server);

            error_log(
                "stockIn() failed: " . $e->getMessage()
            );

            return false;
        }
    }


function stockOut($item_id,$qty,$trans_id=false)
{
    global $server;

    $item_id=(int)$item_id;
    $qty=round((float)$qty,2);
    $user_id=(int)auth()->id;
    $reference=($trans_id!==false && $trans_id!==null && $trans_id!=='')
        ? (int)$trans_id : null;

    if($item_id<=0 || $qty<=0){
        throw new Exception("A valid stock item and quantity are required.");
    }

    $item=itemFind($item_id);

    if(!$item){
        throw new Exception("The selected stock item was not found.");
    }

    $book_id=(int)$item->book_id;

    if($book_id<=0){
        throw new Exception("The stock item has no valid business book.");
    }

    // Check for a previously recorded movement.
    if($reference!==null){
        $existing=prepared_statements(
            "SELECT id,quantity_out
             FROM cashbook_stocks
             WHERE reference=? AND transaction_type='stock_out'
             AND item_id=? AND book_id=?
             LIMIT 1",
            'iii',[$reference,$item_id,$book_id]
        );

        if($existing===false){
            throw new Exception("Unable to check existing stock movements.");
        }

        if($existing->num_rows>0){
            $row=$existing->fetch_assoc();
            $oldQty=round((float)$row['quantity_out'],2);

            if(abs($oldQty-$qty)<0.001){
                return true;
            }

            throw new Exception(
                "This transaction already has a stock-out of {$oldQty}. ".
                "Changing its quantity requires stock reconciliation."
            );
        }
    }

    // Read the available stock.
    $balance=round((float)getItemStockBalance($item_id),2);

    if($qty>$balance){
        throw new Exception(
            "Insufficient stock. Available: {$balance}, requested: {$qty}."
        );
    }

    $newBalance=round($balance-$qty,2);

    // Insert the new stock movement.
    $sql="INSERT INTO cashbook_stocks
          (item_id,book_id,transaction_type,quantity_in,
           quantity_out,balance,reference,user_id)
          VALUES (?,?,'stock_out',0,?,?,?,?)";

    $result=prepared_statements(
        $sql,'iiddii',
        [$item_id,$book_id,$qty,$newBalance,$reference,$user_id]
    );

    if($result===false){
        throw new Exception("Failed to insert stock-out movement.");
    }

    // Update the item's current stock balance.
    $updated=updateStockItemBalance($item_id,$newBalance);

    if($updated===false){
        throw new Exception("Failed to update the item's stock balance.");
    }

    return true;
}


    function restoreStockOut($item_id, $qty)
    {
        global $server;

        $qty = (float) $qty;

        if ($item_id <= 0 || $qty <= 0) {
            return false;
        }

        // Get the item
        $item = itemFind($item_id);

        if (!$item) {
            return false;
        }

        /*
        =====================================================
        GET CURRENT STOCK BALANCE
        =====================================================
        */

        $currentBalance = (float) getItemStockBalance($item_id);

        /*
        =====================================================
        RESTORE THE PREVIOUS STOCK-OUT
        =====================================================
        
        Example:
        
        Current balance = 80
        Previous stock-out = 20
        
        Restored balance = 80 + 20 = 100
        */

        $newBalance = $currentBalance + $qty;

        /*
        =====================================================
        UPDATE CURRENT STOCK BALANCE
        =====================================================
        */

        $updated = updateStockItemBalance(
            $item_id,
            $newBalance
        );

        if ($updated === false) {
            return false;
        }

        return true;
    }

    function getItemStockBalance($item_id)
    {
        global $server;
        // get stock balance for the Item
        $query = mysqli_query($server,"SELECT balance FROM cashbook_stocks WHERE item_id = '{$item_id}' ORDER BY id desc LIMIT 1");
        $row = $query->fetch_assoc();
        $bal = $row['balance'];

        return $bal ?? 0;
    }

    // function to update stock item balance in table
    function updateStockItemBalance($item_id,$balance)
    {
        global $server;
        $book_id = itemFind($item_id)->book_id;

        // check if the item is present in the table
        $check = mysqli_query($server,"SELECT * FROM cashbook_item_stock_balances WHERE item_id = '{$item_id}'");
        if($check->num_rows > 0)
        {
            $stmt = "UPDATE cashbook_item_stock_balances SET balance = ? WHERE item_id = ?";
            prepared_statements($stmt,'ii',[$balance,$item_id]);
        }else{
            $stmt = "INSERT INTO cashbook_item_stock_balances SET item_id = ?, book_id = ?, balance = ?";
            prepared_statements($stmt,'iii',[$item_id,$book_id,$balance]);
        }
    }

    function rebuildCreditorBalance($creditor_id, $book_id = null)
    {
        global $server;

        $creditor_id = (int)$creditor_id;

        if ($book_id === null) {

            $sql = "
                SELECT
                    COALESCE(SUM(credit_amount), 0) -
                    COALESCE(SUM(debit_amount), 0) AS balance
                FROM cashbook_creditor_ledger
                WHERE creditor_id = ?
            ";

            $res = prepared_statements(
                $sql,
                'i',
                [$creditor_id]
            );

        } else {

            $book_id = (int)$book_id;

            $sql = "
                SELECT
                    COALESCE(SUM(credit_amount), 0) -
                    COALESCE(SUM(debit_amount), 0) AS balance
                FROM cashbook_creditor_ledger
                WHERE creditor_id = ?
                AND book_id = ?
            ";

            $res = prepared_statements(
                $sql,
                'ii',
                [$creditor_id, $book_id]
            );
        }

        $row = $res->fetch_assoc();

        $balance = (float)($row['balance'] ?? 0);

        /*
        ----------------------------------------------------------
        Update the creditor balance table
        ----------------------------------------------------------
        */

        saveCreditorBalance(
            $creditor_id,
            $balance,
            date('Y-m-d H:i:s')
        );

        return $balance;
    }

    
    function rebuildCustomerLedgerBalances($customer_id,$book_id)
    {
        global $server;

        $customer_id=(int)$customer_id;
        $book_id=(int)$book_id;

        /*
        * Rebuild running balances from debit and credit values.
        * Uses ledger ID order, consistent with append-only entries.
        */

        $stmt=$server->prepare("
            SELECT id,debit_amount,credit_amount
            FROM cashbook_customer_ledger
            WHERE customer_id=? AND book_id=?
            ORDER BY id ASC
        ");

        if(!$stmt) throw new Exception($server->error);

        $stmt->bind_param('ii',$customer_id,$book_id);

        if(!$stmt->execute()){
            $error=$stmt->error;
            $stmt->close();
            throw new Exception($error);
        }

        $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $balance=0;

        $update=$server->prepare("
            UPDATE cashbook_customer_ledger
            SET balance=?
            WHERE id=? AND customer_id=? AND book_id=?
        ");

        if(!$update) throw new Exception($server->error);

        foreach($rows as $row){

            $balance=round(
                $balance
                +(float)$row['debit_amount']
                -(float)$row['credit_amount'],
                2
            );

            $ledger_id=(int)$row['id'];

            $update->bind_param(
                'diii',
                $balance,$ledger_id,$customer_id,$book_id
            );

            if(!$update->execute()){
                $error=$update->error;
                $update->close();
                throw new Exception($error);
            }
        }

        $update->close();

        $customer=customerFind($customer_id);

        if(!$customer || (int)$customer->book_id!==$book_id){
            throw new Exception("Customer does not belong to this book");
        }

        $saved=saveCustomerBalance(
            $customer_id,
            $balance,
            date('Y-m-d H:i:s')
        );

        if(!$saved){
            throw new Exception("Failed to save customer balance");
        }

        return $balance;
    }

    /*
    |--------------------------------------------------------------------------
    | CASHBOOK REPORT FILTER BUILDER
    |--------------------------------------------------------------------------
    |
    | Builds the WHERE clause and prepared-statement bindings used by:
    |
    | - transactionFilter
    | - Excel export
    | - PDF export
    |
    */

    function buildCashbookReportFilters($book_id, $filters = [])
    {
        $where = [];
        $params = [];
        $types = '';

        /*
        |--------------------------------------------------------------------------
        | BOOK
        |--------------------------------------------------------------------------
        */

        $where[] = "ct.book_id = ?";
        $types .= 'i';
        $params[] = (int)$book_id;


        /*
        |--------------------------------------------------------------------------
        | DATE FROM
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['min_date'])) {
            $where[] = "DATE(ct.created_at) >= ?";
            $types .= 's';
            $params[] = $filters['min_date'];
        }

        /*
        |--------------------------------------------------------------------------
        | DATE TO
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['max_date'])) {
            $where[] = "DATE(ct.created_at) <= ?";
            $types .= 's';
            $params[] = $filters['max_date'];
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filters['month']) &&
            is_numeric($filters['month'])
        ) {

            $where[] = "MONTH(ct.created_at) = ?";
            $types .= 'i';
            $params[] = (int)$filters['month'];

        }


        /*
        |--------------------------------------------------------------------------
        | YEAR
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filters['year']) &&
            is_numeric($filters['year'])
        ) {

            $where[] = "YEAR(ct.created_at) = ?";
            $types .= 'i';
            $params[] = (int)$filters['year'];

        }


        /*
        |--------------------------------------------------------------------------
        | TYPE
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['type'])) {

            if ($filters['type'] === 'credit') {

                $where[] = "COALESCE(ct.credit_amount,0) > 0";

            } elseif ($filters['type'] === 'debit') {

                $where[] = "COALESCE(ct.debit_amount,0) > 0";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filters['category']) &&
            is_numeric($filters['category'])
        ) {
            $where[] = "ct.category_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['category'];
        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['customer']) && is_numeric($filters['customer']))
        {
            $where[] = "ct.customer_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['customer'];
        }

        if (!empty($filters['item']) && is_numeric($filters['item'])) {
            $where[] = "ct.item_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['item'];
        }


        return [
            'where'  => implode(' AND ', $where),
            'types'  => $types,
            'params' => $params
        ];
    }

    // delete route function
    
    function deleteRouteById($routeId)
    {
        global $server;

        $routeId = (int)$routeId;
        if($routeId <= 0) return false;

        try {
            mysqli_begin_transaction($server);

            // Check whether the route exists
            $check = prepared_statements(
                "SELECT id FROM cashbook_routes WHERE id = ? FOR UPDATE",
                'i',
                [$routeId]
            );

            if(!$check || $check->num_rows == 0){
                mysqli_rollback($server);
                return false;
            }

            // Detach customers without deleting their records
            $detach = prepared_statements(
                "UPDATE cashbook_customers SET route_id = NULL WHERE route_id = ?",
                'i',
                [$routeId]
            );

            if($detach === false){
                throw new Exception("Failed to detach customers");
            }

            // Delete the route
            $delete = prepared_statements(
                "DELETE FROM cashbook_routes WHERE id = ?",
                'i',
                [$routeId]
            );

            if($delete === false){
                throw new Exception("Failed to delete route");
            }

            mysqli_commit($server);
            return true;

        }catch(Throwable $e){
            mysqli_rollback($server);
            error_log("Route deletion error: ".$e->getMessage());
            return false;
        }
    }

    
function rebuildCustomerLedgerAfterDelete($customer_id,$book_id)
{
    global $server;

    $customer_id = (int)$customer_id;
    $book_id = (int)$book_id;
    if($customer_id <= 0 || $book_id <= 0) return false;

    try{
        $sql = "SELECT id,credit_amount,debit_amount,created_at
                FROM cashbook_customer_ledger
                WHERE customer_id = ? AND book_id = ?
                ORDER BY created_at ASC,id ASC";
        $result = prepared_statements($sql,'ii',[$customer_id,$book_id]);

        if($result === false) return false;

        $balance = 0;
        $lastDate = date('Y-m-d');

        while($row = $result->fetch_assoc()){
            $balance += (float)$row['debit_amount'] - (float)$row['credit_amount'];
            $lastDate = $row['created_at'];

            $sql = "UPDATE cashbook_customer_ledger SET balance = ? WHERE id = ?";
            $update = prepared_statements($sql,'di',[$balance,$row['id']]);

            if($update === false) return false;
        }

        $sql = "SELECT customer_id FROM cashbook_customer_balances
                WHERE customer_id = ? AND book_id = ?";
        $check = prepared_statements($sql,'ii',[$customer_id,$book_id]);

        if($check === false) return false;

        if($check->num_rows > 0){
            $sql = "UPDATE cashbook_customer_balances
                    SET balance = ?,date = ?
                    WHERE customer_id = ? AND book_id = ?";
            $saved = prepared_statements($sql,'dsii',[$balance,$lastDate,$customer_id,$book_id]);
        }else{
            $sql = "INSERT INTO cashbook_customer_balances
                    (customer_id,book_id,balance,date) VALUES (?,?,?,?)";
            $saved = prepared_statements($sql,'iids',[$customer_id,$book_id,$balance,$lastDate]);
        }

        return $saved !== false;

    }catch(Throwable $e){
        error_log('Customer ledger rebuild: '.$e->getMessage());
        return false;
    }
}

