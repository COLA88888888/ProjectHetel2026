<?php
session_start();
require_once 'config/db.php';

$type = $_GET['type'] ?? 'all';
// Fetch Tax Percent
$stmtTax = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percent'");
$tax_percent = (float)($stmtTax->fetchColumn() ?: 0);
$tax_mult = 1 + ($tax_percent / 100);

// 1. Daily Revenue (Bookings + POS)
$stmtDay = $pdo->prepare("SELECT SUM((total_price + food_charge) * $tax_mult) as daily_revenue FROM bookings WHERE DATE(check_in_date) = CURDATE()");
$stmtDay->execute();
$daily_revenue_bookings = $stmtDay->fetch()['daily_revenue'] ?? 0;

$stmtPosDay = $pdo->prepare("SELECT SUM(amount) as daily_pos FROM orders WHERE DATE(o_date) = CURDATE()");
$stmtPosDay->execute();
$daily_pos = $stmtPosDay->fetch()['daily_pos'] ?? 0;

$daily_revenue = $daily_revenue_bookings + $daily_pos;

// 2. Monthly Revenue (Bookings + POS)
$stmtMonth = $pdo->prepare("SELECT SUM((total_price + food_charge) * $tax_mult) as monthly_revenue FROM bookings WHERE MONTH(check_in_date) = MONTH(CURDATE()) AND YEAR(check_in_date) = YEAR(CURDATE())");
$stmtMonth->execute();
$monthly_revenue_bookings = $stmtMonth->fetch()['monthly_revenue'] ?? 0;

$stmtPosMonth = $pdo->prepare("SELECT SUM(amount) as monthly_pos FROM orders WHERE MONTH(o_date) = MONTH(CURDATE()) AND YEAR(o_date) = YEAR(CURDATE())");
$stmtPosMonth->execute();
$monthly_pos = $stmtPosMonth->fetch()['monthly_pos'] ?? 0;

$monthly_revenue = $monthly_revenue_bookings + $monthly_pos;

