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
                case 'businessProfile':
                    $name = request('name');
                    $email = request('email');
                    $address = request('address');
                    $contact1 = request('contact1');
                    $contact2 = request('contact2');
                    $regn = request('reg_no');

                    // check if a file has been submitted and upload it
                    if(isset($_REQUEST['file']))
                    {
                        $file = $_REQUEST['file'];
                        $profname = $file['name'];
                        $tmp = $file['tmp_name'];

                        // moce uploaded file
                        $sql = "INSERT INTO cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,profile=?,user_id = ?";
                        if(move_uploaded_file($tmp,'../images/'.$name))
                        {
                            if(prepared_statements($sql,'sssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$profname,$authid]))
                            {
                                echo "Success";
                            }
                        }
                    }else{
                        $sql = "INSERT INTO cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,user_id  = ?";
                        if(prepared_statements($sql,'ssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$authid]))
                        {
                            echo "Success";
                        }
                    }                
                    break;
            }
        }
    }else{
        redirect('../');
    }
?>