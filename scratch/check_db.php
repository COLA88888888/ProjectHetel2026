<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$stmt = $pdo->query('DESCRIBE products');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
