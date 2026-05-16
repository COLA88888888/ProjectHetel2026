<?php
require 'config/db.php';
echo $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
?>
