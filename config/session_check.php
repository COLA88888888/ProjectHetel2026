<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent browser caching for protected pages
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1) {
    // If inside a subfolder (like /users/), we need to go up one level
    $rootPath = (strpos($_SERVER['PHP_SELF'], '/users/') !== false || strpos($_SERVER['PHP_SELF'], '/rooms/') !== false || strpos($_SERVER['PHP_SELF'], '/room_types/') !== false) ? '../index.php' : 'index.php';
    header("Location: " . $rootPath);
    exit();
}
