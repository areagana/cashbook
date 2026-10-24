<?php
    ob_start();
    ini_set('display_errors', 0);
    error_reporting(0);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json; charset=utf-8');
    require_once(__dir__.'/../../assets/functions.php');
    if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
    {
        $action = request('action');

        switch($action)
        {
            case 'RegisterUser':
                $name = request('name');
                $email = request('email');
                $password1 = request('password1');
                $password2 = request('password2');
                $role_id = 1;

                    if($password1 == $password2)
                    {
                        $password = password_hash($password1,PASSWORD_DEFAULT);
                        // check if a file has been submitted and upload it
                        $sql = "INSERT INTO cashbook_users SET name = ?, email=?, password = ?,role_id =?";
                        prepared_statements($sql,'sssi',[$name,$email,$password,$role_id]);

                        $response = [
                            'message'=>'Registration successful',
                            'link'=>'../',
                            'access'=>1
                        ];
                    }else{
                        $response = [
                            'message'=>'Passwords should be Similar',
                            'link'=>'../signup.php',
                            'access'=>0
                        ];
                    }
                    echo json_encode($response);
                    exit;
                break;

            case 'LoginUser':
                $email = request('email');
                $password = request('password');
                
                // check details from the table and continue
                $sql = "SELECT id,name,email,contact,business_id,password,role_id FROM cashbook_users WHERE email = ?";
                $res = prepared_statements($sql,'s',[$email]);
                $dat = $res->fetch_assoc();
               
                if($res->num_rows > 0)
                {
                    if(password_verify($password,$dat['password']))
                    {
                        // Establish a fresh authenticated session.
                        session_regenerate_id(true);
                        $_SESSION['verified'] = 1;
                        $_SESSION['code'] = time();
                        $_SESSION['auth'] = (object) $dat;
                        unset($_SESSION['auth']->password);
                        $_SESSION['user_id'] = (int) $dat['id'];

                        // RBAC: load role names, book assignments and permissions.
                        // This must run AFTER auth is established, and only once at login.
                        try {
                            // Initialize ONLY RBAC tables; db_tables.php runs unrelated
                            // migrations and redirects when included, so do not include it.
                            if (!function_exists('khCreatePermissionTables') || !function_exists('khLoadAccessSession')) {
                                throw new RuntimeException('RBAC functions are unavailable; check assets/rbac.php inclusion.');
                            }
                            khCreatePermissionTables();
                            khLoadAccessSession((int) $dat['id']);

                            $response = [
                                'message' => 'Successfully Verified',
                                'link' => '../',
                                'access' => 1
                            ];
                        } catch (Throwable $error) {
                            error_log('KashHub login RBAC error: ' . $error->getMessage());
                            unset($_SESSION['verified'], $_SESSION['code'], $_SESSION['auth'], $_SESSION['user_id']);
                            unset($_SESSION['role_ids'], $_SESSION['role_names'], $_SESSION['assigned_books']);
                            unset($_SESSION['permissions'], $_SESSION['owned_businesses'], $_SESSION['permissions_loaded_at']);
                            $response = [
                                'message' => 'Unable to load account permissions. Contact your administrator.',
                                'link' => '',
                                'access' => 0
                            ];
                        }
                    }else{ // not verified
                        $response = [
                            'message'=>'Wrong Password Entered!!. Please try again',
                            'link'=>'',
                            'access'=>0
                        ];
                    }   
                }else{
                    $response = [
                        'message'=>'Sorry, we seam not to be having your details. Please register.',
                        'link'=>'',
                        'access'=>0
                    ];
                }
                // Preserve a valid JSON response even if an included file emitted whitespace.
                if (ob_get_length()) ob_clean();
                echo json_encode($response);
                exit;   
                break;
        }
    }
?>