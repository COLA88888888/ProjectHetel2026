<?php
require_once 'config/db.php';
$start = '2026-05-01'; // Adjust if needed
$end = '2026-05-16';

$stmt = $pdo->prepare("SELECT status, COUNT(*) as count, SUM(total_price + food_charge) as total FROM bookings WHERE DATE(check_in_date) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$start, $end]);
print_r($stmt->fetchAll());
?>
