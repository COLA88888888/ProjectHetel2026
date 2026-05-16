<?php
require_once 'config/db.php';
echo "Bookings:\n";
$stmt = $pdo->query("SELECT payment_method, COUNT(*) FROM bookings GROUP BY payment_method");
print_r($stmt->fetchAll());
echo "\nOrders:\n";
$stmt = $pdo->query("SELECT payment_method, COUNT(*) FROM orders GROUP BY payment_method");
print_r($stmt->fetchAll());
?>
