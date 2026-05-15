<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$name = 'ໂຄລ່າ ສົມມິນທາ';
$phone = '207735433';
$s = $p->prepare("SELECT id, room_id, customer_name, customer_phone, check_in_date, check_out_date, status FROM bookings WHERE customer_name = ? OR customer_phone = ?");
$s->execute([$name, $phone]);
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
