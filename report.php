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
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 10px; }
        .inner h3 { font-size: 2.2rem; font-weight: bold; }
        .inner p { font-size: 1.1rem; }
        /* Responsive tables */
        .table-responsive { -webkit-overflow-scrolling: touch; }
        .card-title { font-size: 1rem; }
        @media (max-width: 768px) {
            body { padding: 6px; }
            h2 { font-size: 1.2rem; }
            .inner h3 { font-size: 1.3rem; }
            .inner p { font-size: 0.85rem; }
            .card-title { font-size: 0.88rem; }
            .small-box .icon { font-size: 40px; }
            .table { font-size: 0.82rem; }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate { font-size: 0.82rem; }
            .dataTables_wrapper .dataTables_filter input { width: 120px; }
        }
        @media (max-width: 576px) {
            .inner h3 { font-size: 1.1rem; }
            .inner p { font-size: 0.78rem; }
            .table { font-size: 0.75rem; }
            .badge { font-size: 0.7rem; padding: 3px 6px; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-chart-line"></i> ບົດລາຍງານ ແລະ ສະຫຼຸບຜົນ</h2>
        </div>
    </div>

    <!-- Small boxes (Stat box) -->
    <?php if($type == 'all' || $type == 'room_revenue'): ?>
    <div class="row">
        <!-- Daily Revenue -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success shadow-sm">
                <div class="inner">
                    <h3><?php echo number_format($daily_revenue); ?> <sup style="font-size: 20px">₭</sup></h3>
                    <p>ລາຍຮັບມື້ນີ້</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
        </div>
        
        <!-- Monthly Revenue -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info shadow-sm">
                <div class="inner">
                    <h3><?php echo number_format($monthly_revenue); ?> <sup style="font-size: 20px">₭</sup></h3>
                    <p>ລາຍຮັບເດືອນນີ້</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
        </div>

        <!-- Available Rooms -->
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning shadow-sm">
                <div class="inner text-white">
                    <h3><?php echo $available_rooms; ?></h3>
                    <p>ຫ້ອງຫວ່າງພ້ອມໃຊ້</p>
                </div>
                <div class="icon">
                    <i class="fas fa-door-open"></i>
                </div>
            </div>
        </div>

        <!-- Today Customers -->
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger shadow-sm">
                <div class="inner">
                    <h3><?php echo $today_customers; ?></h3>
                    <p>ຈຳນວນລູກຄ້າ (Bill)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <!-- Total Guests -->
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary shadow-sm">
                <div class="inner">
                    <h3><?php echo $total_guests; ?></h3>
                    <p>ຈຳນວນແຂກພັກຕົວຈິງ</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-friends"></i>
                </div>
            </div>
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
                                    <td><?php echo $row['guest_count']; ?> ຄົນ</td>
                                    <td class="text-right"><?php echo number_format($row['total_price']); ?></td>
                                    <td class="text-right text-info"><?php echo number_format($row['food_charge']); ?></td>
                                    <td class="text-right text-success font-weight-bold"><?php echo number_format($row_total); ?></td>
                                    <td>
                                        <?php if($row['status'] == 'Completed'): ?>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> ຊຳລະແລ້ວ</span>
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
