<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['checked']) || $_SESSION['checked'] != 1) {
    header("Location: index.php");
    exit();
}

// Optional: Check for admin status if needed for certain pages
// if ($_SESSION['status'] != "ຜູ້ບໍລິຫານ") {
//     header("Location: menu_user.php");
//     exit();
// }
?>
