<?php
    session_start();
    if(isset($_GET['logout']))
    {
        session_destroy();
    }
    header('Location:assets/db_tables.php');
?>