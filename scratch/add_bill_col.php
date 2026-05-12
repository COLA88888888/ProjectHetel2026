<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN bill_number VARCHAR(20) DEFAULT NULL");
    echo "Column added successfully";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage();
}
?>
