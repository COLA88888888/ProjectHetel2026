<?php
session_start();
require_once 'config/db.php';

// Fetch room types for dropdown
$stmtTypes = $pdo->query("SELECT * FROM room_types ORDER BY id DESC");
$room_types = $stmtTypes->fetchAll();

$available_rooms = [];
$nights = 1;
$selected_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $nights = (int)$_POST['nights'];
    $selected_type = $_POST['room_type'];

    if ($selected_type === 'all' || empty($selected_type)) {
        // Find all available rooms
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE status = 'Available' AND (housekeeping_status = 'ພ້ອມໃຊ້' OR housekeeping_status = 'Ready')");
        $stmt->execute();
    } else {
        // Find available rooms by type
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_type = ? AND status = 'Available' AND (housekeeping_status = 'ພ້ອມໃຊ້' OR housekeeping_status = 'Ready')");
        $stmt->execute([$selected_type]);
    }
    
    $available_rooms = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຮັບລູກຄ້າ Walk-in</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
        .room-card { transition: transform 0.2s; }
        .room-card:hover { transform: scale(1.02); cursor: pointer; border-color: #28a745; }
        .room-price { font-size: 1.2rem; font-weight: 600; color: #28a745; }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if(isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ສຳເລັດ',
                    text: '<?php echo $_SESSION['success']; ?>',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        </script>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ຜິດພາດ',
                    text: '<?php echo $_SESSION['error']; ?>',
                    confirmButtonText: 'ຕົກລົງ'
                });
            });
        </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-walking"></i> ຮັບລູກຄ້າ Walk-in (ບໍ່ໄດ້ຈອງລ່ວງໜ້າ)</h2>
        </div>
    </div>

    <!-- Search/Filter Form -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">1. ສອບຖາມຄວາມຕ້ອງການລູກຄ້າ</h3>
        </div>
        <form action="" method="post">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ພັກຈັກຄືນ?</label>
                            <input type="number" name="nights" class="form-control" value="<?php echo $nights; ?>" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>ຕ້ອງການຫ້ອງແບບໃດ?</label>
                            <select name="room_type" class="form-control">
                                <option value="all">-- ເບິ່ງທຸກປະເພດ --</option>
                                <?php foreach($room_types as $rt): ?>
                                    <option value="<?php echo htmlspecialchars($rt['room_type_name']); ?>" <?php echo ($selected_type == $rt['room_type_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rt['room_type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" name="search" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> ຄົ້ນຫາຫ້ອງຫວ່າງ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Section -->
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])): ?>
    <div class="card card-success card-outline">
        <div class="card-header">
            <h3 class="card-title">2. ແນະນຳຫ້ອງຫວ່າງ (ພ້ອມໃຊ້)</h3>
        </div>
        <div class="card-body bg-light">
            <?php if (count($available_rooms) > 0): ?>
                <div class="row">
                    <?php foreach($available_rooms as $room): ?>
                        <div class="col-md-3">
                            <div class="card room-card shadow-sm border-success">
                                <div class="card-body text-center">
                                    <div class="display-4 text-success mb-2">
                                        <i class="fas fa-door-closed"></i>
                                    </div>
                                    <h4 class="font-weight-bold">ຫ້ອງ <?php echo htmlspecialchars($room['room_number']); ?></h4>
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($room['room_type']); ?> (<?php echo htmlspecialchars($room['bed_type']); ?>)</p>
                                    <hr>
                                    <p class="room-price mb-0"><?php echo number_format($room['price']); ?> ກີບ / ຄືນ</p>
                                    <?php if($nights > 1): ?>
                                        <p class="text-muted small">ຍອດລວມ <?php echo $nights; ?> ຄືນ: <strong><?php echo number_format($room['price'] * $nights); ?> ກີບ</strong></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer p-0">
                                    <a href="checkin.php?room_id=<?php echo $room['id']; ?>&nights=<?php echo $nights; ?>" class="btn btn-success btn-block rounded-0">
                                        <i class="fas fa-check-circle"></i> ເລືອກຫ້ອງນີ້
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <h4 class="text-danger"><i class="fas fa-times-circle text-danger fa-3x mb-3 d-block"></i> ຂໍອະໄພ, ບໍ່ມີຫ້ອງຫວ່າງສຳລັບປະເພດທີ່ເລືອກ!</h4>
                    <p class="text-muted">ກະລຸນາລອງເລືອກປະເພດຫ້ອງອື່ນ ຫຼື ກວດເບິ່ງຫ້ອງທີ່ກຳລັງອະນາໄມ.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

</body>
</html>
