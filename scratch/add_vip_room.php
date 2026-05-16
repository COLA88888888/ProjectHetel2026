<?php
require_once 'config/db.php';
$sql = "INSERT INTO rooms (room_number, room_type, bed_type, price, status, housekeeping_status, bed_type_la, room_type_la) 
        VALUES ('401', 'VIP', 'ຕຽງດ່ຽວ', 500000, 'Available', 'ພ້ອມໃຊ້ງານ', 'ຕຽງດ່ຽວ', 'VIP')";
if ($pdo->exec($sql)) {
    echo "Room 401 (VIP Single Bed) added successfully.\n";
} else {
    echo "Failed to add room.\n";
}
