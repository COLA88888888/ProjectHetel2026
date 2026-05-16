<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE expenses ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER expense_title");
    echo "Column 'category' added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
