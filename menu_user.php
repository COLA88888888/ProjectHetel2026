
<html>
<style>
*{font-family:'Noto Sans Lao', 'Phetsarath OT', sans-serif;}
 .brand-link.text-center { transition: padding 0.3s ease; overflow: hidden; }
 .brand-link.text-center img { transition: all 0.3s ease; }
 .sidebar-collapse .brand-link.text-center { padding: 10px 0 !important; }
 .sidebar-collapse .brand-link.text-center img { width: 35px !important; height: 35px !important; }
 /* Make overlayScrollbars always visible */
 .os-scrollbar { opacity: 1 !important; visibility: visible !important; }
 .os-scrollbar-handle { background: rgba(93,173,226,0.4) !important; }
 .sidebar { height: calc(100vh - 240px) !important; }
 .nav-sidebar { padding-bottom: 30px !important; }

 /* ===== Light Blue Theme for Navbar ===== */
 .main-header.navbar {
   background: linear-gradient(135deg, #5DADE2 0%, #85C1E9 100%) !important;
   border-bottom: 2px solid #3498DB !important;
   box-shadow: 0 2px 8px rgba(93,173,226,0.3) !important;
 }
 .main-header .nav-link,
 .main-header .navbar-nav .nav-link {
   color: #ffffff !important;
 }
 .main-header .nav-link:hover,
 .main-header .navbar-nav .nav-link:hover {
   color: #EBF5FB !important;
   background-color: rgba(255,255,255,0.15) !important;
   border-radius: 6px;
 }
 .main-header .dropdown-toggle { color: #ffffff !important; }
 .main-header .navbar-nav .fas.fa-crown { color: #F9E79F !important; }

 /* ===== Light Blue Theme for Sidebar ===== */
 .main-sidebar {
   background: linear-gradient(180deg, #2E86C1 0%, #2471A3 50%, #1A5276 100%) !important;
   box-shadow: 3px 0 12px rgba(46,134,193,0.3) !important;
 }
 .main-sidebar .brand-link {
   background: rgba(0,0,0,0.08) !important;
   border-bottom: 1px solid rgba(255,255,255,0.15) !important;
 }
 .main-sidebar .brand-link .brand-text {
   color: #ffffff !important;
 }
 .main-sidebar .sidebar .nav-link {
   color: rgba(255,255,255,0.85) !important;
   border-radius: 8px !important;
   margin: 2px 8px !important;
   padding: 10px 12px !important;
   transition: all 0.25s ease !important;
 }
 .main-sidebar .sidebar .nav-link:hover {
   background-color: rgba(255,255,255,0.15) !important;
   color: #ffffff !important;
 }
 .main-sidebar .sidebar .nav-link.active {
   background: linear-gradient(135deg, #5DADE2, #85C1E9) !important;
   color: #ffffff !important;
   box-shadow: 0 3px 8px rgba(93,173,226,0.4) !important;
 }
 .nav-sidebar > .nav-item > .nav-link.active {
   background: linear-gradient(135deg, #5DADE2, #85C1E9) !important;
   color: #ffffff !important;
   box-shadow: 0 3px 8px rgba(93,173,226,0.4) !important;
 }
 .main-sidebar .nav-icon { color: rgba(255,255,255,0.7) !important; }
 .main-sidebar .nav-link:hover .nav-icon,
 .main-sidebar .nav-link.active .nav-icon { color: #ffffff !important; }
 .main-sidebar .nav-treeview { background: rgba(0,0,0,0.08) !important; border-radius: 6px !important; margin: 2px 8px !important; }
 .main-sidebar .nav-treeview .nav-link { padding-left: 20px !important; font-size: 0.92em; }
 .main-sidebar .nav-header { color: rgba(255,255,255,0.5) !important; }
</style>
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ລະບົບບໍລິຫານ ໂຮງແຮມ (User)</title>
<link rel="stylesheet" href="icon/css/all.min.css">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
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
	<script src="../sweetalert/dist/sweetalert2.all.min.js"></script>		
	<script src="../jquery.js"></script>
</head>
<body class="hold-transition sidebar-mini sidebar-no-expand layout-fixed">
<?php
session_start();

// Language Selection Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] ?? 'la';

// Include the appropriate language file
$lang_file = "lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include "lang/la.php";
}

$flags = [
    'la' => ['src' => 'https://flagcdn.com/w20/la.png', 'alt' => 'Lao'],
    'en' => ['src' => 'https://flagcdn.com/w20/gb.png', 'alt' => 'English'],
    'cn' => ['src' => 'https://flagcdn.com/w20/cn.png', 'alt' => 'Chinese'],
    'vn' => ['src' => 'https://flagcdn.com/w20/vn.png', 'alt' => 'Vietnamese'],
];
$active_flag = $flags[$current_lang] ?? $flags['la'];

if(@$_SESSION['checked']<>1){
	echo "<script>alert('ລົງຊືີ່ເຂົ້າໃຊ້ກ່ອນ');
	</script>";
	}
else{
// Fetch hotel logo & name from settings
require_once 'config/db.php';
try {
    $stmtLogo = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('hotel_logo', 'hotel_name')");
    $hotel_settings = $stmtLogo->fetchAll(PDO::FETCH_KEY_PAIR);
    $hotel_logo = !empty($hotel_settings['hotel_logo']) ? 'assets/img/' . $hotel_settings['hotel_logo'] : 'https://via.placeholder.com/150?text=Logo';
    $hotel_name = $hotel_settings['hotel_name'] ?? 'ລະບົບໂຮງແຮມ';
} catch (Exception $e) {
    $hotel_logo = 'https://via.placeholder.com/150?text=Logo';
    $hotel_name = 'ລະບົບໂຮງແຮມ';
}
?>
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="orders/form_orders.php" target="frame" class="nav-link"><b>ໜ້າຫຼັກ</b></a>
      </li>
      
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
     
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
		         <i class="fas fa-expand-arrows-alt"></i>
        
        </a>
      </li>  

      <!-- Usage Package -->
      <li class="nav-item">
        <a class="nav-link" href="#" role="button" style="color: #28a745; font-weight: bold;">
          <i class="fas fa-crown"></i> ແພັກເກັດການນຳໃຊ້
        </a>
      </li>

      <!-- Language Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" id="langDropdown">
          <img src="<?php echo $active_flag['src']; ?>" alt="<?php echo $active_flag['alt']; ?>" style="width: 24px; border-radius: 2px;" id="currentLangFlag">
        </a>
        <div class="dropdown-menu dropdown-menu-right p-0">
          <a href="?lang=la" class="dropdown-item <?php echo $current_lang == 'la' ? 'active' : ''; ?>">
            <img src="https://flagcdn.com/w20/la.png" alt="Lao" class="mr-2"> ລາວ (Lao)
          </a>
          <a href="?lang=en" class="dropdown-item <?php echo $current_lang == 'en' ? 'active' : ''; ?>">
            <img src="https://flagcdn.com/w20/gb.png" alt="English" class="mr-2"> English
          </a>
          <a href="?lang=cn" class="dropdown-item <?php echo $current_lang == 'cn' ? 'active' : ''; ?>">
            <img src="https://flagcdn.com/w20/cn.png" alt="Chinese" class="mr-2"> 中文 (Chinese)
          </a>
          <a href="?lang=vn" class="dropdown-item <?php echo $current_lang == 'vn' ? 'active' : ''; ?>">
            <img src="https://flagcdn.com/w20/vn.png" alt="Vietnamese" class="mr-2"> Tiếng Việt
          </a>
        </div>
      </li>

      <li class="dropdown dropdown-user">
          <a class="nav-link dropdown-toggle link " data-toggle="dropdown">
            <img src="./assets/img/admin-avatar.png"height="30px" width="30px">
              <span></span><?php echo $_SESSION['status'] ?? 'ພະນັກງານ'; ?><i class="fa fa-angle-down m-l-5"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right">
              <!-- <a class="dropdown-item" href="#"><i class="fa fa-user"></i> ໂປຣໄຟຣ໌</a> -->
                <li class="dropdown-divider"></li>
                  <a class="dropdown-item" href="index.php"><i class="fa fa-power-off"></i> <?php echo $lang['logout']; ?></a>
          </ul>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="#" class="brand-link text-center" style="padding: 25px 10px; height: auto; display: block; border-bottom: 1px solid #4b545c;">
      <img src="<?php echo $hotel_logo; ?>" alt="Hotel Logo" class="elevation-3" style="width: 150px; height: 150px; object-fit: cover; margin: 0 auto; opacity: 1;">
      <span class="brand-text font-weight-light d-block mt-3" style="font-size: 18px;"><b><?php echo htmlspecialchars($hotel_name); ?></b></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->


      <!-- SidebarSearch Form -->
     

      <!-- Sidebar Menu -->
      <nav class="mt-2"> 
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                ສ້າງສິນຄ້າ
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">	
              <i class="fas fa-font"></i>
              <p>
                ປະເພດສິນຄ້າ
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"> --> 
               <!-- ຕໍາແໜງຂອງຟອມບັນທຶກປະເພດສິນຄ້າ -->
                <!-- <a href="categories/form_categories.php" target="frame" class="nav-link">
                  <i class="fas fa-plus-circle"></i>
                  <p>ເພີ່ມປະເພດສິນຄ້າ</p>
                </a>
              </li>
              <li class="nav-item"> -->
               <!-- ຕໍາແໜງຂອງລາຍງານປະເພດສິນຄ້າ -->
                <!-- <a href="categories/select_categories.php" target="frame" class="nav-link">
                  <i class="fas fa-eye"></i>
                  <p>ສະແດງປະເພດສິນຄ້າ</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
            <i class="fas fa-users"></i>
              <p>
                ຂໍ້ມູນສິນຄ້າ
                 <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"> -->
               <!-- ຟອມບັນທຶກຂໍ້ມູນສິນຄ້າ -->
                <!-- <a href="products/form_products.php" target="frame" class="nav-link">
                  <i class="fas fa-plus-circle"></i>
                  <p>ບັນທຶກຂໍ້ມູນສິນຄ້າ</p>
                </a>
              </li>
              <li class="nav-item"> -->
               <!-- ຟາຍລາຍງານຂໍ້ມູນສິນຄ້າ -->
                <!-- <a href="products/select_products.php" target="frame" class="nav-link">
                  <i class="fas fa-eye"></i>
                  <p>ລາຍງານຂໍ້ມູນສິນຄ້າ</p>
                </a>
              </li>
              <li class="nav-item"> -->
                <!-- ຟາຍຄົ້ນຫາຂໍ້ມູນສິນຄ້າ -->
                <!-- <a href="#" target="frame" class="nav-link">
                  <i class="fas fa-search"></i>
                  <p>ຄົ້ນຫາຂໍ້ມູນສິນຄ້າ</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
            <i class="fas fa-cart-arrow-down"></i>
              <p>
                ຂໍ້ມູນສິນຄ້ານຳເຂົ້າ
                 <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"> -->
               <!-- ຟອມບັນທຶກຂໍ້ມູນສິນຄ້ານຳເຂົ້າ -->
                <!-- <a href="receives/form_receives.php" target="frame" class="nav-link">
                  <i class="fas fa-plus-circle"></i>
                  <p>ບັນທຶກຂໍ້ມູນສິນຄ້ານຳເຂົ້າ</p>
                 <i class="fas fa-angle-left right"></i>
                </a>
              </li> -->
              <!-- <li class="nav-item"> -->
               <!-- ຟາຍລາຍງານຂໍ້ມູນສິນຄ້ານຳເຂົ້າ -->
                <!-- <a href="receives/select_receives.php" target="frame" class="nav-link">
                  <i class="fas fa-eye"></i>
                  <p>ສະແດງຂໍ້ມູນສິນຄິນນຳເຂົ້າ</p>
                 <i class="fas fa-angle-left right"></i>
                </a>
              </li>
            </ul>
          </li> -->
          <li class="nav-item">
            <a href="#" class="nav-link">
            <i class="fas fa-shopping-cart"></i>
              <p>
                <?php echo $lang['bookings']; ?>
                 <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
               <!-- ຟອມບັນທຶກສິນຄ້າຂາຍອອກ -->
                <a href="orders/form_orders.php" target="frame" class="nav-link">
                  <i class="fas fa-plus-circle"></i>
                  <p>ບັນທຶກລາຍການຈອງ</p>
                </a>
              </li>
              <li class="nav-item">
               <!-- ຟາຍລາຍງານຂໍ້ມູນສິນຄ້າຂາຍອອກ -->
                <a href="orders/select_orders.php" target="frame" class="nav-link">
                  <i class="fas fa-eye"></i>
                  <p>ສະແດງລາຍການຈອງ</p>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">


	<iframe width="100%" height="100%" frameborder="0" name="frame" src="orders/form_orders.php"></iframe>  <!-- Homepage.php -->

       
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
   
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

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
<script>
  $(function() {
    // ===== Active Menu Highlight =====
    var $navLinks = $('.nav-sidebar .nav-link[target="frame"]');
    
    // Clear on refresh, default to first page
    sessionStorage.removeItem('activeMenu');
    $navLinks.first().addClass('active');

    // Sync active menu with iframe
    $('iframe[name="frame"]').on('load', function() {
      try {
        var iframeSrc = this.contentWindow.location.href;
        $navLinks.removeClass('active');
        $('.nav-sidebar .nav-item > .nav-link').removeClass('active');
        $navLinks.each(function() {
          if (iframeSrc.indexOf($(this).attr('href')) !== -1) {
            $(this).addClass('active');
            var $parentLi = $(this).closest('.nav-treeview').closest('.nav-item');
            if ($parentLi.length) {
              $parentLi.addClass('menu-open menu-is-opening');
              $parentLi.children('.nav-link').addClass('active');
            }
          }
        });
      } catch(e) {}
    });

    // Click handler
    $navLinks.on('click', function() {
      var $clicked = $(this);
      $navLinks.removeClass('active');
      $('.nav-sidebar .nav-item > .nav-link').removeClass('active');
      $clicked.addClass('active');
      var $parentLi = $clicked.closest('.nav-treeview').closest('.nav-item');
      if ($parentLi.length) {
        $parentLi.children('.nav-link').addClass('active');
      }

      // Auto-close sidebar on small screens
      if ($(window).width() <= 991) {
        setTimeout(function() {
          $('body').removeClass('sidebar-open');
          $('body').addClass('sidebar-collapse sidebar-closed');
          $('#sidebar-overlay').remove();
          $('.sidebar-overlay').remove();
        }, 150);
      }
    });

    // Language dropdown
    $('.lang-select').on('click', function(e) {
      e.preventDefault();
      $('.lang-select').removeClass('active');
      $(this).addClass('active');
      var flagSrc = $(this).find('img').attr('src');
      var flagAlt = $(this).find('img').attr('alt');
      $('#currentLangFlag').attr('src', flagSrc).attr('alt', flagAlt);
    });
  });
</script>
</body>
</html>

<?php
 }
?>
<!-- update !-->

