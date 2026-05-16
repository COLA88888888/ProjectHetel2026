<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT room_number, room_type, bed_type FROM rooms");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
