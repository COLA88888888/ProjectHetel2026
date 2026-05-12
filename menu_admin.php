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

if (!isset($_SESSION['checked']) || $_SESSION['checked'] <> 1) {
    $_SESSION['checked'] = 1;
    $_SESSION['fname'] = 'ຜູ້ບໍລິຫານ';
    $_SESSION['lname'] = 'ລະບົບ';
    $_SESSION['user_id'] = 1;
    $_SESSION['status'] = 'ຜູ້ບໍລິຫານ';
    $_SESSION['permissions'] = '["room_types","rooms","bookings","housekeeping","reports","settings","users"]';
}

$perms = json_decode($_SESSION['permissions'] ?? '[]', true);
$is_admin = ($_SESSION['status'] === 'ຜູ້ບໍລິຫານ');

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
<html>
 <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;700&display=swap" rel="stylesheet">
<style>
 *:not(.fas):not(.far):not(.fab):not(.fa) { font-family: 'Noto Sans Lao Looped', sans-serif !important; }
 .fas, .far, .fab, .fa { font-family: "Font Awesome 5 Free" !important; font-weight: 900 !important; }
 html, body, .nav-link, .brand-text, h1, h2, h3, h4, h5, h6, .btn, .form-control, .card-title { 
    font-family: 'Noto Sans Lao Looped', sans-serif !important; 
 }
 .nav-sidebar .menu-is-opening > .nav-link p > .right.fa-angle-right,
 .nav-sidebar .menu-open > .nav-link p > .right.fa-angle-right {
   transform: rotate(90deg) !important;
 }
 .brand-link.text-center { transition: padding 0.3s ease; overflow: hidden; }
 .brand-link.text-center img { transition: all 0.3s ease; }
 .sidebar-collapse .brand-link.text-center { padding: 10px 0 !important; }
 .sidebar-collapse .brand-link.text-center img { width: 35px !important; height: 35px !important; }
 /* Make overlayScrollbars always visible */
 .os-scrollbar { opacity: 1 !important; visibility: visible !important; }
 .os-scrollbar-handle { background: rgba(93,173,226,0.4) !important; }
 .sidebar { height: calc(100vh - 240px) !important; }
 .nav-sidebar { padding-bottom: 30px !important; }
 
 @media (max-width: 768px) {
    .brand-text { font-size: 0.9rem !important; }
    .nav-sidebar .nav-link p { font-size: 0.8rem !important; }
    .main-header .navbar-nav .nav-link { font-size: 0.85rem !important; }
    .user-panel .info a { font-size: 0.85rem !important; }
 }

 /* ===== Light Blue Theme for Navbar ===== */
 .main-header.navbar {
   background: linear-gradient(135deg, #029affff 0%, #0099ffff 100%) !important;
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
   background: linear-gradient(180deg, #0099ffff 0%, #0099ffff 50%, #009dffff 100%) !important;
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
  .dropdown-item, .dropdown-toggle { cursor: pointer !important; }
</style>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ລະບົບບໍລິຫານ ໂຮງແຮມ (Hotel Management)</title>
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
  <script src="sweetalert/dist/sweetalert2.all.min.js"></script>		
  <script src="plugins/jquery/jquery.min.js"></script>
</head>
<body class="hold-transition sidebar-mini sidebar-no-expand layout-fixed">

<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="walkin.php" target="frame" class="nav-link"><b>ໜ້າຫຼັກ</b></a>
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
      <!-- <li class="nav-item">
        <a class="nav-link" href="#" role="button" style="color: #28a745; font-weight: bold;">
         ແພັກເກັດການນຳໃຊ້
        </a>
      </li> -->

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
            <img src="./assets/img/admin-avatar.png" height="30px" width="30px">
            <span></span><?php echo $_SESSION['status']; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-right">
              <a class="dropdown-item" href="javascript:void(0);" onclick="confirmLogout()"><i class="fa fa-power-off"></i> <?php echo $lang['logout']; ?></a>
          </ul>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="menu_admin.php" class="brand-link text-center" style="padding: 25px 10px; height: auto; display: block; border-bottom: 1px solid #4b545c;">
      <img src="<?php echo $hotel_logo; ?>" alt="Hotel Logo" class="elevation-3" style="width: 150px; height: 150px; object-fit: cover; margin: 0 auto; opacity: 1;">
      <span class="brand-text font-weight-light d-block mt-3" style="font-size: 18px;"><b><?php echo htmlspecialchars($hotel_name); ?></b></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2 pb-5">
        <ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-child-indent" data-widget="treeview" role="menu" data-accordion="true">
        
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; letter-spacing: 1px;">ໜ້າຫຼັກ</li>
          <li class="nav-item">
            <a href="Homepage.php" target="frame" class="nav-link active">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>ດາດສ໌ບອດ</p>
            </a>
          </li>

          <?php if($is_admin || in_array('bookings', $perms) || in_array('walkin', $perms) || in_array('checkout', $perms) || in_array('room_service', $perms)): ?>
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ບໍລິການລູກຄ້າ</li>
          <?php if($is_admin || in_array('walkin', $perms)): ?>
          <li class="nav-item">
            <a href="walkin.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-door-open"></i>
              <p>ເຂົ້າພັກ</p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($is_admin || in_array('bookings', $perms)): ?>
          <li class="nav-item">
            <a href="reserve.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>ຈອງຫ້ອງພັກ</p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($is_admin || in_array('checkout', $perms)): ?>
          <li class="nav-item">
            <a href="checkout.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-receipt"></i>
              <p>Check-out</p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($is_admin || in_array('room_service', $perms)): ?>
          <li class="nav-item">
            <a href="room_service.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-bell"></i>
              <p>ບໍລິການເພີ່ມເຕີມ</p>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if($is_admin || in_array('pos', $perms) || in_array('stock', $perms)): ?>
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ຄັງສິນຄ້າ ແລະ ການຂາຍ</li>
          <?php if($is_admin || in_array('pos', $perms)): ?>
          <li class="nav-item">
            <a href="pos.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>ຂາຍສິນຄ້າ (POS)</p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($is_admin || in_array('stock', $perms)): ?>
          <li class="nav-item">
            <a href="stock.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-boxes"></i>
              <p>ສະຕ໋ອກສິນຄ້າ</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-tags"></i>
              <p>
                 ຂໍ້ມູນສິນຄ້າ
				         <i class="fas fa-angle-right right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="form_product_categories.php" target="frame" class="nav-link">
                  <i class="fas fa-th-list nav-icon"></i>
                  <p>ໝວດໝູ່ສິນຄ້າ</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="form_product_units.php" target="frame" class="nav-link">
                  <i class="fas fa-balance-scale nav-icon"></i>
                  <p>ຫົວໜ່ວຍສິນຄ້າ</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if($is_admin || in_array('report', $perms) || in_array('rooms', $perms) || in_array('settings', $perms) || in_array('users', $perms)): ?>
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ການຈັດການ ແລະ ລາຍງານ</li>
          <?php if($is_admin || in_array('report', $perms)): ?>
          <li class="nav-item">
            <a href="report.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>ລາຍງານການເງິນ</p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($is_admin): ?>
          <li class="nav-item">
            <a href="logs.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>ປະຫວັດລະບົບ</p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($is_admin || in_array('rooms', $perms)): ?>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-hotel"></i>
              <p>
                 ຕັ້ງຄ່າຫ້ອງພັກ
				         <i class="fas fa-angle-right right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="rooms/select_rooms.php" target="frame" class="nav-link">
                  <i class="fas fa-door-open nav-icon"></i>
                  <p>ລາຍລະອຽດຫ້ອງ</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="room_types/form_room_types.php" target="frame" class="nav-link">
                  <i class="fas fa-tags nav-icon"></i>
                  <p>ປະເພດຫ້ອງ</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>
          <?php if($is_admin || in_array('settings', $perms) || in_array('users', $perms)): ?>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                 ຕັ້ງຄ່າລະບົບ
                 <i class="fas fa-angle-right right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if($is_admin || in_array('settings', $perms)): ?>
              <li class="nav-item">
                <a href="settings.php" target="frame" class="nav-link">
                  <i class="fas fa-hotel nav-icon"></i>
                  <p>ຂໍ້ມູນໂຮງແຮມ</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="currency/form_currency.php" target="frame" class="nav-link">
                  <i class="fas fa-money-bill-wave nav-icon"></i>
                  <p>ສະກຸນເງິນ</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if($is_admin || in_array('users', $perms)): ?>
              <li class="nav-item">
                <a href="users/manage_users.php" target="frame" class="nav-link">
                  <i class="fas fa-users-cog nav-icon"></i>
                  <p>ຈັດການຜູ້ໃຊ້</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>
          <?php endif; ?>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	<iframe width="100%" height="100%" frameborder="0" name="frame" src="Homepage.php"></iframe>
  </div>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<script>
  $(function() {
    // ===== Active Menu Highlight =====
    var $navLinks = $('.nav-sidebar .nav-link[target="frame"]');
    
    // Clear active on full page load, then highlight based on iframe src
    sessionStorage.removeItem('activeMenu');
    // Default: highlight ດາດສ໌ບອດ
    $navLinks.each(function() {
      if ($(this).attr('href') === 'Homepage.php') {
        $(this).addClass('active');
      }
    });

    // Listen for iframe load to sync active menu
    $('iframe[name="frame"]').on('load', function() {
      try {
        var iframeSrc = this.contentWindow.location.href;
        $navLinks.removeClass('active');
        $('.nav-sidebar .nav-item > .nav-link').removeClass('active');
        
        $navLinks.each(function() {
          var href = $(this).attr('href');
          if (iframeSrc.indexOf(href) !== -1) {
            $(this).addClass('active');
            var $parentLi = $(this).closest('.nav-treeview').closest('.nav-item');
            if ($parentLi.length) {
              $parentLi.addClass('menu-open menu-is-opening');
              $parentLi.children('.nav-link').addClass('active');
            }
          }
        });
      } catch(e) { /* cross-origin */ }
    });

    // Click handler for all sidebar links that target the iframe
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
<script>
function confirmLogout() {
    Swal.fire({
        title: 'ຢືນຢັນການອອກຈາກລະບົບ',
        text: "ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ຫຼືບໍ່?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ຕົກລົງ, ອອກຈາກລະບົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    })
}
</script>
</body>
</html>
