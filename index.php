<?php
    require_once(__dir__.'/assets/functions.php');
    if(isset($_GET['logout']))
    {
        session_destroy();
    }
    // CHECK VERIFICATION
    if(isVerified())
    {
        redirect('assets/db_tables.php');
    }else{
        redirect('login/');
    }
    
?>