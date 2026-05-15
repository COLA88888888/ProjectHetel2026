<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
    $p->exec("ALTER TABLE currency ADD COLUMN symbol_la VARCHAR(10), ADD COLUMN symbol_en VARCHAR(10), ADD COLUMN symbol_cn VARCHAR(10)");
    echo "Success: Added symbol columns to currency table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
