<?php
    session_start();
    require(__dir__.'/db_connect.php');
    date_default_timezone_set('Africa/Kampala');
    if(isset($_GET['logout']))
    {
        session_destroy();
    }

    function prepared_statements($stat, $binds = '', $vars = [])
    {
        global $server;

        if(empty($stat))
        {
            return false;
        }

        $stmt = $server->prepare($stat);

        if(!$stmt)
        {
            die("Prepare failed: " . $server->error);
        }

        /*
        =====================================
        BIND PARAMETERS
        =====================================
        */

        if(!empty($binds) && !empty($vars))
        {
            $stmt->bind_param($binds, ...$vars);
        }

        /*
        =====================================
        EXECUTE
        =====================================
        */

        if(!$stmt->execute())
        {
            die("Execute failed: " . $stmt->error);
        }

        /*
        =====================================
        DETECT QUERY TYPE
        =====================================
        */

        $queryType =
            strtoupper(
                strtok(trim($stat), " ")
            );

        /*
        =====================================
        SELECT QUERIES
        =====================================
        */

        if($queryType == 'SELECT')
        {
            return $stmt->get_result();
        }

        /*
        =====================================
        INSERT QUERIES
        =====================================
        */

        if($queryType == 'INSERT')
        {
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
        $stmt = "SELECT ct.*,c.name as category,p.name as paymode,i.name as item_name,cust.name as customer_name FROM cashbook_transactions ct 
                    LEFT JOIN cashbook_categories c ON c.id = ct.category_id
                    LEFT JOIN cashbook_paymodes p ON p.id = ct.paymode_id
                    LEFT JOIN cashbook_items i ON i.id = ct.item_id
                    LEFT JOIN cashbook_customers cust ON cust.id = ct.customer_id
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
        $stmt = "SELECT * FROM cashbook_customers WHERE id = ?";
        $res = prepared_statements($stmt,'i',[$id]);
        $row = $res->fetch_assoc();
        return myObject($row);
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
    function hasRole($array)
    {
        global $server;
        $auth = auth();
        $roles = mysqli_query($server,"SELECT * FROM cashbook_roles");
        $dat =[];
        while($r = $roles->fetch_assoc())
        {
            if($r['id']== $auth->role_id)
            {
                $dat[] = $r['name'];
            }
        }

        foreach($array as $role)
        {
            if(in_array($role,$dat))
            {
                return true;
            }else{
                return false;
            }
        }
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
    function customerLedgerUpdate($customer_id,$credit,$debit,$category_id,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty)
    {
        $sql = "
            SELECT 
                customer_id,
                SUM(debit_amount - credit_amount) AS balance
            FROM cashbook_customer_ledger
            WHERE customer_id = ? AND book_id = ?
            GROUP BY customer_id
        ";

        $res = prepared_statements($sql, 'ii', [$customer_id, $book_id]);
        $row = $res->fetch_assoc();

        $balance = $row['balance'] ?? 0;
        // use balance tp update the ledger
        $newBalance = $balance + $debit - $credit;
        
        $stmt = "INSERT INTO  cashbook_customer_ledger SET customer_id = ?, credit_amount = ?, debit_amount = ?, details = ?,book_id = ?,paymode_id = ?,transaction_id = ?,created_at=?,user_id=?,item_id = ?, quantity = ?,balance = ?";
        prepared_statements($stmt,'iddsiiisiiid',[$customer_id,$credit,$debit,$details,$book_id,$payment_mode,$trans_id,$date,$user_id,$item_id,$qty,$newBalance]);  
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

                <link = rel='stylesheet' href='https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css'>  
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

                    <script src="../assets/js/bootstrap.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script src ="https://cdn.datatables.net/2.3.8/js/dataTables.min.js" ></script>
                    <script src="../assets/js/xdialog.3.4.0.min.js"></script>
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
                        <a href="#" class='sidebar-link has-dropdown collapsed' data-toggle="collapse" data-target="#stockMenu">
                            <i class="fa fa-box"></i>
                            <span>Stock</span>
                        </a>
                        <ul id="stockMenu" class="collapse">
                            <li><a href="../stock/?bkid=<?=encryptor('encrypt',$book->id);?>">View Stock</a></li>
                            <li><a href="#">Add Stock</a></li>
                        </ul>
                    </li>
                    <li class='sidebar-item'>
                        <a href="#" class='sidebar-link has-dropdown collapsed' data-toggle="collapse" data-target="#itemsMenu">
                            <i class="fa fa-box"></i>
                            <span>Items</span>
                        </a>
                        <ul id="itemsMenu" class="collapse">
                            <li><a href="../items/?bsid=<?=encryptor('encrypt',$book->id);?>">View Items</a></li>
                            <li><a href="#">Add Item</a></li>
                        </ul>
                    </li>
                    <li class='sidebar-item'>
                        <a href="#" class='sidebar-link has-dropdown collapsed' data-toggle="collapse" data-target="#usersMenu">
                            <i class="fa fa-users"></i>
                            <span>Users</span>
                        </a>
                        <ul id="usersMenu" class="collapse">
                            <li><a href="../members/?bkid=<?=encryptor('encrypt',$book->id);?>">View Users</a></li>
                            <li><a href="#">Add User</a></li>
                        </ul>
                    </li>
                    <li class='sidebar-item'>
                        <a href="#" class='sidebar-link has-dropdown collapsed' data-toggle="collapse" data-target="#categoriesMenu">
                            <i class="fa fa-box"></i>
                            <span>Categories</span>
                        </a>
                        <ul id="categoriesMenu" class="collapse">
                            <li><a href="../categories/?bkid=<?=encryptor('encrypt',$book->id);?>">View Categories</a></li>
                            <li><a href="#">Add Category</a></li>
                        </ul>
                    </li>
                    <li class='sidebar-item'>
                        <a href="#" class='sidebar-link has-dropdown collapsed' data-toggle="collapse" data-target="#PayModesMenu">
                            <i class="fa fa-handshake"></i>
                            <span>Pay Modes</span>
                        </a>
                        <ul id="PayModesMenu" class="collapse">
                            <li><a href="../modes/?bsid=<?=encryptor('encrypt',$book->id);?>">View Pay Modes</a></li>
                            <li><a href="#">Add Category</a></li>
                        </ul>
                    </li>
                    <li class='sidebar-item'>
                        <a href="#" class='sidebar-link has-dropdown collapsed' data-toggle="collapse" data-target="#RouteManagersMenu">
                            <i class="fa fa-undo"></i>
                            <span>Invoices</span>
                        </a>
                        <ul id="RouteManagersMenu" class="collapse">
                            <li><a href="../invoices/?bsid=<?=encryptor('encrypt',$book->id);?>">View Invoices</a></li>
                        </ul>
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

        $sql = $server->prepare("
            SELECT COALESCE( SUM(CASE
                                WHEN type IN ('invoice','credit_sale')
                                THEN debit_amount
                                ELSE -credit_amount
                            END
                        ),0
                    ) AS balance
                FROM cashbook_customer_ledger
                WHERE customer_id = ?
            ");
        $sql->bind_param('i',$customer_id);
        $sql->execute();

        return $sql->get_result()->fetch_assoc()['balance'];
    }

    function insertCustomerLedgerInvoice($customer_id, $type,$amount,$invoice_id,$trans_id,$book_id,$invoice_no)
    {
        global $server;

        $currentBalance = getCustomerBalance($customer_id);
        $user_id = auth()->id;
        // get new balance
        $newBalance = $currentBalance + $amount;

        $stmt = $server->prepare("INSERT INTO cashbook_customer_ledger(customer_id,type,debit_amount,balance,invoice_id,invoice_amount,transaction_id,book_id,user_id,details) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('isddidiiis',$customer_id,$type,$amount,$newBalance,$invoice_id,$amount,$trans_id,$book_id,$user_id,$invoice_no);
        return $stmt->execute();
    }
?>