<?php
    $db_name = 'fru_cashbook';
    $user ='root';
    $pass = 'root';
    $host ='localhost';

    $server = mysqli_connect($host, $user, $pass, $db_name);

    if (!$server) {
        die("Connection failed: " . mysqli_connect_error());
    }
?>