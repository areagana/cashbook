<?php
    require_once(__dir__.'/../../assets/functions.php');
    if(isVerified())
    {
        if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $action = request('action');
            $authid = auth()->id;
            switch($action)
            {
                // case 'businessProfile':
                //     $name = request('name');
                //     $email = request('email');
                //     $address = request('address');
                //     $contact1 = request('contact1');
                //     $contact2 = request('contact2');
                //     $regn = request('reg_no');
                //     $id = (isset($_REQUEST['business_id'])) ? request('business_id') : '';

                //     // check if a file has been submitted and upload it
                //     if(isset($_REQUEST['file']))
                //     {
                //         $file = $_REQUEST['file'];
                //         $profname = $file['name'];
                //         $tmp = $file['tmp_name'];

                //         // check id
                //         if(!empty($id))
                //         {
                //             // moce uploaded file
                //             $sql = "UPDATE cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,profile=? WHERE id = ?";
                //             if(move_uploaded_file($tmp,'../images/'.$name))
                //             {
                //                 prepared_statements($sql,'sssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$profname,$id]);
                //                 $_SESSION['success'] ='Data Updated';
                //             }
                //         }else{
                //             // moce uploaded file
                //             $sql = "INSERT INTO cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,profile=?,user_id = ?";
                //             if(move_uploaded_file($tmp,'../images/'.$name))
                //             {
                //                 prepared_statements($sql,'sssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$profname,$authid]);
                //                 $_SESSION['success'] ='Data Updated';
                //             }
                //         }
                        
                //     }else{
                //         if(!empty($id))
                //         {
                //             // moce uploaded file
                //             $sql = "UPDATE cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ? WHERE id = ?";
                //             prepared_statements($sql,'ssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$id]);
                //             $_SESSION['success'] ='Data Updated';
                //         }else{
                //              $sql = "INSERT INTO cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,user_id  = ?";
                //             prepared_statements($sql,'ssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$authid]);
                //             $_SESSION['success'] ='Data Updated';
                //         }
                //     }                
                // break;
                case 'businessProfile':

                    $name     = trim(request('name'));
                    $email    = trim(request('email'));
                    $address  = trim(request('address'));
                    $contact1 = trim(request('contact1'));
                    $contact2 = trim(request('contact2'));
                    $regn     = trim(request('reg_no'));

                    $id = !empty($_POST['business_id'])
                        ? (int) $_POST['business_id']
                        : 0;

                    if (empty($name)) {
                        throw new Exception('Business name is required.');
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CURRENT BUSINESS
                    |--------------------------------------------------------------------------
                    */

                    $oldProfile = '';

                    if ($id > 0) {

                        $oldResult = prepared_statements(
                            "SELECT profile
                            FROM cashbook_business_profile
                            WHERE id = ?
                            LIMIT 1",
                            'i',
                            [$id]
                        );

                        if ($oldResult && $oldResult->num_rows > 0) {

                            $oldRow = $oldResult->fetch_assoc();

                            $oldProfile = $oldRow['profile'] ?? '';
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LOGO UPLOAD
                    |--------------------------------------------------------------------------
                    */

                    $newProfile = null;

                    if (
                        isset($_FILES['profile']) &&
                        $_FILES['profile']['error'] === UPLOAD_ERR_OK
                    ) {

                        $file = $_FILES['profile'];


                        /*
                        |--------------------------------------------------------------------------
                        | DIRECTORY
                        |--------------------------------------------------------------------------
                        */

                        $uploadDirectory =
                            dirname(__DIR__, 2)
                            . '/assets/uploads/business_logos/';


                        if (!is_dir($uploadDirectory)) {

                            if (!mkdir($uploadDirectory, 0755, true)) {
                                throw new Exception(
                                    'Unable to create logo directory.'
                                );
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | SIZE
                        |--------------------------------------------------------------------------
                        */

                        if ($file['size'] > (2 * 1024 * 1024)) {

                            throw new Exception(
                                'Logo must not exceed 2MB.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | MIME TYPE
                        |--------------------------------------------------------------------------
                        */

                        $allowedTypes = [
                            'image/png'  => 'png',
                            'image/jpeg' => 'jpg',
                            'image/webp' => 'webp'
                        ];


                        $finfo = new finfo(FILEINFO_MIME_TYPE);

                        $mime =
                            $finfo->file($file['tmp_name']);


                        if (!isset($allowedTypes[$mime])) {

                            throw new Exception(
                                'Only PNG, JPG and WebP images are allowed.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | VALID IMAGE
                        |--------------------------------------------------------------------------
                        */

                        if (@getimagesize($file['tmp_name']) === false) {

                            throw new Exception(
                                'Invalid image file.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | FILENAME
                        |--------------------------------------------------------------------------
                        */

                        $extension =
                            $allowedTypes[$mime];


                        $newProfile =
                            'business_'
                            . ($id ?: $authid)
                            . '_'
                            . bin2hex(random_bytes(8))
                            . '.'
                            . $extension;


                        $destination =
                            $uploadDirectory
                            . $newProfile;


                        if (!move_uploaded_file(
                            $file['tmp_name'],
                            $destination
                        )) {

                            throw new Exception(
                                'Unable to save business logo.'
                            );
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE BUSINESS
                    |--------------------------------------------------------------------------
                    */

                    if ($id > 0) {

                        if ($newProfile !== null) {

                            $sql = "
                                UPDATE cashbook_business_profile
                                SET
                                    name = ?,
                                    email = ?,
                                    address = ?,
                                    contact1 = ?,
                                    contact2 = ?,
                                    reg_no = ?,
                                    profile = ?
                                WHERE id = ?
                            ";

                            $save = prepared_statements(
                                $sql,
                                'sssssssi',
                                [
                                    $name,
                                    $email,
                                    $address,
                                    $contact1,
                                    $contact2,
                                    $regn,
                                    $newProfile,
                                    $id
                                ]
                            );

                        } else {

                            $sql = "
                                UPDATE cashbook_business_profile
                                SET
                                    name = ?,
                                    email = ?,
                                    address = ?,
                                    contact1 = ?,
                                    contact2 = ?,
                                    reg_no = ?
                                WHERE id = ?
                            ";

                            $save = prepared_statements(
                                $sql,
                                'ssssssi',
                                [
                                    $name,
                                    $email,
                                    $address,
                                    $contact1,
                                    $contact2,
                                    $regn,
                                    $id
                                ]
                            );
                        }

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE BUSINESS
                        |--------------------------------------------------------------------------
                        */

                        if ($newProfile !== null) {

                            $sql = "
                                INSERT INTO cashbook_business_profile
                                (
                                    name,
                                    email,
                                    address,
                                    contact1,
                                    contact2,
                                    reg_no,
                                    profile,
                                    user_id
                                )
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ";

                            $save = prepared_statements(
                                $sql,
                                'sssssssi',
                                [
                                    $name,
                                    $email,
                                    $address,
                                    $contact1,
                                    $contact2,
                                    $regn,
                                    $newProfile,
                                    $authid
                                ]
                            );

                        } else {

                            $sql = "INSERT INTO cashbook_business_profile
                                (
                                    name,
                                    email,
                                    address,
                                    contact1,
                                    contact2,
                                    reg_no,
                                    user_id
                                )
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ";

                            $save = prepared_statements($sql,'ssssssi',
                                [
                                    $name,
                                    $email,
                                    $address,
                                    $contact1,
                                    $contact2,
                                    $regn,
                                    $authid
                                ]
                            );
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VERIFY DATABASE SAVE
                    |--------------------------------------------------------------------------
                    */

                    if (!$save) {

                        /*
                        | Remove newly uploaded file because database save failed.
                        */

                        if (
                            $newProfile &&
                            isset($destination) &&
                            is_file($destination)
                        ) {
                            @unlink($destination);
                        }

                        throw new Exception(
                            'Business profile database update failed.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | REMOVE OLD LOGO
                    |--------------------------------------------------------------------------
                    */

                    if ($newProfile && !empty($oldProfile) && $oldProfile !== $newProfile
                    ) {

                        $oldFile = $uploadDirectory . basename($oldProfile);
                        if (is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                    }


                    $_SESSION['success'] = 'Business profile updated successfully.';

                break;
            }
        }
    }else{
        redirect('../');
    }
?>