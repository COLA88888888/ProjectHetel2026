<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$tables = ['rooms', 'room_types', 'product_categories', 'products', 'currency', 'product_units'];
foreach($tables as $t) {
    echo "TABLE: $t\n";
    $s = $p->query("DESCRIBE $t");
    while($r = $s->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . "\n";
    echo "\n";
}
