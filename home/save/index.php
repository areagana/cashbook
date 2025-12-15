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
                    $id = (isset($_REQUEST['business_id'])) ? request('business_id') : '';

                    // check if a file has been submitted and upload it
                    if(isset($_REQUEST['file']))
                    {
                        $file = $_REQUEST['file'];
                        $profname = $file['name'];
                        $tmp = $file['tmp_name'];

                        // check id
                        if(!empty($id))
                        {
                            // moce uploaded file
                            $sql = "UPDATE cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,profile=? WHERE id = ?";
                            if(move_uploaded_file($tmp,'../images/'.$name))
                            {
                                prepared_statements($sql,'sssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$profname,$id]);
                                $_SESSION['success'] ='Data Updated';
                            }
                        }else{
                            // moce uploaded file
                            $sql = "INSERT INTO cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,profile=?,user_id = ?";
                            if(move_uploaded_file($tmp,'../images/'.$name))
                            {
                                prepared_statements($sql,'sssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$profname,$authid]);
                                $_SESSION['success'] ='Data Updated';
                            }
                        }
                        
                    }else{
                        if(!empty($id))
                        {
                            // moce uploaded file
                            $sql = "UPDATE cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ? WHERE id = ?";
                            prepared_statements($sql,'ssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$id]);
                            $_SESSION['success'] ='Data Updated';
                        }else{
                             $sql = "INSERT INTO cashbook_business_profile SET name = ?, email=?, address = ?, contact1 = ?, contact2 = ?, reg_no = ?,user_id  = ?";
                            prepared_statements($sql,'ssssssi',[$name,$email,$address,$contact1,$contact2,$regn,$authid]);
                            $_SESSION['success'] ='Data Updated';
                        }
                    }                
                break;
            }
        }
    }else{
        redirect('../');
    }
?>