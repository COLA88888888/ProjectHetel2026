<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$s = $p->query('DESCRIBE room_types');
while($r = $s->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . "\n";
