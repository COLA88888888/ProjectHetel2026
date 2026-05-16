<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM rooms WHERE room_type = 'VIP' LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
