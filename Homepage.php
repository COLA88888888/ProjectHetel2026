<?php
session_start();
if (!isset($_SESSION['checked']) || $_SESSION['checked'] <> 1) {
    $_SESSION['checked'] = 1;
    $_SESSION['fname'] = 'Admin';
    $_SESSION['lname'] = 'System';
    $_SESSION['user_id'] = 1;
    $_SESSION['status'] = 'Admin';
    $_SESSION['permissions'] = '["room_types","rooms","bookings","housekeeping","reports","settings","users"]';
}
?>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>
<script src="jquery.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped+Looped:wght@400;700&display=swap" rel="stylesheet">
    <style>
    * {
      font-family: 'Noto Sans Lao Looped', sans-serif;
    }

    #save {
      margin-top: 30;
    }
  </style>

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ລະບົບບໍລິຫານ ໂຮງແຮມ</title>
    <link href="./assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="./assets/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
    <link href="./assets/vendors/themify-icons/css/themify-icons.css" rel="stylesheet" />
    <!-- PLUGINS STYLES-->
    <link href="./assets/vendors/jvectormap/jquery-jvectormap-2.0.3.css" rel="stylesheet" />
    <!-- THEME STYLES-->
    <link href="assets/css/main.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="icon/css/all.min.css">
    <link rel="stylesheet" href="fontawesome-free-6.2.1-web/css/all.min.css">
    <script src="sweetalert/dist/sweetalert2.all.min.js"></script>
    <script src="jquery.js"></script>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="ionicons.min.css">
    <script src="ionicons.designerpack/index.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="icon/css/all.min.css">
    <link rel="stylesheet" href="fontawesome-free-6.2.1-web/css/all.min.css">
    <script src="sweetalert/dist/sweetalert2.all.min.js"></script>
    <script src="jquery.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped+Looped:wght@400;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Noto Sans Lao Looped', sans-serif; }
    </style>
  </head>
<?php
require_once 'config/db.php';

