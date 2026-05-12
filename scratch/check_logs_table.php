<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE system_logs");
    $columns = $stmt->fetchAll();
    echo json_encode($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
