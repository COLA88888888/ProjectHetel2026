<?php
    session_start();

        unset($_SESSION['fname']);
        unset($_SESSION['lname']);
        unset($_SESSION['user_id']);
        unset($_SESSION['checked']);

    session_destroy();
    
        header("location:index.php");
        exit;

?>
