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
                // Registration must be POST-only; normalize emails consistently.
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    $response = ['message' => 'Invalid registration request.', 'link' => '', 'access' => 0];
                } else {
                    $name = trim((string)request('name', ''));
                    $email = strtolower(trim((string)request('email', '')));
                    $password1 = (string)request('password1', '');
                    $password2 = (string)request('password2', '');
                    $role_id = 1; // Existing registration convention; verify owner-role mapping separately.

                    try {
                        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $response = ['message' => 'Enter your name and a valid email address.', 'link' => '', 'access' => 0];
                        } elseif ($password1 === '' || $password1 !== $password2) {
                            $response = ['message' => 'Passwords are empty or do not match.', 'link' => '', 'access' => 0];
                        } else {
                            // Case-insensitive lookup, including older mixed-case addresses.
                            $existing = prepared_statements(
                                'SELECT id FROM cashbook_users WHERE LOWER(email) = ? LIMIT 1',
                                's',
                                [$email]
                            );

                            if ($existing->num_rows > 0) {
                                $response = [
                                    'message' => 'This email is already registered. Please log in.',
                                    'link' => '../', // Existing login entry point; adjust if your login URL differs.
                                    'access' => 0,
                                    'existing_account' => true
                                ];
                            } else {
                                $passwordHash = password_hash($password1, PASSWORD_DEFAULT);
                                prepared_statements(
                                    'INSERT INTO cashbook_users (name, email, password, role_id) VALUES (?, ?, ?, ?)',
                                    'sssi',
                                    [$name, $email, $passwordHash, $role_id]
                                );
                                $response = [
                                    'message' => 'Registration successful. Please log in.',
                                    'link' => '../',
                                    'access' => 1
                                ];
                            }
                        }
                    } catch (mysqli_sql_exception $error) {
                        // A database UNIQUE index also protects concurrent registrations.
                        if ((int)$error->getCode() === 1062) {
                            $response = [
                                'message' => 'This email is already registered. Please log in.',
                                'link' => '../',
                                'access' => 0,
                                'existing_account' => true
                            ];
                        } else {
                            error_log('KashHub registration error: ' . $error->getMessage());
                            $response = ['message' => 'Registration could not be completed. Please try again.', 'link' => '', 'access' => 0];
                        }
                    } catch (Throwable $error) {
                        error_log('KashHub registration error: ' . $error->getMessage());
                        $response = ['message' => 'Registration could not be completed. Please try again.', 'link' => '', 'access' => 0];
                    }
                }

                if (ob_get_length()) ob_clean();
                echo json_encode($response);
                exit;

            case 'LoginUser':
                $email = strtolower(trim((string)request('email')));
                $password = request('password');
                
                // check details from the table and continue
                $sql = "SELECT id,name,email,contact,business_id,password,role_id FROM cashbook_users WHERE LOWER(email) = ? LIMIT 1";
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