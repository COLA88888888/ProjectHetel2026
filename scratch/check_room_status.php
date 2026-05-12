<?php
require_once 'config/db.php';
$res = $pdo->query("SELECT DISTINCT status, housekeeping_status FROM rooms")->fetchAll();
echo json_encode($res);
?>
