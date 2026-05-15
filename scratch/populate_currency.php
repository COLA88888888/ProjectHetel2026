<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
    $p->exec("UPDATE currency SET symbol_la = symbol, symbol_en = symbol, symbol_cn = symbol WHERE symbol_la IS NULL OR symbol_la = ''");
    echo "Success: Populated symbol columns.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
