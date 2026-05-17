<?php
require_once '../config/db.php';
$stmt = $pdo->query("SELECT * FROM product_categories");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