try {
    // Hotel Metrics
    $total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn() ?: 0;
    $available_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'Available' AND (housekeeping_status = 'ພ້ອມໃຊ້' OR housekeeping_status = 'Ready')")->fetchColumn() ?: 0;
    // Unavailable rooms = Occupied + Cleaning + Maintenance/Broken
    $unavailable_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status != 'Available' OR (housekeeping_status != 'ພ້ອມໃຊ້' AND housekeeping_status != 'Ready')")->fetchColumn() ?: 0;
    $guest_count = $pdo->query("SELECT COALESCE(SUM(guest_count), 0) FROM bookings WHERE status IN ('Occupied', 'Checked In')")->fetchColumn() ?: 0;
    
    // Revenue calculations (Room + POS)
    // Updated to include 'Occupied', 'Completed', and 'Checked In' to ensure Walk-in revenue shows up immediately
    $room_revenue = $pdo->query("SELECT SUM(total_price + COALESCE(food_charge, 0)) FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied')")->fetchColumn() ?: 0;
    $pos_revenue = $pdo->query("SELECT SUM(amount) FROM orders")->fetchColumn() ?: 0;
    $total_revenue = $room_revenue + $pos_revenue;

    // Use current date from PHP to ensure sync with MySQL
    $current_date = date('Y-m-d');
    
    $stmtTR = $pdo->prepare("SELECT SUM(total_price + COALESCE(food_charge, 0)) FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied') AND (DATE(check_in_date) = ? OR DATE(created_at) = ?)");
    $stmtTR->execute([$current_date, $current_date]);
    $today_room = $stmtTR->fetchColumn() ?: 0;

    $stmtTP = $pdo->prepare("SELECT SUM(amount) FROM orders WHERE DATE(o_date) = ?");
    $stmtTP->execute([$current_date]);
    $today_pos = $stmtTP->fetchColumn() ?: 0;
    
    $today_revenue = $today_room + $today_pos;
    
    // Fetch today's arrivals (Reservations starting today)
    $stmtArrivals = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'Booked' AND DATE(check_in_date) = ?");
    $stmtArrivals->execute([$current_date]);
    $arrivals_count = $stmtArrivals->fetchColumn() ?: 0;

    // Fetch system activity history (Last 8 actions)
    $stmt_history = $pdo->query("SELECT b.customer_name, b.status, b.created_at, r.room_number 
                                 FROM bookings b 
                                 JOIN rooms r ON b.room_id = r.id 
                                 ORDER BY b.created_at DESC 
                                 LIMIT 8");
    $activity_logs = $stmt_history->fetchAll();

    // Fetch Last 7 Days Revenue
    $days = [];
    $room_revenue_7d = [];
    $pos_revenue_7d = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $date_label = date('d/m', strtotime("-$i days"));
        $days[] = $date_label;

        // Room Revenue - Including Occupied for real-time chart tracking
        $stmtR = $pdo->prepare("SELECT SUM(total_price + COALESCE(food_charge, 0)) as total FROM bookings WHERE status IN ('Completed', 'Checked In', 'Occupied') AND DATE(check_in_date) = ?");
        $stmtR->execute([$date]);
        $room_revenue_7d[] = $stmtR->fetch()['total'] ?? 0;

        // POS Revenue
        $stmtP = $pdo->prepare("SELECT SUM(amount) as total FROM orders WHERE DATE(o_date) = ?");
        $stmtP->execute([$date]);
        $pos_revenue_7d[] = $stmtP->fetch()['total'] ?? 0;
    }
} catch (PDOException $e) {
    $total_rooms = 0;
    $available_rooms = 0;
    $booked_rooms = 0;
    $guest_count = 0;
    $total_revenue = 0;
    $today_revenue = 0;
}
?> 
    <style>
      body { font-family: 'Noto Sans Lao Looped', sans-serif; }
      .container-fluid { padding: 30px; }
      .bg-teal { background-color: #20c997 !important; }

      @media (max-width: 768px) {
        .container-fluid { padding: 10px; }
        .small-box .inner h3 { font-size: 1.3rem !important; }
        .small-box .inner p { font-size: 0.78rem !important; }
        .small-box .icon { font-size: 40px !important; top: 5px !important; right: 8px !important; }
        .small-box .small-box-footer { font-size: 0.75rem !important; padding: 4px 0 !important; }
        .card-title { font-size: 0.9rem !important; }
        .table { font-size: 0.8rem !important; }
        .btn-sm { font-size: 0.75rem !important; }
      }

      @media (max-width: 480px) {
        .container-fluid { padding: 6px; }
        .small-box .inner { padding: 8px 10px; }
        .small-box .inner h3 { font-size: 1.05rem !important; }
        .small-box .inner p { font-size: 0.7rem !important; margin-bottom: 0 !important; }
        .small-box .icon { font-size: 30px !important; }
        .small-box .small-box-footer { font-size: 0.7rem !important; }
        .col-6 { padding-left: 4px; padding-right: 4px; }
        .row { margin-left: -4px; margin-right: -4px; }
      }
    </style>
  <body class="hold-transition sidebar-mini layout-fixed">
    <div class="container-fluid">
      <div class="row">
        <!-- Card 1: Available Rooms -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?= number_format($available_rooms); ?> ຫ້ອງ</h3>
              <p>ຫ້ອງຫວ່າງ</p>
            </div>
            <div class="icon">
              <i class="fas fa-door-open"></i>
            </div>
            <a href="rooms/select_rooms.php" class="small-box-footer">ຈັດການຂໍ້ມູນຫ້ອງ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Card 2: Booked Rooms -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?= number_format($unavailable_rooms); ?> ຫ້ອງ</h3>
              <p>ຫ້ອງບໍ່ຫວ່າງ</p>
            </div>
            <div class="icon">
              <i class="fas fa-door-closed"></i>
            </div>
            <a href="report.php?type=room_history" class="small-box-footer">ປະຫວັດການຈອງ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Card 3: Total Rooms -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?= number_format($total_rooms); ?> ຫ້ອງ</h3>
              <p>ຈຳນວນຫ້ອງທັງໝົດ</p>
            </div>
            <div class="icon">
              <i class="fas fa-hotel"></i>
            </div>
            <a href="rooms/select_rooms.php" class="small-box-footer">ຈັດການຂໍ້ມູນຫ້ອງ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Card 4: Total Guests -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-primary text-white">
            <div class="inner">
              <h3><?= number_format($guest_count); ?> ຄົນ</h3>
              <p>ຈຳນວນແຂກທັງໝົດ</p>
            </div>
            <div class="icon">
              <i class="fas fa-users"></i>
            </div>
            <a href="report.php?type=room_history" class="small-box-footer">ແຂກເຂົ້າພັກ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Card 5: Today's Revenue -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-teal text-white">
            <div class="inner">
              <h3><?= number_format($today_revenue); ?> ກີບ</h3>
              <p>ລາຍຮັບມື້ນີ້</p>
            </div>
            <div class="icon">
              <i class="fas fa-wallet"></i>
            </div>
            <a href="report.php?type=room_revenue" class="small-box-footer">ເບິ່ງລາຍງານ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Card 6: Total Revenue -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-dark text-white">
            <div class="inner">
              <h3><?= number_format($total_revenue); ?> ກີບ</h3>
              <p>ລາຍຮັບລາຍເດືອນ</p>
            </div>
            <div class="icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
            <a href="report.php?type=finance" class="small-box-footer">ເບິ່ງລາຍງານ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Card 7: Today's Arrivals (Notification) -->
        <div class="col-lg-4 col-6">
          <div class="small-box bg-indigo text-white" style="background-color: #6610f2 !important;">
            <div class="inner">
              <h3><?= number_format($arrivals_count); ?> ລາຍການ</h3>
              <p>ແຂກທີ່ສິມາພັກມື້ນີ້</p>
            </div>
            <div class="icon">
              <i class="fas fa-calendar-day"></i>
            </div>
            <a href="reserve.php" class="small-box-footer">ເບິ່ງລາຍຊື່ແຂກຈອງ <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
    </div>

      <!-- Chart Section -->
      <div class="row mt-3">
        <!-- Line Chart -->
        <div class="col-lg-8 col-12 mb-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="m-0 font-weight-bold text-dark" style="font-size: 0.95rem;"><i class="fas fa-chart-line mr-2 text-primary"></i> ສະຫຼຸບລາຍຮັບ</h5>
                    <select id="chartPeriod" class="form-control form-control-sm" style="width: auto; border-radius: 8px; font-weight: 600; border: 2px solid #3498DB; color: #3498DB;">
                        <option value="daily">ລາຍວັນ</option>
                        <option value="weekly">ລາຍອາທິດ</option>
                        <option value="monthly">ລາຍເດືອນ</option>
                        <option value="yearly">ລາຍປີ</option>
                    </select>
                </div>
                <div class="card-body p-2 p-md-3">
                    <canvas id="lineChart" style="min-height: 200px; height: 280px; max-height: 300px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <!-- Donut Chart -->
        <div class="col-lg-4 col-12 mb-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="m-0 font-weight-bold text-dark" style="font-size: 0.95rem;"><i class="fas fa-chart-pie mr-2 text-danger"></i> ອັດຕາສ່ວນລາຍຮັບ</h5>
                </div>
                <div class="card-body p-2 p-md-3 d-flex align-items-center justify-content-center">
                    <canvas id="donutChart" style="max-height: 280px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
      </div>



    </div>
  </div>
    
    <!-- jQuery -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
      $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="plugins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="plugins/summernote/summernote-bs4.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="dist/js/demo.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="dist/js/pages/dashboard.js"></script>
    <script src="./assets/vendors/jquery/dist/jquery.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/popper.js/dist/umd/popper.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/bootstrap/dist/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/metisMenu/dist/metisMenu.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <!-- PAGE LEVEL PLUGINS-->
    <script src="./assets/vendors/chart.js/dist/Chart.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/jvectormap/jquery-jvectormap-2.0.3.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js" type="text/javascript"></script>
    <script src="./assets/vendors/jvectormap/jquery-jvectormap-us-aea-en.js" type="text/javascript"></script>
    <!-- CORE SCRIPTS-->
    <script src="./assets/js/app.min.js" type="text/javascript"></script>
    <!-- PAGE LEVEL SCRIPTS-->
    <script>
      $(function () {
        // Set Chart.js global font
        Chart.defaults.global.defaultFontFamily = "'Noto Sans Lao Looped', sans-serif";
        
        var lineChart = null;
        var donutChart = null;

        function loadChartData(period) {
          $.getJSON('chart_data.php', { period: period }, function(data) {
            var roomTotal = data.roomData.reduce(function(a,b){ return a+b; }, 0);
            var posTotal = data.posData.reduce(function(a,b){ return a+b; }, 0);

            // Destroy old charts
            if (lineChart) lineChart.destroy();
            if (donutChart) donutChart.destroy();

            // ===== LINE CHART =====
            var lineCtx = $('#lineChart').get(0).getContext('2d');
            lineChart = new Chart(lineCtx, {
              type: 'line',
              data: {
                labels: data.labels,
                datasets: [
                  {
                    label: 'ລາຍຮັບຫ້ອງພັກ',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderColor: '#3498DB',
                    pointBorderColor: '#3498DB',
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                    lineTension: 0.3,
                    fill: true,
                    data: data.roomData
                  },
                  {
                    label: 'ລາຍຮັບ POS',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderColor: '#E74C3C',
                    pointBorderColor: '#E74C3C',
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                    lineTension: 0.3,
                    fill: true,
                    data: data.posData
                  }
                ]
              },
              options: {
                animation: { 
                  duration: 2000,
                  easing: 'easeOutQuart'
                },
                maintainAspectRatio: false,
                responsive: true,
                legend: { display: true, position: 'bottom', labels: { fontSize: 12 } },
                scales: {
                  xAxes: [{ gridLines: { display: false } }],
                  yAxes: [{
                    gridLines: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { 
                      beginAtZero: true,
                      callback: function(v) { return v.toLocaleString('en-US') + ' ₭'; } 
                    }
                  }]
                },
                tooltips: {
                  callbacks: {
                    label: function(t, d) {
                      return d.datasets[t.datasetIndex].label + ': ' + Number(t.yLabel).toLocaleString('en-US') + ' ກີບ';
                    }
                  }
                }
              }
            });

            // ===== DONUT CHART =====
            var donutCtx = $('#donutChart').get(0).getContext('2d');
            donutChart = new Chart(donutCtx, {
              type: 'doughnut',
              data: {
                labels: ['ລາຍຮັບຫ້ອງພັກ', 'ລາຍຮັບ POS'],
                datasets: [{
                  data: [roomTotal, posTotal],
                  backgroundColor: ['#3498DB', '#E74C3C'],
                  hoverBackgroundColor: ['#2980B9', '#C0392B'],
                  borderWidth: 2,
                  borderColor: '#fff'
                }]
              },
              options: {
                animation: { 
                  duration: 2000,
                  easing: 'easeOutQuart'
                },
                responsive: true,
                maintainAspectRatio: true,
                cutoutPercentage: 60,
                legend: { display: true, position: 'bottom', labels: { fontSize: 12 } },
                tooltips: {
                  callbacks: {
                    label: function(t, d) {
                      var val = d.datasets[0].data[t.index];
                      var total = d.datasets[0].data.reduce(function(a,b){ return a+b; }, 0);
                      var pct = total > 0 ? ((val/total)*100).toFixed(1) : 0;
                      return d.labels[t.index] + ': ' + Number(val).toLocaleString('en-US') + ' ກີບ (' + pct + '%)';
                    }
                  }
                }
              }
            });

          });
        }

        // Initial load
        loadChartData('daily');

        // Period change
        $('#chartPeriod').on('change', function() {
          loadChartData($(this).val());
        });
      })
    </script>

  </body>
  </html>



  