// 3. Number of Customers (Bookings today)
$stmtCust = $pdo->prepare("
    SELECT COUNT(id) as today_customers 
    FROM bookings 
    WHERE DATE(check_in_date) = CURDATE()
");
$stmtCust->execute();
$today_customers = $stmtCust->fetch()['today_customers'] ?? 0;

// 4. Guest Count (Total people stayed today)
$stmtGuests = $pdo->prepare("
    SELECT SUM(guest_count) as total_guests 
    FROM bookings 
    WHERE DATE(check_in_date) = CURDATE()
");
$stmtGuests->execute();
$total_guests = $stmtGuests->fetch()['total_guests'] ?? 0;

// 5. Available Rooms
$stmtRooms = $pdo->prepare("
    SELECT COUNT(id) as available_rooms 
    FROM rooms 
    WHERE status = 'Available' AND (housekeeping_status = 'ພ້ອມໃຊ້' OR housekeeping_status = 'Ready')
");
$stmtRooms->execute();
$available_rooms = $stmtRooms->fetch()['available_rooms'] ?? 0;

// Get recent transactions (Last 10 completed bookings)
$stmtRecent = $pdo->query("
    SELECT b.*, r.room_number 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    ORDER BY b.id DESC LIMIT 20
");
$recent_bookings = $stmtRecent->fetchAll();

// Fetch Currently Unavailable Rooms (Booked Today or Occupied/Staying)
$today_val = date('Y-m-d');
$stmtUnavailable = $pdo->query("
    SELECT b.*, r.room_number, r.room_type 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status IN ('Occupied', 'Checked In')
    OR (b.status = 'Booked' AND b.check_in_date <= '$today_val' AND b.check_out_date > '$today_val')
    ORDER BY b.status DESC, b.check_in_date ASC
");
$unavailable_list = $stmtUnavailable->fetchAll();

// Get recent POS transactions
$stmtRecentPos = $pdo->query("
    SELECT o.*, p.prod_name, p.category 
    FROM orders o 
    JOIN products p ON o.prod_id = p.prod_id 
    ORDER BY o.order_id DESC LIMIT 20
");
$recent_pos = $stmtRecentPos->fetchAll();

// Fetch Monthly Data for the last 6 months for the Chart
$months = [];
$room_revenue_chart = [];
$pos_revenue_chart = [];
$expenses_chart = [];

for ($i = 5; $i >= 0; $i--) {
    $month_date = date('Y-m', strtotime("-$i months"));
    $month_label = date('m/Y', strtotime("-$i months"));
    $months[] = $month_label;

    // Room Revenue
    $stmtRC = $pdo->prepare("SELECT SUM((total_price + food_charge) * $tax_mult) as total FROM bookings WHERE DATE_FORMAT(check_in_date, '%Y-%m') = ?");
    $stmtRC->execute([$month_date]);
    $room_revenue_chart[] = $stmtRC->fetch()['total'] ?? 0;

    // POS Revenue
    $stmtPC = $pdo->prepare("SELECT SUM(amount) as total FROM orders WHERE DATE_FORMAT(o_date, '%Y-%m') = ?");
    $stmtPC->execute([$month_date]);
    $pos_revenue_chart[] = $stmtPC->fetch()['total'] ?? 0;

    // Expenses (Stock)
    $stmtEC = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?");
    $stmtEC->execute([$month_date]);
    $expenses_chart[] = $stmtEC->fetch()['total'] ?? 0;
}

// Fetch Room Type Revenue Breakdown (Total or Last 6 Months)
$room_type_labels = [];
$room_type_revenue = [];
$stmtRT = $pdo->query("
    SELECT r.room_type, SUM((b.total_price + b.food_charge) * $tax_mult) as total 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status IN ('Completed', 'Checked In')
    GROUP BY r.room_type 
    ORDER BY total DESC
");
while($row = $stmtRT->fetch()) {
    $room_type_labels[] = $row['room_type'];
    $room_type_revenue[] = (float)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານ ແລະ ສະຫຼຸບຜົນ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', 'Phetsarath OT', 'Saysettha OT', sans-serif !important; background-color: #f0f4f8; padding: 20px; }
        
        /* ===== Modern & Compact Stat Cards ===== */
        .stat-cards-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .stat-card {
            position: relative;
            border-radius: 12px;
            padding: 16px 18px 14px;
            color: #fff;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 110px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .stat-card.gc-green  { background: linear-gradient(135deg, #1D976C 0%, #93F9B9 100%); }
        .stat-card.gc-amber  { background: linear-gradient(135deg, #FF8008 0%, #FFC837 100%); }
        .stat-card.gc-blue   { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }
        .stat-card.gc-indigo { background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%); }
        .stat-card.gc-teal   { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); }
        .stat-card.gc-dark   { background: linear-gradient(135deg, #30E8BF 0%, #FF8235 100%); }

        .stat-card-label { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; opacity: 0.95; margin-bottom: 6px; }
        .stat-card-value { font-size: 1.8rem; font-weight: 800; line-height: 1.1; }
        .stat-card-icon { font-size: 1.8rem; opacity: 0.25; position: absolute; top: 10px; right: 12px; }

        /* Section Header */
        .section-header { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e0e6ed; }
        .section-header h2 { margin: 0; font-weight: 800; color: #2c3e50; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header-icon { background: #3498db; color: #fff; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(52,152,219,0.3); }

        .card { border-radius: 12px !important; border: none !important; box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; }
        .card-header { background: #fff !important; border-bottom: 1px solid #f0f4f8 !important; border-radius: 12px 12px 0 0 !important; }
        .card-title { font-weight: 700 !important; color: #2c3e50 !important; }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .stat-cards-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card-value { font-size: 1.15rem; }
            .stat-card { min-height: 80px; padding: 10px; }
            .stat-card-label { font-size: 0.65rem; }
            .section-header h2 { font-size: 1.1rem; }
            .container-fluid { padding: 0 5px; width: 100% !important; overflow-x: hidden; }
            .row { margin-left: -5px; margin-right: -5px; width: 100% !important; }
            .col-12, .col-lg-8, .col-lg-4 { padding-left: 5px; padding-right: 5px; width: 100% !important; }
        }
        @media (max-width: 480px) {
            .stat-cards-row { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .stat-card-value { font-size: 1rem; }
            .stat-card-label { font-size: 0.6rem; }
            .stat-card { min-height: 75px; padding: 8px; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="section-header">
        <h2>ບົດລາຍງານ ແລະ ສະຫຼຸບຜົນ</h2>
    </div>

    <!-- Small boxes (Stat box) -->
    <?php if($type == 'all' || $type == 'room_revenue'): ?>
    <div class="stat-cards-row">
        <!-- Daily Revenue -->
        <div class="stat-card gc-green">
            <div class="stat-card-top">
                <div class="stat-card-label">ລາຍຮັບມື້ນີ້</div>
                <div class="stat-card-value"><?php echo number_format($daily_revenue); ?> <sup style="font-size: 1rem">₭</sup></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
        
        <!-- Monthly Revenue -->
        <div class="stat-card gc-blue">
            <div class="stat-card-top">
                <div class="stat-card-label">ລາຍຮັບເດືອນນີ້</div>
                <div class="stat-card-value"><?php echo number_format($monthly_revenue); ?> <sup style="font-size: 1rem">₭</sup></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-chart-bar"></i></div>
        </div>

        <!-- Available Rooms -->
        <div class="stat-card gc-amber">
            <div class="stat-card-top">
                <div class="stat-card-label">ຫ້ອງຫວ່າງພ້ອມໃຊ້</div>
                <div class="stat-card-value"><?php echo $available_rooms; ?> <sup style="font-size: 1rem">ຫ້ອງ</sup></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-door-open"></i></div>
        </div>

        <!-- Today Customers -->
        <div class="stat-card gc-teal">
            <div class="stat-card-top">
                <div class="stat-card-label">ຈຳນວນລູກຄ້າ (Bill)</div>
                <div class="stat-card-value"><?php echo $today_customers; ?> <sup style="font-size: 1rem">ບິນ</sup></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
        </div>

        <!-- Total Guests -->
        <div class="stat-card gc-indigo">
            <div class="stat-card-top">
                <div class="stat-card-label">ແຂກພັກຕົວຈິງ</div>
                <div class="stat-card-value"><?php echo $total_guests; ?> <sup style="font-size: 1rem">ຄົນ</sup></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-user-friends"></i></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Finance & Room Revenue Charts -->
    <div class="row mt-3" id="chartsContainer">
        <?php if($type == 'all' || $type == 'finance'): ?>
        <div class="col-lg-8 col-12 mb-3">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-chart-bar text-primary"></i> ກຣາຟສະຫຼຸບລາຍຮັບ - ລາຍຈ່າຍ (6 ເດືອນຫຼ້າສຸດ)</h3>
                </div>
                <div class="card-body">
                    <canvas id="financeChart" style="min-height: 250px; height: 350px; max-height: 350px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($type == 'all' || $type == 'finance' || $type == 'room_revenue'): ?>
        <div class="<?php echo ($type == 'room_revenue') ? 'col-12' : 'col-lg-4 col-12'; ?> mb-3">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-door-open text-success"></i> ລາຍຮັບແບ່ງຕາມປະເພດຫ້ອງ</h3>
                </div>
                <div class="card-body">
                    <canvas id="roomTypeChart" style="min-height: 250px; height: 350px; max-height: 350px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($type == 'room_revenue'): ?>
        <!-- Additional Detail for Room Revenue Page -->
        <div class="col-12 mb-3">
            <div class="card card-info card-outline shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-chart-line text-info"></i> ແນວໂນ້ມລາຍຮັບຫ້ອງພັກ (6 ເດືອນຫຼ້າສຸດ)</h3>
                </div>
                <div class="card-body">
                    <canvas id="roomTrendChart" style="min-height: 250px; height: 350px; max-height: 350px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>


    </div>

    <!-- Unavailable Rooms Table -->
    <?php if($type == 'all' || $type == 'room_history'): ?>
    <div class="row mb-4" id="unavailableRooms">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title"><i class="fas fa-door-closed"></i> ລາຍງານຫ້ອງບໍ່ຫວ່າງ (ຈອງ ແລະ ກຳລັງພັກ)</h3>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                    <table id="unavailableTable" class="table table-bordered table-striped text-center mb-0" style="min-width: 650px;">
                        <thead class="bg-light">
                            <tr>
                                <th>ເລກຫ້ອງ</th>
                                <th>ຊື່ລູກຄ້າ</th>
                                <th>ສະຖານະ</th>
                                <th>ວັນທີເຂົ້າ</th>
                                <th>ວັນທີອອກ</th>
                                <th>ຈຳນວນຄືນ</th>
                                <th>ຍອດລວມ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($unavailable_list) > 0): ?>
                                <?php foreach($unavailable_list as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['room_number']); ?></strong><br><small><?php echo htmlspecialchars($row['room_type']); ?></small></td>
                                        <td class="text-left font-weight-bold"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Booked'): ?>
                                                <span class="badge badge-primary px-3"><i class="fas fa-calendar-check"></i> ຈອງ</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning text-white px-3"><i class="fas fa-clock"></i> ກຳລັງພັກ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-success"><?php echo date('d/m/Y', strtotime($row['check_in_date'])); ?></td>
                                        <td class="text-danger"><?php echo date('d/m/Y', strtotime($row['check_out_date'])); ?></td>
                                        <td>
                                            <?php 
                                                $diff = date_diff(date_create($row['check_in_date']), date_create($row['check_out_date']));
                                                echo $diff->format("%a"); 
                                            ?>
                                        </td>
                                        <td class="text-right font-weight-bold"><?php echo number_format($row['total_price']); ?> ₭</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-4 text-muted">ບໍ່ມີຫ້ອງທີ່ບໍ່ຫວ່າງໃນເວລານີ້</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Transactions Table -->
    <?php if($type == 'all' || $type == 'room_history'): ?>
    <div class="row mt-4" id="roomHistory">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title"><i class="fas fa-bed text-primary"></i> ປະຫວັດການຈອງຫ້ອງພັກຫຼ້າສຸດ</h3>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                    <table id="reportTable" class="table table-bordered table-striped text-center mb-0" style="min-width: 650px;">
                        <thead>
                            <tr class="bg-light">
                                <th>ວັນທີ Check-in</th>
                                <th>ເລກຫ້ອງ</th>
                                <th>ຊື່ລູກຄ້າ</th>
                                <th>ຈຳນວນຄືນ</th>
                                <th>ຈຳນວນແຂກ</th>
                                <th>ຄ່າຫ້ອງ</th>
                                <th>ຄ່າອາຫານ</th>
                                <th class="text-success font-weight-bold">ລວມທັງໝົດ</th>
                                <th>ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_bookings as $row): ?>
                                <?php 
                                    $subtotal = $row['total_price'] + $row['food_charge'];
                                    $row_tax = round($subtotal * ($tax_percent / 100));
                                    $row_total = $subtotal + $row_tax;
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['check_in_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['room_number']); ?></strong></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td>
                                        <?php 
                                            $diff = date_diff(date_create($row['check_in_date']), date_create($row['check_out_date']));
                                            echo $diff->format("%a"); 
                                        ?>
                                    </td>
                                    <td><?php echo $row['guest_count']; ?> ຄົນ</td>
                                    <td class="text-right"><?php echo number_format($row['total_price']); ?></td>
                                    <td class="text-right text-info"><?php echo number_format($row['food_charge']); ?></td>
                                    <td class="text-right text-success font-weight-bold"><?php echo number_format($row_total); ?></td>
                                    <td>
                                        <?php if($row['status'] == 'Completed'): ?>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> ຊຳລະແລ້ວ</span>
                                        <?php elseif($row['status'] == 'Booked'): ?>
                                            <span class="badge badge-primary"><i class="fas fa-calendar-check"></i> ຈອງ</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning text-white"><i class="fas fa-clock"></i> ກຳລັງພັກ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent POS Transactions Table -->
    <?php if($type == 'all' || $type == 'pos_history'): ?>
    <div class="row mt-4 mb-4" id="posHistory">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title"><i class="fas fa-shopping-cart text-success"></i> ປະຫວັດການຂາຍສິນຄ້າໜ້າຮ້ານ (POS) ຫຼ້າສຸດ</h3>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                    <table id="posTable" class="table table-bordered table-striped text-center mb-0" style="min-width: 500px;">
                        <thead>
                            <tr class="bg-light">
                                <th>ວັນເວລາ</th>
                                <th class="text-left">ຊື່ສິນຄ້າ</th>
                                <th>ປະເພດ</th>
                                <th>ຈຳນວນ</th>
                                <th class="text-success font-weight-bold">ຍອດລວມ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_pos as $row): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="text-left font-weight-bold"><?php echo htmlspecialchars($row['prod_name']); ?></td>
                                    <td><span class="badge badge-secondary"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                    <td><?php echo $row['o_qty']; ?></td>
                                    <td class="text-right text-success font-weight-bold"><?php echo number_format($row['amount']); ?> ກີບ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>

<script>
$(document).ready(function() {
    var dtConfig = {
        "order": [[0, "desc"]],
        "language": {
            "sProcessing":   "ກຳລັງດຳເນີນການ...",
            "sLengthMenu":   "ສະແດງ _MENU_ ລາຍການ",
            "sZeroRecords":  "ບໍ່ມີຂໍ້ມູນໃນຕາຕະລາງ",
            "sInfo":         "ສະແດງ _START_ ຫາ _END_ ຈາກທັງໝົດ _TOTAL_ ລາຍການ",
            "sInfoEmpty":    "ສະແດງ 0 ຫາ 0 ຈາກ 0 ລາຍການ",
            "sInfoFiltered": "(ກັ່ນຕອງຈາກທັງໝົດ _MAX_ ລາຍການ)",
            "sSearch":       "ຄົ້ນຫາ:",
            "oPaginate": {
                "sFirst":    "ໜ້າທຳອິດ",
                "sPrevious": "ກ່ອນໜ້າ",
                "sNext":     "ຖັດໄປ",
                "sLast":     "ໜ້າສຸດທ້າຍ"
            }
        }
    };
    $('#reportTable').DataTable(dtConfig);
    $('#posTable').DataTable(dtConfig);
    $('#unavailableTable').DataTable(dtConfig);

    // Chart.js Configuration
    Chart.defaults.global.defaultFontFamily = "'Noto Sans Lao Looped', sans-serif";
    
    <?php if($type == 'all' || $type == 'finance'): ?>
    var financeChartCanvas = $('#financeChart').get(0).getContext('2d');
    var financeChartData = {
      labels  : <?php echo json_encode($months); ?>,
      datasets: [
        {
          label               : 'ລາຍຮັບຈາກຫ້ອງພັກ',
          backgroundColor     : '#3c8dbc',
          borderColor         : '#3c8dbc',
          data                : <?php echo json_encode($room_revenue_chart); ?>
        },
        {
          label               : 'ລາຍຮັບຈາກການຂາຍສິນຄ້າ',
          backgroundColor     : '#28a745',
          borderColor         : '#28a745',
          data                : <?php echo json_encode($pos_revenue_chart); ?>
        },
        {
          label               : 'ລາຍຈ່າຍ (ນຳເຂົ້າສິນຄ້າ)',
          backgroundColor     : '#dc3545',
          borderColor         : '#dc3545',
          data                : <?php echo json_encode($expenses_chart); ?>
        }
      ]
    }

    var financeChartOptions = {
      animation: {
          duration: 2000,
          easing: 'easeOutQuart'
      },
      hover: {
          animationDuration: 1000
      },
      responsiveAnimationDuration: 1000,
      maintainAspectRatio : false,
      responsive : true,
      legend: {
        display: true
      },
      scales: {
        xAxes: [{
          gridLines : {
            display : false,
          }
        }],
        yAxes: [{
          gridLines : {
            display : false,
          },
          ticks: {
              callback: function(value) {
                  return value.toLocaleString('en-US') + ' ₭';
              }
          }
        }]
      },
      tooltips: {
          callbacks: {
              label: function(tooltipItem, data) {
                  return data.datasets[tooltipItem.datasetIndex].label + ': ' + Number(tooltipItem.yLabel).toLocaleString('en-US') + ' ກີບ';
              }
          }
      }
    }

    new Chart(financeChartCanvas, {
      type: 'bar',
      data: financeChartData,
      options: financeChartOptions
    });
    <?php endif; ?>

    <?php if($type == 'all' || $type == 'finance' || $type == 'room_revenue'): ?>
    // Room Type Revenue Chart
    var roomTypeCanvas = $('#roomTypeChart').get(0).getContext('2d');
    var roomTypeData = {
      labels  : <?php echo json_encode($room_type_labels); ?>,
      datasets: [
        {
          data                : <?php echo json_encode($room_type_revenue); ?>,
          backgroundColor     : ['#28a745', '#007bff', '#ffc107', '#dc3545', '#17a2b8', '#6610f2'],
        }
      ]
    }
    var roomTypeOptions = {
      animation: {
          duration: 2000,
          easing: 'easeOutQuart'
      },
      maintainAspectRatio : false,
      responsive : true,
      legend: {
        display: true,
        position: 'bottom'
      },
      tooltips: {
          callbacks: {
              label: function(tooltipItem, data) {
                  var val = data.datasets[0].data[tooltipItem.index];
                  return data.labels[tooltipItem.index] + ': ' + Number(val).toLocaleString('en-US') + ' ກີບ';
              }
          }
      }
    }

    new Chart(roomTypeCanvas, {
      type: 'doughnut',
      data: roomTypeData,
      options: roomTypeOptions
    });
    <?php endif; ?>

    <?php if($type == 'room_revenue'): ?>
    // Room Trend Chart (Line Chart for room_revenue page)
    var roomTrendCanvas = $('#roomTrendChart').get(0).getContext('2d');
    var roomTrendData = {
      labels  : <?php echo json_encode($months); ?>,
      datasets: [
        {
          label: 'ລາຍຮັບຫ້ອງພັກ',
          backgroundColor: 'rgba(23, 162, 184, 0.1)',
          borderColor: '#17a2b8',
          borderWidth: 3,
          data: <?php echo json_encode($room_revenue_chart); ?>,
          fill: true,
          lineTension: 0.3,
          pointRadius: 5,
          pointBackgroundColor: '#17a2b8'
        }
      ]
    }
    new Chart(roomTrendCanvas, {
      type: 'line',
      data: roomTrendData,
      options: {
        animation: { duration: 2000, easing: 'easeOutQuart' },
        maintainAspectRatio: false,
        responsive: true,
        scales: {
          yAxes: [{
            ticks: { 
              beginAtZero: true,
              callback: function(v) { return v.toLocaleString('en-US') + ' ₭'; } 
            }
          }]
        }
      }
    });
    <?php endif; ?>

});
</script>
</body>
</html>
