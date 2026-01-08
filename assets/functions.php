<?php
    session_start();
    require(__dir__.'/db_connect.php');
    if(isset($_GET['logout']))
    {
        session_destroy();
    }
    // FUNCTION TO submit query
    function prepared_statements($stat,$binds,$vars=[])
    {
        global $server;
        if(!empty($stat))
        {
            $stmt = $server->prepare($stat);
            $stmt->bind_param($binds,...$vars);
            $stmt->execute();            
            $res = $stmt->get_result();
            return $res;
        }else{
            return; // abort the function and return epty function
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
                <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
                <!-- Fontawesome -->
                <link rel="stylesheet" href="../assets/fontawesome-free-5.14.0-web/css/all.css">

                <!-- Custom CSS -->
                <link rel="stylesheet" href="../assets/css/custom.css">
                <link rel="stylesheet" href="../assets/css/xdialog.3.4.0.min.css">    
            </head>
            <body>
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
                <div class="container-fluid">
                    <?php sessionMessage();?>
        <?php
    }

    function pageFooter()
    {
        ?>
            </div>
                <script src="../assets/js/jquery-3.5.1.min.js"></script>
                <script src="../assets/js/bootstrap.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script src="../assets/js/xdialog.3.4.0.min.js"></script>
                <script src="../assets/js/custom.js"></script>
            </body>
        </html>
        <?php
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
        $stmt = "SELECT ct.*,c.name as category,p.name as paymode FROM cashbook_transactions ct 
                    LEFT JOIN cashbook_categories c ON c.id = ct.category_id
                    LEFT JOIN cashbook_paymodes p ON p.id = ct.paymode_id
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
?>