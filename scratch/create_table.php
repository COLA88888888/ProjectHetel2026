<?php
require_once 'config/db.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS payment_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        room_number VARCHAR(50) NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'payment_notifications' created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
