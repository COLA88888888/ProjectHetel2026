<?php
session_start();
require_once 'config/db.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    $_SESSION['error'] = "ບໍ່ພົບຂໍ້ມູນການຈອງ!";
    header("Location: reserve.php");
    exit();
}

// Get booking info
$stmt = $pdo->prepare("
    SELECT b.*, r.room_number, r.room_type, r.bed_type, r.price 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.id = ? AND b.status = 'Booked'
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = "ການຈອງນີ້ບໍ່ມີ ຫຼື ໄດ້ເຂົ້າພັກແລ້ວ!";
    header("Location: reserve.php");
    exit();
}

// Handle confirm check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_checkin'])) {
    // Update booking status to Occupied
    $pdo->prepare("UPDATE bookings SET status = 'Occupied', check_in_date = CURDATE() WHERE id = ?")->execute([$booking_id]);
    // Update room status to Occupied
    $pdo->prepare("UPDATE rooms SET status = 'Occupied' WHERE id = ?")->execute([$booking['room_id']]);
    
    $_SESSION['success'] = "ເຂົ້າພັກສຳເລັດ! ຫ້ອງ " . $booking['room_number'];
    header("Location: reserve.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຢືນຢັນເຂົ້າພັກ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 10px; }
        .info-label { font-weight: 600; color: #555; }
        .info-value { font-weight: 700; color: #333; }
        @media (max-width: 576px) {
            .display-3 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-12">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-success text-white text-center py-3">
                    <h4 class="m-0"><i class="fas fa-sign-in-alt"></i> ຢືນຢັນການເຂົ້າພັກ</h4>
                    <small>ຈາກການຈອງລ່ວງໜ້າ</small>
                </div>
                <div class="card-body text-center">
                    <div class="display-3 text-success mb-3"><i class="fas fa-door-open"></i></div>
                    <h3 class="mb-1">ຫ້ອງ <?php echo htmlspecialchars($booking['room_number']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($booking['room_type']); ?> (<?php echo htmlspecialchars($booking['bed_type']); ?>)</p>
                    
                    <hr>
                    
                    <div class="row text-left px-3">
                        <div class="col-6 mb-2">
                            <span class="info-label"><i class="fas fa-user text-primary"></i> ຊື່ລູກຄ້າ:</span><br>
                            <span class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="info-label"><i class="fas fa-phone text-primary"></i> ເບີໂທ:</span><br>
                            <span class="info-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="info-label"><i class="fas fa-calendar-check text-success"></i> ວັນເຂົ້າ:</span><br>
                            <span class="info-value text-success"><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="info-label"><i class="fas fa-calendar-times text-danger"></i> ວັນອອກ:</span><br>
                            <span class="info-value text-danger"><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="info-label"><i class="fas fa-users text-info"></i> ຈຳນວນແຂກ:</span><br>
                            <span class="info-value"><?php echo $booking['guest_count']; ?> ຄົນ</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="info-label"><i class="fas fa-money-bill text-warning"></i> ມັດຈຳ:</span><br>
                            <span class="info-value text-info"><?php echo number_format($booking['deposit_amount']); ?> ກີບ</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mt-3 py-2">
                        <strong><i class="fas fa-coins"></i> ຍອດລວມ: <?php echo number_format($booking['total_price']); ?> ກີບ</strong>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <form action="" method="post" class="d-flex gap-2">
                        <a href="reserve.php" class="btn btn-default flex-fill mr-2"><i class="fas fa-arrow-left"></i> ກັບຄືນ</a>
                        <button type="submit" name="confirm_checkin" class="btn btn-success flex-fill px-4">
                            <i class="fas fa-check-circle"></i> ຢືນຢັນເຂົ້າພັກ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>
</body>
</html>
