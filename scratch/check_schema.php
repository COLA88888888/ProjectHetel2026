<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
echo "BOOKINGS COLUMNS:\n";
$s = $p->query('DESCRIBE bookings');
while($r = $s->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . "\n";
echo "\nORDERS COLUMNS:\n";
$s = $p->query('DESCRIBE orders');
while($r = $s->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . "\n";
