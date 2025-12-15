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
                $role_id = request('role_id');

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
                        $_SESSION['verified']  = 1;
                        $_SESSION['code']  = time();
                        $user = json_decode(json_encode($dat));
                        $_SESSION['auth'] = $user;
                        
                        $response = [
                            'message'=>'Successfully Verified',
                            'link'=>'../',
                            'access'=>1
                        ];
                        
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
                echo json_encode($response);   
                exit;   
                break;
        }
    }
?>