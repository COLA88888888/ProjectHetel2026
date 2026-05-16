<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE payment_notifications");
    print_r($stmt->fetchAll());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
