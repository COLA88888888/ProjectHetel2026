<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE room_types");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
