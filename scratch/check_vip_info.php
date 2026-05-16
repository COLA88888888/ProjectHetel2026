<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT MAX(room_number) as last_room FROM rooms");
$row = $stmt->fetch();
echo "Last room number: " . $row['last_room'] . "\n";

$stmt = $pdo->query("SELECT * FROM room_types WHERE room_type_name LIKE '%VIP%'");
$vip_types = $stmt->fetchAll();
print_r($vip_types);
