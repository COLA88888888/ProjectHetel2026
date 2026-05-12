<?php
require_once 'config/db.php';

$period = $_GET['period'] ?? 'daily';

$labels = [];
$roomData = [];
$posData = [];

// Fetch Tax Percent
$stmtTax = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percent'");
$tax_percent = (float)($stmtTax->fetchColumn() ?: 0);
$tax_mult = 1 + ($tax_percent / 100);

switch ($period) {
    case 'daily':
        // Current week (Monday to Sunday)
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("monday this week +$i days"));
            $labels[] = date('d/m', strtotime("monday this week +$i days"));
            
            $stmtR = $pdo->prepare("SELECT SUM((total_price + COALESCE(food_charge, 0)) * $tax_mult) as total FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied') AND DATE(check_in_date) = ?");
            $stmtR->execute([$date]);
            $roomData[] = (float)($stmtR->fetch()['total'] ?? 0);
            
            $stmtP = $pdo->prepare("SELECT SUM(amount) as total FROM orders WHERE DATE(o_date) = ?");
            $stmtP->execute([$date]);
            $posData[] = (float)($stmtP->fetch()['total'] ?? 0);
        }
        break;
        
    case 'weekly':
        // Last 8 weeks
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = date('Y-m-d', strtotime("monday this week -$i weeks"));
            $weekEnd = date('Y-m-d', strtotime("sunday this week -$i weeks"));
            $labels[] = date('d/m', strtotime($weekStart));
            
            $stmtR = $pdo->prepare("SELECT SUM((total_price + COALESCE(food_charge, 0)) * $tax_mult) as total FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied') AND DATE(check_in_date) BETWEEN ? AND ?");
            $stmtR->execute([$weekStart, $weekEnd]);
            $roomData[] = (float)($stmtR->fetch()['total'] ?? 0);
            
            $stmtP = $pdo->prepare("SELECT SUM(amount) as total FROM orders WHERE DATE(o_date) BETWEEN ? AND ?");
            $stmtP->execute([$weekStart, $weekEnd]);
            $posData[] = (float)($stmtP->fetch()['total'] ?? 0);
        }
        break;
        
    case 'monthly':
        // Last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $labels[] = date('m/Y', strtotime("-$i months"));
            
            $stmtR = $pdo->prepare("SELECT SUM((total_price + COALESCE(food_charge, 0)) * $tax_mult) as total FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied') AND DATE_FORMAT(check_in_date, '%Y-%m') = ?");
            $stmtR->execute([$month]);
            $roomData[] = (float)($stmtR->fetch()['total'] ?? 0);
            
            $stmtP = $pdo->prepare("SELECT SUM(amount) as total FROM orders WHERE DATE_FORMAT(o_date, '%Y-%m') = ?");
            $stmtP->execute([$month]);
            $posData[] = (float)($stmtP->fetch()['total'] ?? 0);
        }
        break;
        
    case 'yearly':
        // Last 5 years
        for ($i = 4; $i >= 0; $i--) {
            $year = date('Y', strtotime("-$i years"));
            $labels[] = $year;
            
            $stmtR = $pdo->prepare("SELECT SUM((total_price + COALESCE(food_charge, 0)) * $tax_mult) as total FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied') AND YEAR(check_in_date) = ?");
            $stmtR->execute([$year]);
            $roomData[] = (float)($stmtR->fetch()['total'] ?? 0);
            
            $stmtP = $pdo->prepare("SELECT SUM(amount) as total FROM orders WHERE YEAR(o_date) = ?");
            $stmtP->execute([$year]);
            $posData[] = (float)($stmtP->fetch()['total'] ?? 0);
        }
        break;
}

// Fetch Room Type Revenue for the selected period
$roomTypeLabels = [];
$roomTypeRevenue = [];

$typeQuery = "SELECT r.room_type, SUM((b.total_price + COALESCE(b.food_charge, 0)) * $tax_mult) as total 
              FROM bookings b 
              JOIN rooms r ON b.room_id = r.id 
              WHERE b.status IN ('Completed', 'Checked In') ";

if ($period == 'daily') {
    $typeQuery .= "AND DATE(b.check_in_date) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) ";
} elseif ($period == 'weekly') {
    $typeQuery .= "AND DATE(b.check_in_date) >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) ";
} elseif ($period == 'monthly') {
    $typeQuery .= "AND DATE(b.check_in_date) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) ";
} elseif ($period == 'yearly') {
    $typeQuery .= "AND DATE(b.check_in_date) >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR) ";
}

$typeQuery .= "GROUP BY r.room_type ORDER BY total DESC";
$stmtType = $pdo->query($typeQuery);
while ($row = $stmtType->fetch()) {
    $roomTypeLabels[] = $row['room_type'];
    $roomTypeRevenue[] = (float)$row['total'];
}

header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'roomData' => $roomData,
    'posData' => $posData,
    'roomTypeLabels' => $roomTypeLabels,
    'roomTypeData' => $roomTypeRevenue
]);
