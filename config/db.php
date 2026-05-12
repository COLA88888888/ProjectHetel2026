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
         $defCurr = ['currency_name' => 'ກີບ', 'symbol' => '₭', 'currency_code' => 'LAK'];
     }
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
