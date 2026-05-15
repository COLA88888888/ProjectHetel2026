<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$s = $p->query("SELECT id, room_id, guest_count, customer_name, status FROM bookings WHERE id IN (28, 29)");
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
