<?php
    session_start();
    require(__dir__.'/db_connect.php');
    date_default_timezone_set('Africa/Kampala');
    if(isset($_GET['logout']))
    {
        session_destroy();
    }

    // function prepared_statements($stat, $binds = '', $vars = [])
    // {
    //     global $server;

    //     if(empty($stat))
    //     {
    //         return false;
    //     }

    //     $stmt = $server->prepare($stat);

    //     if(!$stmt)
    //     {
    //         die("Prepare failed: " . $server->error);
    //     }

    //     /*
    //     =====================================
    //     BIND PARAMETERS
    //     =====================================
    //     */

    //     if(!empty($binds) && !empty($vars))
    //     {
    //         $stmt->bind_param($binds, ...$vars);
    //     }

    //     /*
    //     =====================================
    //     EXECUTE
    //     =====================================
    //     */

    //     if(!$stmt->execute())
    //     {
    //         die("Execute failed: " . $stmt->error);
    //     }

    //     /*
    //     =====================================
    //     DETECT QUERY TYPE
    //     =====================================
    //     */

    //     $queryType = strtoupper(strtok(trim($stat), " "));

    //     /*
    //     =====================================
    //     SELECT QUERIES
    //     =====================================
    //     */

    //     if($queryType == 'SELECT')
    //     {
    //         return $stmt->get_result();
    //     }

    //     /*
    //     =====================================
    //     INSERT QUERIES
    //     =====================================
    //     */

    //     if($queryType == 'INSERT')
    //     {
    //         return $stmt->insert_id;
    //     }

    //     /*
    //     =====================================
    //     UPDATE / DELETE
    //     =====================================
    //     */

    //     return $stmt->affected_rows;
    // }

    function prepared_statements($stat, $binds = '', $vars = [])
    {
        global $server;

        if (empty(trim($stat))) {
            return false;
        }

        /*
        =====================================
        PREPARE
        =====================================
        */

        $stmt = $server->prepare($stat);

        if (!$stmt) {
            die("Prepare failed: " . $server->error);
        }

        /*
        =====================================
        BIND PARAMETERS
        =====================================
        */

        if (!empty($binds) && !empty($vars)) {

            if (strlen($binds) != count($vars)) {
                die("Bind parameter count does not match variable count.");
            }

            $stmt->bind_param($binds, ...$vars);
        }

        /*
        =====================================
        EXECUTE
        =====================================
        */

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        /*
        =====================================
        DETECT QUERY TYPE
        =====================================
        */

        $query = ltrim($stat);

        /*
        Remove SQL comments if they appear
        before the actual query.
        */

        $query = preg_replace('/^(--[^\n]*\n|\/\*.*?\*\/\s*)+/s', '', $query);

        $queryType = strtoupper(strtok(ltrim($query), " \t\r\n"));

        /*
        =====================================
        SELECT
        =====================================
        */

        if ($queryType === 'SELECT') {

            $result = $stmt->get_result();

            if ($result === false) {
                die("Unable to get SELECT result: " . $stmt->error);
            }

            return $result;
        }

        /*
        =====================================
        INSERT
        =====================================
        */

        if ($queryType === 'INSERT') {
            return $stmt->insert_id;
        }

        /*
        =====================================
        UPDATE / DELETE
        =====================================
        */

        return $stmt->affected_rows;
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

    function request($data)
    {
        $request = (isset($_REQUEST)) ? e($_REQUEST[$data]) : e($_POST[$data]);
        return  $request;
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
        $stmt = "SELECT ct.*,c.name as category,p.name as paymode,i.name as item_name,cust.name as customer_name,ci.id as invoice_id,ci.invoice_no FROM cashbook_transactions ct 
                    LEFT JOIN cashbook_categories c ON c.id = ct.category_id
                    LEFT JOIN cashbook_paymodes p ON p.id = ct.paymode_id
                    LEFT JOIN cashbook_items i ON i.id = ct.item_id
                    LEFT JOIN cashbook_customers cust ON cust.id = ct.customer_id
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
        $sql = "SELECT * FROM cashbook_routes WHERE id = ?";
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
    function customerLedgerUpdate($customer_id,$credit,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$type = false)
    {
        
        // check if the transaction already exists to avoid incrementing balance twice
        $check_sql = "SELECT * FROM cashbook_customer_ledger WHERE transaction_id = ?";
        $check_res = prepared_statements($check_sql, 'i', [$trans_id]);
        
        if ($check_res->num_rows > 0) 
        {
            // Transaction already exists, update it
            $balance = getCustomerBalanceBeforeTransaction($customer_id, $trans_id);

            // use balance to update the ledger
            $newBalance = $balance + $debit - $credit;
            $_SESSION['success'] = $newBalance."deb -".$debit." Cred -".$credit;

            $update_sql = "UPDATE cashbook_customer_ledger SET type = ?, customer_id = ?, credit_amount = ?, debit_amount = ?, details = ?,book_id = ?,paymode_id = ?,created_at=?,user_id=?,item_id = ?, quantity = ?,balance = ? WHERE transaction_id = ?";
            prepared_statements($update_sql,'siddsiisiiidi',[$type,$customer_id,$credit,$debit,$details,$book_id,$payment_mode,$date,$user_id,$item_id,$qty,$newBalance,$trans_id]);

            // update customer balance
            saveCustomerBalance($customer_id,$newBalance,$date);
        
        }else {
            // get customer balance
            $balance = getCustomerBalance($customer_id);
           
            // use balance to update the ledger
            $newBalance = $balance + $debit - $credit;

            // Insert new transaction
            $stmt = "INSERT INTO  cashbook_customer_ledger SET type=?, customer_id = ?, credit_amount = ?, debit_amount = ?, details = ?,book_id = ?,paymode_id = ?,transaction_id = ?,created_at=?,user_id=?,item_id = ?, quantity = ?,balance = ?";
            prepared_statements($stmt,'siddsiiisiiid',[$type,$customer_id,$credit,$debit,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$newBalance]);  

            // update customer balance
            saveCustomerBalance($customer_id,$newBalance,$date);
        }
    }

    // set book function
    function setBook($book)
    {
        $_SESSION['book_id'] = encryptor('encrypt',$book->id);
    }

     //header and footer functions
    function pageHeader($header = false)
    {
        ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?=($header) ? $header : "Cashbook";?></title>
                
                <!-- Bootstrap 4 -->
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
                

                <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"> -->

                <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
                <!-- Fontawesome -->
                <link rel="stylesheet" href="../assets/fontawesome-free-5.14.0-web/css/all.css">

                <!-- Custom CSS -->
                <link rel="stylesheet" href="../assets/css/custom.css">
                <link rel="stylesheet" href="../assets/style.css">
                <link rel="stylesheet" href="../assets/css/xdialog.3.4.0.min.css"> 
                
                <!-- linie icons -->
                <link rel="stylesheet" href="https://cdn.lineicons.com/5.1/line/lineicons.css" />
                <link rel="stylesheet" href="https://cdn.lineicons.com/5.1/solid/lineicons-solid.css" />

                <!-- select 2 css -->
                <link rel='stylesheet' href='https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css'>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css"> 
            </head>
            <body>
                <div class="wrapper">
                    <?=(isset($_REQUEST['book_id']) || isset($_REQUEST['bkid']) || isset($_REQUEST['bsid'])) ? sideBar() : ""; ?>
                    <div class="main">
                        <nav class="navbar navbar-expand-lg bg-success">
                            <div class="container-fluid">
                                <a class="navbar-brand text-white" href="../">FR(U)-CASHBOOK</a>
                                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
                                    <span class="navbar-toggler-icon"></span>
                                    </button>
                                <div class="collapse navbar-collapse" id="navbarText">
                                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                        <li class="nav-item">
                                            
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <form class="form-inline my-2 my-lg-0">
                                <div class="dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-user"></i> <?=$_SESSION['auth']->name;?>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#">Action</a>
                                        <a class="dropdown-item" href="#">Profile</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="../?logout=1"><strong>Logout</strong></a>
                                    </div>
                                </div>
                            </form>
                        </nav>
                        <?php sessionMessage();?>
                
        <?php
    }

    function pageFooter()
    {
        ?>           </div> <!-- end main -->
                </div> <!-- end wrapper -->
                    <script src="../assets/js/jquery-3.5.1.min.js"></script>
                    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> -->
                    
                    <!-- select 2 scripts -->
                    <!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
                    <script src="../assets/js/bootstrap.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script src ="https://cdn.datatables.net/2.3.8/js/dataTables.min.js" ></script>
                    <script src="../assets/js/xdialog.3.4.0.min.js"></script>
                    <script src="../assets/js/select2.min.js" defer></script>
                    <script src="../assets/js/custom.js"></script>
                    <script src="../assets/script.js"></script>
            </body>
        </html>
        <?php
    }

    function sideBar()
    {
        global $book;
        $book = bookFind(encryptor('decrypt',$_SESSION['book_id'])) ?? bookFind(encryptor('decrypt',$_REQUEST['bkid']));
        ?>
            <aside id="sidebar">
                <div class="sidebar-header">
                    <button id="toggle-btn" type="button" class='p-2'>
                        <i class="fa fa-bars"></i>
                    </button>
                    <div class="sidebar-logo h3">
                        <a href="#" class="sidebar-link">
                            <span>CASHBOOK</span>
                        </a>
                    </div>
                </div>

                <ul class="sidebar-nav">
                    <li class='sidebar-item'>
                        <a href="../" class ='sidebar-link'>
                            <i class="fa fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class='sidebar-item'>
                        <a href="../customers/?bsid=<?=encryptor('encrypt',$book->id);?>" class ='sidebar-link'>
                            <i class="fa fa-user"></i>
                            <span>Customers</span>
                        </a>
                    </li>

                    <li class='sidebar-item'>
                        <a href="../stock/?bkid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <i class="fa fa-box"></i>
                            <span>Stock</span>
                        </a>
                    </li>
                    <li class='sidebar-item'>
                        <a href="../items/?bsid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <i class="fa fa-box"></i>
                            <span>Items</span>
                        </a>
                    </li>
                    <li class='sidebar-item'>
                        <a href="../members/?bkid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <i class="fa fa-users"></i>
                            <span>Users</span>
                        </a>
                        <!-- <ul id="usersMenu" class="collapse">
                            <li><a href="../members/?bkid=<?//=encryptor('encrypt',$book->id);?>">View Users</a></li>
                            <li><a href="#">Add User</a></li>
                        </ul> -->
                    </li>
                    <li class='sidebar-item'>
                        <a href="../category/?bkid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <i class="fa fa-box"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class='sidebar-item'>
                        <a href="../modes/?bsid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <i class="fa fa-handshake"></i>
                            <span>Pay Modes</span>
                        </a>
                    </li>
                    <li class='sidebar-item'>
                        <a href="../invoices/?bsid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <!-- data-toggle="collapse" data-target="#Invoices-menu" -->
                            <i class="fa fa-undo"></i>
                            <span>Invoices</span>
                        </a>
                    </li>
                    <li class='sidebar-item'>
                        <a href="../routes/?bsid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link has-dropdown collapsed'>
                            <i class="fa fa-redo"></i>
                            <span>Routes</span>
                        </a>
                    </li>
                    <li class='sidebar-item'>
                        <a href="../route_managers/?bsid=<?=encryptor('encrypt',$book->id);?>" class='sidebar-link'>
                            <i class="fa fa-redo"></i>
                            <span>Route Managers</span>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-footer">
                    <a href="../?logout=1">
                        <i class="fa fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>

            </aside>
        <?php
    }

    // invoice find function
    function invoiceFind($id)
    {
        $stmt = "SELECT ci.*,cc.name as customer,cc.contact FROM cashbook_invoices ci 
                    LEFT JOIN cashbook_customers cc ON cc.id = ci.customer_id
                WHERE ci.id = ?";
        $res = prepared_statements($stmt,'i',[$id]);
        return myObject($res->fetch_assoc());
    }

    function getCustomerBalance($customer_id)
    {
        global $server;

        $stmt = $server->prepare("
            SELECT COALESCE(balance, 0) AS balance
            FROM cashbook_customer_ledger
            WHERE customer_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $customer_id);
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

    function insertCustomerLedgerInvoice($customer_id, $type,$amount,$invoice_id,$trans_id,$book_id,$invoice_no)
    {
        global $server;

        $currentBalance = getCustomerBalance($customer_id);
        $invoice = invoiceFind($invoice_id);
        $user_id = auth()->id;
        // get new balance
        $newBalance = $currentBalance + $amount;

        $stmt = $server->prepare("INSERT INTO cashbook_customer_ledger(customer_id,type,debit_amount,balance,invoice_id,invoice_amount,transaction_id,book_id,user_id,details,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('isddidiiiss',$customer_id,$type,$amount,$newBalance,$invoice_id,$amount,$trans_id,$book_id,$user_id,$invoice_no,$invoice->created_at);

        // update customer balance
        saveCustomerBalance($customer_id,$newBalance,$invoice->created_at); 
        return $stmt->execute();
    }

    // update customer balances table
    function saveCustomerBalance($customer_id,$balance,$date)
    {
        global $server;
        //   check if the customer  exists in the cashbook_customer_balances table
        $check_stmt = "SELECT * FROM cashbook_customer_balances WHERE customer_id = ?";
        $check = prepared_statements($check_stmt,'i',[$customer_id]);

        if($check->num_rows > 0)
        {
            $stmt = "UPDATE cashbook_customer_balances SET balance = ?,date = ? WHERE customer_id = ?";
            return prepared_statements($stmt,'dis',[$balance,$date,$customer_id]);
        }else
        {
            $stmt = "INSERT INTO cashbook_customer_balances SET customer_id = ?, balance = ?,date = ?";
            return prepared_statements($stmt,'ids',[$customer_id,$balance,$date]);
        }
    }
?>