<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE bookings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
