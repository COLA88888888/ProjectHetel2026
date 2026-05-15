<?php
require_once 'config/db.php';
try {
    echo "Updating product_units...\n";
    $pdo->exec("ALTER TABLE product_units ADD COLUMN IF NOT EXISTS unit_name_la VARCHAR(255) AFTER unit_name");
    $pdo->exec("ALTER TABLE product_units ADD COLUMN IF NOT EXISTS unit_name_en VARCHAR(255) AFTER unit_name_la");
    $pdo->exec("ALTER TABLE product_units ADD COLUMN IF NOT EXISTS unit_name_cn VARCHAR(255) AFTER unit_name_en");
    
    // Fill with current unit_name
    $pdo->exec("UPDATE product_units SET unit_name_la = unit_name WHERE unit_name_la IS NULL OR unit_name_la = ''");
    echo "product_units updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
