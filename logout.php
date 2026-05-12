<?php
    session_start();
    require_once 'config/db.php';
    logActivity($pdo, "ອອກຈາກລະບົບ");

    unset($_SESSION['fname']);
    unset($_SESSION['lname']);
    unset($_SESSION['user_id']);
    unset($_SESSION['checked']);

    session_destroy();
    
        header("location:index.php");
        exit;

?>
