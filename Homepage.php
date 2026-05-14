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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;700&display=swap" rel="stylesheet">
    <style>
    * {
      font-family: 'Noto Sans Lao Looped', 'Phetsarath OT', 'Saysettha OT', sans-serif;
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
    // Hotel Metrics
    $total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn() ?: 0;
    
    // Rooms that are NOT available (Occupied, Cleaning, or Reserved)
    // The user wants to count BOTH Reservations (Booked) and Staying (Occupied) as Unavailable
    $unavailable_rooms = $pdo->query("
        SELECT COUNT(DISTINCT id) FROM rooms 
        WHERE status != 'Available' 
        OR (housekeeping_status != 'ພ້ອມໃຊ້' AND housekeeping_status != 'Ready')
        OR id IN (SELECT room_id FROM bookings WHERE status IN ('Booked', 'Occupied', 'Checked In'))
    ")->fetchColumn() ?: 0;
    
    $available_rooms = $total_rooms - $unavailable_rooms;
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
      body { font-family: 'Noto Sans Lao Looped', sans-serif; background: #f0f4f8; }
      .dashboard-page { padding: 24px 20px 20px; }

      /* ===== Modern & Compact Stat Cards ===== */
      .stat-cards-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }

      .stat-card {
        position: relative;
        border-radius: 12px;
        padding: 16px 18px 14px;
        color: #fff;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-decoration: none;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 110px;
        border: 1px solid rgba(255,255,255,0.1);
      }

      /* No Hover - Keep static */
      .stat-card:hover, .stat-card:focus, .stat-card:active {
        color: #fff !important;
        text-decoration: none !important;
        transform: none !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
      }

      /* Premium Gradient presets */
      .stat-card.gc-green  { background: linear-gradient(135deg, #1D976C 0%, #93F9B9 100%); }
      .stat-card.gc-amber  { background: linear-gradient(135deg, #FF8008 0%, #FFC837 100%); }
      .stat-card.gc-blue   { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }
      .stat-card.gc-indigo { background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%); }
      .stat-card.gc-teal   { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); }
      .stat-card.gc-dark   { background: linear-gradient(135deg, #232526 0%, #414345 100%); }

      .stat-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
      .stat-card-label {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        opacity: 0.95;
        margin-bottom: 6px;
        white-space: nowrap;
      }
      .stat-card-value {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.2px;
      }
      .stat-card-icon {
        font-size: 1.8rem;
        opacity: 0.25;
        z-index: 1;
        position: absolute;
        top: 10px;
        right: 12px;
      }
      .stat-card-footer {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.68rem;
        font-weight: 600;
        opacity: 0.85;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid rgba(255,255,255,0.2);
        z-index: 1;
      }
      .stat-card-footer i { font-size: 0.65rem; }

      /* ===== Chart Section ===== */
      .card-title { font-size: 0.9rem !important; }
      .table { font-size: 0.8rem !important; }

      /* ===== Responsive: Force 2 Columns on Mobile ===== */
      @media (max-width: 768px) {
        .dashboard-page { padding: 14px 10px; }
        .section-header h4 { font-size: 1.1rem; }
        .stat-cards-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .stat-card-value { font-size: 1.15rem; }
        .stat-card { min-height: 80px; padding: 10px 12px; }
        .stat-card-icon { font-size: 1.3rem; }
        .stat-card-label { font-size: 0.65rem; }
        .stat-card-footer { font-size: 0.65rem; margin-top: 8px; padding-top: 6px; }
      }
      @media (max-width: 480px) {
        .dashboard-page { padding: 8px 6px; }
        .section-header h4 { font-size: 1rem; }
        .stat-cards-row { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .stat-card-value { font-size: 1rem; }
        .stat-card { min-height: 75px; padding: 8px 10px; }
      }
    </style>
  <body class="hold-transition sidebar-mini layout-fixed">
    <div class="dashboard-page">

      <!-- ===== Modern Stats Cards ===== -->
      <div class="stat-cards-row">

        <!-- Card 1: Available Rooms -->
        <a href="rooms/select_rooms.php" class="stat-card gc-green">
          <div class="stat-card-top">
            <div>
              <div class="stat-card-label">ຫ້ອງຫວ່າງ</div>
              <div class="stat-card-value"><?= number_format($available_rooms) ?> <span style="font-size:1rem;font-weight:600;">ຫ້ອງ</span></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-door-open"></i></div>
          </div>
          <div class="stat-card-footer">
            <i class="fas fa-arrow-right"></i> ຈັດການຂໍ້ມູນຫ້ອງ
          </div>
        </a>

        <!-- Card 2: Unavailable Rooms -->
        <a href="report.php?type=room_history" class="stat-card gc-amber">
          <div class="stat-card-top">
            <div>
              <div class="stat-card-label">ຫ້ອງບໍ່ຫວ່າງ</div>
              <div class="stat-card-value"><?= number_format($unavailable_rooms) ?> <span style="font-size:1rem;font-weight:600;">ຫ້ອງ</span></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-door-closed"></i></div>
          </div>
          <div class="stat-card-footer">
            <i class="fas fa-arrow-right"></i> ປະຫວັດການຈອງ
          </div>
        </a>

        <!-- Card 3: Total Rooms -->
        <a href="rooms/select_rooms.php" class="stat-card gc-blue">
          <div class="stat-card-top">
            <div>
              <div class="stat-card-label">ຫ້ອງທັງໝົດ</div>
              <div class="stat-card-value"><?= number_format($total_rooms) ?> <span style="font-size:1rem;font-weight:600;">ຫ້ອງ</span></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-hotel"></i></div>
          </div>
          <div class="stat-card-footer">
            <i class="fas fa-arrow-right"></i> ຈັດການຂໍ້ມູນຫ້ອງ
          </div>
        </a>

        <!-- Card 4: Total Guests -->
        <a href="report.php?type=room_history" class="stat-card gc-indigo">
          <div class="stat-card-top">
            <div>
              <div class="stat-card-label">ແຂກທັງໝົດ</div>
              <div class="stat-card-value"><?= number_format($guest_count) ?> <span style="font-size:1rem;font-weight:600;">ຄົນ</span></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
          </div>
          <div class="stat-card-footer">
            <i class="fas fa-arrow-right"></i> ແຂກເຂົ້າພັກ
          </div>
        </a>

        <!-- Card 5: Today Revenue -->
        <a href="report.php?type=room_revenue" class="stat-card gc-teal">
          <div class="stat-card-top">
            <div>
              <div class="stat-card-label">ລາຍຮັບມື້ນີ້</div>
              <div class="stat-card-value"><?= number_format($today_revenue) ?> <span style="font-size:0.9rem;font-weight:600;">ກີບ</span></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-wallet"></i></div>
          </div>
          <div class="stat-card-footer">
            <i class="fas fa-arrow-right"></i> ເບິ່ງລາຍງານ
          </div>
        </a>

        <!-- Card 6: Total Revenue -->
        <a href="report.php?type=finance" class="stat-card gc-dark">
          <div class="stat-card-top">
            <div>
              <div class="stat-card-label">ລາຍຮັບລາຍເດືອນ</div>
              <div class="stat-card-value"><?= number_format($total_revenue) ?> <span style="font-size:0.9rem;font-weight:600;">ກີບ</span></div>
            </div>
            <div class="stat-card-icon"><i class="fas fa-dollar-sign"></i></div>
          </div>
          <div class="stat-card-footer">
            <i class="fas fa-arrow-right"></i> ເບິ່ງລາຍງານ
          </div>
        </a>

      </div>
      <!-- /.stat-cards-row -->

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



  
