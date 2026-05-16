<?php
require_once 'config/db.php';

$rooms_to_add = [
    ['402', 'VIP', 'ຕຽງດ່ຽວ', 500000],
    ['403', 'VIP', 'ຕຽງດ່ຽວ', 500000],
    ['404', 'VIP', 'ຕຽງດ່ຽວ', 500000],
    ['405', 'VIP', 'ຕຽງດ່ຽວ', 500000],
];

foreach ($rooms_to_add as $room) {
    $sql = "INSERT INTO rooms (room_number, room_type, bed_type, price, status, housekeeping_status, bed_type_la, room_type_la) 
            VALUES (?, ?, ?, ?, 'Available', 'ພ້ອມໃຊ້ງານ', ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$room[0], $room[1], $room[2], $room[3], $room[2], $room[1]])) {
        echo "Room {$room[0]} (VIP Single Bed) added successfully.\n";
    } else {
        echo "Failed to add room {$room[0]}.\n";
    }
}

echo "\n--- Current VIP Rooms ---\n";
$stmt = $pdo->query("SELECT room_number, room_type, bed_type, price FROM rooms WHERE room_type = 'VIP'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
