<?php
require_once 'config/db.php';
$tables = ['bookings', 'products', 'room_services'];
foreach($tables as $t) {
    echo "\nTable: $t\n";
    $stmt = $pdo->query("DESCRIBE $t");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
