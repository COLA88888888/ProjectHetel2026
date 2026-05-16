<?php
$host = 'localhost';
$db   = 'db_hotel';
$user = 'root';
$pass = '';
$charset = 'utf8';
require_once __DIR__ . '/logger.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);

     // Fetch Default Currency Global Variable
     $stmtCur = $pdo->query("SELECT * FROM currency WHERE is_default = 1 LIMIT 1");
     $defCurr = $stmtCur->fetch();
     if(!$defCurr) {
         // Fallback if none selected
         $defCurr = ['currency_name' => 'ກີບ', 'symbol' => '₭', 'currency_code' => 'LAK', 'exchange_rate' => 1];
     }

     // Global Currency Converter Function
     function formatCurrency($amount_kip) {
         global $defCurr;
         $rate = (float)($defCurr['exchange_rate'] ?? 1);
         if ($rate <= 0) $rate = 1;
         
         $converted = $amount_kip / $rate;
         
         // Format based on currency type (Decimals for USD/CNY, integer for LAK/THB)
         if (in_array($defCurr['currency_code'], ['USD', 'CNY', 'EUR'])) {
             return number_format($converted, 2) . ' ' . $defCurr['currency_name'];
         } else {
             return number_format($converted) . ' ' . $defCurr['currency_name'];
         }
     }
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
