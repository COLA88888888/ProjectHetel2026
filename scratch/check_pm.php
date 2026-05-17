<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT id, bill_number, customer_name, total_price, food_charge, deposit_amount, amount_received, payment_method FROM bookings WHERE bill_number = '2026010' OR customer_name = 'khola' LIMIT 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
