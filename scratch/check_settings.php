<?php
$p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
$s = $p->query("SELECT * FROM settings");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
