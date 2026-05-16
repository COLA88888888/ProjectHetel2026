<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE bookings");
$fields = $stmt->fetchAll();
foreach($fields as $f) {
    echo $f['Field'] . " - " . $f['Type'] . "\n";
}
?>
