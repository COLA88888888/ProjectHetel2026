<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE expenses");
print_r($stmt->fetchAll());
?>
