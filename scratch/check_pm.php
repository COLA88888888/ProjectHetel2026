<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
echo "BOOKINGS PM:\n";
$s = $p->query('SELECT DISTINCT payment_method FROM bookings');
while($r = $s->fetch()) echo ($r[0] ?: "NULL") . "\n";
echo "\nORDERS PM:\n";
$s = $p->query('SELECT DISTINCT payment_method FROM orders');
while($r = $s->fetch()) echo ($r[0] ?: "NULL") . "\n";
