<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->prepare("INSERT INTO room_types (room_type_name, description) VALUES (?, ?)");
    $stmt->execute(['ຫ້ອງຄອບຄົວ', 'ຫ້ອງສຳລັບຄອບຄົວ (Family Room)']);
    echo "Family Room type added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
