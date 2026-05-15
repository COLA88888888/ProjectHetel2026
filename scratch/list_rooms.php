<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$s = $p->query("SELECT id, room_number FROM rooms");
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $r['id'] . " -> Room: " . $r['room_number'] . "\n";
}
