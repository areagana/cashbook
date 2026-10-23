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
                        // Load role assignments once, immediately after password verification.
                        $roleResult = prepared_statements(
                            "SELECT role_id FROM cashbook_role_user WHERE user_id = ?",
                            'i',
                            [(int) $dat['id']]
                        );
                        $roleIds = [];
                        while ($role = $roleResult->fetch_assoc()) {
                            $roleIds[] = (int) $role['role_id'];
                        }

                        // Load the user's book memberships for session-based access checks.
                        $bookResult = prepared_statements(
                            "SELECT book_id FROM cashbook_book_users WHERE user_id = ?",
                            'i',
                            [(int) $dat['id']]
                        );
                        $assignedBooks = [];
                        while ($book = $bookResult->fetch_assoc()) {
                            $assignedBooks[] = (int) $book['book_id'];
                        }

                        // Regenerate the session ID to protect against session fixation.
                        session_regenerate_id(true);
                        $_SESSION['verified'] = 1;
                        $_SESSION['code'] = time();
                        $_SESSION['auth'] = (object) $dat;
                        unset($_SESSION['auth']->password);
                        $_SESSION['user_id'] = (int) $dat['id'];
                        $_SESSION['role_ids'] = array_values(array_unique($roleIds));
                        $_SESSION['assigned_books'] = array_values(array_unique($assignedBooks));
                        
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