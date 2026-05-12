<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM room_types");
echo json_encode($stmt->fetchAll());
?>
