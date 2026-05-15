<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$notifications = [];
$total_count = 0;

// 1. Low Stock Products
$stmtLow = $pdo->query("SELECT prod_name, qty FROM products WHERE qty <= 5 LIMIT 5");
while ($row = $stmtLow->fetch()) {
    $notifications[] = [
        'type' => 'low_stock',
        'title' => 'ສິນຄ້າໃກ້ຈະໝົດ!',
        'text' => $row['prod_name'] . ' ເຫຼືອພຽງ ' . $row['qty'],
        'icon' => 'fas fa-box-open',
        'color' => 'text-warning',
        'link' => 'stock.php'
    ];
    $total_count++;
}

// 2. New Room Service Orders (Last 5 minutes)
$stmtService = $pdo->query("SELECT item_name, created_at FROM room_services WHERE created_at >= NOW() - INTERVAL 5 MINUTE LIMIT 5");
while ($row = $stmtService->fetch()) {
    $notifications[] = [
        'type' => 'room_service',
        'title' => 'ມີລາຍການສັ່ງໃໝ່!',
        'text' => $row['item_name'] . ' (' . date('H:i', strtotime($row['created_at'])) . ')',
        'icon' => 'fas fa-utensils',
        'color' => 'text-info',
        'link' => 'room_service.php'
    ];
    $total_count++;
}

// 3. Today's Checkouts
$stmtCheckout = $pdo->query("SELECT r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.status = 'Occupied' AND b.check_out_date = CURDATE() LIMIT 5");
while ($row = $stmtCheckout->fetch()) {
    $notifications[] = [
        'type' => 'checkout',
        'title' => 'ຮອດກຳນົດ Check-out!',
        'text' => 'ຫ້ອງ ' . $row['room_number'] . ' ຮອດກຳນົດອອກມື້ນີ້',
        'icon' => 'fas fa-door-closed',
        'color' => 'text-danger',
        'link' => 'checkout.php'
    ];
    $total_count++;
}

echo json_encode([
    'count' => $total_count,
    'items' => $notifications
]);
