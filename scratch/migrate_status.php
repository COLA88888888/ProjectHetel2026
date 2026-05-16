<?php
require_once '../config/db.php';

try {
    $stmt = $pdo->prepare("UPDATE rooms SET housekeeping_status = 'ພ້ອມໃຊ້ງານ' WHERE housekeeping_status = 'ພ້ອມໃຊ້'");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " rooms from 'ພ້ອມໃຊ້' to 'ພ້ອມໃຊ້ງານ'.\n";
    
    $stmt2 = $pdo->prepare("UPDATE rooms SET housekeeping_status = 'ພ້ອມໃຊ້ງານ' WHERE housekeeping_status = 'Ready'");
    $stmt2->execute();
    echo "Updated " . $stmt2->rowCount() . " rooms from 'Ready' to 'ພ້ອມໃຊ້ງານ'.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
